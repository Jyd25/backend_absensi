<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(protected array $report)
    {
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['LAPORAN KEHADIRAN'];
        $rows[] = ['Cahaya Rancamaya Islamic Boarding School'];
        $rows[] = ['Periode: ' . $this->report['period']];
        $rows[] = [];

        $rows[] = ['No', 'NIK', 'Nama', 'Departemen', 'Jabatan', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Status Pulang', 'Alamat Masuk', 'Alamat Pulang', 'Face', 'Keterangan'];

        $rowNum = 1;
        foreach ($this->report['items'] as $emp) {
            foreach ($emp['records'] as $r) {
                $rows[] = [
                    $rowNum++,
                    $emp['nik'],
                    $emp['name'],
                    $emp['department'],
                    $emp['position'],
                    $r['date'],
                    $r['check_in'],
                    $r['check_out'],
                    $r['status'],
                    $r['status_checkout'],
                    $r['checkin_address'],
                    $r['checkout_address'],
                    $r['face'],
                    $r['remarks'],
                ];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Laporan Kehadiran';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('A2')->applyFromArray(['font' => ['size' => 11]]);
        $sheet->getStyle('A3')->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '555555']]]);

        $sheet->getStyle('A5:N5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0EA5E9'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A5:N{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        return [];
    }
}
