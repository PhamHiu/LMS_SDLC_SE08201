<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use App\Services\AI\PolicyRuleEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Thống kê số buổi nghỉ học của học sinh theo từng môn học.
 * Dùng chung PolicyRuleEngine với AI Support để áp dụng đúng phiên bản quy chế đang hiệu lực.
 */
class AbsenceStatisticsService
{
    public const STATUS_SAFE = 'SAFE';       // < (ngưỡng - 5%)
    public const STATUS_WARNING = 'WARNING'; // tiệm cận ngưỡng
    public const STATUS_FAILED = 'FAILED';   // vượt ngưỡng (Trượt môn)

    public function __construct(
        private readonly ?PolicyRuleEngine $policyEngine = null,
    ) {
    }

    private function engine(): PolicyRuleEngine
    {
        return $this->policyEngine ?? app(PolicyRuleEngine::class);
    }

    /**
     * Báo cáo số buổi nghỉ theo từng (Học sinh × Môn học/Lớp) cho Admin.
     */
    public function getAdminSubjectAbsenceReport(?int $subjectId = null, string $status = 'ALL', ?string $search = null): array
    {
        $enrollments = DB::table('enrollments as e')
            ->join('users as u', 'u.user_id', '=', 'e.user_id')
            ->join('classes as c', 'c.class_id', '=', 'e.class_id')
            ->join('subjects as s', 's.subject_id', '=', 'c.subject_id')
            ->leftJoin('majors as m', 'm.major_id', '=', 'u.major_id')
            ->where('u.role', User::ROLE_STUDENT)
            ->when($subjectId, fn ($q) => $q->where('c.subject_id', $subjectId))
            ->select([
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.student_code',
                'm.major_name',
                'c.class_id',
                'c.class_name',
                'c.total_sessions',
                's.subject_id',
                's.subject_code',
                's.subject_name',
            ])
            ->orderBy('s.subject_name')
            ->orderBy('u.full_name')
            ->get();

        // Tổng hợp điểm danh theo (user_id, class_id) trong 1 query duy nhất
        $attMap = DB::table('attendances as a')
            ->join('sessions as cs', 'cs.session_id', '=', 'a.session_id')
            ->selectRaw(
                'a.user_id, cs.class_id,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as absent_count',
                [Attendance::PRESENT, Attendance::LATE, Attendance::ABSENT]
            )
            ->groupBy('a.user_id', 'cs.class_id')
            ->get()
            ->keyBy(fn ($r) => $r->user_id . '_' . $r->class_id);

        $allRows = $enrollments->map(function ($row) use ($attMap) {
            $att = $attMap->get($row->user_id . '_' . $row->class_id);
            $totalSessions = max(1, (int) $row->total_sessions);
            $present = (int) ($att->present_count ?? 0);
            $late = (int) ($att->late_count ?? 0);
            $absent = (int) ($att->absent_count ?? 0);
            $rate = round(($absent / $totalSessions) * 100, 1);

            return [
                'user_id' => (int) $row->user_id,
                'student_code' => $row->student_code ?: ('HL' . str_pad((string) $row->user_id, 5, '0', STR_PAD_LEFT)),
                'full_name' => $row->full_name,
                'email' => $row->email,
                'major_name' => $row->major_name ?? 'Chưa phân ngành',
                'class_id' => (int) $row->class_id,
                'class_name' => $row->class_name,
                'subject_id' => (int) $row->subject_id,
                'subject_code' => $row->subject_code,
                'subject_name' => $row->subject_name,
                'total_sessions' => $totalSessions,
                'present_sessions' => $present,
                'late_sessions' => $late,
                'absent_sessions' => $absent,
                'max_allowed_absences' => (int) floor($totalSessions * 0.2),
                'absence_rate' => $rate,
                'status' => $this->resolveStatus($rate),
            ];
        });

        // Bộ đếm trước khi lọc theo trạng thái (nhưng giữ theo môn đang chọn)
        $counts = [
            'total' => $allRows->count(),
            self::STATUS_SAFE => $allRows->where('status', self::STATUS_SAFE)->count(),
            self::STATUS_WARNING => $allRows->where('status', self::STATUS_WARNING)->count(),
            self::STATUS_FAILED => $allRows->where('status', self::STATUS_FAILED)->count(),
        ];

        $filtered = $allRows;

        if ($status !== 'ALL' && in_array($status, [self::STATUS_SAFE, self::STATUS_WARNING, self::STATUS_FAILED], true)) {
            $filtered = $filtered->where('status', $status);
        }

        $needle = trim((string) $search);
        if ($needle !== '') {
            $lower = mb_strtolower($needle);
            $filtered = $filtered->filter(function ($r) use ($lower) {
                return str_contains(mb_strtolower($r['full_name']), $lower)
                    || str_contains(mb_strtolower($r['student_code']), $lower)
                    || str_contains(mb_strtolower($r['subject_name']), $lower)
                    || str_contains(mb_strtolower($r['class_name']), $lower);
            });
        }

        $filtered = $filtered->sortByDesc('absence_rate')->values();

        $subjects = Subject::orderBy('subject_name')->get(['subject_id', 'subject_code', 'subject_name']);

        return [
            'rows' => $filtered,
            'counts' => $counts,
            'subjects' => $subjects,
        ];
    }

