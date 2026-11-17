<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CẤU HÌNH HỆ THỐNG AI CẢNH BÁO NGUY CƠ NGHỈ HỌC / BỎ HỌC (SMART LMS)
    |--------------------------------------------------------------------------
    | Dựa trên tài liệu nghiên cứu học thuật (17/11/2026):
    | - Trọng số thực nghiệm chuẩn: Vắng học (40%) + Điểm số (35%) + Trượt môn (25%).
    | - Toàn bộ tính toán là ĐỘNG 100% dựa trên công thức toán học giải tích.
    | - Điểm nguy cơ (0 - 100) là thang đo mức độ ưu tiên hỗ trợ sư phạm.
    |--------------------------------------------------------------------------
    */

    'weights' => [
        // Trọng số chuyên cần (A) - Dấu hiệu hành vi theo dõi sớm
        'attendance' => (float) env('RISK_WEIGHT_ATTENDANCE', 0.40),

        // Trọng số học lực (G) - Điểm trung bình GPA và mức độ theo kịp môn học
        'academic' => (float) env('RISK_WEIGHT_ACADEMIC', 0.35),

        // Trọng số nợ môn / trượt / học lại (R) - Khó khăn học tập tích lũy
        'retake' => (float) env('RISK_WEIGHT_RETAKE', 0.25),
    ],

    'thresholds' => [
        // Ngưỡng cấm thi / trượt chuyên cần theo quy chế đào tạo (20%)
        'attendance_fail_rate' => (float) env('ATTENDANCE_FAIL_RATE', 0.20),

        // Ngưỡng cảnh báo sớm chuyên cần tiệm cận cấm thi (15%)
        'attendance_warning_rate' => (float) env('ATTENDANCE_WARNING_RATE', 0.15),

        // Điểm GPA an toàn (GPA >= 7.0 thì rủi ro điểm G = 0)
        'gpa_safe' => (float) env('RISK_GPA_SAFE', 7.0),

        // Điểm GPA nguy cơ tối đa (GPA <= 4.0 thì rủi ro điểm G = 1.0)
        'gpa_critical' => (float) env('RISK_GPA_CRITICAL', 4.0),

        // Điểm chuẩn đạt môn học
        'passing_score' => (float) env('RISK_PASSING_SCORE', 5.0),
    ],

    'levels' => [
        'safe' => [
            'min' => 0.0,
            'max' => 24.9,
            'label' => 'An toàn (Safe)',
            'color' => '#10b981', // green
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'action' => 'Duy trì theo dõi học vụ thường kỳ.',
        ],
        'medium' => [
            'min' => 25.0,
            'max' => 49.9,
            'label' => 'Cần theo dõi (Watchlist)',
            'color' => '#f59e0b', // amber
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            'action' => 'Nhắc nhở qua thông báo LMS, rà soát các buổi vắng tiệm cận 20%.',
        ],
        'high' => [
            'min' => 50.0,
            'max' => 74.9,
            'label' => 'Nguy cơ cao (High)',
            'color' => '#f97316', // orange
            'badge' => 'bg-orange-50 text-orange-700 border-orange-200',
            'action' => 'Giảng viên/Cố vấn liên hệ trực tiếp, tìm hiểu nguyên nhân và phụ đạo.',
        ],
        'very_high' => [
            'min' => 75.0,
            'max' => 100.0,
            'label' => 'Nguy cơ rất cao (Very High)',
            'color' => '#ef4444', // red
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            'action' => 'Ưu tiên hỗ trợ khẩn cấp, họp phụ huynh/sinh viên, xem xét kế hoạch giãn hạn/bảo lưu.',
        ],
    ],

    // Danh mục 9 nhóm nguyên nhân học thuật chuẩn mực (Theo tài liệu nghiên cứu)
    'root_causes' => [
        'academic_struggle' => [
            'code' => 'academic_struggle',
            'category' => '1. Kết quả học tập',
            'label' => 'Hổng kiến thức / Điểm yếu (<5.0) & Nợ bài',
            'short' => 'Hổng kiến thức & Nợ bài',
            'description' => 'Điểm thấp, không đạt yêu cầu học phần, khó theo kịp bài giảng.',
            'color' => '#f43f5e',
            'solution' => 'Phụ đạo 1-1, trợ lý AI Mr. Biết Tuốt, tư vấn lộ trình học bù.',
        ],
        'post_ban_drop' => [
            'code' => 'post_ban_drop',
            'category' => '2. Chuyên cần & Ý thức',
            'label' => 'Buông xuôi sau vượt ngưỡng cấm thi (>20%)',
            'short' => 'Buông xuôi do cấm thi (>20%)',
            'description' => 'Đã vắng quá 20% buổi học môn học, dẫn đến tâm lý bỏ bê các môn còn lại.',
            'color' => '#f97316',
            'solution' => 'Hướng dẫn thủ tục xin học lại/học cải thiện sớm, tránh nản lòng bỏ cả kỳ.',
        ],
        'morning_weekend' => [
            'code' => 'morning_weekend',
            'category' => '9. Điều kiện tiếp cận',
            'label' => 'Khó khăn ca sáng sớm (07:15) & Cuối tuần',
            'short' => 'Khó khăn ca sáng / Đi lại xa',
            'description' => 'Vắng tập trung vào các ca học sớm hoặc ngày cuối tuần, thường do nhà xa hoặc phương tiện.',
            'color' => '#eab308',
            'solution' => 'Hỗ trợ chuyển ca học/lớp phù hợp hơn, cung cấp video bài giảng lưu lại.',
        ],
        'multi_subject_overload' => [
            'code' => 'multi_subject_overload',
            'category' => '7. Nhà trường & Chương trình',
            'label' => 'Quá tải lịch học / Yếu nhiều môn cùng lúc',
            'short' => 'Quá tải đa môn',
            'description' => 'Đăng ký quá nhiều tín chỉ hoặc lịch thi/nộp bài dồn dập trong cùng một tuần.',
            'color' => '#a855f7',
            'solution' => 'Cố vấn giãn tiến độ, điều chỉnh kế hoạch học tập cá nhân hóa.',
        ],
        'financial_work' => [
            'code' => 'financial_work',
            'category' => '3. Tài chính & Việc làm thêm',
            'label' => 'Khó khăn tài chính / Phải đi làm thêm nhiều giờ',
            'short' => 'Tài chính / Đi làm thêm',
            'description' => 'Thiếu học phí/sinh hoạt phí, phải làm thêm giờ hành chính dẫn đến vắng học và kiệt sức.',
            'color' => '#14b8a6',
            'solution' => 'Hỗ trợ học bổng, giãn hạn đóng học phí, giới thiệu việc làm bán thời gian phù hợp.',
        ],
        'motivation_major' => [
            'code' => 'motivation_major',
            'category' => '4. Động lực & Chọn sai ngành',
            'label' => 'Chọn sai ngành / Thiếu động lực (PROTEUS: 24.35%)',
            'short' => 'Chọn sai ngành / Chán nản',
            'description' => 'Không thấy hứng thú với chuyên ngành, mất phương hướng nghề nghiệp.',
            'color' => '#ec4899',
            'solution' => 'Tư vấn hướng nghiệp, kiểm tra trắc nghiệm năng lực, hướng dẫn chuyển ngành nếu cần.',
        ],
        'excused_personal' => [
            'code' => 'excused_personal',
            'category' => '5. Gia đình & Biến cố cá nhân',
            'label' => 'Biến cố gia đình / Có việc riêng có phép',
            'short' => 'Biến cố gia đình',
            'description' => 'Gia đình có việc hiếu hỉ, ốm đau, phải chăm sóc người thân.',
            'color' => '#06b6d4',
            'solution' => 'Tạo điều kiện bảo lưu hợp lệ hoặc học bù trực tuyến linh hoạt.',
        ],
        'mental_health' => [
            'code' => 'mental_health',
            'category' => '6. Sức khỏe thể chất & Tinh thần',
            'label' => 'Sức khỏe yếu / Căng thẳng, kiệt sức tâm lý',
            'short' => 'Sức khỏe & Tâm lý',
            'description' => 'Bệnh kéo dài, khó thích nghi môi trường mới, stress thi cử.',
            'color' => '#6366f1',
            'solution' => 'Kết nối bộ phận y tế và phòng tham vấn tâm lý học đường, bảo mật thông tin.',
        ],
        'unexcused_habit' => [
            'code' => 'unexcused_habit',
            'category' => '2. Chuyên cần & Ý thức',
            'label' => 'Vắng tự phát không phép / Ý thức kỷ luật',
            'short' => 'Vắng tự phát không phép',
            'description' => 'Nghỉ học không xin phép, thói quen đi trễ, thiếu kỷ luật tự giác.',
            'color' => '#3b82f6',
            'solution' => 'Gặp trực tiếp trao đổi nội quy, lập cam kết chuyên cần, gắn điểm rèn luyện.',
        ],
    ],
];
