<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/** The reset email for app members, whose link opens the member reset form. */
class MemberResetPassword extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('member.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
