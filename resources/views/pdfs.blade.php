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
    {{-- @php
    $data = session('veriff_active_person');
    dd($data)
@endphp --}}
    <div class="w-screen h-screen flex justify-start flex-col items-center mt-10">
        <h1>Your Pdfs</h1>
        <a href="{{route('formulaire1')}}" class="my-2 hover:text-green-700">Formulaire 1</a>
        <a href="{{route('formulaire2')}}" class="my-2 hover:text-green-700">Formulaire 2</a>
    </div>
</body>
</html>