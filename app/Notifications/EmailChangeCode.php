<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The code that proves someone can read the new email they asked for. */
class EmailChangeCode extends Notification
{
    public function __construct(public string $code, public int $minutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your FitPass email verification code')
            ->line('Use this code to confirm the new email on your FitPass account:')
            ->line("**{$this->code}**")
            ->line("The code expires in {$this->minutes} minutes.")
            ->line('If you did not ask to change your email, you can ignore this message.');
    }
}
