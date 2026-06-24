<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationUrl;
    public $langue_user;

    public function __construct($verificationUrl, $langue_user = 'fr')
    {
        $this->verificationUrl = $verificationUrl;
        $this->langue_user = $langue_user;
    }

    public function build()
    {
        return $this->subject('Vérification de votre adresse email')
            ->view('emails.verify_email')
            ->with(['verificationUrl' => $this->verificationUrl, 'langue_user' => $this->langue_user]);
    }
}
