<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }

    public function index(Request $request)
    {
        $categorySlugs = config('shop.home_categories', []);

        return view('home', [
            'topCategories' => $this->homeService->getTopCategories(),
            'coupons'       => $this->homeService->getActiveCoupons(6, $request->user()),
            'sections'      => $this->homeService->getHomeSections($categorySlugs),
        ]);
    }
}
