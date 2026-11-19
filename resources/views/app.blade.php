<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Aplica el tema antes del primer paint para evitar el flash --}}
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('appearance') || 'light';
                    var isDark =
                        stored === 'dark' ||
                        (stored === 'system' &&
                            window.matchMedia(
                                '(prefers-color-scheme: dark)',
                            ).matches);

                    document.documentElement.classList.toggle('dark', isDark);
                    document.documentElement.style.colorScheme = isDark
                        ? 'dark'
                        : 'light';
                } catch (error) {
                    /* localStorage bloqueado: se queda en el tema por defecto */
                }
            })();
        </script>

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/App.tsx'])

        <title>{{ config('app.name', 'Laravel') }}</title>

        @inertiaHead
    </head>

    <body class="font-sans antialiased">
        @inertia
    </body>
</html>