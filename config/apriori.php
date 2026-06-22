<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Apriori Algorithm Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho thuật toán Apriori trong recommendation engine
    |
    */

    // Ngưỡng hỗ trợ tối thiểu (0-1)
    // Ví dụ: 0.02 = 2% giao dịch phải chứa itemset này
    'min_support' => env('APRIORI_MIN_SUPPORT', 0.02),

    // Ngưỡng độ tin cậy tối thiểu (0-1)
    // Ví dụ: 0.30 = nếu A được mua, có 30% khả năng mua B
    'min_confidence' => env('APRIORI_MIN_CONFIDENCE', 0.30),

    // Ngưỡng lift tối thiểu (0+)
    // Ví dụ: 1.0 = không có liên quan; > 1.0 = có liên quan dương
    'min_lift' => env('APRIORI_MIN_LIFT', 1.0),

    // Số lần xuất hiện tối thiểu
    'min_pair_count' => env('APRIORI_MIN_PAIR_COUNT', 2),

    // Kích thước itemset tối đa (2-5)
    // 2 = chỉ cặp sản phẩm
    // 3 = cặp + bộ ba
    // 4 = cặp + bộ ba + bộ bốn
    'max_itemset_size' => env('APRIORI_MAX_ITEMSET_SIZE', 4),

    // Thời gian cache (giờ)
    'cache_hours' => env('APRIORI_CACHE_HOURS', 24),

    // Bật debug mode
    'debug' => env('APRIORI_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Preset Configurations
    |--------------------------------------------------------------------------
    |
    | Các cấu hình sẵn cho các trường hợp khác nhau
    |
    */

    'presets' => [
        // Khắt khe - chỉ lấy quy tắc rất mạnh
        'strict' => [
            'min_support' => 0.05,      // 5%
            'min_confidence' => 0.50,   // 50%
            'min_lift' => 1.5,
            'min_pair_count' => 5,
            'max_itemset_size' => 3,
        ],

        // Cân bằng - mặc định
        'balanced' => [
            'min_support' => 0.02,      // 2%
            'min_confidence' => 0.30,   // 30%
            'min_lift' => 1.0,
            'min_pair_count' => 2,
            'max_itemset_size' => 4,
        ],

        // Rộng rãi - phát hiện cả quy tắc yếu
        'loose' => [
            'min_support' => 0.01,      // 1%
            'min_confidence' => 0.20,   // 20%
            'min_lift' => 0.8,
            'min_pair_count' => 1,
            'max_itemset_size' => 5,
        ],
    ],
];
