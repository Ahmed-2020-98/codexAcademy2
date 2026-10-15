<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0A5A9E">
<title>{{ isset($title) ? $title.' — ' : '' }}أكاديمية كودكس | Codex Academy</title>
<meta name="description" content="{{ $description ?? 'أكاديمية كودكس: كورسات مسجلة ولايف وحضورية في اللغات والبرمجة، مع متابعة شخصية ولوحة مهام أسبوعية وشهادات معتمدة.' }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Readex+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
