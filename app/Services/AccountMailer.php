<?php

namespace App\Services;

use App\Notifications\AccountMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AccountMailer
{
    public function send(string $email, AccountMail $notification): bool
    {
        if (! app()->environment('testing') && ! $this->safeTransport(config('mail.default'))) {
            Log::warning('Account mail requires a delivery transport; log/array mailers are not allowed.');

            return false;
        }
        try {
            Notification::route('mail', $email)->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Account notification delivery failed.', ['kind' => $notification->kind, 'exception_type' => $exception::class]);

            return false;
        }
    }

    private function safeTransport(string $name, array $seen = []): bool
    {
        if (in_array($name, $seen, true)) {
            return false;
        }
        $mailer = config('mail.mailers.'.$name, []);
        $transport = $mailer['transport'] ?? $name;
        if (in_array($transport, ['log', 'array'])) {
            return false;
        }
        if (in_array($transport, ['failover', 'roundrobin'])) {
            foreach ($mailer['mailers'] ?? [] as $child) {
                if (! $this->safeTransport($child, [...$seen, $name])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function url(string $route, array $parameters = []): string
    {
        // Do not derive recovery destinations from an untrusted HTTP Host header.
        return rtrim(config('app.url'), '/').route($route, $parameters, false);
    }
}
