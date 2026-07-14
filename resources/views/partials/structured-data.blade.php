@php
    $siteUrl = route('home');
    $organizationId = $siteUrl . '#organization';
    $websiteId = $siteUrl . '#website';
    $schemaGraph = [
        [
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => config('app.name', 'Thế Giới Trái Cây'),
            'url' => $siteUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => 'https://theme.hstatic.net/200000157781/1001036201/14/logo.png?v=1061',
            ],
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'telephone' => '+84333499426',
                    'contactType' => 'customer service',
                    'areaServed' => 'VN',
                    'availableLanguage' => ['vi'],
                ],
            ],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $siteUrl,
            'name' => config('app.name', 'Thế Giới Trái Cây'),
            'publisher' => ['@id' => $organizationId],
            'inLanguage' => 'vi-VN',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
    $schemaJson = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $schemaGraph],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp
<script type="application/ld+json">{!! $schemaJson !!}</script>
