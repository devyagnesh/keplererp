<?php

namespace App\Mail;

use App\Models\GeneratedDocument;
use App\Models\PayrollDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Payslip email with PDF attachment (SRS §21.13).
 */
class PayslipProcessedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PayrollDetail $detail,
        public GeneratedDocument $document,
    ) {}

    public function envelope(): Envelope
    {
        $period = $this->detail->payrollRun !== null
            ? $this->detail->payrollRun->period_year.'-'.str_pad((string) $this->detail->payrollRun->period_month, 2, '0', STR_PAD_LEFT)
            : 'payroll';

        return new Envelope(
            subject: 'Payslip — '.$period,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payslip-processed',
            with: [
                'employeeName' => $this->detail->employee?->name ?? 'Employee',
                'netSalary' => (string) $this->detail->net_salary,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->document->file_path === '') {
            return [];
        }

        return [
            Attachment::fromStorageDisk((string) config('pdf.disk', 'local'), $this->document->file_path)
                ->as($this->document->download_name)
                ->withMime('application/pdf'),
        ];
    }
}
