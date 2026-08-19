<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryRailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (config('shop.quick_category_slugs', []) as $sortOrder => $slug) {
            Category::query()->create([
                'name' => 'Danh mục '.($sortOrder + 1),
                'slug' => $slug,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
        }
    }

    public function test_storefront_renders_the_configured_quick_category_rail_in_order(): void
    {
        $slugs = config('shop.quick_category_slugs');
        $categories = Category::query()
            ->whereIn('slug', $slugs)
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('data-category-rail', false)
            ->assertSee('aria-label="Danh mục"', false);

        $html = $response->getContent();
        $previousPosition = strpos($html, 'aria-label="Danh mục"');

        foreach ($slugs as $slug) {
            $category = $categories->get($slug);

            $this->assertNotNull($category, "Missing configured category: {$slug}");
            $response->assertSee(route('categories.show', $slug), false);

            $position = strpos($html, 'aria-label="' . e($category->name) . '"');
            $this->assertNotFalse($position, "Missing rail label for category: {$slug}");
            $this->assertGreaterThan($previousPosition, $position, "Category rail order is incorrect at: {$slug}");
            $previousPosition = $position;
        }
    }

    public function test_category_rail_marks_the_current_category_and_stays_out_of_checkout(): void
    {
        $slug = config('shop.quick_category_slugs.0');

        $this->get(route('categories.show', $slug))
            ->assertOk()
            ->assertSee('aria-current="page"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('<aside class="tgc-category-rail"', false);
    }
}
