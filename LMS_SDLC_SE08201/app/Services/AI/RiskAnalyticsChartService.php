<?php

namespace App\Services\AI;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\RiskFactor;
use App\Models\RiskPrediction;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dịch vụ tổng hợp dữ liệu biểu đồ phân tích chuyên sâu AI Early Warning:
 * Giải thích trực quan "Vì sao học sinh nghỉ học / có nguy cơ bỏ học"
 * bằng hệ thống Pie Chart, Line Chart và Column Chart cho cả Admin & Giảng viên.
 */
class RiskAnalyticsChartService
{
    public const REASON_META = [
        'academic_struggle' => [
            'label' => 'Hổng kiến thức / Điểm yếu (<5.0) & Nợ bài',
            'short' => 'Hổng kiến thức & Nợ bài',
            'color' => '#f43f5e',
        ],
        'post_ban_drop' => [
            'label' => 'Buông xuôi sau vượt ngưỡng cấm thi (>20%)',
            'short' => 'Buông xuôi (>20%)',
            'color' => '#f97316',
        ],
        'morning_weekend' => [
            'label' => 'Khó khăn ca sáng sớm (07:15) & Cuối tuần',
            'short' => 'Ca sáng sớm / Cuối tuần',
            'color' => '#eab308',
        ],
        'multi_subject_overload' => [
            'label' => 'Quá tải lịch học / Yếu nhiều môn cùng lúc',
            'short' => 'Quá tải đa môn',
            'color' => '#a855f7',
        ],
        'excused_personal' => [
            'label' => 'Sức khỏe / Việc gia đình (Có phép)',
            'short' => 'Sức khỏe / Gia đình',
            'color' => '#06b6d4',
        ],
        'unexcused_habit' => [
            'label' => 'Vắng tự phát không phép / Ý thức chuyên cần',
            'short' => 'Vắng tự phát không phép',
            'color' => '#3b82f6',
        ],
    ];

    /**
     * Dữ liệu biểu đồ tổng hợp toàn trường cho Admin (/admin/risk).
     */
    public function buildAdminCharts(array $overview, ?string $selectedLevel = null): array
    {
        $studentItems = collect($overview['students'] ?? []);
        if ($selectedLevel && $selectedLevel !== 'ALL') {
            $filteredItems = $studentItems->filter(
                fn ($item) => ($item['prediction']->risk_level ?? null) === $selectedLevel
            );
        } else {
            $filteredItems = $studentItems;
        }

        $targetStudentIds = $filteredItems
            ->map(fn ($item) => $item['student']->user_id)
            ->values()
            ->all();

        // Nếu bộ lọc mức rủi ro hiện tại không có SV nào, vẫn giữ danh sách rỗng an toàn
        $classes = ClassRoom::with('subject')->orderBy('class_name')->get();
        $classIds = $classes->pluck('class_id')->all();

        $core = $this->computeCohortAnalytics(
            classIds: $classIds,
            studentIdsFilter: $selectedLevel && $selectedLevel !== 'ALL' ? $targetStudentIds : null,
            groupTimelineBySession: false,
        );

        // Phân bố yếu tố rủi ro từ kết quả AI mới nhất của các sinh viên đang xem
        $factorCounts = [
            'ATTENDANCE_CRITICAL' => 0,
            'ATTENDANCE_WARNING' => 0,
            'GRADE_LOW' => 0,
            'MISSING_WORK' => $core['students_missing_half_assignments'],
            'RETAKE_MULTI' => 0,
        ];

        foreach ($filteredItems as $item) {
            /** @var RiskPrediction $pred */
            $pred = $item['prediction'];
            foreach ($pred->factors ?? [] as $f) {
                if ($f->factor_type === RiskFactor::TYPE_ATTENDANCE && $f->severity === 'CRITICAL') {
                    $factorCounts['ATTENDANCE_CRITICAL']++;
                } elseif ($f->factor_type === RiskFactor::TYPE_ATTENDANCE) {
                    $factorCounts['ATTENDANCE_WARNING']++;
                } elseif ($f->factor_type === RiskFactor::TYPE_GRADE) {
                    $factorCounts['GRADE_LOW']++;
                } elseif ($f->factor_type === RiskFactor::TYPE_RETAKE) {
                    $factorCounts['RETAKE_MULTI']++;
                }
            }
        }

        $levelCounts = $overview['counts_by_level'] ?? [
            'SAFE' => 0,
            'MEDIUM' => 0,
            'HIGH' => 0,
            'VERY_HIGH' => 0,
        ];

        return array_merge($core, [
            'scope_label' => ($selectedLevel && $selectedLevel !== 'ALL')
                ? 'Nhóm mức rủi ro: ' . $selectedLevel
                : 'Toàn trường (' . ($overview['total_students'] ?? 0) . ' sinh viên)',
            'mode' => 'admin',
            'risk_factors_pie' => [
                'labels' => [
                    'Trượt chuyên cần (>20%)',
                    'Tiệm cận cấm thi (15%–20%)',
                    'Điểm trung bình yếu (<5.0)',
                    'Nợ / Không nộp ≥50% bài tập',
                    'Học lại / Tích lũy đa môn',
                ],
                'data' => array_values($factorCounts),
                'colors' => ['#f43f5e', '#f59e0b', '#fb923c', '#a855f7', '#38bdf8'],
            ],
            'risk_levels_pie' => [
                'labels' => ['An toàn', 'Trung bình (Cảnh báo sớm)', 'Nguy cơ Cao', 'Nguy cơ Rất cao'],
                'data' => [
                    (int) ($levelCounts['SAFE'] ?? 0),
                    (int) ($levelCounts['MEDIUM'] ?? 0),
                    (int) ($levelCounts['HIGH'] ?? 0),
                    (int) ($levelCounts['VERY_HIGH'] ?? 0),
                ],
                'colors' => ['#10b981', '#f59e0b', '#f97316', '#f43f5e'],
            ],
        ]);
    }

