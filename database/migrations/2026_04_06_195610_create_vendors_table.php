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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 30)->unique()->comment('Internal supplier code');
            $table->string('name', 255)->comment('Supplier / legal display name');
            $table->string('contact_person', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 15);
            $table->char('gstin', 15)->nullable()->comment('GSTIN when registered');
            $table->char('pan', 10)->nullable();
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100);
            $table->char('state_code', 2)->comment('GST state code');
            $table->char('pincode', 6);
            $table->string('payment_terms', 100)->nullable()->comment('e.g. Net 30');
            $table->text('notes')->nullable();
            $table->string('status', 32)->index()->comment('pending_approval, active, blocked');
            $table->timestamp('approved_at')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('vendor_code');
            $table->index('gstin');
            $table->index('state_code');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
