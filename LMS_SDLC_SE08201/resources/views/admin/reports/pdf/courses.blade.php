<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Báo cáo Khóa học</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #4c1d95; text-transform: uppercase; margin: 0; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; color: #475569; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Báo Cáo Quản Lý Khóa Học</h1>
        <p class="subtitle">Trường Đại học FPT - Hệ thống LMS | Ngày xuất: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">STT</th>
                <th width="15%">Mã lớp</th>
                <th width="20%">Tên lớp</th>
                <th width="20%">Môn học</th>
                <th width="20%">Giảng viên</th>
                <th class="text-center" width="10%">Sĩ số</th>
                <th class="text-center" width="10%">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @php 
            $statusMap = [
                \App\Models\ClassRoom::STATUS_PENDING => 'Chờ mở',
                \App\Models\ClassRoom::STATUS_ACTIVE => 'Đang học',
                \App\Models\ClassRoom::STATUS_CLOSED => 'Đã đóng',
            ]; 
            @endphp
            @forelse($data as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->class_id }}</td>
                <td>{{ $row->class_name }}</td>
                <td>{{ $row->subject?->subject_name }}</td>
                <td>{{ $row->teacher?->full_name }}</td>
                <td class="text-center">{{ $row->students_count ?? 0 }}</td>
                <td class="text-center">{{ $statusMap[$row->status] ?? $row->status }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Không có dữ liệu</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Báo cáo được xuất tự động từ hệ thống LMS.
    </div>
</body>
</html>
