<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { text-align: center; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 18px; letter-spacing: 1px; color: #0f172a; }
        .header h2 { font-size: 11px; font-weight: normal; color: #475569; margin-top: 3px; }
        .header .period { display: inline-block; margin-top: 6px; background: #e0f2fe; color: #0369a1; padding: 2px 12px; border-radius: 8px; font-size: 10px; }
        .employee-block { margin-bottom: 16px; page-break-inside: avoid; }
        .employee-head { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 4px; padding: 6px 10px; margin-bottom: 6px; }
        .employee-head .name { font-size: 11px; font-weight: bold; color: #0c4a6e; }
        .employee-head .meta { font-size: 9px; color: #64748b; margin-top: 1px; }
        .summary { font-size: 9px; margin-bottom: 5px; color: #334155; }
        .summary span { margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0ea5e9; color: #ffffff; font-size: 8px; padding: 4px 3px; text-align: center; }
        td { border: 1px solid #d1d5db; padding: 3px; font-size: 8px; vertical-align: top; }
        td.center { text-align: center; }
        .footer { position: fixed; bottom: -18px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEHADIRAN</h1>
        <h2>Cahaya Rancamaya Islamic Boarding School</h2>
        <span class="period">Periode: {{ $report['period'] }}</span>
    </div>

    @forelse($report['items'] as $emp)
        <div class="employee-block">
            <div class="employee-head">
                <div class="name">{{ $emp['nik'] }} — {{ $emp['name'] }}</div>
                <div class="meta">Jabatan: {{ $emp['position'] }} &nbsp;|&nbsp; Departemen: {{ $emp['department'] }}</div>
            </div>
            @php
                $hadir = collect($emp['records'])->where('status', 'Hadir')->count();
                $terlambat = collect($emp['records'])->where('status', 'Terlambat')->count();
                $alpha = collect($emp['records'])->where('status', 'Alpha')->count();
                $libur = collect($emp['records'])->where('status', 'Libur')->count();
                $pulangCepat = collect($emp['records'])->where('status_checkout', 'Pulang Cepat')->count();
            @endphp
            <div class="summary">
                <span><strong>Hadir:</strong> {{ $hadir }}</span>
                <span><strong>Terlambat:</strong> {{ $terlambat }}</span>
                <span><strong>Alpha:</strong> {{ $alpha }}</span>
                <span><strong>Libur:</strong> {{ $libur }}</span>
                <span><strong>Pulang Cepat:</strong> {{ $pulangCepat }}</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:4%">No</th>
                        <th style="width:9%">Tanggal</th>
                        <th style="width:7%">Masuk</th>
                        <th style="width:7%">Pulang</th>
                        <th style="width:8%">Status</th>
                        <th style="width:9%">Status Pulang</th>
                        <th style="width:19%">Alamat Masuk</th>
                        <th style="width:19%">Alamat Pulang</th>
                        <th style="width:6%">Face</th>
                        <th style="width:12%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($emp['records'] as $i => $r)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="center">{{ $r['date'] }}</td>
                            <td class="center">{{ $r['check_in'] }}</td>
                            <td class="center">{{ $r['check_out'] }}</td>
                            <td class="center">{{ $r['status'] }}</td>
                            <td class="center">{{ $r['status_checkout'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($r['checkin_address'], 60, '…') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($r['checkout_address'], 60, '…') }}</td>
                            <td class="center">{{ $r['face'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($r['remarks'], 40, '…') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center;color:#94a3b8;padding:24px 0;">Tidak ada data kehadiran untuk periode ini.</p>
    @endforelse

    <div class="footer">Dicetak otomatis oleh Sistem Kehadiran — {{ now()->format('d/m/Y H:i') }} WIB</div>
</body>
</html>
