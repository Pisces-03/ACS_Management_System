<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var string */
    private string $code;

    /** @var int */
    private int $expiresMinutes;

    public function __construct(string $code, int $expiresMinutes)
    {
        $this->code = $code;
        $this->expiresMinutes = $expiresMinutes;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Two-Factor Authentication Code')
            ->greeting('Hello ' . ($notifiable->first_name ?? ''))
            ->line('Use the following code to complete your sign-in:')
            ->line('')
            ->line('Code: ' . $this->code)
            ->line('')
            ->line('This code expires in ' . $this->expiresMinutes . ' minutes.')
            ->line('If you did not attempt to sign in, please secure your account immediately.');
    }
}
























