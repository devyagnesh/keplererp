<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppTemplateJob;
use App\Models\Company;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\LeaveApplication;
use App\Models\License;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\PayrollRun;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WhatsAppLog;

/**
 * Queues SRS-aligned WhatsApp template notifications when company toggle and API config allow.
 */
class WhatsAppNotificationService
{
    /**
     * Whether WhatsApp outbound is allowed for this deployment.
     */
    public function isEnabledForCompany(): bool
    {
        $company = Company::query()->orderBy('id')->first();
        if ($company === null || ! $company->whatsapp_enabled) {
            return false;
        }

        $driver = (string) config('whatsapp.driver', 'log');
        if ($driver === 'log') {
            return true;
        }

        $phoneId = config('whatsapp.cloud.phone_number_id');
        $token = config('whatsapp.cloud.access_token');

        return is_string($phoneId) && $phoneId !== '' && is_string($token) && $token !== '';
    }

    /**
     * Normalise a phone / mobile string to E.164 digits (no +) for India by default.
     */
    public function normalizeToE164(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        $cc = (string) config('whatsapp.default_country_calling_code', '91');
        if (strlen($digits) === 10) {
            return $cc.$digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return $cc.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * PO approved (or finance-approved): notify vendor (SRS WA-01, communication map).
     */
    public function notifyPurchaseOrderApproved(PurchaseOrder $purchaseOrder): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $purchaseOrder->loadMissing('vendor');
        $vendor = $purchaseOrder->vendor;
        if (! $vendor instanceof Vendor) {
            return;
        }
        $to = $this->normalizeToE164($vendor->phone);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.po_approved', 'po_approved');
        $delivery = $purchaseOrder->expected_delivery?->format('Y-m-d') ?? '-';
        $this->dispatchTemplate(
            $to,
            'po_approved',
            $template,
            [
                $purchaseOrder->po_number,
                (string) $purchaseOrder->total_amount,
                $delivery,
            ],
            PurchaseOrder::class,
            $purchaseOrder->id,
            $vendor->name
        );
    }

    /**
     * PO marked sent: notify vendor that the formal PO was dispatched (SRS procurement; template `po_dispatch`).
     */
    public function notifyPurchaseOrderDispatchedToVendor(PurchaseOrder $purchaseOrder): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $purchaseOrder->loadMissing('vendor');
        $vendor = $purchaseOrder->vendor;
        if (! $vendor instanceof Vendor) {
            return;
        }
        $to = $this->normalizeToE164($vendor->phone);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.po_dispatch', 'po_dispatch');
        $delivery = $purchaseOrder->expected_delivery?->format('Y-m-d') ?? '-';
        $this->dispatchTemplate(
            $to,
            'po_dispatch',
            $template,
            [
                $purchaseOrder->po_number,
                $delivery,
            ],
            PurchaseOrder::class,
            $purchaseOrder->id,
            $vendor->name
        );
    }

