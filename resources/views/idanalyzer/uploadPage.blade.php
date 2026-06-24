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
    <h2>Please verify your identity</h2>
    <form action="{{ url('/idanalyzer/session') }}" method="POST" enctype="multipart/form-data" class="">
        @csrf
        <button type="submit" class=" bg-black px-4 py-2 text-white text-md rounded-md">Verify your identity</button>
    </form>
    </div>
</body>
</html>
