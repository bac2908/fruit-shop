<?php

namespace App\Http\Controllers;

use App\Services\AprioriRecommendationService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;
    protected $aprioriRecommendationService;

    public function __construct(ProductService $productService, AprioriRecommendationService $aprioriRecommendationService)
    {
        $this->productService = $productService;
        $this->aprioriRecommendationService = $aprioriRecommendationService;
    }

    public function index(Request $request)
    {
        $products = $this->productService->getCollection($request);
        $allCategories = $this->productService->getCategories();
        $featuredProducts = $this->productService->getFeaturedProducts(5);

        return view('products.index', [
            'products'          => $products,
            'allCategories'     => $allCategories,
            'featuredProducts'  => $featuredProducts,
        ]);
    }

    public function show($slug)
    {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            abort(404);
        }

        $relatedProducts = $this->productService->getRelatedProducts($product, 8);
        $optionProducts = $product->has_gear_detail
            ? $this->productService->getOptionProducts($product, 8)
            : collect([$product]);
        $featuredProducts = $this->productService->getFeaturedProducts(5);
        $frequentlyBoughtTogether = $this->aprioriRecommendationService->recommendForProduct($product, 4);
        $aprioriStats = $this->aprioriRecommendationService->getStats();

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'optionProducts' => $optionProducts,
            'featuredProducts' => $featuredProducts,
            'frequentlyBoughtTogether' => $frequentlyBoughtTogether,
            'aprioriStats' => $aprioriStats,
        ]);
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->get('q', ''));

        if ($keyword === '') {
            return redirect()->route('products.index');
        }

        $products = $this->productService->search($keyword);
        $allCategories = $this->productService->getCategories();
        $featuredProducts = $this->productService->getFeaturedProducts(5);

        return view('products.index', [
            'products' => $products,
            'allCategories' => $allCategories,
            'featuredProducts' => $featuredProducts,
            'searchKeyword' => $keyword,
        ]);
    }
}
