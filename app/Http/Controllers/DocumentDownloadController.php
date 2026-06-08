<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Signed PDF download endpoint (SRS §21.13).
 */
class DocumentDownloadController extends Controller
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Stream a stored PDF via temporary signed URL.
     */
    public function __invoke(Request $request, GeneratedDocument $generatedDocument): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Download link has expired or is invalid.');
        }

        if (! $generatedDocument->is_active || $generatedDocument->isExpired()) {
            abort(410, 'This document link is no longer valid.');
        }

        return $this->pdfGenerator->streamStored($generatedDocument);
    }
}
