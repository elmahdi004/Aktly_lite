<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="flex justify-center items-center w-screen h-screen flex-col gap-y-6 bg-gray-50 text-gray-900">
        <h1 class="text-2xl font-semibold">Welcome, {{ $user->name }}</h1>
        
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-md text-left space-y-3">
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Joined:</strong> {{ $user->created_at->format('F j, Y') }}</p>
            {{-- Add more profile fields as needed --}}
        </div>

        <a href="{{ route('logout') }}" class="text-blue-600 underline text-sm hover:text-blue-800">
            Logout
        </a>
    </div>
</body>
</html>