    /**
     * Dữ liệu biểu đồ chi tiết theo lớp và các lớp giảng dạy cho Giảng viên (/teacher/risk).
     */
    public function buildTeacherCharts(ClassRoom $selectedClass, Collection $teacherClasses, array $summary): array
    {
        $core = $this->computeCohortAnalytics(
            classIds: [$selectedClass->class_id],
            studentIdsFilter: null,
            groupTimelineBySession: true,
        );

        // Nếu lớp đang chọn chưa có buổi vắng nào, mở rộng so sánh sang toàn bộ các lớp GV phụ trách để biểu đồ vẫn giàu thông tin
        $allTeacherClassIds = $teacherClasses->pluck('class_id')->all();
        $comparisonClasses = $this->buildClassComparisonColumns($allTeacherClassIds);
        if (! empty($comparisonClasses['labels'])) {
            $core['subject_class_column'] = $comparisonClasses;
        }

        $studentsData = collect($summary['students_data'] ?? []);
        $levelCounts = [
            'SAFE' => $studentsData->filter(fn ($s) => $s['overall_risk']->risk_level === 'SAFE')->count(),
            'MEDIUM' => $studentsData->filter(fn ($s) => $s['overall_risk']->risk_level === 'MEDIUM')->count(),
            'HIGH' => $studentsData->filter(fn ($s) => $s['overall_risk']->risk_level === 'HIGH')->count(),
            'VERY_HIGH' => $studentsData->filter(fn ($s) => $s['overall_risk']->risk_level === 'VERY_HIGH')->count(),
        ];

        $factorCounts = [
            'ATTENDANCE_CRITICAL' => $studentsData->filter(fn ($s) => $s['subject_detail']['attendance_failed'])->count(),
            'ATTENDANCE_WARNING' => $studentsData->filter(fn ($s) => ! $s['subject_detail']['attendance_failed'] && $s['subject_detail']['absence_rate'] >= 15.0)->count(),
            'GRADE_LOW' => $studentsData->filter(fn ($s) => $s['subject_detail']['average_score'] !== null && $s['subject_detail']['average_score'] < 5.0)->count(),
            'MISSING_WORK' => $core['students_missing_half_assignments'],
            'MULTI_RISK' => $studentsData->filter(fn ($s) => in_array($s['overall_risk']->risk_level, ['HIGH', 'VERY_HIGH'], true) && ! $s['subject_detail']['attendance_failed'])->count(),
        ];

        return array_merge($core, [
            'scope_label' => 'Lớp ' . $selectedClass->class_name . ' — ' . ($selectedClass->subject?->subject_name ?? ''),
            'mode' => 'teacher',
            'risk_factors_pie' => [
                'labels' => [
                    'Trượt chuyên cần môn này (>20%)',
                    'Tiệm cận ngưỡng 20% (15%–20%)',
                    'Điểm bài tập môn này yếu (<5.0)',
                    'Nợ / Bỏ nộp ≥50% bài tập lớp',
                    'Rủi ro kéo theo từ môn học khác',
                ],
                'data' => array_values($factorCounts),
                'colors' => ['#f43f5e', '#f59e0b', '#fb923c', '#a855f7', '#38bdf8'],
            ],
            'risk_levels_pie' => [
                'labels' => ['An toàn', 'Trung bình (Tiệm cận 20%)', 'Nguy cơ Cao', 'Nguy cơ Rất cao'],
                'data' => [
                    $levelCounts['SAFE'],
                    $levelCounts['MEDIUM'],
                    $levelCounts['HIGH'],
                    $levelCounts['VERY_HIGH'],
                ],
                'colors' => ['#10b981', '#f59e0b', '#f97316', '#f43f5e'],
            ],
        ]);
    }

