<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the old address, so a change nobody asked for does not go unnoticed. */
class EmailChanged extends Notification
{
    public function __construct(public string $username, public string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('The email on your FitPass account was changed')
            ->line("The email for the FitPass account \"{$this->username}\" was changed to {$this->newEmail}.")
            ->line('If you did not make this change, tell the gym owner straight away.');
    }
}
