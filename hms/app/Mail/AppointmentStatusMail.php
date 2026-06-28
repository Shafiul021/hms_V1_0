<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    /**
     * Get the message envelope.
     * Subject is dynamically built from the appointment status value.
     */
    public function envelope(): Envelope
    {
        $status = ucfirst($this->appointment->status->value ?? 'Updated');

        return new Envelope(
            subject: "Your Appointment has been {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointment-status',
            with: [
                'appointment' => $this->appointment,
                'patient'     => $this->appointment->patient,
                'doctor'      => $this->appointment->doctor,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
