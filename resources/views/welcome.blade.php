<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('favicon_new.ico') }}">

        <title>Aktly Lite</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
        @endif
        @vite('resources/js/app.js')

        <!-- Font awesome cdn-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Supademo SDK -->
        <script src="https://script.supademo.com/supademo.js"></script>
    </head>
    <body class="w-screen h-screen">
        {{-- @php
            session()->flush();
        @endphp --}}
        <div id="app"></div>
        {{-- <div class="bg-green-200 w-2/4 h-[200px] p-4 flex items-center justify-center">
            <form action="{{route('getEnterpriseByNumber')}}" method="post">
                @csrf
                <input type="number" name="enterprise_number" placeholder="enterprise number" class="px-2 appearance-none" required>
                <select name="langue" id="">
                    <option value="fr">fr</option>
                    <option value="nl">nl</option>
                </select>
                <input type="submit" value="submit" class=" bg-green-700 px-2 text-white rounded-sm cursor-pointer">
            </form>
        </div> --}}
    </body>
</html>