    /**
     * Báo cáo số buổi nghỉ học của học sinh trong 1 lớp/môn cụ thể cho Giảng viên.
     */
    public function getClassAbsenceReport(ClassRoom $class, string $status = 'ALL', ?string $search = null): array
    {
        $class->loadMissing('subject');
        $students = $class->students()->orderBy('full_name')->get();
        $totalSessions = max(1, (int) $class->total_sessions);
        $maxAllowed = (int) floor($totalSessions * 0.2);

        $sessionIds = $class->sessions()->pluck('session_id');
        $attMap = Attendance::whereIn('session_id', $sessionIds)
            ->whereIn('user_id', $students->pluck('user_id'))
            ->get()
            ->groupBy('user_id');

        $allRows = $students->map(function (User $student) use ($attMap, $totalSessions, $maxAllowed) {
            $records = $attMap->get($student->user_id, collect());
            $present = $records->where('status', Attendance::PRESENT)->count();
            $late = $records->where('status', Attendance::LATE)->count();
            $absent = $records->where('status', Attendance::ABSENT)->count();
            $rate = round(($absent / $totalSessions) * 100, 1);

            return [
                'student' => $student,
                'user_id' => $student->user_id,
                'student_code' => $student->student_code ?: ('HL' . str_pad((string) $student->user_id, 5, '0', STR_PAD_LEFT)),
                'full_name' => $student->full_name,
                'email' => $student->email,
                'total_sessions' => $totalSessions,
                'present_sessions' => $present,
                'late_sessions' => $late,
                'absent_sessions' => $absent,
                'max_allowed_absences' => $maxAllowed,
                'absence_rate' => $rate,
                'status' => $this->resolveStatus($rate),
            ];
        });

        $counts = [
            'total' => $allRows->count(),
            self::STATUS_SAFE => $allRows->where('status', self::STATUS_SAFE)->count(),
            self::STATUS_WARNING => $allRows->where('status', self::STATUS_WARNING)->count(),
            self::STATUS_FAILED => $allRows->where('status', self::STATUS_FAILED)->count(),
        ];

        $filtered = $allRows;
        if ($status !== 'ALL' && in_array($status, [self::STATUS_SAFE, self::STATUS_WARNING, self::STATUS_FAILED], true)) {
            $filtered = $filtered->where('status', $status);
        }

        $needle = trim((string) $search);
        if ($needle !== '') {
            $lower = mb_strtolower($needle);
            $filtered = $filtered->filter(function ($r) use ($lower) {
                return str_contains(mb_strtolower($r['full_name']), $lower)
                    || str_contains(mb_strtolower($r['student_code']), $lower);
            });
        }

        return [
            'class' => $class,
            'rows' => $filtered->sortByDesc('absence_rate')->values(),
            'counts' => $counts,
        ];
    }

