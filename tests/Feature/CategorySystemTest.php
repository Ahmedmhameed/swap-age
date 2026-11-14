<?php

namespace Tests\Feature;

use App\Actions\CreateCategory;
use App\Actions\SyncTradeCategories;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Trade;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategorySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_with_name_and_slug(): void
    {
        $administrator = User::factory()->create(['is_admin' => true]);

        $category = app(CreateCategory::class)->handle($administrator, [
            'name' => 'Books',
            'slug' => 'books',
        ]);

        $this->assertModelExists($category);
        $this->assertSame('books', $category->slug);
    }

    public function test_category_cannot_be_deleted_while_a_trade_uses_it(): void
    {
        $category = Category::factory()->create();
        $trade = Trade::factory()->create();
        $trade->categories()->attach($category);

        $this->expectException(QueryException::class);

        Category::query()->whereKey($category->id)->delete();
    }

    public function test_trade_can_have_multiple_categories_and_soft_delete_keeps_the_pivot_rows(): void
    {
        $trade = Trade::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $trade->categories()->sync($categories->modelKeys());

        $this->assertCount(2, $trade->categories);

        $trade->delete();

        foreach ($categories as $category) {
            $this->assertDatabaseHas('trade_category', [
                'trade_id' => $trade->id,
                'category_id' => $category->id,
            ]);
        }
    }

    public function test_sync_trade_categories_succeeds_for_draft_without_incrementing_version(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $previousCategory = Category::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $trade->categories()->attach($previousCategory);

        app(SyncTradeCategories::class)->handle($trade, $trade->owner, $categories->modelKeys());

        $this->assertEqualsCanonicalizing($categories->modelKeys(), $trade->fresh()->categories->modelKeys());
        $this->assertDatabaseMissing('trade_category', [
            'trade_id' => $trade->id,
            'category_id' => $previousCategory->id,
        ]);
        $this->assertSame(1, $trade->fresh()->version);
    }

    #[DataProvider('nonDraftTradeStatuses')]
    public function test_sync_trade_categories_rejects_non_draft_trades(TradeStatus $status): void
    {
        $trade = Trade::factory()->create(['status' => $status]);
        $category = Category::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(SyncTradeCategories::class)->handle($trade, $trade->owner, [$category->id]);
    }

    public static function nonDraftTradeStatuses(): array
    {
        return [
            'created' => [TradeStatus::Created],
            'negotiating' => [TradeStatus::Negotiating],
            'accepted' => [TradeStatus::Accepted],
            'completed' => [TradeStatus::Completed],
            'cancelled' => [TradeStatus::Cancelled],
        ];
    }

    public function test_sync_trade_categories_rejects_a_user_who_is_not_the_owner(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $category = Category::factory()->create();
        $otherUser = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(SyncTradeCategories::class)->handle($trade, $otherUser, [$category->id]);
    }

    public function test_category_policy_allows_public_view_and_restricts_management_to_administrators(): void
    {
        $policy = new CategoryPolicy;
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $administrator = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($policy->viewAny(null));
        $this->assertTrue($policy->view(null, $category));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $category));
        $this->assertFalse($policy->delete($user, $category));
        $this->assertTrue($policy->create($administrator));
        $this->assertTrue($policy->update($administrator, $category));
        $this->assertTrue($policy->delete($administrator, $category));
    }

    public function test_category_and_trade_factories_create_valid_records(): void
    {
        $category = Category::factory()->create();
        $trade = Trade::factory()->create();

        $this->assertModelExists($category);
        $this->assertSame(Str::slug($category->name), $category->slug);
        $this->assertModelExists($trade);
        $this->assertCount(0, $trade->categories);
    }

    public function test_products_table_no_longer_has_a_category_column(): void
    {
        Product::factory()->create();

        $this->assertFalse(Schema::hasColumn('products', 'category'));
    }
}
