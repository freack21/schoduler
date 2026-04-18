<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Penjadwalan SMA' }} - SMA Negeri 1 Tapung Hulu</title>
    <meta name="description" content="Sistem Penjadwalan Mata Pelajaran SMA Negeri 1 Tapung Hulu">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireStyles
</head>
<body class="bg-bg-light min-h-screen">
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