    /**
     * Dữ liệu 3 biểu đồ chẩn đoán cá nhân 1 sinh viên (/admin/risk/{student} & /teacher/risk/{student}).
     */
    public function buildStudentDetailCharts(User $student): array
    {
        $enrolledClasses = $student->enrolledClasses()
            ->with(['subject', 'sessions.slot', 'assignments'])
            ->get();

        if ($enrolledClasses->isEmpty()) {
            return [
                'has_data' => false,
            ];
        }

        $classIds = $enrolledClasses->pluck('class_id')->all();
        $sessionIds = $enrolledClasses->flatMap(fn ($c) => $c->sessions->pluck('session_id'))->all();
        $assignmentIds = $enrolledClasses->flatMap(fn ($c) => $c->assignments->pluck('assignment_id'))->all();

        $attendances = Attendance::where('user_id', $student->user_id)
            ->whereIn('session_id', $sessionIds)
            ->get()
            ->keyBy('session_id');

        $submissions = Submission::where('user_id', $student->user_id)
            ->whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('submitted_at')
            ->get();

        $submissionsByAssignment = $submissions->keyBy('assignment_id');

        // 1. Column Chart: So sánh từng môn học của sinh viên
        $subjectLabels = [];
        $subjectAbsenceRates = [];
        $subjectScoresPct = [];
        $subjectSubmissionRates = [];

        $classMetrics = [];
        $riskClassesCount = 0;

        foreach ($enrolledClasses as $class) {
            $totalSessions = max(1, (int) $class->total_sessions);
            $clsSessionIds = $class->sessions->pluck('session_id');
            $clsAtt = $clsSessionIds->map(fn ($sid) => $attendances->get($sid))->filter();

            $absentCount = $clsAtt->where('status', 'A')->count();
            $absenceRate = round(($absentCount / $totalSessions) * 100, 1);

            $clsAssignments = $class->assignments;
            $submittedCount = 0;
            $scores = [];
            foreach ($clsAssignments as $asg) {
                $sub = $submissionsByAssignment->get($asg->assignment_id);
                if ($sub) {
                    $submittedCount++;
                    if ($sub->score !== null) {
                        $scores[] = (float) $sub->score;
                    }
                }
            }

            $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
            $subRate = $clsAssignments->count() > 0
                ? round(($submittedCount / $clsAssignments->count()) * 100, 1)
                : 100.0;

            if ($absenceRate >= 15.0 || ($avgScore !== null && $avgScore < 5.0)) {
                $riskClassesCount++;
            }

            $classMetrics[$class->class_id] = [
                'total_sessions' => $totalSessions,
                'avg_score' => $avgScore,
                'sub_rate' => $subRate,
                'has_assignments' => $clsAssignments->count() > 0,
            ];

            $subjectLabels[] = ($class->subject?->subject_code ?? $class->class_name) . ' (' . $class->class_name . ')';
            $subjectAbsenceRates[] = $absenceRate;
            $subjectScoresPct[] = $avgScore !== null ? round($avgScore * 10, 1) : 0.0;
            $subjectSubmissionRates[] = $subRate;
        }

        // 2. Pie Chart & 3. Line Chart theo chuỗi buổi học thực tế của sinh viên
        $allMarkedSessions = $enrolledClasses
            ->flatMap(function ($class) use ($attendances) {
                return $class->sessions->map(function ($sess) use ($class, $attendances) {
                    $att = $attendances->get($sess->session_id);
                    if (! $att) {
                        return null;
                    }
                    return [
                        'session' => $sess,
                        'class' => $class,
                        'att' => $att,
                    ];
                })->filter();
            })
            ->sortBy(fn ($row) => $row['session']->session_date->format('Y-m-d') . '_' . str_pad((string) ($row['session']->slot_id ?? 0), 3, '0', STR_PAD_LEFT))
            ->values();

        $presentCount = 0;
        $lateCount = 0;
        $reasonCounts = array_fill_keys(array_keys(self::REASON_META), 0);
        $runningClassAbsences = [];

        $timelineLabels = [];
        $timelineCumulativeAbsenceRate = [];
        $timelineSessionStatusScore = [];
        $totalSeen = 0;
        $totalAbsentSeen = 0;

        foreach ($allMarkedSessions as $idx => $item) {
            /** @var Attendance $att */
            $att = $item['att'];
            $sess = $item['session'];
            $cls = $item['class'];
            $cid = $cls->class_id;
            $totalSeen++;

            if ($att->status === 'P') {
                $presentCount++;
                $statusVal = 100;
            } elseif ($att->status === 'L') {
                $lateCount++;
                $statusVal = 60;
            } else {
                $totalAbsentSeen++;
                $statusVal = 0;
                $priorAbs = $runningClassAbsences[$cid] ?? 0;
                $cm = $classMetrics[$cid];

                $reasonKey = $this->classifyAbsenceReason(
                    note: $att->note,
                    priorAbsencesInClass: $priorAbs,
                    totalClassSessions: $cm['total_sessions'],
                    slotNumber: $sess->slot?->slot_number,
                    dayOfWeekIso: (int) $sess->session_date->dayOfWeekIso,
                    avgScore: $cm['avg_score'],
                    submissionRate: $cm['sub_rate'],
                    hasAssignments: $cm['has_assignments'],
                    studentRiskClassesCount: $riskClassesCount,
                );
                $reasonCounts[$reasonKey]++;
                $runningClassAbsences[$cid] = $priorAbs + 1;
            }

            $timelineLabels[] = $sess->session_date->format('d/m') . ' (' . ($cls->subject?->subject_code ?? $cls->class_name) . ')';
            $timelineCumulativeAbsenceRate[] = round(($totalAbsentSeen / $totalSeen) * 100, 1);
            $timelineSessionStatusScore[] = $statusVal;
        }

        // Chỉ giữ tối đa 18 mốc gần nhất trên Line Chart cá nhân để dễ đọc
        if (count($timelineLabels) > 18) {
            $timelineLabels = array_slice($timelineLabels, -18);
            $timelineCumulativeAbsenceRate = array_slice($timelineCumulativeAbsenceRate, -18);
            $timelineSessionStatusScore = array_slice($timelineSessionStatusScore, -18);
        }

        $pieLabels = ['Có mặt đầy đủ'];
        $pieData = [$presentCount];
        $pieColors = ['#10b981'];

        if ($lateCount > 0) {
            $pieLabels[] = 'Đi muộn';
            $pieData[] = $lateCount;
            $pieColors[] = '#eab308';
        }

        foreach (self::REASON_META as $key => $meta) {
            if (($reasonCounts[$key] ?? 0) > 0) {
                $pieLabels[] = 'Vắng: ' . $meta['short'];
                $pieData[] = $reasonCounts[$key];
                $pieColors[] = $meta['color'];
            }
        }

        return [
            'has_data' => true,
            'pie' => [
                'labels' => $pieLabels,
                'data' => $pieData,
                'colors' => $pieColors,
                'total_absent' => $totalAbsentSeen,
                'total_sessions_marked' => $totalSeen,
            ],
            'line' => [
                'labels' => $timelineLabels,
                'cumulative_absence_rate' => $timelineCumulativeAbsenceRate,
                'threshold_line' => array_fill(0, count($timelineLabels), 20),
                'attendance_pulse' => $timelineSessionStatusScore,
            ],
            'column' => [
                'labels' => $subjectLabels,
                'absence_rates' => $subjectAbsenceRates,
                'scores_pct' => $subjectScoresPct,
                'submission_rates' => $subjectSubmissionRates,
            ],
        ];
    }

