<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $surname;
    public $activity_name;
    public $details;
    public $note;
    public $description;
    public $prenotare;
    public $coupon_code;
    public $fileName;
    public $expiration_date;
    public $title;
    public $telefono;
    public $location_name;
    public $mailTemplate; // 👈 aggiunto
    /**
     * Create a new message instance.
     */
    public function __construct($dataEmail, $mailTemplate = 'emails.mail')
    {
        $this->name = $dataEmail['name'];
        $this->surname = $dataEmail['surname'];
        $this->activity_name = $dataEmail['activity_name'];
        $this->details = $dataEmail['details'];
        $this->note = $dataEmail['note'];
        $this->description = $dataEmail['description'];
        $this->prenotare = $dataEmail['prenotare'];
        $this->coupon_code = $dataEmail['coupon_code'];
        $this->expiration_date = $dataEmail['expiration_date'];
        $this->title = $dataEmail['title'];
        $this->fileName = $dataEmail['fileName'];
        $this->telefono = $dataEmail['telefono'];
        $this->location_name = $dataEmail['location_name'];
        $this->mailTemplate = $mailTemplate; // 👈 salva il template
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->mailTemplate,
        );
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [

            Attachment::fromPath(public_path('vouchers/' . $this->fileName))

        ];
    }
}
