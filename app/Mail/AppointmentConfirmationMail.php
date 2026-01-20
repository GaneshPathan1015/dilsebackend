<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;

class AppointmentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $details;

    public function __construct(Appointment $appointment, array $details = [])
    {
        $this->appointment = $appointment;
        $this->details = $details;
    }

    public function build()
    {
        $subject = 'Appointment Confirmation - Dilse Jewels';
        
        if (isset($this->details['type'])) {
            switch ($this->details['type']) {
                case 'update':
                    $subject = 'Appointment Updated - Dilse Jewels';
                    break;
                case 'status_update':
                    $subject = 'Appointment Status Updated - Dilse Jewels';
                    break;
                case 'reminder':
                    $subject = 'Appointment Reminder - Dilse Jewels';
                    break;
            }
        }

        return $this->subject($subject)
                    ->view('emails.appointment-confirmation')
                    ->with([
                        'appointment' => $this->appointment,
                        'details' => $this->details,
                    ]);
    }
}