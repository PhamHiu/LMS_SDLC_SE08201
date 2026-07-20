<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Báo cáo Chuyên cần</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #064e3b; text-transform: uppercase; margin: 0; }
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
        <h1 class="title">Báo Cáo Chuyên Cần Sinh Viên</h1>
        <p class="subtitle">Trường Đại học FPT - Hệ thống LMS | Ngày xuất: {{ $date }} | Giảng viên</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">STT</th>
                <th width="15%">Lớp</th>
                <th width="15%">Ngày học</th>
                <th width="25%">Sinh viên</th>
                <th class="text-center" width="15%">Trạng thái</th>
                <th width="25%">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            @php $statusMap = ['P' => 'Có mặt', 'A' => 'Vắng', 'L' => 'Muộn']; @endphp
            @forelse($data as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->session?->classRoom?->class_name }}</td>
                <td>{{ $row->attendance_time ? \Carbon\Carbon::parse($row->attendance_time)->format('d/m/Y') : '' }}</td>
                <td>{{ $row->student?->full_name }}</td>
                <td class="text-center">{{ $statusMap[$row->status] ?? $row->status }}</td>
                <td>{{ $row->remarks }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Không có dữ liệu</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Báo cáo được xuất tự động từ hệ thống LMS.
    </div>
</body>
</html>
