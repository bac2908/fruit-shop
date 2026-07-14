<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use DOMDocument;
use DOMElement;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $cacheKey = 'seo:sitemap:' . sha1(route('home'));
        $xml = Cache::remember($cacheKey, now()->addHours(6), fn () => $this->buildSitemap());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /notifications',
            'Disallow: /checkout',
            'Disallow: /cart',
            'Disallow: /search',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            '',
            'Sitemap: ' . route('seo.sitemap'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function buildSitemap(): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $urlSet = $document->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $document->appendChild($urlSet);

        $staticPages = [
            ['route' => 'home', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['route' => 'products.index', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['route' => 'about', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['route' => 'contact.page', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['route' => 'page.faq', 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['route' => 'page.return', 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['route' => 'page.shipping.payment', 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['route' => 'page.privacy', 'changefreq' => 'yearly', 'priority' => '0.2'],
            ['route' => 'page.terms', 'changefreq' => 'yearly', 'priority' => '0.2'],
        ];

        foreach ($staticPages as $page) {
            $this->appendUrl($document, $urlSet, route($page['route']), null, $page['changefreq'], $page['priority']);
        }

        Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Category $category) use ($document, $urlSet) {
                $this->appendUrl(
                    $document,
                    $urlSet,
                    route('categories.show', $category->slug),
                    $category->updated_at?->toAtomString(),
                    'weekly',
                    '0.7'
                );
            });

        Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use ($document, $urlSet) {
                $this->appendUrl(
                    $document,
                    $urlSet,
                    route('products.show', $product->slug),
                    $product->updated_at?->toAtomString(),
                    'weekly',
                    '0.8'
                );
            });

        return $document->saveXML() ?: '';
    }

    private function appendUrl(
        DOMDocument $document,
        DOMElement $urlSet,
        string $location,
        ?string $lastModified,
        string $changeFrequency,
        string $priority
    ): void {
        $url = $document->createElement('url');
        $locationElement = $document->createElement('loc');
        $locationElement->appendChild($document->createTextNode($location));
        $url->appendChild($locationElement);

        if ($lastModified) {
            $url->appendChild($document->createElement('lastmod', $lastModified));
        }

        $url->appendChild($document->createElement('changefreq', $changeFrequency));
        $url->appendChild($document->createElement('priority', $priority));
        $urlSet->appendChild($url);
    }
}