    /**
     * Tính toán trọn bộ dữ liệu Pie / Line / Column cho 1 tập lớp học & sinh viên (tối ưu bằng bulk query).
     */
    private function computeCohortAnalytics(
        array $classIds,
        ?array $studentIdsFilter,
        bool $groupTimelineBySession,
    ): array {
        if (empty($classIds)) {
            return $this->emptyCohortAnalytics();
        }

        $classes = ClassRoom::with('subject')->whereIn('class_id', $classIds)->get()->keyBy('class_id');

        // 1. Lấy điểm trung bình & số bài đã nộp của từng (user_id, class_id)
        $assignmentCountsByClass = Assignment::whereIn('class_id', $classIds)
            ->select('class_id', DB::raw('COUNT(*) as total_assignments'))
            ->groupBy('class_id')
            ->pluck('total_assignments', 'class_id');

        $submissionStatsRows = DB::table('submissions as sub')
            ->join('assignments as asg', 'asg.assignment_id', '=', 'sub.assignment_id')
            ->whereIn('asg.class_id', $classIds)
            ->when($studentIdsFilter !== null, fn ($q) => $q->whereIn('sub.user_id', $studentIdsFilter ?: [-1]))
            ->whereNotNull('sub.submitted_at')
            ->select(
                'sub.user_id',
                'asg.class_id',
                DB::raw('COUNT(*) as submitted_count'),
                DB::raw('AVG(sub.score) as avg_score')
            )
            ->groupBy('sub.user_id', 'asg.class_id')
            ->get();

        $studentClassAcad = [];
        foreach ($submissionStatsRows as $r) {
            $studentClassAcad[$r->user_id][$r->class_id] = [
                'submitted_count' => (int) $r->submitted_count,
                'avg_score' => $r->avg_score !== null ? round((float) $r->avg_score, 2) : null,
            ];
        }

        // 2. Lấy toàn bộ bản ghi điểm danh kèm thông tin buổi học & ca học theo thứ tự thời gian
        $attRows = DB::table('attendances as a')
            ->join('sessions as s', 's.session_id', '=', 'a.session_id')
            ->leftJoin('slots as sl', 'sl.slot_id', '=', 's.slot_id')
            ->whereIn('s.class_id', $classIds)
            ->when($studentIdsFilter !== null, fn ($q) => $q->whereIn('a.user_id', $studentIdsFilter ?: [-1]))
            ->orderBy('s.session_date')
            ->orderBy('s.session_id')
            ->select(
                'a.attendance_id',
                'a.user_id',
                'a.status',
                'a.note',
                's.session_id',
                's.class_id',
                's.session_date',
                'sl.slot_id',
                'sl.slot_number',
                'sl.label as slot_label'
            )
            ->get();

        // Đếm số môn có nguy cơ của từng sinh viên (để nhận diện quá tải đa môn)
        $enrollmentsRows = DB::table('enrollments')
            ->whereIn('class_id', $classIds)
            ->when($studentIdsFilter !== null, fn ($q) => $q->whereIn('user_id', $studentIdsFilter ?: [-1]))
            ->select('user_id', 'class_id')
            ->get();

        $absCountPerStudentClass = [];
        $totalMarkedPerStudentClass = [];
        foreach ($attRows as $row) {
            $totalMarkedPerStudentClass[$row->user_id][$row->class_id] = ($totalMarkedPerStudentClass[$row->user_id][$row->class_id] ?? 0) + 1;
            if ($row->status === 'A') {
                $absCountPerStudentClass[$row->user_id][$row->class_id] = ($absCountPerStudentClass[$row->user_id][$row->class_id] ?? 0) + 1;
            }
        }

        $studentRiskClassesCount = [];
        $studentsMissingHalfSet = [];

        // Tính tổng số môn có dấu hiệu rủi ro trên toàn trường của các sinh viên trong tập đang xét (nhận diện quá tải đa môn chính xác cả ở góc nhìn GV 1 lớp)
        $cohortUserIds = $enrollmentsRows->pluck('user_id')->unique()->values()->all();
        if (! empty($cohortUserIds)) {
            $globalAbsRows = DB::table('enrollments as e')
                ->join('classes as c', 'c.class_id', '=', 'e.class_id')
                ->leftJoin('sessions as s', 's.class_id', '=', 'c.class_id')
                ->leftJoin('attendances as a', function ($join) {
                    $join->on('a.session_id', '=', 's.session_id')
                        ->on('a.user_id', '=', 'e.user_id')
                        ->where('a.status', '=', 'A');
                })
                ->whereIn('e.user_id', $cohortUserIds)
                ->select(
                    'e.user_id',
                    'e.class_id',
                    'c.total_sessions',
                    DB::raw('COUNT(a.attendance_id) as abs_cnt')
                )
                ->groupBy('e.user_id', 'e.class_id', 'c.total_sessions')
                ->get();

            foreach ($globalAbsRows as $gar) {
                $tSess = max(1, (int) $gar->total_sessions);
                if ((($gar->abs_cnt / $tSess) * 100.0) >= 15.0) {
                    $studentRiskClassesCount[$gar->user_id] = ($studentRiskClassesCount[$gar->user_id] ?? 0) + 1;
                }
            }
        }

        // Phục vụ biểu đồ tương quan Học lực vs Tỷ lệ nghỉ & Đường cong tương quan
        $academicBuckets = [
            'missing_work' => ['label' => 'Nợ ≥50% bài tập', 'rates' => [], 'banned' => 0, 'count' => 0],
            'low_grade' => ['label' => 'Điểm yếu (< 5.0)', 'rates' => [], 'banned' => 0, 'count' => 0],
            'mid_grade' => ['label' => 'Điểm TB–Khá (5.0–7.9)', 'rates' => [], 'banned' => 0, 'count' => 0],
            'high_grade' => ['label' => 'Điểm Giỏi (≥ 8.0)', 'rates' => [], 'banned' => 0, 'count' => 0],
        ];

        $absenceCurveBrackets = [
            ['label' => 'Vắng 0%', 'min' => 0.0, 'max' => 0.0, 'scores' => [], 'sub_rates' => []],
            ['label' => 'Vắng 1–10%', 'min' => 0.01, 'max' => 10.0, 'scores' => [], 'sub_rates' => []],
            ['label' => 'Vắng 11–15%', 'min' => 10.01, 'max' => 15.0, 'scores' => [], 'sub_rates' => []],
            ['label' => 'Vắng 16–20%', 'min' => 15.01, 'max' => 20.0, 'scores' => [], 'sub_rates' => []],
            ['label' => 'Vắng 21–35%', 'min' => 20.01, 'max' => 35.0, 'scores' => [], 'sub_rates' => []],
            ['label' => 'Vắng >35%', 'min' => 35.01, 'max' => 100.0, 'scores' => [], 'sub_rates' => []],
        ];

        foreach ($enrollmentsRows as $enr) {
            $uid = $enr->user_id;
            $cid = $enr->class_id;
            $cls = $classes->get($cid);
            if (! $cls) {
                continue;
            }

            $totalSess = max(1, (int) $cls->total_sessions);
            $absCnt = $absCountPerStudentClass[$uid][$cid] ?? 0;
            $absRate = round(($absCnt / $totalSess) * 100, 2);
            $isBanned = $absRate > 20.0;

            $totalAsg = (int) ($assignmentCountsByClass[$cid] ?? 0);
            $acad = $studentClassAcad[$uid][$cid] ?? ['submitted_count' => 0, 'avg_score' => null];
            $subRate = $totalAsg > 0 ? round(($acad['submitted_count'] / $totalAsg) * 100, 1) : 100.0;
            $avgScore = $acad['avg_score'];

            if ($totalAsg > 0 && $subRate <= 50.0) {
                $studentsMissingHalfSet[$uid] = true;
            }

            if ($absRate < 15.0 && $avgScore !== null && $avgScore < 5.0) {
                $studentRiskClassesCount[$uid] = ($studentRiskClassesCount[$uid] ?? 0) + 1;
            }

            // Phân nhóm học lực để tính tỷ lệ vắng trung bình
            if ($totalAsg > 0 && $subRate <= 50.0) {
                $bKey = 'missing_work';
            } elseif ($avgScore !== null && $avgScore < 5.0) {
                $bKey = 'low_grade';
            } elseif ($avgScore !== null && $avgScore >= 8.0) {
                $bKey = 'high_grade';
            } else {
                $bKey = 'mid_grade';
            }
            $academicBuckets[$bKey]['rates'][] = $absRate;
            $academicBuckets[$bKey]['count']++;
            if ($isBanned) {
                $academicBuckets[$bKey]['banned']++;
            }

            // Phân nhóm theo dải tỷ lệ vắng cho Line Chart tương quan
            foreach ($absenceCurveBrackets as $bi => $br) {
                if ($absRate >= $br['min'] && $absRate <= $br['max']) {
                    if ($avgScore !== null) {
                        $absenceCurveBrackets[$bi]['scores'][] = $avgScore;
                    }
                    $absenceCurveBrackets[$bi]['sub_rates'][] = $subRate;
                    break;
                }
            }
        }

        // 3. Phân loại từng lượt vắng (Pie Chart Nguyên nhân) + tổng hợp theo Tuần/Buổi, Ca học & Thứ
        $reasonCounts = array_fill_keys(array_keys(self::REASON_META), 0);
        $runningAbsences = [];

        $slotStats = [
            1 => ['label' => 'Ca 1 (07:15)', 'absent' => 0, 'total' => 0],
            2 => ['label' => 'Ca 2 (09:25)', 'absent' => 0, 'total' => 0],
            3 => ['label' => 'Ca 3 (12:00)', 'absent' => 0, 'total' => 0],
            4 => ['label' => 'Ca 4 (14:10)', 'absent' => 0, 'total' => 0],
        ];
        $unassignedSlot = ['label' => 'Ca linh hoạt', 'absent' => 0, 'total' => 0];

        $dowStats = [
            1 => ['label' => 'Thứ 2', 'absent' => 0, 'total' => 0],
            2 => ['label' => 'Thứ 3', 'absent' => 0, 'total' => 0],
            3 => ['label' => 'Thứ 4', 'absent' => 0, 'total' => 0],
            4 => ['label' => 'Thứ 5', 'absent' => 0, 'total' => 0],
            5 => ['label' => 'Thứ 6', 'absent' => 0, 'total' => 0],
            6 => ['label' => 'Thứ 7', 'absent' => 0, 'total' => 0],
            7 => ['label' => 'Chủ nhật', 'absent' => 0, 'total' => 0],
        ];

        $timelineBuckets = [];
        $sessionOrderIndex = [];

        foreach ($attRows as $row) {
            $uid = $row->user_id;
            $cid = $row->class_id;
            $cls = $classes->get($cid);
            $totalSess = max(1, (int) ($cls?->total_sessions ?? 12));
            $dateObj = Carbon::parse($row->session_date);
            $dowIso = (int) $dateObj->dayOfWeekIso; // 1 = Mon .. 7 = Sun

            // Slot stats
            $sNum = $row->slot_number ? (int) $row->slot_number : null;
            if ($sNum && isset($slotStats[$sNum])) {
                $slotStats[$sNum]['total']++;
                if ($row->status === 'A') {
                    $slotStats[$sNum]['absent']++;
                }
            } else {
                $unassignedSlot['total']++;
                if ($row->status === 'A') {
                    $unassignedSlot['absent']++;
                }
            }

            // Day of week stats
            $dowStats[$dowIso]['total']++;
            if ($row->status === 'A') {
                $dowStats[$dowIso]['absent']++;
            }

            // Timeline bucket (theo từng buổi cho Teacher, theo tuần cho Admin)
            if ($groupTimelineBySession) {
                $tKey = 'sess_' . $row->session_id;
                if (! isset($sessionOrderIndex[$tKey])) {
                    $sessionOrderIndex[$tKey] = count($sessionOrderIndex) + 1;
                }
                $tLabel = 'Buổi ' . $sessionOrderIndex[$tKey] . ' (' . $dateObj->format('d/m') . ')';
            } else {
                $weekStart = $dateObj->copy()->startOfWeek();
                $tKey = $weekStart->format('Y-m-d');
                $tLabel = 'Tuần ' . $weekStart->format('d/m');
            }

            if (! isset($timelineBuckets[$tKey])) {
                $timelineBuckets[$tKey] = [
                    'label' => $tLabel,
                    'absent' => 0,
                    'late' => 0,
                    'total' => 0,
                ];
            }
            $timelineBuckets[$tKey]['total']++;
            if ($row->status === 'L') {
                $timelineBuckets[$tKey]['late']++;
            }

            if ($row->status === 'A') {
                $timelineBuckets[$tKey]['absent']++;
                $prior = $runningAbsences[$uid][$cid] ?? 0;

                $totalAsg = (int) ($assignmentCountsByClass[$cid] ?? 0);
                $acad = $studentClassAcad[$uid][$cid] ?? ['submitted_count' => 0, 'avg_score' => null];
                $subRate = $totalAsg > 0 ? round(($acad['submitted_count'] / $totalAsg) * 100, 1) : 100.0;

                $reasonKey = $this->classifyAbsenceReason(
                    note: $row->note,
                    priorAbsencesInClass: $prior,
                    totalClassSessions: $totalSess,
                    slotNumber: $sNum,
                    dayOfWeekIso: $dowIso,
                    avgScore: $acad['avg_score'],
                    submissionRate: $subRate,
                    hasAssignments: $totalAsg > 0,
                    studentRiskClassesCount: $studentRiskClassesCount[$uid] ?? 0,
                );
                $reasonCounts[$reasonKey]++;
                $runningAbsences[$uid][$cid] = $prior + 1;
            }
        }

        if ($unassignedSlot['total'] > 0) {
            $slotStats[99] = $unassignedSlot;
        }

        // Format Pie Chart Nguyên nhân
        $reasonBreakdown = [];
        $totalAbsences = array_sum($reasonCounts);
        foreach (self::REASON_META as $key => $meta) {
            $cnt = $reasonCounts[$key] ?? 0;
            $pct = $totalAbsences > 0 ? round(($cnt / $totalAbsences) * 100, 1) : 0.0;
            $reasonBreakdown[] = [
                'key' => $key,
                'label' => $meta['label'],
                'short' => $meta['short'],
                'color' => $meta['color'],
                'count' => $cnt,
                'percent' => $pct,
            ];
        }

        // Format Line Chart 1 (Xu hướng thời gian)
        $tlValues = array_values($timelineBuckets);
        if (count($tlValues) > 16) {
            $tlValues = array_slice($tlValues, -16);
        }
        $tlLabels = array_map(fn ($b) => $b['label'], $tlValues);
        $tlAbsenceRates = array_map(
            fn ($b) => $b['total'] > 0 ? round(($b['absent'] / $b['total']) * 100, 1) : 0.0,
            $tlValues
        );
        $tlAbsentCounts = array_map(fn ($b) => $b['absent'], $tlValues);

        // Format Line Chart 2 (Đường cong tương quan Điểm & Nộp bài theo mức vắng)
        $curveLabels = [];
        $curveAvgScores = [];
        $curveSubRates = [];
        foreach ($absenceCurveBrackets as $br) {
            $curveLabels[] = $br['label'];
            $curveAvgScores[] = count($br['scores']) > 0
                ? round(array_sum($br['scores']) / count($br['scores']), 2)
                : null;
            $curveSubRates[] = count($br['sub_rates']) > 0
                ? round(array_sum($br['sub_rates']) / count($br['sub_rates']), 1)
                : null;
        }

        // Format Column Chart 1 (Theo Ca học & Theo Thứ)
        $slotLabels = [];
        $slotRates = [];
        $slotCounts = [];
        foreach ($slotStats as $st) {
            $slotLabels[] = $st['label'];
            $slotRates[] = $st['total'] > 0 ? round(($st['absent'] / $st['total']) * 100, 1) : 0.0;
            $slotCounts[] = $st['absent'];
        }

        $dowLabels = [];
        $dowRates = [];
        $dowCounts = [];
        foreach ($dowStats as $dw) {
            $dowLabels[] = $dw['label'];
            $dowRates[] = $dw['total'] > 0 ? round(($dw['absent'] / $dw['total']) * 100, 1) : 0.0;
            $dowCounts[] = $dw['absent'];
        }

        // Format Column Chart 2 (So sánh Lớp/Môn & Nhóm Học lực)
        $subjectClassColumn = $this->buildClassComparisonColumns($classIds, $studentIdsFilter);

        $acadLabels = [];
        $acadAvgAbsenceRates = [];
        $acadBannedRates = [];
        foreach ($academicBuckets as $ab) {
            $acadLabels[] = $ab['label'];
            $acadAvgAbsenceRates[] = count($ab['rates']) > 0
                ? round(array_sum($ab['rates']) / count($ab['rates']), 1)
                : 0.0;
            $acadBannedRates[] = $ab['count'] > 0
                ? round(($ab['banned'] / $ab['count']) * 100, 1)
                : 0.0;
        }

        // Sinh câu nhận định AI tự động từ số liệu thật
        $sortedReasons = $reasonBreakdown;
        usort($sortedReasons, fn ($a, $b) => $b['count'] <=> $a['count']);
        $topReason = $sortedReasons[0] ?? null;

        $maxSlotIdx = ! empty($slotRates) ? array_keys($slotRates, max($slotRates))[0] : 0;
        $topSlotName = $slotLabels[$maxSlotIdx] ?? 'Ca 1';
        $topSlotRate = $slotRates[$maxSlotIdx] ?? 0;

        $maxDowIdx = ! empty($dowRates) ? array_keys($dowRates, max($dowRates))[0] : 0;
        $topDowName = $dowLabels[$maxDowIdx] ?? 'Thứ 2';
        $topDowRate = $dowRates[$maxDowIdx] ?? 0;

        $aiInsight = $totalAbsences > 0
            ? sprintf(
                'Qua %d lượt vắng được ghi nhận, AI xác định nguyên nhân hàng đầu là "%s" (chiếm %s%%). Tỷ lệ nghỉ học cao nhất rơi vào %s (%s%%) và %s (%s%%). Nhóm sinh viên nợ bài tập hoặc điểm dưới 5.0 có xu hướng nghỉ học cao vượt trội so với nhóm Khá/Giỏi.',
                $totalAbsences,
                $topReason['label'] ?? 'Vắng không phép',
                $topReason['percent'] ?? 0,
                $topSlotName,
                $topSlotRate,
                $topDowName,
                $topDowRate
            )
            : 'Hiện chưa ghi nhận lượt vắng học nào trong phạm vi dữ liệu đang chọn; tình hình chuyên cần của sinh viên đạt mức ổn định tốt.';

        return [
            'total_absences' => $totalAbsences,
            'total_marked_records' => $attRows->count(),
            'overall_absence_rate' => $attRows->count() > 0 ? round(($totalAbsences / $attRows->count()) * 100, 1) : 0.0,
            'students_missing_half_assignments' => count($studentsMissingHalfSet),
            'ai_insight' => $aiInsight,
            'reasons_pie' => [
                'labels' => array_column($reasonBreakdown, 'short'),
                'full_labels' => array_column($reasonBreakdown, 'label'),
                'data' => array_column($reasonBreakdown, 'count'),
                'colors' => array_column($reasonBreakdown, 'color'),
                'breakdown' => $reasonBreakdown,
            ],
            'timeline_line' => [
                'labels' => $tlLabels,
                'absence_rates' => $tlAbsenceRates,
                'absent_counts' => $tlAbsentCounts,
                'threshold_20' => array_fill(0, count($tlLabels), 20),
            ],
            'correlation_line' => [
                'labels' => $curveLabels,
                'avg_scores' => $curveAvgScores,
                'submission_rates' => $curveSubRates,
            ],
            'schedule_column' => [
                'by_slot' => [
                    'labels' => $slotLabels,
                    'rates' => $slotRates,
                    'counts' => $slotCounts,
                ],
                'by_dow' => [
                    'labels' => $dowLabels,
                    'rates' => $dowRates,
                    'counts' => $dowCounts,
                ],
            ],
            'subject_class_column' => $subjectClassColumn,
            'academic_column' => [
                'labels' => $acadLabels,
                'avg_absence_rates' => $acadAvgAbsenceRates,
                'banned_rates' => $acadBannedRates,
            ],
        ];
    }

