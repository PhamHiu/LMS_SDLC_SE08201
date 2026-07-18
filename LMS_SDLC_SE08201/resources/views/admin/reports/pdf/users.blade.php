<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Báo cáo Người dùng</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #f43f5e; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #881337; text-transform: uppercase; margin: 0; }
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
        <h1 class="title">Danh Sách Người Dùng Hệ Thống</h1>
        <p class="subtitle">Trường Đại học FPT - Hệ thống LMS | Ngày xuất: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">STT</th>
                <th width="15%">Mã ND</th>
                <th width="20%">Họ tên</th>
                <th width="25%">Email</th>
                <th class="text-center" width="15%">Vai trò</th>
                <th width="20%">Chuyên ngành</th>
            </tr>
        </thead>
        <tbody>
            @php 
            $roleMap = [
                \App\Models\User::ROLE_ADMIN => 'Quản trị viên',
                \App\Models\User::ROLE_TEACHER => 'Giảng viên',
                \App\Models\User::ROLE_STUDENT => 'Sinh viên',
            ]; 
            @endphp
            @forelse($data as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->user_id }}</td>
                <td>{{ $row->full_name }}</td>
                <td>{{ $row->email }}</td>
                <td class="text-center">{{ $roleMap[$row->role] ?? $row->role }}</td>
                <td>{{ $row->major?->major_name }}</td>
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
