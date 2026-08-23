<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px 0; }
        .container { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #0ea5e9, #14b8a6); padding: 28px 32px; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 28px 32px; }
        .badge { display: inline-block; background: #e0f2fe; color: #0369a1; font-size: 12px; padding: 3px 12px; border-radius: 999px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13px; }
        th { background: #f8fafc; color: #475569; text-align: left; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .button { display: inline-block; margin-top: 8px; background: #0ea5e9; color: #ffffff !important; text-decoration: none; font-weight: bold; font-size: 14px; padding: 10px 22px; border-radius: 8px; }
        .note { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 8px; padding: 12px 14px; font-size: 12.5px; line-height: 1.5; }
        .footer { padding: 18px 32px; background: #f8fafc; color: #94a3b8; font-size: 11.5px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Laporan Kehadiran</h1>
            <p>Cahaya Rancamaya Islamic Boarding School</p>
            <span class="badge">Periode: {{ $report['period'] }}</span>
        </div>

        <div class="content">
            <p style="font-size:14px;color:#334155;">Assalamu'alaikum, <strong>{{ $recipientName }}</strong>,</p>
            <p style="font-size:13.5px;color:#475569;line-height:1.6;">
                Berikut kami lampirkan laporan kehadiran periode <strong>{{ $report['period'] }}</strong>
                dalam format {{ $format === 'excel' ? 'Excel (.xlsx)' : 'PDF' }}.
                Total {{ count($report['items']) }} karyawan tercatat dalam laporan ini.
            </p>

            <table>
                <thead>
                    <tr><th>NIK</th><th>Nama</th><th>Departemen</th><th>Jumlah Hari Tercatat</th></tr>
                </thead>
                <tbody>
                    @foreach (array_slice($report['items'], 0, 10) as $emp)
                        <tr>
                            <td>{{ $emp['nik'] }}</td>
                            <td>{{ $emp['name'] }}</td>
                            <td>{{ $emp['department'] }}</td>
                            <td>{{ count($emp['records']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if (count($report['items']) > 10)
                <p style="font-size:12px;color:#94a3b8;">…dan {{ count($report['items']) - 10 }} karyawan lainnya. Rincian lengkap tersedia pada lampiran.</p>
            @endif

            <div class="note" style="margin-top:16px;">
                Lampiran tidak dapat dibuka? Pastikan perangkat Anda mendukung file
                {{ $format === 'excel' ? '.xlsx' : '.pdf' }} atau hubungi administrator sekolah.
            </div>
        </div>

        <div class="footer">
            Email otomatis dari Sistem Kehadiran — jangan balas email ini.<br>
            &copy; {{ date('Y') }} Cahaya Rancamaya Islamic Boarding School
        </div>
    </div>
</body>
</html>
