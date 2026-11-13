<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RequestProduct
{
    /** @param array<string, mixed> $data */
    public function handle(User $requester, array $data): Product
    {
        return DB::transaction(function () use ($requester, $data): Product {
            Gate::forUser($requester)->authorize('create', Product::class);

            return Product::create([
                ...$data,
                'created_by' => $requester->id,
                'is_approved' => false,
            ]);
        });
    }
}
