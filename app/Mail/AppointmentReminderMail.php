<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;

class AppointmentReminderMail extends Mailable
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
        return $this->subject('Reminder: Your Appointment Tomorrow - ' . config('app.name'))
                    ->view('emails.appointment-reminder')
                    ->with([
                        'appointment' => $this->appointment,
                        'details' => $this->details,
                    ]);
    }
}