<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Analyzer Verification</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="flex justify-center items-center w-screen h-screen flex-col gap-y-10">
        @if (session('error'))
        <h2 class="text-red-700 text-lg text-center">{{session('error')}}</h2>
        @else
        <h2 class="text-red-700 text-lg text-center">Something wrong please try later !</h2>
        @endif
    </div>
</body>
</html>