    /**
     * Notify the PO creator about workflow updates (pending finance, final approval, rejection).
     *
     * @param  'pending_finance'|'final_approved'|'rejected'  $stage
     */
    public function notifyPurchaseOrderCreator(PurchaseOrder $purchaseOrder, string $stage, ?string $rejectedReason = null): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $purchaseOrder->loadMissing('creator');
        $user = $purchaseOrder->creator;
        if (! $user instanceof User) {
            return;
        }
        $to = $this->normalizeToE164($user->whatsapp_number);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.po_staff_update', 'po_staff_update');
        $headline = match ($stage) {
            'pending_finance' => 'Pending finance approval',
            'final_approved' => 'Fully approved',
            'rejected' => 'Rejected',
            default => 'Purchase order update',
        };
        $detail = match ($stage) {
            'pending_finance' => 'Total '.(string) $purchaseOrder->total_amount.' INR — awaiting finance sign-off.',
            'final_approved' => 'Total '.(string) $purchaseOrder->total_amount.' INR.',
            'rejected' => $this->truncateBodyText((string) ($rejectedReason ?? $purchaseOrder->rejected_reason ?? '')),
            default => '',
        };
        $this->dispatchTemplate(
            $to,
            'po_staff_update',
            $template,
            [
                $purchaseOrder->po_number,
                $headline,
                $detail,
            ],
            PurchaseOrder::class,
            $purchaseOrder->id,
            $user->name
        );
    }

    /**
     * PR approved: notify requester (SRS procurement step 1 completion).
     */
    public function notifyPurchaseRequisitionApproved(PurchaseRequisition $purchaseRequisition): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $purchaseRequisition->loadMissing('requester');
        $user = $purchaseRequisition->requester;
        if (! $user instanceof User) {
            return;
        }
        $to = $this->normalizeToE164($user->whatsapp_number);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.pr_approved', 'pr_approved');
        $this->dispatchTemplate(
            $to,
            'pr_approved',
            $template,
            [
                $purchaseRequisition->pr_number,
                'Your requisition is approved. You may proceed to raise a PO.',
            ],
            PurchaseRequisition::class,
            $purchaseRequisition->id,
            $user->name
        );
    }

    /**
     * GRN posted: notify Purchase Managers and Warehouse Managers with WhatsApp numbers (SRS WA-04).
     */
    public function notifyGrnPosted(GoodsReceipt $goodsReceipt): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $goodsReceipt->loadMissing(['vendor', 'lines.item']);
        $vendor = $goodsReceipt->vendor;
        $vendorName = $vendor instanceof Vendor ? $vendor->name : '-';
        $lines = $goodsReceipt->lines;
        $summary = $lines->isEmpty()
            ? '-'
            : $lines->take(3)->map(fn ($l) => ($l->item?->sku ?? '?').'×'.$l->accepted_qty)->implode(', ');
        if ($lines->count() > 3) {
            $summary .= '…';
        }

        $template = (string) config('whatsapp.templates.grn_posted', 'grn_posted');
        $params = [
            $goodsReceipt->grn_number,
            $vendorName,
            $summary,
        ];

        $roleNames = ['Purchase Manager', 'Warehouse Manager'];
        foreach ($roleNames as $roleName) {
            $users = User::query()
                ->role($roleName)
                ->whereNotNull('whatsapp_number')
                ->where('whatsapp_number', '!=', '')
                ->get(['id', 'name', 'whatsapp_number']);
            foreach ($users as $user) {
                $to = $this->normalizeToE164($user->whatsapp_number);
                if ($to === null) {
                    continue;
                }
                $this->dispatchTemplate(
                    $to,
                    'grn_posted',
                    $template,
                    $params,
                    GoodsReceipt::class,
                    $goodsReceipt->id,
                    $user->name
                );
            }
        }
    }

    /**
     * Posted invoice: notify customer (SRS WA-05; PDF via template / separate template in Meta).
     */
    public function notifyInvoicePosted(Invoice $invoice, Customer $customer): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $to = $this->normalizeToE164($customer->phone);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.invoice_sent', 'invoice_sent');
        $due = $invoice->due_date;
        $dueStr = $due !== null ? $due->format('Y-m-d') : '-';
        $this->dispatchTemplate(
            $to,
            'invoice_sent',
            $template,
            [
                $invoice->invoice_number,
                (string) $invoice->total_amount,
                $dueStr,
            ],
            Invoice::class,
            $invoice->id,
            $customer->name
        );
    }

    /**
     * PR rejected: notify requester (SRS procurement flow §4.1 step 2).
     */
    public function notifyPurchaseRequisitionRejected(PurchaseRequisition $purchaseRequisition): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $purchaseRequisition->loadMissing('requester');
        $user = $purchaseRequisition->requester;
        if (! $user instanceof User) {
            return;
        }
        $to = $this->normalizeToE164($user->whatsapp_number);
        if ($to === null) {
            return;
        }
        $reason = (string) $purchaseRequisition->rejected_reason;
        if (strlen($reason) > 480) {
            $reason = substr($reason, 0, 477).'...';
        }
        $template = (string) config('whatsapp.templates.pr_rejected', 'pr_rejected');
        $this->dispatchTemplate(
            $to,
            'pr_rejected',
            $template,
            [
                $purchaseRequisition->pr_number,
                $reason,
            ],
            PurchaseRequisition::class,
            $purchaseRequisition->id,
            $user->name
        );
    }

    /**
     * Low stock for one SKU: Purchase Managers with WhatsApp (SRS WA-03).
     * One alert per recipient + item + calendar day (avoids duplicate queue noise).
     */
    public function notifyLowStockItem(Item $item, string $currentQty): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $template = (string) config('whatsapp.templates.low_stock', 'low_stock');
        $users = User::query()
            ->whereHas('roles', fn ($r) => $r->whereIn('name', ['Purchase Manager', 'Warehouse Manager']))
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->get(['id', 'name', 'whatsapp_number']);
        foreach ($users as $user) {
            $to = $this->normalizeToE164($user->whatsapp_number);
            if ($to === null) {
                continue;
            }
            if ($this->hasLowStockAlertTodayForRecipient($item->id, $to)) {
                continue;
            }
            $this->dispatchTemplate(
                $to,
                'low_stock',
                $template,
                [
                    $item->name,
                    $currentQty,
                    (string) $item->reorder_level,
                ],
                Item::class,
                $item->id,
                $user->name
            );
        }
    }

    /**
     * Payment received from customer (SRS WA-07).
     */
    public function notifyCustomerPaymentReceived(Payment $payment, Invoice $invoice, Customer $customer): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $to = $this->normalizeToE164($customer->phone);
        if ($to === null) {
            return;
        }
        $balance = bcsub((string) $invoice->total_amount, (string) $invoice->amount_paid, 2);
        $template = (string) config('whatsapp.templates.payment_receipt', 'payment_receipt');
        $this->dispatchTemplate(
            $to,
            'payment_receipt',
            $template,
            [
                $payment->payment_number,
                (string) $payment->amount,
                $balance,
            ],
            Payment::class,
            $payment->id,
            $customer->name
        );
    }

    /**
     * Payment sent to vendor (SRS WA-08).
     */
    public function notifyVendorPaymentSent(Payment $payment, Vendor $vendor): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $to = $this->normalizeToE164($vendor->phone);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.payment_sent', 'payment_sent');
        $this->dispatchTemplate(
            $to,
            'payment_sent',
            $template,
            [
                $payment->payment_number,
                (string) $payment->amount,
                (string) ($payment->utr_reference ?? '—'),
            ],
            Payment::class,
            $payment->id,
            $vendor->name
        );
    }

    /**
     * Overdue invoice reminder (SRS WA-06).
     */
    public function notifyPaymentOverdue(Invoice $invoice, Customer $customer, int $daysOverdue): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $to = $this->normalizeToE164($customer->phone);
        if ($to === null) {
            return;
        }
        $outstanding = bcsub((string) $invoice->total_amount, (string) $invoice->amount_paid, 2);
        $template = (string) config('whatsapp.templates.payment_overdue', 'payment_overdue');
        $this->dispatchTemplate(
            $to,
            'payment_overdue',
            $template,
            [
                $invoice->invoice_number,
                (string) $daysOverdue,
                $outstanding,
            ],
            Invoice::class,
            $invoice->id,
            $customer->name
        );
    }

    /**
     * Leave approved notification to employee (SRS WA-11).
     */
    public function notifyLeaveApproved(LeaveApplication $leave, string $approvedByName): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $leave->loadMissing('employee');
        $employee = $leave->employee;
        if ($employee === null) {
            return;
        }
        $to = $this->normalizeToE164($employee->whatsapp);
        if ($to === null) {
            return;
        }
        $template = (string) config('whatsapp.templates.leave_approved', 'leave_approved');
        $this->dispatchTemplate(
            $to,
            'leave_approved',
            $template,
            [
                $leave->start_date?->format('d M Y') ?? '',
                $leave->end_date?->format('d M Y') ?? '',
                $leave->leave_type,
                $approvedByName,
            ],
            LeaveApplication::class,
            $leave->id,
            $employee->name
        );
    }

    /**
     * Production work order released / in progress (SRS WA-09).
     */
    public function notifyProductionStarted(ProductionOrder $order): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $order->loadMissing('item');
        $this->notifyProductionStaff(
            'prod_started',
            (string) config('whatsapp.templates.prod_started', 'prod_started'),
            [
                $order->wo_number,
                $order->item?->sku ?? '—',
                (string) $order->qty_planned,
            ],
            ProductionOrder::class,
            $order->id
        );
    }

    /**
     * Production work order completed (SRS WA-09).
     */
    public function notifyProductionCompleted(ProductionOrder $order): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $order->loadMissing('item');
        $qty = (string) ($order->actual_qty ?? $order->qty_planned);
        $this->notifyProductionStaff(
            'prod_complete',
            (string) config('whatsapp.templates.prod_complete', 'prod_complete'),
            [
                $order->wo_number,
                $order->item?->sku ?? '—',
                $qty,
            ],
            ProductionOrder::class,
            $order->id
        );
    }

    /**
     * Salary credited after payroll run (SRS WA-12).
     */
    public function notifySalaryCredited(Employee $employee, PayrollRun $run, string $netSalary, ?string $payslipUrl = null): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }
        $to = $this->normalizeToE164($employee->whatsapp ?? $employee->phone);
        if ($to === null) {
            return;
        }
        $period = $run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT);
        $template = (string) config('whatsapp.templates.salary_credited', 'salary_credited');
        $params = [
            $employee->name,
            $period,
            $netSalary,
        ];
        if ($payslipUrl !== null) {
            $params[] = $payslipUrl;
        }
        $this->dispatchTemplate(
            $to,
            'salary_credited',
            $template,
            $params,
            PayrollRun::class,
            $run->id,
            $employee->name
        );
    }

    /**
     * @param  list<string>  $bodyParameters
     */
    protected function notifyProductionStaff(
        string $eventType,
        string $templateName,
        array $bodyParameters,
        string $referenceType,
        int $referenceId
    ): void {
        $permissionNames = ['production.log', 'production.order.create'];
        $users = User::query()
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->where(function ($query) use ($permissionNames): void {
                $query->whereHas('permissions', fn ($p) => $p->whereIn('name', $permissionNames))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->whereIn('name', $permissionNames));
            })
            ->get(['id', 'name', 'whatsapp_number']);

        foreach ($users as $user) {
            $to = $this->normalizeToE164($user->whatsapp_number);
            if ($to === null) {
                continue;
            }
            $this->dispatchTemplate(
                $to,
                $eventType,
                $templateName,
                $bodyParameters,
                $referenceType,
                $referenceId,
                $user->name
            );
        }
    }

    /**
     * AMC / license expiry warning to Super Admin and Admin users (SRS WA-14).
     */
    public function notifyLicenseExpiryReminder(int $daysRemaining, string $renewalUrl): void
    {
        if (! $this->isEnabledForCompany()) {
            return;
        }

        $template = (string) config('whatsapp.templates.license_expiry', 'license_expiry');
        $users = User::query()
            ->whereHas('roles', fn ($r) => $r->whereIn('name', ['Super Admin', 'Admin']))
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->get(['id', 'name', 'whatsapp_number']);

        foreach ($users as $user) {
            $to = $this->normalizeToE164($user->whatsapp_number);
            if ($to === null) {
                continue;
            }
            if ($this->hasLicenseExpiryAlertToday($to)) {
                continue;
            }
            $daysLabel = $daysRemaining < 0
                ? (string) abs($daysRemaining).' days ago'
                : (string) $daysRemaining;
            $this->dispatchTemplate(
                $to,
                'license_expiry',
                $template,
                [
                    $daysLabel,
                    $renewalUrl,
                ],
                License::class,
                null,
                $user->name
            );
        }
    }

    /**
     * True if a license_expiry log already exists for this recipient on the local date.
     */
    public function hasLicenseExpiryAlertToday(string $recipientE164): bool
    {
        return WhatsAppLog::query()
            ->where('event_type', 'license_expiry')
            ->where('recipient_number', $recipientE164)
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    /**
     * @return list<int> Item IDs currently below reorder company-wide (aggregated stock).
     */
    public function findLowStockItemIds(): array
    {
        $ids = [];
        $items = Item::query()
            ->where('is_active', true)
            ->whereRaw('CAST(reorder_level AS DECIMAL(14,4)) > 0')
            ->withSum('inventoryBalances', 'quantity')
            ->get(['id', 'reorder_level']);
        foreach ($items as $item) {
            $qty = (string) ($item->inventory_balances_sum_quantity ?? '0');
            if (bccomp($qty, (string) $item->reorder_level, 4) < 0) {
                $ids[] = (int) $item->id;
            }
        }

        return $ids;
    }

    /**
     * True if a low_stock log already exists for this item, recipient, and local date.
     */
    public function hasLowStockAlertTodayForRecipient(int $itemId, string $recipientE164): bool
    {
        return WhatsAppLog::query()
            ->where('event_type', 'low_stock')
            ->where('reference_type', Item::class)
            ->where('reference_id', $itemId)
            ->where('recipient_number', $recipientE164)
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    private function truncateBodyText(string $text, int $maxLen = 480): string
    {
        if (strlen($text) <= $maxLen) {
            return $text;
        }

        return substr($text, 0, $maxLen - 3).'...';
    }

    /**
     * @param  list<string>  $bodyParameters
     */
    private function dispatchTemplate(
        string $toE164,
        string $eventType,
        string $templateName,
        array $bodyParameters,
        ?string $referenceType,
        ?int $referenceId,
        ?string $recipientDisplayName
    ): void {
        SendWhatsAppTemplateJob::dispatch(
            $toE164,
            $eventType,
            $templateName,
            $bodyParameters,
            $referenceType,
            $referenceId,
            $recipientDisplayName
        );
    }
}
