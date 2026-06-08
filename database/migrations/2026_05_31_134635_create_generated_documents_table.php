<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->index()->comment('PdfDocumentType enum value');
            $table->morphs('documentable');
            $table->string('module', 30)->comment('Storage folder: sales, purchase, hr, etc.');
            $table->string('file_path')->comment('Relative path on configured PDF disk');
            $table->string('download_name')->comment('Suggested filename for download');
            $table->json('meta')->nullable()->comment('Report filters or generation context');
            $table->string('meta_hash', 64)->nullable()->index();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index()->comment('Signed URL expiry');
            $table->foreignId('superseded_by')->nullable()->constrained('generated_documents')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id', 'document_type', 'is_active'], 'generated_documents_lookup');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('po_approval_threshold');
            $table->string('bank_account_number', 40)->nullable()->after('bank_name');
            $table->string('bank_ifsc', 15)->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_ifsc']);
        });

        Schema::dropIfExists('generated_documents');
    }
};
