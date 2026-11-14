<?php

namespace App\Http\Controllers;

use App\Actions\CreateCategory;
use App\Actions\DeleteCategory;
use App\Actions\UpdateCategory;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Category::class);

        return view('categories.index', ['categories' => Category::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('categories.edit', ['category' => new Category]);
    }

    public function store(Request $request, CreateCategory $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:categories,slug'],
        ]);
        $action->handle($request->user(), $data);

        return redirect()->route('categories.index')->with('status', 'تم إنشاء التصنيف.');
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:categories,slug,'.$category->id],
        ]);
        $action->handle($category, $request->user(), $data);

        return redirect()->route('categories.index')->with('status', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category, Request $request, DeleteCategory $action): RedirectResponse
    {
        $action->handle($category, $request->user());

        return redirect()->route('categories.index')->with('status', 'تم حذف التصنيف.');
    }
}
