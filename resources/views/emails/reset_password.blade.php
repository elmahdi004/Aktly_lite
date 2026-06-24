<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('auth.reset_password_subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h4>{{ __('auth.reset_password_greeting', ['name' => $name]) }}</h4>

    <p>{{ __('auth.reset_password_line1') }}</p>

    <a href="{{ $url }}" style="display:inline-block;padding:10px 20px;background:#3490dc;color:#fff;text-decoration:none;border-radius:5px;">
        {{ __('auth.reset_password_button') }}
    </a>

    <p>{{ __('auth.reset_password_line2') }}</p>


    

    <img src="{{ $logo }}" alt="Logo" width="300">

    <p>{{ __('auth.reset_password_salutation', ['company' => 'Aktly Lite']) }}</p>



</body>
</html>
