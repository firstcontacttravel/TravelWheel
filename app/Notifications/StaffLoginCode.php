<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The code staff enter after their password. Sent straight away rather than
 * through the queue like Filament's own: someone is waiting for it, and a
 * stuck scheduler must not lock staff out of the admin.
 */
class StaffLoginCode extends Notification
{
    public function __construct(
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your TravelWheel admin sign-in code')
            ->line('Your sign-in code is: '.$this->code)
            ->line("It expires in {$this->codeExpiryMinutes} minutes. If you didn't try to sign in, tell the CEO straight away.");
    }
}
