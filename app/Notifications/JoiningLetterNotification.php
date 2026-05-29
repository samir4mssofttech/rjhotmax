<?php

namespace App\Notifications;

use App\Models\Applicant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoiningLetterNotification extends Notification
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

        // 1. Generate the PDF from the Blade view
        $pdf = Pdf::loadView('pdfs.joining_letter', [
            'applicant' => $record
        ]);

        // 2. Save the PDF to a temporary path so it can be attached
        $fileName = 'Joining_Letter_' . $record->applicant_code . '.pdf';
        $tempPath = storage_path('app/temp/' . $fileName);
        
        // Ensure the temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $pdf->save($tempPath);

        // 3. Create the Email
        return (new MailMessage)
            ->subject('Your Joining Letter - ' . config('app.name'))
            ->greeting('Hello ' . $record->applicant_name . ',')
            ->line('We are delighted to offer you a position at ' . config('app.name') . '.')
            ->line('Please find your official Joining/Offer Letter attached to this email as a PDF.')
            ->line('Kindly review the details and let us know if you accept the offer.')
            ->line('We look forward to having you on our team!')
            ->attach($tempPath, [
                'as' => 'Joining_Letter.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
