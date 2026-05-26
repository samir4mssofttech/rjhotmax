<?php

namespace App\Notifications;

use App\Models\Applicant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\File;

class OfferLetterNotification extends Notification
{
    public function __construct(public Applicant $applicant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $record = $this->applicant;

        // 1. Generate the PDF using the offer_letter blade view
        $pdf = Pdf::loadView('pdfs.offer_letter', [
            'applicant' => $record
        ]);

        // 2. Handle temporary file storage
        $fileName = 'Offer_Letter_' . ($record->applicant_code ?? $record->id) . '.pdf';
        $tempDir = storage_path('app/temp');
        
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/' . $fileName;
        $pdf->save($tempPath);

        // 3. Construct the Email
        return (new MailMessage)
            ->subject('Job Offer: ' . config('app.name'))
            ->greeting('Hello ' . $record->applicant_name . ',')
            ->line('We are delighted to offer you a position at ' . config('app.name') . '!')
            ->line('Please find your official Offer Letter attached to this email as a PDF.')
            ->line('Kindly review the terms and conditions and let us know your acceptance.')
            ->line('We look forward to having you join our team!')
            ->attach($tempPath, [
                'as' => $fileName,
                'mime' => 'application/pdf',
            ]);
    }
}