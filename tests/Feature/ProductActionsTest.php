<?php

namespace Tests\Feature;

use App\Actions\ApproveProduct;
use App\Actions\RequestProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_product_creates_a_pending_product_owned_by_the_requester(): void
    {
        $requester = User::factory()->create();
        $product = app(RequestProduct::class)->handle($requester, [
            'brand' => 'Example',
            'name' => 'Model One',
        ]);

        $this->assertFalse($product->is_approved);
        $this->assertSame($requester->id, $product->created_by);
    }

    public function test_approve_product_allows_administrators(): void
    {
        $administrator = User::factory()->create();
        $administrator->forceFill(['is_admin' => true])->save();
        $product = Product::factory()->create(['is_approved' => false]);

        app(ApproveProduct::class)->handle($product, $administrator);

        $this->assertTrue($product->fresh()->is_approved);
    }

    public function test_approve_product_rejects_non_administrators(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_approved' => false]);

        $this->expectException(AuthorizationException::class);

        app(ApproveProduct::class)->handle($product, $user);
    }
}
