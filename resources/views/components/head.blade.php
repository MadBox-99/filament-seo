@props(['title' => null])

@php($seo = \Madbox99\FilamentSeo\Facades\Seo::resolve())

<title>{{ filled($title) ? $title : $seo->title }}</title>

@if ($seo->description)
<meta name="description" content="{{ $seo->description }}">
@endif
@if ($seo->keywords)
<meta name="keywords" content="{{ $seo->keywords }}">
@endif
@if ($seo->robots)
<meta name="robots" content="{{ $seo->robots }}">
@endif
@if ($seo->canonical)
<link rel="canonical" href="{{ $seo->canonical }}">
@endif
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ filled($title) ? $title : $seo->title }}">
@if ($seo->description)
<meta property="og:description" content="{{ $seo->description }}">
@endif
@if ($seo->ogImage)
<meta property="og:image" content="{{ $seo->ogImage }}">
@endif
@if ($seo->canonical)
<meta property="og:url" content="{{ $seo->canonical }}">
@endif
@foreach ($seo->schemas as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
@endforeach
