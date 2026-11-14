<?php

namespace App\Actions;

use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteCategory
{
    public function handle(Category $category, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $category);

        if ($category->trades()->exists()) {
            throw new DomainRuleViolation('لا يمكن حذف تصنيف مرتبط بصفقة.');
        }

        $category->delete();
    }
}
