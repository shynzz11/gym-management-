<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ business_config('business_name', config('app.name', 'Laravel')) }}</title>

        @php
            $faviconSetting = trim((string) business_config('favicon_url', ''));
            $defaultFaviconUrl = 'https://api.dicebear.com/7.x/initials/svg?seed=N';
            $favicon = $defaultFaviconUrl;

            if ($faviconSetting !== '') {
                if (str_starts_with($faviconSetting, 'http://') || str_starts_with($faviconSetting, 'https://')) {
                    $favicon = $faviconSetting;
                } else {
                    $relativeFavicon = ltrim($faviconSetting, '/');

                    if (str_starts_with($relativeFavicon, 'storage/')) {
                        $relativeFavicon = substr($relativeFavicon, strlen('storage/'));
                    }

                    if ($relativeFavicon !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($relativeFavicon)) {
                        $favicon = '/storage/' . $relativeFavicon;
                    }
                }
            }
        @endphp
        <link rel="icon" href="{{ $favicon }}" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <style>
            :root {
                /* Dynamic Colors - Logic Controlled by config/branding/colors.php */
                @foreach(config('branding.colors.css_vars', []) as $key => $data)
                {{ $key }}: {{ $data['key'] ? business_config($data['key'], $data['default']) : $data['default'] }};
                @endforeach

                --font-primary: "{{ business_config('font_primary', config('branding.fonts.primary', 'Instrument Sans')) }}", sans-serif;
                --font-secondary: "{{ business_config('font_secondary', config('branding.fonts.secondary', 'Roboto')) }}", sans-serif;
            }
        </style>

        <script>
            // Initialize Dark Mode based on localStorage -> Default to Light
            (function() {
                try {
                    const persistedTheme = localStorage.getItem('theme');
                    
                    if (persistedTheme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        // Default to light if not set or if set to light
                        document.documentElement.classList.remove('dark');
                    }
                } catch (e) {
                    console.error('Theme initialization failed', e);
                }
            })();
        </script>

        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
