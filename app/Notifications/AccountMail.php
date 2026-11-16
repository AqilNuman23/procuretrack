<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountMail extends Notification
{
    public function __construct(public readonly string $kind, public readonly ?string $url = null, public readonly ?string $companyEmail = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $body, $action] = match ($this->kind) {
            'invitation' => ['You are invited to ProcureTrack', 'Accept this invitation within two days. Administrator review and account setup are required before access is granted.', 'Accept invitation'],
            'activation' => ['Activate your ProcureTrack account', 'Your application was approved and your company mailbox is ready. Set your private application password within 24 hours.', 'Set up your account'],
            'reset' => ['Reset your ProcureTrack password', 'A password reset was requested. This link expires in 30 minutes and can be used once.', 'Choose a new password'],
            'review' => ['An employee needs account recovery', 'A recovery request needs identity verification through an established contact or in person. Do not approve based on Worker ID alone.', 'Review recovery requests'],
            'verify' => ['Verify your replacement recovery email', 'Confirm access to this address before it becomes your recovery email. This link expires in 30 minutes.', 'Verify recovery email'],
            default => ['Your ProcureTrack account was updated', 'Your password or recovery email has changed. Contact your administrator immediately if you did not authorize this.', null],
        };
        $message = (new MailMessage)->subject($subject)->greeting('Hello,')->line($body);
        if ($this->kind === 'activation' && $this->companyEmail) {
            $message->line('Your assigned company mailbox is '.$this->companyEmail.'. Ask your administrator for the provider sign-in address and secure mailbox access instructions. Application login uses your Worker ID.');
        }
        if ($action && $this->url) {
            $message->action($action, $this->url);
        }

        return $message->line('Never share your password or account recovery links.');
    }
}
