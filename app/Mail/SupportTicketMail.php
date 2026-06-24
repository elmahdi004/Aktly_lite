<?php
namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function build()
    {
        $email = $this->subject('Nouvelle réclamation : ' . $this->ticket->type)
            ->view('emails.support_ticket')
            ->with(['ticket' => $this->ticket]);
        \Log::info('Building email for ticket ID: ' . $this->ticket->id);
        \Log::info('Ticket details: ' . print_r($this->ticket->toArray(), true));
        if (!empty($this->ticket->file_path)) {
            $attachments = json_decode($this->ticket->file_path, true);
            \Log::info('Attachments: ' . print_r($attachments, true));
            foreach ($attachments as $path) {
                $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
                \Log::info('Attachment path: ' . $path);
                $fullPath = storage_path('app/private/' . $path);

                if (file_exists($fullPath)) {
                    $email->attach($fullPath);
                }
            }
        }

        return $email;
    }
}
