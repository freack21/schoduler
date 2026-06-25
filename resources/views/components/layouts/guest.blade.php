<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Primary SEO --}}
    <title>{{ $title ?? 'SMA Negeri 1 Tapung Hulu' }} | Sistem Penjadwalan Akademik</title>
    <meta name="description" content="SMA Negeri 1 Tapung Hulu — Sistem Penjadwalan Mata Pelajaran Digital. Portal akademik modern untuk siswa, guru, dan admin. Terakreditasi A. Berlokasi di Jl. Kampung Lama No. 10, Kasikan, Tapung Hulu, Kampar, Riau.">
    <meta name="keywords" content="SMA Negeri 1 Tapung Hulu, SMAN 1 Tapung Hulu, sekolah Kampar, penjadwalan sekolah, jadwal pelajaran, SMA Riau, sekolah menengah atas, Tapung Hulu, sistem akademik, portal siswa, portal guru">
    <meta name="author" content="SMA Negeri 1 Tapung Hulu">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'SMA Negeri 1 Tapung Hulu' }} | Sistem Penjadwalan Akademik">
    <meta property="og:description" content="Portal Akademik Digital SMA Negeri 1 Tapung Hulu. Sistem penjadwalan modern untuk mewujudkan ekosistem pendidikan yang efisien dan transparan.">
    <meta property="og:image" content="{{ asset('image/foto1.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SMA Negeri 1 Tapung Hulu">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'SMA Negeri 1 Tapung Hulu' }} | Sistem Penjadwalan Akademik">
    <meta name="twitter:description" content="Portal Akademik Digital SMA Negeri 1 Tapung Hulu. Sistem penjadwalan modern.">
    <meta name="twitter:image" content="{{ asset('image/foto1.jpg') }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">

    {{-- Fonts & Assets --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireStyles

    {{-- JSON-LD Structured Data: Organization --}}
    @php
    $baseUrl = url('/');
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => 'SMA Negeri 1 Tapung Hulu',
        'alternateName' => 'SMAN 1 Tapung Hulu',
        'url' => $baseUrl,
        'description' => 'Sekolah Menengah Atas Negeri 1 Tapung Hulu — Terakreditasi A. Membangun generasi berprestasi, mandiri, berakar pada kearifan lokal, dan berwawasan lingkungan global.',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Jl. Kampung Lama No. 10',
            'addressLocality' => 'Kasikan',
            'addressRegion' => 'Riau',
            'addressCountry' => 'ID',
            'postalCode' => '28464',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '0.5787',
            'longitude' => '100.9534',
        ],
        'telephone' => '085271991329',
        'email' => 'sma.negeri1.tapunghulu@gmail.com',
        'sameAs' => [$baseUrl],
    ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
</head>
<body class="bg-primary min-h-screen">
    {{ $slot }}
    @livewireScripts

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: { popup: 'swal-toast-custom' },
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (data) => {
                const d = Array.isArray(data) ? data[0] : data;
                Toast.fire({ icon: d.type || 'success', title: d.message || 'Berhasil!' });
            });
        });
    </script>

    <style>
        .swal-toast-custom {
            font-family: 'Inter', sans-serif !important;
            font-size: 0.875rem !important;
            border-radius: 0.75rem !important;
        }
    </style>
</body>
</html>
