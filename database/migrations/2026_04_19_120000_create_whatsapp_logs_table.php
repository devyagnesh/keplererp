<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks outbound WhatsApp template messages (ManufactureERP SRS §17.4).
     */
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index()->comment('e.g. invoice_sent, low_stock, po_approved');
            $table->string('reference_type', 100)->nullable()->index()->comment('Polymorphic model class');
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('recipient_name', 100)->nullable();
            $table->string('recipient_number', 20)->comment('E.164 digits without +');
            $table->string('template_name', 100)->index();
            $table->string('message_id', 120)->nullable()->comment('Meta wamid when accepted');
            $table->string('status', 20)->index()->comment('QUEUED, SENT, DELIVERED, READ, FAILED');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
