<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TwilioService;

class SmsController extends Controller
{
    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    public function sendSms()
    {
        $phoneNumber = "+18777804236"; 
        $link = "https://example.com/verify"; 
        $message = "Click here to verify your account: $link";

        $this->twilio->sendSms($phoneNumber, $message);

        return response()->json(['message' => 'SMS sent successfully!']);
    }
}
