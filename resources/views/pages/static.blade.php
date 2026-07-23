@extends('layouts.app')

@section('title', (($page['meta_title'] ?? null) ?: ($page['title'] ?? 'Trang nội dung')) . ' - Thế Giới Trái Cây')
@section('meta_description', ($page['meta_description'] ?? null) ?: ($page['intro'] ?? ''))

@section('content')
<section class="bread_crumb py-4">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <ul class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
                    <li class="home">
                        <a itemprop="url" href="{{ route('home') }}"><span itemprop="title">Trang chủ</span></a>
                        <span> <i class="fa fa-angle-right"></i> </span>
                    </li>
                    <li><strong><span itemprop="title">{{ $page['title'] ?? 'Trang nội dung' }}</span></strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="page-static-wrap">
    <div class="container">
        <div class="static-card">
            <h1 class="static-title">{{ $page['title'] ?? 'Trang nội dung' }}</h1>

            @if(!empty($page['intro']))
                <p class="static-intro">{{ $page['intro'] }}</p>
            @endif

            @if(!empty($page['content']))
                <div class="static-section static-rich-content">{!! $page['content'] !!}</div>
            @endif

            @if(!empty($page['sections']))
                @foreach($page['sections'] as $section)
                    <div class="static-section">
                        @if(!empty($section['heading']))
                            <h2>{{ $section['heading'] }}</h2>
                        @endif

                        @if(!empty($section['paragraphs']))
                            @foreach($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        @endif

                        @if(!empty($section['list']))
                            <ul>
                                @foreach($section['list'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            @endif

            @if(!empty($page['faqs']))
                <div class="static-section">
                    <h2>Danh sách câu hỏi</h2>
                    <div class="faq-list">
                        @foreach($page['faqs'] as $faq)
                            <details class="faq-item" {{ $loop->first ? 'open' : '' }}>
                                <summary>{{ $faq['q'] }}</summary>
                                <p>{{ $faq['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($page['actions']))
                <div class="static-actions">
                    @foreach($page['actions'] as $action)
                        <a href="{{ $action['url'] }}" class="btn-static">{{ $action['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    body {
        background: #efefef;
    }

    .page-static-wrap {
        padding-bottom: 30px;
    }

    .static-card {
        background: #fff;
        border: 1px solid #e9e9e9;
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.04);
    }

    .static-title {
        font-size: 34px;
        font-weight: 700;
        color: #2f2f2f;
        margin: 0 0 12px;
    }

    .static-intro {
        color: #4f4f4f;
        font-size: 16px;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .static-section {
        margin-bottom: 24px;
    }

    .static-section:last-child {
        margin-bottom: 0;
    }

    .static-section h2 {
        font-size: 21px;
        margin: 0 0 10px;
        color: #2f2f2f;
        font-weight: 700;
    }

    .static-section p {
        margin: 0 0 10px;
        font-size: 15px;
        line-height: 1.85;
        color: #555;
    }

    .static-section ul {
        margin: 0;
        padding-left: 20px;
    }

    .static-section li {
        margin-bottom: 8px;
        color: #555;
        line-height: 1.7;
    }

    .faq-list {
        display: grid;
        gap: 10px;
    }

    .faq-item {
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        padding: 10px 14px;
        background: #fafafa;
    }

    .faq-item summary {
        cursor: pointer;
        list-style: none;
        font-weight: 600;
        color: #2f2f2f;
        font-size: 15px;
        outline: none;
    }

    .faq-item summary::-webkit-details-marker {
        display: none;
    }

    .faq-item p {
        margin-top: 10px;
    }

    .static-actions {
        margin-top: 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-static {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        padding: 0 16px;
        border-radius: 999px;
        background: #7fbe3b;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
    }

    .btn-static:hover {
        background: #6ba42f;
        color: #fff;
    }

    @media (max-width: 768px) {
        .static-card {
            padding: 20px 16px;
        }

        .static-title {
            font-size: 28px;
        }

        .static-section h2 {
            font-size: 19px;
        }
    }
</style>
@endpush
