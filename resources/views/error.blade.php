<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Error Page</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body>
    <div class="w-screen h-screen flex items-center justify-center">
    @if(session('error'))
            <div class="text-center">
                <p class="text-lg font-semibold text-red-500">{{ session('error') }}</p>
            </div>
        @endif
    </div>
    </body>
</html>