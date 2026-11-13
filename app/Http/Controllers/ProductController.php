<?php

namespace App\Http\Controllers;

use App\Actions\ApproveProduct;
use App\Actions\RequestProduct;
use App\Http\Requests\RequestProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);
        $products = Product::query()
            ->when(! $request->user()?->is_admin, fn ($query) => $query->where('is_approved', true))
            ->orderBy('brand')
            ->orderBy('name')
            ->paginate(24);

        return view('products.index', compact('products'));
    }

    public function store(RequestProductRequest $request, RequestProduct $action): RedirectResponse
    {
        $product = $action->handle($request->user(), $request->validated());

        return redirect()->route('products.index')->with('status', "تم إرسال طلب المنتج #{$product->id} للمراجعة.");
    }

    public function approve(Request $request, Product $product, ApproveProduct $action): RedirectResponse
    {
        $action->handle($product, $request->user());

        return back()->with('status', 'تم اعتماد المنتج.');
    }
}