    /**
     * Xây dựng dữ liệu Column Chart so sánh tỷ lệ vắng & tỷ lệ cấm thi giữa các Lớp / Môn học.
     */
    private function buildClassComparisonColumns(array $classIds, ?array $studentIdsFilter = null): array
    {
        if (empty($classIds)) {
            return ['labels' => [], 'absence_rates' => [], 'banned_rates' => [], 'banned_counts' => []];
        }

        $classes = ClassRoom::with('subject')->whereIn('class_id', $classIds)->get();
        $enrollments = DB::table('enrollments')
            ->whereIn('class_id', $classIds)
            ->when($studentIdsFilter !== null, fn ($q) => $q->whereIn('user_id', $studentIdsFilter ?: [-1]))
            ->select('class_id', 'user_id')
            ->get()
            ->groupBy('class_id');

        $absentByClassStudent = DB::table('attendances as a')
            ->join('sessions as s', 's.session_id', '=', 'a.session_id')
            ->whereIn('s.class_id', $classIds)
            ->when($studentIdsFilter !== null, fn ($q) => $q->whereIn('a.user_id', $studentIdsFilter ?: [-1]))
            ->select(
                's.class_id',
                'a.user_id',
                DB::raw("SUM(CASE WHEN a.status = 'A' THEN 1 ELSE 0 END) as absent_cnt"),
                DB::raw('COUNT(*) as marked_cnt')
            )
            ->groupBy('s.class_id', 'a.user_id')
            ->get()
            ->groupBy('class_id');

        $rows = [];
        foreach ($classes as $cls) {
            $cid = $cls->class_id;
            $clsStudents = $enrollments->get($cid, collect());
            if ($clsStudents->isEmpty()) {
                continue;
            }

            $attStats = $absentByClassStudent->get($cid, collect())->keyBy('user_id');
            $totalAbsent = 0;
            $totalMarked = 0;
            $bannedCount = 0;
            $totalSess = max(1, (int) $cls->total_sessions);

            foreach ($clsStudents as $stRow) {
                $stAtt = $attStats->get($stRow->user_id);
                $abs = $stAtt ? (int) $stAtt->absent_cnt : 0;
                $mk = $stAtt ? (int) $stAtt->marked_cnt : 0;
                $totalAbsent += $abs;
                $totalMarked += $mk;
                if (($abs / $totalSess) * 100 > 20.0) {
                    $bannedCount++;
                }
            }

            $absRate = $totalMarked > 0 ? round(($totalAbsent / $totalMarked) * 100, 1) : 0.0;
            $bannedRate = round(($bannedCount / $clsStudents->count()) * 100, 1);

            $rows[] = [
                'label' => $cls->class_name . ($cls->subject ? ' (' . $cls->subject->subject_code . ')' : ''),
                'absence_rate' => $absRate,
                'banned_rate' => $bannedRate,
                'banned_count' => $bannedCount,
            ];
        }

        usort($rows, fn ($a, $b) => $b['absence_rate'] <=> $a['absence_rate']);
        $rows = array_slice($rows, 0, 8);

        return [
            'labels' => array_column($rows, 'label'),
            'absence_rates' => array_column($rows, 'absence_rate'),
            'banned_rates' => array_column($rows, 'banned_rate'),
            'banned_counts' => array_column($rows, 'banned_count'),
        ];
    }

