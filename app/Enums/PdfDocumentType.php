<?php

namespace App\Enums;

/**
 * PDF document types per SRS addendum chapter 21.
 */
enum PdfDocumentType: string
{
    case TaxInvoice = 'tax_invoice';
    case PurchaseOrder = 'purchase_order';
    case Grn = 'grn';
    case Quotation = 'quotation';
    case DeliveryChallan = 'delivery_challan';
    case Payslip = 'payslip';
    case PayrollSummary = 'payroll_summary';
    case StockLedger = 'stock_ledger';
    case Gstr1 = 'gstr1';
    case Gstr3b = 'gstr3b';
    case VendorStatement = 'vendor_statement';
    case ProductionOrder = 'production_order';

    /**
     * Storage module folder under documents/.
     */
    public function module(): string
    {
        return match ($this) {
            self::TaxInvoice, self::Quotation, self::DeliveryChallan => 'sales',
            self::PurchaseOrder, self::Grn => 'purchase',
            self::Payslip, self::PayrollSummary => 'hr',
            self::StockLedger => 'inventory',
            self::Gstr1, self::Gstr3b, self::VendorStatement => 'finance',
            self::ProductionOrder => 'production',
        };
    }

    /**
     * Blade view name under resources/views/pdfs/.
     */
    public function viewName(): string
    {
        return match ($this) {
            self::TaxInvoice => 'pdfs.sales.tax-invoice',
            self::PurchaseOrder => 'pdfs.purchase.purchase-order',
            self::Grn => 'pdfs.purchase.grn',
            self::Quotation => 'pdfs.sales.quotation',
            self::DeliveryChallan => 'pdfs.sales.delivery-challan',
            self::Payslip => 'pdfs.hr.payslip',
            self::PayrollSummary => 'pdfs.hr.payroll-summary',
            self::StockLedger => 'pdfs.inventory.stock-ledger',
            self::Gstr1 => 'pdfs.finance.gstr1',
            self::Gstr3b => 'pdfs.finance.gstr3b',
            self::VendorStatement => 'pdfs.finance.vendor-statement',
            self::ProductionOrder => 'pdfs.production.production-order',
        };
    }

    /**
     * Paper orientation per SRS §21.1.
     */
    public function orientation(): string
    {
        return match ($this) {
            self::StockLedger, self::Gstr1, self::Gstr3b, self::PayrollSummary, self::VendorStatement => 'landscape',
            default => 'portrait',
        };
    }

    /**
     * Human-readable document title for PDF header.
     */
    public function title(): string
    {
        return match ($this) {
            self::TaxInvoice => 'TAX INVOICE',
            self::PurchaseOrder => 'PURCHASE ORDER',
            self::Grn => 'GOODS RECEIPT NOTE',
            self::Quotation => 'QUOTATION',
            self::DeliveryChallan => 'DELIVERY CHALLAN',
            self::Payslip => 'SALARY SLIP',
            self::PayrollSummary => 'PAYROLL SUMMARY',
            self::StockLedger => 'STOCK LEDGER REPORT',
            self::Gstr1 => 'GSTR-1 REPORT',
            self::Gstr3b => 'GSTR-3B SUMMARY',
            self::VendorStatement => 'VENDOR STATEMENT',
            self::ProductionOrder => 'PRODUCTION ORDER',
        };
    }
}