    /**
     * Chi tiết số buổi nghỉ theo từng môn học + lịch sử các buổi vắng của 1 học sinh.
     */
    public function getStudentSubjectBreakdown(User $student, ?array $limitClassIds = null): array
    {
        $classesQuery = $student->enrolledClasses()->with('subject')->orderBy('class_name');
        if ($limitClassIds !== null) {
            $classesQuery->whereIn('classes.class_id', $limitClassIds);
        }
        $classes = $classesQuery->get();

        $classIds = $classes->pluck('class_id')->all();

        $attByClass = DB::table('attendances as a')
            ->join('sessions as cs', 'cs.session_id', '=', 'a.session_id')
            ->where('a.user_id', $student->user_id)
            ->when(! empty($classIds), fn ($q) => $q->whereIn('cs.class_id', $classIds))
            ->selectRaw(
                'cs.class_id,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as absent_count',
                [Attendance::PRESENT, Attendance::LATE, Attendance::ABSENT]
            )
            ->groupBy('cs.class_id')
            ->get()
            ->keyBy('class_id');

        $subjects = $classes->map(function (ClassRoom $cls) use ($attByClass) {
            $att = $attByClass->get($cls->class_id);
            $totalSessions = max(1, (int) $cls->total_sessions);
            $present = (int) ($att->present_count ?? 0);
            $late = (int) ($att->late_count ?? 0);
            $absent = (int) ($att->absent_count ?? 0);
            $rate = round(($absent / $totalSessions) * 100, 1);

            return [
                'class_id' => $cls->class_id,
                'class_name' => $cls->class_name,
                'subject_code' => $cls->subject?->subject_code ?? '—',
                'subject_name' => $cls->subject?->subject_name ?? 'Chưa gắn môn',
                'total_sessions' => $totalSessions,
                'present_sessions' => $present,
                'late_sessions' => $late,
                'absent_sessions' => $absent,
                'max_allowed_absences' => (int) floor($totalSessions * 0.2),
                'absence_rate' => $rate,
                'status' => $this->resolveStatus($rate),
            ];
        })->sortByDesc('absence_rate')->values();

        $absentHistory = DB::table('attendances as a')
            ->join('sessions as cs', 'cs.session_id', '=', 'a.session_id')
            ->join('classes as c', 'c.class_id', '=', 'cs.class_id')
            ->join('subjects as s', 's.subject_id', '=', 'c.subject_id')
            ->where('a.user_id', $student->user_id)
            ->where('a.status', Attendance::ABSENT)
            ->when(! empty($classIds), fn ($q) => $q->whereIn('cs.class_id', $classIds))
            ->select([
                'cs.session_date',
                'c.class_name',
                's.subject_name',
                's.subject_code',
                'a.note',
            ])
            ->orderByDesc('cs.session_date')
            ->limit(30)
            ->get();

        return [
            'subjects' => $subjects,
            'absent_history' => $absentHistory,
            'summary' => [
                'total_subjects' => $subjects->count(),
                'failed_subjects' => $subjects->where('status', self::STATUS_FAILED)->count(),
                'warning_subjects' => $subjects->where('status', self::STATUS_WARNING)->count(),
                'safe_subjects' => $subjects->where('status', self::STATUS_SAFE)->count(),
                'total_absent_sessions' => $subjects->sum('absent_sessions'),
            ],
        ];
    }

    public function resolveStatus(float $absenceRate): string
    {
        $resolved = $this->engine()->resolveAttendanceRule();
        $rule = $resolved['rule'] ?? null;
        $eval = $this->engine()->evaluateAbsence((int) round($absenceRate * 10), 1000, $rule);

        return $eval['status'] ?? self::STATUS_SAFE;
    }

    /**
     * Đánh giá chuyên cần dùng chung với AI Support bằng tỷ lệ chính xác trước khi làm tròn.
     */
    public function evaluateClassAttendance(int $absentSessions, int $totalSessions, ?ClassRoom $class = null): array
    {
        $resolved = $this->engine()->resolveAttendanceRule($class);
        $rule = $resolved['rule'] ?? null;

        return $this->engine()->evaluateAbsence($absentSessions, $totalSessions, $rule);
    }
}