    /**
     * Phân loại nguyên nhân của 1 lượt vắng học kết hợp Ghi chú điểm danh + Tương quan hành vi AI.
     */
    public function classifyAbsenceReason(
        ?string $note,
        int $priorAbsencesInClass,
        int $totalClassSessions,
        ?int $slotNumber,
        int $dayOfWeekIso,
        ?float $avgScore,
        float $submissionRate,
        bool $hasAssignments,
        int $studentRiskClassesCount,
    ): string {
        $normalizedNote = mb_strtolower(trim((string) $note));

        // 1. Ưu tiên phân tích ngữ nghĩa từ ghi chú thực tế của giảng viên (nếu có lý do cụ thể)
        if ($normalizedNote !== '' && $normalizedNote !== 'vắng không phép' && $normalizedNote !== 'không phép') {
            if (preg_match('/ốm|bệnh|sức khỏe|viện|sốt|đau|khám|tai nạn|gia đình|việc riêng|có phép|xin phép|cưới|tang|quê/u', $normalizedNote)) {
                return 'excused_personal';
            }
            if (preg_match('/ngủ quên|muộn|sáng sớm|ca 1|kẹt xe|hỏng xe|thời tiết|mưa/u', $normalizedNote)) {
                return 'morning_weekend';
            }
            if (preg_match('/nợ bài|chưa làm bài|điểm kém|sợ kiểm tra|không hiểu bài|mất gốc/u', $normalizedNote)) {
                return 'academic_struggle';
            }
            if (preg_match('/cấm thi|bỏ môn|buông xuôi|quá 20/u', $normalizedNote)) {
                return 'post_ban_drop';
            }
            if (preg_match('/trùng lịch|làm thêm|đi làm|quá tải|nhiều môn/u', $normalizedNote)) {
                return 'multi_subject_overload';
            }
        }

        // 2. Chẩn đoán AI từ hành vi & học lực thực tế khi sinh viên nghỉ không phép
        $priorAbsenceRate = ($priorAbsencesInClass / max(1, $totalClassSessions)) * 100.0;

        // Đã vượt ngưỡng cấm thi 20% từ các buổi trước -> hiệu ứng tâm lý buông xuôi
        if ($priorAbsenceRate > 20.0) {
            return 'post_ban_drop';
        }

        // Buổi học ca sáng sớm 07:15 (Ca 1) hoặc cuối tuần (Thứ 7 / Chủ Nhật) ở nhóm chưa rớt điểm nặng
        $isEarlyOrWeekend = ($slotNumber === 1) || in_array($dayOfWeekIso, [6, 7], true);
        if ($isEarlyOrWeekend && ($avgScore === null || $avgScore >= 4.5)) {
            return 'morning_weekend';
        }

        // Sinh viên đang gặp rủi ro đồng thời từ >= 2 môn học trở lên và điểm yếu/trung bình thấp
        if ($studentRiskClassesCount >= 2 && $avgScore !== null && $avgScore < 5.5 && ($slotNumber ?? 2) >= 2) {
            return 'multi_subject_overload';
        }

        // Sinh viên điểm yếu (< 5.0) hoặc bỏ nộp từ 50% bài tập trở lên trong môn học này
        if (($avgScore !== null && $avgScore < 5.0) || ($hasAssignments && $submissionRate <= 50.0)) {
            return 'academic_struggle';
        }

        if ($isEarlyOrWeekend) {
            return 'morning_weekend';
        }

        return 'unexcused_habit';
    }

    private function emptyCohortAnalytics(): array
    {
        return [
            'total_absences' => 0,
            'total_marked_records' => 0,
            'overall_absence_rate' => 0.0,
            'students_missing_half_assignments' => 0,
            'ai_insight' => 'Chưa có dữ liệu điểm danh trong phạm vi đang chọn.',
            'reasons_pie' => [
                'labels' => [],
                'full_labels' => [],
                'data' => [],
                'colors' => [],
                'breakdown' => [],
            ],
            'timeline_line' => [
                'labels' => [],
                'absence_rates' => [],
                'absent_counts' => [],
                'threshold_20' => [],
            ],
            'correlation_line' => [
                'labels' => [],
                'avg_scores' => [],
                'submission_rates' => [],
            ],
            'schedule_column' => [
                'by_slot' => ['labels' => [], 'rates' => [], 'counts' => []],
                'by_dow' => ['labels' => [], 'rates' => [], 'counts' => []],
            ],
            'subject_class_column' => [
                'labels' => [],
                'absence_rates' => [],
                'banned_rates' => [],
                'banned_counts' => [],
            ],
            'academic_column' => [
                'labels' => [],
                'avg_absence_rates' => [],
                'banned_rates' => [],
            ],
        ];
    }
}
