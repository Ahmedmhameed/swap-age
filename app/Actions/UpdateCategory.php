<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateCategory
{
    /** @param array{name: string, slug: string} $data */
    public function handle(Category $category, User $actor, array $data): Category
    {
        Gate::forUser($actor)->authorize('update', $category);
        $category->update($data);

        return $category;
    }
}
