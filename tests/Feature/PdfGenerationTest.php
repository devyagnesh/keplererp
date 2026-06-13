<?php

namespace Tests\Feature;

use App\Enums\PdfDocumentType;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\Invoice;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Pdf\PdfGeneratorService;
use App\Support\NumberToWords;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\ProcessesPayrollRuns;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use ProcessesPayrollRuns;
    use RefreshDatabase;

    public function test_invoice_post_generates_tax_invoice_pdf(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Company::factory()->create(['state_code' => '24']);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-PDF-Q',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
            'payment_terms_days' => 30,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))->assertOk();

        $invoice = Invoice::query()->where('sales_order_id', $order->id)->first();
        $this->assertNotNull($invoice);

        $document = GeneratedDocument::query()
            ->where('document_type', PdfDocumentType::TaxInvoice)
            ->where('documentable_type', Invoice::class)
            ->where('documentable_id', $invoice->id)
            ->first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_pdf_generator_stores_document_and_signed_url_works(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create([
            'state_code' => '24',
            'bank_name' => 'HDFC Bank',
            'bank_account_number' => '1234567890',
            'bank_ifsc' => 'HDFC0001234',
        ]);
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['state_code' => '24', 'gstin' => '24AAAAA0000A1Z5']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-PDF-STORE',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '500.00',
            'taxable_amount' => '500.00',
            'cgst_amount' => '45.00',
            'sgst_amount' => '45.00',
            'igst_amount' => '0.00',
            'total_amount' => '590.00',
            'payment_terms_days' => 30,
            'created_by' => $user->id,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-PDF-1',
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '500.00',
            'discount_amount' => '0.00',
            'taxable_amount' => '500.00',
            'cgst_amount' => '45.00',
            'sgst_amount' => '45.00',
            'igst_amount' => '0.00',
            'total_amount' => '590.00',
            'status' => 'posted',
            'created_by' => $user->id,
        ]);

        $pdf = app(PdfGeneratorService::class);
        $document = $pdf->generate(PdfDocumentType::TaxInvoice, $invoice, $user->id);

        $this->assertInstanceOf(GeneratedDocument::class, $document);
        $this->assertTrue($document->is_active);
        Storage::disk('local')->assertExists($document->file_path);

        $url = $pdf->signedDownloadUrl($document);
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_payroll_approve_generates_payslip_pdfs(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create(['whatsapp_enabled' => false]);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Employee::query()->create([
            'emp_code' => 'E001',
            'name' => 'Test Employee',
            'department' => 'Production',
            'designation' => 'Operator',
            'join_date' => now()->subYear()->toDateString(),
            'is_active' => true,
            'basic_salary' => '12000.00',
            'hra' => '3000.00',
            'pf_number' => 'PF123',
        ]);
        $run = PayrollRun::query()->create([
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->lockAndProcessPayroll($run, $user);
        $run->refresh();
        $this->assertSame('processed', $run->status);

        $this->approvePayroll($run, $user);
        $run->refresh();
        $this->assertSame('approved', $run->status);

        $detail = PayrollDetail::query()->where('payroll_run_id', $run->id)->first();
        $this->assertNotNull($detail);

        $payslipDoc = GeneratedDocument::query()
            ->where('document_type', PdfDocumentType::Payslip)
            ->where('documentable_type', PayrollDetail::class)
            ->where('documentable_id', $detail->id)
            ->first();
        $this->assertNotNull($payslipDoc);
        Storage::disk('local')->assertExists($payslipDoc->file_path);

        $summaryDoc = GeneratedDocument::query()
            ->where('document_type', PdfDocumentType::PayrollSummary)
            ->where('documentable_type', PayrollRun::class)
            ->where('documentable_id', $run->id)
            ->first();
        $this->assertNotNull($summaryDoc);
    }

    public function test_number_to_words_helper(): void
    {
        $this->assertSame(
            'Rupees Five Hundred Ninety Only',
            NumberToWords::rupees('590.00')
        );
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        Storage::fake('local');
        $document = GeneratedDocument::query()->create([
            'document_type' => PdfDocumentType::TaxInvoice,
            'documentable_type' => Invoice::class,
            'documentable_id' => 1,
            'module' => 'sales',
            'file_path' => 'documents/sales/2026-05/test.pdf',
            'download_name' => 'test.pdf',
            'expires_at' => now()->subHour(),
            'is_active' => true,
        ]);
        Storage::disk('local')->put($document->file_path, '%PDF-1.4 test');

        $url = URL::temporarySignedRoute(
            'documents.download',
            now()->subMinutes(5),
            ['generatedDocument' => $document->id]
        );

        $this->get($url)->assertForbidden();
    }
}
