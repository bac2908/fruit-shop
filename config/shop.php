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

    'shipping' => [
        'free_threshold' => env('SHOP_FREE_SHIPPING_THRESHOLD', 500000),
        'default_fee' => env('SHOP_DEFAULT_SHIPPING_FEE', 70000),
        'local_express_province_code' => env('SHOP_LOCAL_EXPRESS_PROVINCE_CODE', '79'),
        'local_express_eta' => env('SHOP_LOCAL_EXPRESS_ETA', '30 - 90 phút'),
        'province_partner_eta' => env('SHOP_PROVINCE_PARTNER_ETA', '2 - 48 giờ làm việc'),
        'contact_required_eta' => env('SHOP_CONTACT_REQUIRED_ETA', 'Shop liên hệ xác nhận'),
        'remote_ward_surcharge' => env('SHOP_REMOTE_WARD_SURCHARGE', 20000),
        'remote_keywords' => [
            'Đặc khu',
            'Phú Quốc',
            'Thổ Châu',
            'Kiên Hải',
            'Côn Đảo',
        ],
        'zones' => [
            'hcm' => [
                'label' => 'TP. Hồ Chí Minh',
                'fee' => 25000,
                'province_codes' => ['79'],
            ],
            'near_hcm' => [
                'label' => 'Khu vực gần TP. Hồ Chí Minh',
                'fee' => 35000,
                'province_codes' => ['75', '80'],
            ],
            'mekong' => [
                'label' => 'Miền Tây Nam Bộ',
                'fee' => 45000,
                'province_codes' => ['82', '86', '91', '92', '96'],
            ],
            'highland_south_central' => [
                'label' => 'Tây Nguyên và Nam Trung Bộ',
                'fee' => 50000,
                'province_codes' => ['52', '56', '66', '68'],
            ],
            'central' => [
                'label' => 'Miền Trung',
                'fee' => 55000,
                'province_codes' => ['38', '40', '42', '44', '46', '48', '51'],
            ],
            'north' => [
                'label' => 'Miền Bắc',
                'fee' => 65000,
                'province_codes' => ['01', '04', '08', '11', '12', '14', '15', '19', '20', '22', '24', '25', '31', '33', '37'],
            ],
        ],
    ],

    'order_automation' => [
        'auto_confirm_stock_reserved' => env('SHOP_AUTO_CONFIRM_STOCK_RESERVED', true),
        'auto_mark_cod_paid_on_done' => env('SHOP_AUTO_MARK_COD_PAID_ON_DONE', true),
        'order_placed_email_enabled' => env('SHOP_ORDER_PLACED_EMAIL_ENABLED', true),
        'order_confirmed_email_enabled' => env('SHOP_ORDER_CONFIRMED_EMAIL_ENABLED', true),
        'order_cancelled_email_enabled' => env('SHOP_ORDER_CANCELLED_EMAIL_ENABLED', true),
        'low_stock_alert_enabled' => env('SHOP_LOW_STOCK_ALERT_ENABLED', true),
        'low_stock_fallback_threshold' => env('SHOP_LOW_STOCK_FALLBACK_THRESHOLD', 5),
        'bank_transfer_expire_hours' => env('SHOP_BANK_TRANSFER_EXPIRE_HOURS', 24),
        'momo_expire_minutes' => env('SHOP_MOMO_EXPIRE_MINUTES', 30),
    ],

    'returns' => [
        'request_window_hours' => env('SHOP_RETURN_REQUEST_WINDOW_HOURS', 24),
        'refund_days' => env('SHOP_RETURN_REFUND_DAYS', 3),
        'max_evidence_mb' => env('SHOP_RETURN_MAX_EVIDENCE_MB', 3),
        'email_enabled' => env('SHOP_RETURN_EMAIL_ENABLED', true),
    ],

    'contact' => [
        'inbox_email' => env('CONTACT_INBOX_EMAIL', env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'))),
        'min_fill_seconds' => (int) env('CONTACT_MIN_FILL_SECONDS', 1),
        'form_expire_minutes' => (int) env('CONTACT_FORM_EXPIRE_MINUTES', 240),
        'duplicate_window_minutes' => (int) env('CONTACT_DUPLICATE_WINDOW_MINUTES', 15),
    ],
];
