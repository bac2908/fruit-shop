<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sitemap_contains_public_catalog_and_excludes_inactive_products(): void
    {
        $suffix = uniqid();
        $category = Category::query()->create([
            'name' => 'SEO ' . $suffix,
            'slug' => 'seo-' . $suffix,
            'sort_order' => 999,
            'is_active' => true,
        ]);
        $activeProduct = $this->createProduct($category, 'Sản phẩm SEO ' . $suffix, true);
        $inactiveProduct = $this->createProduct($category, 'Sản phẩm ẩn SEO ' . $suffix, false);

        Cache::forget('seo:sitemap:' . sha1(route('home')));

        $response = $this->get(route('seo.sitemap'));

        $response->assertOk();
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($response->getContent()));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $locations = collect($xpath->query('//sm:loc'))
            ->map(fn ($node) => $node->textContent)
            ->all();

        $this->assertContains(route('home'), $locations);
        $this->assertContains(route('categories.show', $category->slug), $locations);
        $this->assertContains(route('products.show', $activeProduct->slug), $locations);
        $this->assertNotContains(route('products.show', $inactiveProduct->slug), $locations);
    }

    public function test_robots_declares_sitemap_and_blocks_private_areas(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /checkout', false)
            ->assertSee('Sitemap: ' . route('seo.sitemap'), false);
    }

    public function test_private_pages_are_noindex_while_home_is_indexable(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow"', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow"', false);
    }

    public function test_product_page_contains_product_and_breadcrumb_json_ld(): void
    {
        $suffix = uniqid();
        $category = Category::query()->create([
            'name' => 'Schema ' . $suffix,
            'slug' => 'schema-' . $suffix,
            'sort_order' => 999,
            'is_active' => true,
        ]);
        $product = $this->createProduct($category, 'Sản phẩm Schema ' . $suffix, true);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $types = collect($this->extractJsonLd($response->getContent()))
            ->flatMap(fn (array $data) => collect($data['@graph'] ?? [$data])->pluck('@type'))
            ->filter()
            ->values();

        $this->assertTrue($types->contains('Product'));
        $this->assertTrue($types->contains('BreadcrumbList'));
    }

    public function test_analytics_script_is_not_loaded_before_consent(): void
    {
        config()->set('services.analytics.enabled', true);
        config()->set('services.analytics.measurement_id', 'G-TEST12345');

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('data-analytics-consent', false)
            ->assertSee('G-TEST12345', false)
            ->assertDontSee('src="https://www.googletagmanager.com/gtag/js?id=G-TEST12345"', false);
    }

    private function extractJsonLd(string $html): array
    {
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);

        return collect($xpath->query('//script[@type="application/ld+json"]'))
            ->map(fn ($node) => json_decode($node->textContent, true))
            ->filter(fn ($data) => is_array($data))
            ->values()
            ->all();
    }

    private function createProduct(Category $category, string $name, bool $isActive): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug() . '-' . uniqid(),
            'sku' => 'SEO-' . uniqid(),
            'unit' => 'kg',
            'stock' => 5,
            'price' => 100000,
            'thumb' => 'images/test-product.jpg',
            'short_desc' => 'Dữ liệu kiểm thử SEO.',
            'description' => 'Dữ liệu kiểm thử SEO và schema sản phẩm.',
            'is_active' => $isActive,
        ]);
    }
}
