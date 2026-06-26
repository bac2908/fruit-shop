<?php

return [
    'home_categories' => [
        'trai-cay-viet-nam',
        'trai-cay-nhap-khau',
        'trai-cay-thai-lan',
        'gio-qua-va-set-qua',
        'qua-cuoi-va-mam-cung',
        'hang-vao-mua',
        'san-pham-ban-chay'
    ],

    'mam_dia_ngu_qua' => [
        'collection_slugs' => [
            'mam-ngu-qua-l1',
            'mam-ngu-qua-l2',
            'mam-ngu-qua-l3',
            'mam-ngu-qua-l4',
            'mam-ngu-qua-m1',
            'mam-ngu-qua-m2',
            'mam-ngu-qua-m3',
            'mam-ngu-qua-m4',
        ],

        'featured_slugs' => [
            'nho-xanh-uc-pegasus',
            'kiwi-do-rubyred-zespri-newzealand',
            'man-hau-moc-chau',
            'tao-sach-huu-co-juliet-phap',
            'cherry-noi-dia-trung',
            'kum-quat-tac-ngot-noi-dia-trung',
        ],

        'type_category_slug' => 'qua-cuoi-va-mam-cung',
    ],

    // Optional manual slugs for products that should always show gear-detail flow.
    // Keep empty by default, then append slugs if you map additional detail products from reference site.
    'gear_detail_slugs' => [
    ],

    'bank_transfer' => [
        'bank_name' => env('SHOP_BANK_NAME', 'Vietcombank'),
        'account_name' => env('SHOP_BANK_ACCOUNT_NAME', 'THE GIOI TRAI CAY'),
        'account_number' => env('SHOP_BANK_ACCOUNT_NUMBER', '0123456789'),
        'branch' => env('SHOP_BANK_BRANCH', ''),
    ],
];
