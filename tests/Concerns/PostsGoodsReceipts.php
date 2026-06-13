<?php

namespace Tests\Concerns;

use App\Models\GoodsReceipt;
use App\Models\User;

/**
 * Posts draft GRNs in feature tests (SRS draft → post workflow).
 */
trait PostsGoodsReceipts
{
    protected function postLatestGrn(User $user): GoodsReceipt
    {
        $grn = GoodsReceipt::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->postJson(route('admin.purchase.grns.post', $grn))->assertOk();

        return $grn->fresh();
    }
}
