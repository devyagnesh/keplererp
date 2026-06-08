<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PoApproval;
use App\Models\PurchaseOrder;
use App\Models\User;

/**
 * Multi-level PO approval audit trail (SRS §10 / po_approvals).
 */
class PoApprovalService
{
    public function record(
        PurchaseOrder $purchaseOrder,
        User $approver,
        string $action,
        int $level,
        ?string $comments = null
    ): PoApproval {
        $employee = Employee::query()->where('user_id', $approver->id)->first();
        $designation = $employee?->designation
            ?? $approver->roles->first()?->name
            ?? 'Approver';

        $purchaseOrder->update(['approval_level' => max($purchaseOrder->approval_level, $level)]);

        return PoApproval::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'approval_level' => $level,
            'action' => $action,
            'approved_by' => $approver->id,
            'approver_designation' => $designation,
            'comments' => $comments,
            'approved_at' => now(),
        ]);
    }
}
