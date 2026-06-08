<?php

namespace App\Services;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Vendor master business rules: codes, approval, blocking.
 */
class VendorService
{
    /**
     * Create a new vendor (starts in pending approval).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data, User $creator): Vendor
    {
        return DB::transaction(function () use ($data, $creator): Vendor {
            $data['vendor_code'] = $this->nextVendorCode();
            $data['status'] = VendorStatus::PendingApproval;
            $data['created_by'] = $creator->id;

            return Vendor::query()->create($data);
        });
    }

    /**
     * Update vendor master fields (not status transitions — use dedicated actions).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    /**
     * @return array{vendor: Vendor, portal_plain_password: string|null}
     */
    public function update(Vendor $vendor, array $data): array
    {
        return DB::transaction(function () use ($vendor, $data): array {
            unset($data['vendor_code'], $data['status'], $data['approved_at'], $data['approved_by'], $data['created_by']);
            unset($data['generate_portal_password']);
            $portalPlain = $this->applyPortalFields($vendor, $data);
            $vendor->update($data);

            return [
                'vendor' => $vendor->fresh(),
                'portal_plain_password' => $portalPlain,
            ];
        });
    }

    /**
     * Sync portal credentials; returns plaintext password when a new password was set.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyPortalFields(Vendor $vendor, array &$data): ?string
    {
        $plain = null;
        $enabled = filter_var($data['portal_enabled'] ?? $vendor->portal_enabled, FILTER_VALIDATE_BOOLEAN);

        if (! $enabled) {
            $data['portal_enabled'] = false;
            $data['portal_password'] = null;

            return null;
        }

        $data['portal_enabled'] = true;
        $generate = filter_var($data['generate_portal_password'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $provided = isset($data['portal_password']) && is_string($data['portal_password'])
            ? trim($data['portal_password'])
            : '';

        if ($provided !== '') {
            $plain = $provided;
        } elseif ($generate || $vendor->portal_password === null) {
            $plain = Str::password(12, symbols: false);
        }

        if ($plain !== null) {
            $data['portal_password'] = $plain;
        } else {
            unset($data['portal_password']);
        }

        return $plain;
    }

    /**
     * Approve a pending vendor (active for purchasing).
     *
     * @throws Throwable
     */
    public function approve(Vendor $vendor, User $approver): Vendor
    {
        return DB::transaction(function () use ($vendor, $approver): Vendor {
            $vendor->update([
                'status' => VendorStatus::Active,
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]);

            return $vendor->fresh();
        });
    }

    /**
     * Block an active vendor.
     *
     * @throws Throwable
     */
    public function block(Vendor $vendor): Vendor
    {
        return DB::transaction(function () use ($vendor): Vendor {
            $vendor->update([
                'status' => VendorStatus::Blocked,
            ]);

            return $vendor->fresh();
        });
    }

    /**
     * Reactivate a blocked vendor (requires re-approval if policy demands — here: direct active).
     *
     * @throws Throwable
     */
    public function activate(Vendor $vendor): Vendor
    {
        return DB::transaction(function () use ($vendor): Vendor {
            $vendor->update([
                'status' => VendorStatus::Active,
            ]);

            return $vendor->fresh();
        });
    }

    /**
     * Soft-delete vendor when allowed.
     *
     * @throws Throwable
     */
    public function delete(Vendor $vendor): void
    {
        DB::transaction(function () use ($vendor): void {
            $vendor->delete();
        });
    }

    /**
     * Generate next internal vendor code (V-00001 style).
     */
    protected function nextVendorCode(): string
    {
        $max = (int) Vendor::withTrashed()->max('id');

        return 'V-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
