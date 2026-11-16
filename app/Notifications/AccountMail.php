<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountMail extends Notification
{
    public function __construct(public readonly string $kind, public readonly ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $body, $action] = match ($this->kind) {
            'reset' => ['Reset your ProcureTrack password', 'A password reset was requested. This link expires in 30 minutes and can be used once.', 'Choose a new password'],
            'review' => ['An employee needs account recovery', 'A recovery request needs identity verification through an established contact or in person. Do not approve based on Worker ID alone.', 'Review recovery requests'],
            'verify' => ['Verify your replacement recovery email', 'Confirm access to this address before it becomes your recovery email. This link expires in 30 minutes.', 'Verify recovery email'],
            default => ['Your ProcureTrack account was updated', 'Your password or recovery email has changed. Contact your administrator immediately if you did not authorize this.', null],
        };
        $message = (new MailMessage)->subject($subject)->greeting('Hello,')->line($body);
        if ($action && $this->url) {
            $message->action($action, $this->url);
        }

        return $message->line('Never share your password or account recovery links.');
    }
}
