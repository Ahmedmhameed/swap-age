<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_administrator_cannot_create_a_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Books', 'slug' => 'books'])
            ->assertForbidden();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_list_is_available_to_guests(): void
    {
        Category::factory()->create();

        $this->get(route('categories.index'))->assertOk();
    }

    public function test_administrator_can_create_update_and_delete_categories(): void
    {
        $administrator = User::factory()->create(['is_admin' => true]);

        $this->actingAs($administrator)
            ->post(route('categories.store'), ['name' => 'Books', 'slug' => 'books'])
            ->assertRedirect(route('categories.index'));

        $category = Category::query()->where('slug', 'books')->firstOrFail();

        $this->put(route('categories.update', $category), ['name' => 'Printed Books', 'slug' => 'printed-books'])
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Printed Books']);

        $this->delete(route('categories.destroy', $category->fresh()))
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
