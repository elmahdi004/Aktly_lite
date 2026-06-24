<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;

class TwilioService
{
    protected $twilio;

    public function __construct()
    {
         $this->twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
    }

    private function maskSid($sid)
    {
        if (!$sid || !is_string($sid)) {
            return null;
        }
        $prefix = substr($sid, 0, 6);
        $suffix = substr($sid, -4);
        return $prefix . '...' . $suffix;
    }

    public function sendSms($to, $message)
    {
        $phoneNumber = $to;
        $from = env('TWILIO_PHONE_NUMBER');
        \Log::info('Twilio sendSms attempt', [
            'sid_masked' => $this->maskSid(env('TWILIO_SID')),
            'from' => $from,
            'to' => $phoneNumber,
            'message_length' => is_string($message) ? strlen($message) : null,
        ]);

        try {
            return $this->twilio->messages->create($phoneNumber, [
               'from' => $from,
               'body' => $message
            ]);
        } catch (RestException $e) {
            \Log::error('Twilio sendSms failed', [
                'status' => $e->getStatusCode(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'sid_masked' => $this->maskSid(env('TWILIO_SID')),
                'from' => $from,
                'to' => $phoneNumber,
            ]);
            throw $e;
        }
    }
}
