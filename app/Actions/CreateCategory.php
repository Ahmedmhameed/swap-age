<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateCategory
{
    /** @param array{name: string, slug: string} $data */
    public function handle(User $actor, array $data): Category
    {
        Gate::forUser($actor)->authorize('create', Category::class);

        return Category::query()->create($data);
    }
}
