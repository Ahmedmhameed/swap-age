<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveProduct
{
    public function handle(Product $product, User $administrator): Product
    {
        return DB::transaction(function () use ($product, $administrator): Product {
            $lockedProduct = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($administrator)->authorize('approve', $lockedProduct);
            $lockedProduct->is_approved = true;
            $lockedProduct->save();

            return $lockedProduct;
        });
    }
}
