<?php

namespace App\Jobs;

use App\Enums\PdfDocumentType;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued PDF rendering job (SRS §21.1 — pdf-generation queue).
 */
class GenerateDocumentPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public PdfDocumentType $type,
        public string $documentableType,
        public int $documentableId,
        public ?int $userId = null,
        public array $meta = [],
    ) {
        $this->onQueue((string) config('pdf.queue', 'pdf-generation'));
    }

    /**
     * Execute the job.
     */
    public function handle(PdfGeneratorService $pdfGenerator): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->documentableType;
        $model = $modelClass::query()->find($this->documentableId);

        if ($model === null) {
            return;
        }

        $pdfGenerator->generate($this->type, $model, $this->userId, $this->meta);
    }
}
