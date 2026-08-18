<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SalesPopupUrlTest extends TestCase
{
    public function test_sales_popup_uses_host_independent_local_urls(): void
    {
        Cache::forget('sales_popup_products_v2');

        $response = $this
            ->withServerVariables(['HTTP_HOST' => '127.0.0.1'])
            ->get('/');

        $response->assertOk();

        $matched = preg_match(
            '/var products = (\[.*?\]);\s*var toast/s',
            $response->getContent(),
            $matches
        );

        $this->assertSame(1, $matched);

        $products = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertNotEmpty($products);
        $this->assertNotEmpty(array_filter(
            $products,
            fn (array $product) => str_starts_with($product['image'], '/images/')
        ));

        foreach ($products as $product) {
            $this->assertStringStartsWith('/products/', $product['url']);
            $this->assertStringNotContainsString('127.0.0.1', $product['url']);
            $this->assertStringNotContainsString('127.0.0.1', $product['image']);
        }
    }
}
