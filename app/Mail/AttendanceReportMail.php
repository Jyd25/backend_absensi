<?php

namespace App\Mail;

use App\Exports\AttendanceReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $report,
        public string $format = 'pdf',
        public string $recipientName = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Laporan Kehadiran ({$this->report['period']}) — Cahaya Rancamaya Islamic Boarding School",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-report',
            with: [
                'report' => $this->report,
                'recipientName' => $this->recipientName,
                'format' => $this->format,
            ],
        );
    }

    public function attachments(): array
    {
        $filename = 'laporan-kehadiran-' . str_replace(' ', '', $this->report['period']);

        if ($this->format === 'excel') {
            $binary = Excel::raw(
                new AttendanceReportExport($this->report),
                \Maatwebsite\Excel\Excel::XLSX
            );

            return [
                Attachment::fromData(fn () => $binary, "{$filename}.xlsx")
                    ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ];
        }

        $pdf = Pdf::loadView('reports.attendance-pdf', ['report' => $this->report])
            ->setPaper('a4', 'landscape');

        return [
            Attachment::fromData(fn () => $pdf->output(), "{$filename}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
