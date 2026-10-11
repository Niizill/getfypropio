@php
    $path = request()->path();
    $isMemberArea = str_starts_with($path, 'm/') || request()->attributes->get('member_area_slug');
    $isCheckout = str_starts_with($path, 'c/') || str_starts_with($path, 'checkout') || str_starts_with($path, 'api-checkout');
    $skipPanelPwa = $isMemberArea || $isCheckout;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['panel-app' => ! $skipPanelPwa])>
<head>
    <meta charset="utf-8">
    @if($skipPanelPwa)
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @else
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    @endif
    <script>
        (function(){try{var s=localStorage.getItem('theme');var t=s||'dark';document.documentElement.classList.toggle('dark',t==='dark');@unless($skipPanelPwa)if(window.matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true){document.documentElement.classList.add('pwa-standalone');}@endunless}catch(_){}})();
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(!empty($openGraph) && is_array($openGraph))
        <title>{{ e($openGraph['title'] ?? config('getfy.app_name', config('app.name', 'Getfy'))) }}</title>
        @if(!empty($openGraph['description']))
            <meta name="description" content="{{ e($openGraph['description']) }}">
        @endif
        <meta property="og:type" content="{{ e($openGraph['type'] ?? 'website') }}">
        <meta property="og:site_name" content="{{ e($openGraph['site_name'] ?? config('getfy.app_name', config('app.name', 'Getfy'))) }}">
        <meta property="og:title" content="{{ e($openGraph['title'] ?? '') }}">
        @if(!empty($openGraph['description']))
            <meta property="og:description" content="{{ e($openGraph['description']) }}">
        @endif
        @if(!empty($openGraph['url']))
            <meta property="og:url" content="{{ e($openGraph['url']) }}">
            <link rel="canonical" href="{{ e($openGraph['url']) }}">
        @endif
        @if(!empty($openGraph['favicon']))
            <link rel="icon" href="{{ e($openGraph['favicon']) }}" type="image/png" sizes="32x32">
            <link rel="shortcut icon" href="{{ e($openGraph['favicon']) }}" type="image/png">
        @endif
        @if(!empty($openGraph['image']))
            <meta property="og:image" content="{{ e($openGraph['image']) }}">
            <meta property="og:image:secure_url" content="{{ e($openGraph['image']) }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ e($openGraph['title'] ?? '') }}">
            @if(!empty($openGraph['description']))
                <meta name="twitter:description" content="{{ e($openGraph['description']) }}">
            @endif
            <meta name="twitter:image" content="{{ e($openGraph['image']) }}">
        @endif
    @else
        <title>{{ config('getfy.app_name', config('app.name', 'Getfy')) }}</title>
    @endif
    @unless($skipPanelPwa)
    @php
        $wlFavicon = \App\Support\BrandFavicon::publicUrl();
        $wlAppName = trim((string) config('getfy.app_name', config('app.name', 'Getfy')));
        if ($wlAppName === '') {
            $wlAppName = 'Getfy';
        }
        $wlThemeColor = config('getfy.pwa_theme_color');
        $wlThemeColor = ($wlThemeColor !== null && $wlThemeColor !== '') ? $wlThemeColor : config('getfy.theme_primary', '#0ea5e9');
        $wlAppleIcon = \App\Support\PwaIcon::customPublicUrl('192')
            ?? \App\Support\PwaIcon::customPublicUrl('512')
            ?? \App\Support\PwaIcon::publicUrl('192');
    @endphp
    <link rel="icon" href="{{ $wlFavicon }}" type="image/png" sizes="32x32">
    <link rel="shortcut icon" href="{{ $wlFavicon }}" type="image/png">
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <meta name="theme-color" content="{{ $wlThemeColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ e($wlAppName) }}">
    @if($wlAppleIcon)
    <link rel="apple-touch-icon" href="{{ $wlAppleIcon }}">
    @endif
    <script>
        (function(){var e=null;window.addEventListener('beforeinstallprompt',function(t){t.preventDefault();e=t;window.__pwaInstallPrompt=e;},{capture:true});Object.defineProperty(window,'__pwaInstallPrompt',{get:function(){return e;},set:function(t){e=t;}});})();
    </script>
    @endunless
    @inertiaHead
    @php
        $viteDevHot = public_path('hot');
        $vueBridgeBuilt = public_path('build/getfy-plugin-vue.mjs');
        $vueBridgeImport = (! file_exists($viteDevHot) && file_exists($vueBridgeBuilt))
            ? asset('build/getfy-plugin-vue.mjs')
            : \Illuminate\Support\Facades\Vite::asset('resources/js/plugins/getfyPluginVueBridge.js');
    @endphp
    <script type="importmap">
        {"scopes":{"/plugins/":{"vue":@json($vueBridgeImport)}}}
    </script>
    @php
        $assetContext = \App\Plugins\PluginAssetQueue::contextForRequest(request());
        \App\Plugins\PluginAssetQueue::fireHeadHooks($assetContext);
        $themeTokensHead = \App\Plugins\ThemeEngine::tokensForRequest(request());
        $pluginContextStyles = \App\Plugins\PluginAssetQueue::stylesFor($assetContext);
        $pluginContextScripts = \App\Plugins\PluginAssetQueue::scriptsFor($assetContext);
    @endphp
    @if (!empty($themeTokensHead))
    <style>:root { @foreach ($themeTokensHead as $cssVar => $cssValue) {{ $cssVar }}: {{ $cssValue }}; @endforeach }</style>
    @endif
    @foreach ($pluginContextStyles as $asset)
    <link rel="stylesheet" href="{{ $asset['url'] }}" data-plugin-style="{{ $asset['handle'] }}">
    @endforeach
    @foreach ($pluginContextScripts as $asset)
    <script src="{{ $asset['url'] }}" @if(!empty($asset['defer'])) defer @endif data-plugin-script="{{ $asset['handle'] }}"></script>
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Lagartixa Pay: responsive logo and distinct light/dark login themes, independent of cached Vite assets. --}}
    <style>
        aside > div:has(img[src*="/brand/lagartixapay-logo.webp"]) {padding-block:12px !important;}
        aside a[href="/dashboard"] > div:has(> img[src*="/brand/lagartixapay-logo.webp"]) {
            width:188px !important;height:66px !important;min-width:0 !important;
        }
        aside img[src*="/brand/lagartixapay-logo.webp"] {
            width:188px !important;height:66px !important;max-width:none !important;
            max-height:none !important;object-fit:contain !important;object-position:left center !important;
        }

        /* CLARO: branco e amarelo suave, mantendo o seletor de tema ativo. */
        html:not(.dark) .wl-root {background:#fffdf4 !important;color:#161616 !important;}
        html:not(.dark) .wl-root > div:first-child {
            background:linear-gradient(155deg,#ffffff 0%,#fffbea 66%,#fff5c9 100%) !important;
            border-color:#f0e6c3 !important;
        }
        html:not(.dark) .wl-root > div:first-child h1,
        html:not(.dark) .wl-root > div:first-child .text-zinc-900 {color:#181818 !important;}
        html:not(.dark) .wl-root > div:first-child p,
        html:not(.dark) .wl-root > div:first-child label {color:#5a5545 !important;}
        html:not(.dark) .wl-root > div:first-child .bg-white,
        html:not(.dark) .wl-root > div:first-child .dark\\:bg-zinc-800\\/40 {
            background:#fff !important;border-color:#eddfb5 !important;
            box-shadow:0 8px 38px rgba(75,51,5,.065) !important;
        }
        html:not(.dark) .wl-root .wl-input {
            background:#fffefa !important;color:#181818 !important;border-color:#d9cfb8 !important;
        }
        html:not(.dark) .wl-root .wl-input::placeholder {color:#817b6d !important;}
        html:not(.dark) .wl-root header img[src*="/brand/lagartixapay-logo.webp"] {
            background:#191919 !important;border-radius:12px;padding:7px 11px;box-sizing:border-box;
        }
        html:not(.dark) .wl-root > div:last-child {
            background:
                radial-gradient(ellipse at 77% 27%,rgba(255,208,65,.38),transparent 55%),
                linear-gradient(140deg,#fffefa 0%,#fff4cb 60%,#fce5a8 100%) !important;
        }
        html:not(.dark) .wl-root > div:last-child > div:nth-child(3) {
            background:linear-gradient(to top,rgba(255,250,229,.97),rgba(255,250,229,0) 65%) !important;
        }
        html:not(.dark) .wl-root > div:last-child > div:last-child > p {
            color:#202020 !important;
        }

        /* ESCURO: preto e amarelo, sem forçar a aparência do modo claro. */
        html.dark .wl-root {background:#080808 !important;color:#fafafa !important;}
        html.dark .wl-root > div:first-child {
            background:linear-gradient(160deg,#171717 0%,#090909 76%) !important;
            border-color:#262626 !important;
        }
        html.dark .wl-root > div:first-child h1,
        html.dark .wl-root > div:first-child .text-zinc-900 {color:#fafafa !important;}
        html.dark .wl-root > div:first-child p,
        html.dark .wl-root > div:first-child label {color:#b5b5b5 !important;}
        html.dark .wl-root > div:first-child .bg-white,
        html.dark .wl-root > div:first-child .dark\\:bg-zinc-800\\/40 {
            background:#171717 !important;border-color:#303030 !important;
        }
        html.dark .wl-root .wl-input {
            background:#111113 !important;color:#fff !important;border-color:#3e3e42 !important;
        }
        html.dark .wl-root .wl-input::placeholder {color:#85858b !important;}
        html.dark .wl-root > div:last-child {
            background:
                radial-gradient(ellipse at 82% 20%,rgba(255,207,38,.21),transparent 49%),
                radial-gradient(ellipse at 42% 95%,rgba(235,163,15,.12),transparent 57%),
                linear-gradient(135deg,#080808 0%,#15120a 52%,#090909 100%) !important;
        }
        html.dark .wl-root > div:last-child > div:nth-child(3) {
            background:linear-gradient(to top,rgba(0,0,0,.83),transparent 58%) !important;
        }
        html.dark .wl-root > div:last-child > div:last-child > p {color:#fff !important;}

        /* Shared login details */
        .wl-root .wl-input:focus {
            border-color:#eab308 !important;box-shadow:0 0 0 3px rgba(234,179,8,.19) !important;
        }
        .wl-root .wl-submit {background:#facc15 !important;color:#121212 !important;}
        .wl-root .wl-link {color:#d69200 !important;}
        html.dark .wl-root .wl-link {color:#facc15 !important;}
        .wl-root header img[src*="/brand/lagartixapay-logo.webp"] {
            width:240px !important;height:77px !important;max-width:240px !important;
            object-fit:contain !important;object-position:left center !important;
        }
        .wl-root > div:last-child > div:first-child {background:none !important;opacity:0 !important;}
        .wl-root > div:last-child > img[src*="/brand/lagartixapay-mascot.webp"] {
            position:absolute !important;inset:auto 0 0 auto !important;width:auto !important;
            height:92% !important;max-width:88% !important;object-fit:contain !important;
            object-position:right bottom !important;
            filter:drop-shadow(0 15px 35px rgba(0,0,0,.21));
        }
        html.dark .wl-root > div:last-child > img[src*="/brand/lagartixapay-mascot.webp"] {
            filter:drop-shadow(0 15px 35px rgba(0,0,0,.6));
        }
        .wl-root > div:last-child > div:last-child {z-index:2 !important;}
        .wl-root > div:last-child > div:last-child > p:first-child {
            color:#c08100 !important;
        }
        html.dark .wl-root > div:last-child > div:last-child > p:first-child {
            color:#facc15 !important;
        }
        @media(max-width:420px) {
            .wl-root header img[src*="/brand/lagartixapay-logo.webp"] {
                width:195px !important;max-width:195px !important;height:65px !important;
            }
        }
    </style>
</head>
<body class="antialiased">
    @if($isMemberArea)
    <style>
        #app:empty { min-height: 100vh; background: #18181b; }
        #app:empty::before {
            content: '';
            position: fixed;
            inset: 0;
            margin: auto;
            width: 2.5rem;
            height: 2.5rem;
            border: 3px solid rgba(255,255,255,0.15);
            border-top-color: #0ea5e9;
            border-radius: 50%;
            animation: ma-boot-spin 0.75s linear infinite;
        }
        @keyframes ma-boot-spin { to { transform: rotate(360deg); } }
    </style>
    @endif
    @php
        $page = $page ?? [];
        $page['props'] = array_merge(
            [
                'auth' => ['user' => null],
                'flash' => ['success' => null, 'error' => null],
                'platform' => null,
            ],
            $page['props'] ?? []
        );
    @endphp
    <div id="app" data-page="{{ json_encode($page) }}"></div>
</body>
</html>
