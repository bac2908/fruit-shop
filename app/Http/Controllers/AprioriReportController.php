<?php

namespace App\Http\Controllers;

use App\Services\AprioriRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AprioriReportController extends Controller
{
    protected $aprioriService;

    public function __construct(AprioriRecommendationService $aprioriService)
    {
        $this->aprioriService = $aprioriService;
    }

    /**
     * Lấy thống kê chi tiết cho báo cáo
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->aprioriService->getStats();
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy tất cả quy tắc Apriori với filter
     */
    public function rules(Request $request): JsonResponse
    {
        try {
            $rules = $this->aprioriService->getAllRules(
                $request->get('min_support'),
                $request->get('min_confidence'),
                $request->get('min_lift'),
                $request->get('limit', 100)
            );

            return response()->json([
                'success' => true,
                'data' => $rules,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy frequent itemsets
     */
    public function itemsets(Request $request): JsonResponse
    {
        try {
            $itemsetSize = $request->get('size', 2);
            $itemsets = $this->aprioriService->getFrequentItemsets($itemsetSize);

            return response()->json([
                'success' => true,
                'data' => $itemsets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa cache Apriori (admin only)
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->aprioriService->clearCache();
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
