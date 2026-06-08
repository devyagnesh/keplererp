<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Stores vendor compliance documents on the local disk.
 */
class VendorDocumentService
{
    /**
     * @var list<string>
     */
    protected array $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function store(Vendor $vendor, UploadedFile $file, string $documentType, User $uploader): VendorDocument
    {
        if (! in_array($file->getMimeType(), $this->allowedMimes, true)) {
            throw new InvalidArgumentException('Only PDF, JPEG, or PNG files are allowed.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('File size must not exceed 5 MB.');
        }

        return DB::transaction(function () use ($vendor, $file, $documentType, $uploader): VendorDocument {
            $path = $file->store('vendor-documents/'.$vendor->id, 'local');

            return VendorDocument::query()->create([
                'vendor_id' => $vendor->id,
                'document_type' => $documentType,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => $uploader->id,
            ]);
        });
    }

    public function delete(VendorDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            if (Storage::disk('local')->exists($document->storage_path)) {
                Storage::disk('local')->delete($document->storage_path);
            }
            $document->delete();
        });
    }
}
