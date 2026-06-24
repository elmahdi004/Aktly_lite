<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotificationCustom extends Notification
{
    use Queueable;

    public $token;
    public $locale;

    public function __construct($token, $locale)
    {
        $this->token = $token;
        $this->locale = $locale;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
       app()->setLocale($this->locale ?? 'fr');

        $baseUrl = rtrim(config('app.url'), '/');
        $resetUrl = $baseUrl . '/reset-password/' . $this->token
            . '?email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject(__('auth.reset_password_subject'))
            ->view('emails.reset_password', [
                'url' => $resetUrl,
                'name' => $notifiable->name,
                'logo' => asset('logo_final.png'),
            ]);
        }
}
