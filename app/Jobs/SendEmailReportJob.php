<?php

namespace App\Jobs;

use App\Mail\AttendanceReportMail;
use App\Models\EmailReport;
use App\Services\AttendanceReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public EmailReport $emailReport,
    ) {}

    public function handle(AttendanceReportService $reportService): void
    {
        try {
            $report = $reportService->buildForEmployee(
                (int) $this->emailReport->employee_id,
                $this->emailReport->start_date,
                $this->emailReport->end_date
            );

            $records = $report['items'][0]['records'] ?? [];
            if (empty($records)) {
                $this->emailReport->update([
                    'status' => 'failed',
                    'sent_at' => null,
                    'error_message' => 'Tidak ada data kehadiran pada periode ini.',
                ]);
                Log::warning('EmailReport tidak memiliki data kehadiran', ['id' => $this->emailReport->id]);
                return;
            }

            Mail::to($this->emailReport->recipient_email)->send(
                new AttendanceReportMail(
                    $report,
                    $this->emailReport->format,
                    $this->emailReport->user?->name ?? '',
                )
            );

            $this->emailReport->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            SendNotificationJob::dispatchSync(
                $this->emailReport->user_id,
                'Laporan Kehadiran Terkirim',
                "Laporan kehadiran periode {$this->emailReport->start_date} s/d {$this->emailReport->end_date} telah dikirim ke email Anda ({$this->emailReport->recipient_email}).",
                'success',
                ['action' => 'email_report_sent', 'email_report_id' => $this->emailReport->id],
            );
        } catch (\Throwable $e) {
            Log::error('SendEmailReportJob gagal: ' . $e->getMessage(), [
                'id' => $this->emailReport->id,
                'exception' => $e,
            ]);

            $this->emailReport->update([
                'status' => 'failed',
                'sent_at' => null,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}