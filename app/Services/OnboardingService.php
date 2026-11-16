<?php

namespace App\Services;

use App\Models\Foundation\Activation;
use App\Models\Foundation\Company;
use App\Models\Foundation\Invitation;
use App\Models\Foundation\Mailbox;
use App\Models\Foundation\Review;
use App\Models\Foundation\Team;
use App\Models\Foundation\User;
use App\Notifications\AccountMail;
use App\Rules\ApplicationPassword;
use App\Services\Access\ModuleAccess;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingService
{
    public function __construct(private FoundationWriter $writer, private ModuleAccess $access, private AccountMailer $mailer) {}

    private function actor(User $actor, bool $administrator = false): User
    {
        Company::query()->whereKey($actor->US_CONo)->lockForUpdate()->firstOrFail();
        $current = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
        abort_unless($this->access->eligible($current) && $current->USAuthVersion === $actor->USAuthVersion, 403);
        abort_unless($this->access->administrator($current) || (! $administrator && $this->access->pic($current)), 403);

        return $current;
    }

    public function invite(User $actor, array $data): Invitation
    {
        [$invitation, $token] = $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $data) {
            $actor = $this->actor($actor);
            $data = Validator::make($data, ['team' => ['required', 'string', 'max:10'], 'name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:254'], 'worker_id' => ['required', 'string', 'max:50']])->validate();
            $team = Team::query()->whereKey($data['team'])->where('TM_CONo', $actor->US_CONo)->where('TMActive', true)->firstOrFail();
            abort_unless($this->access->administrator($actor) || $actor->US_TMNo === $team->getKey(), 403);
            $email = strtolower(trim($data['email']));
            $worker = trim($data['worker_id']);
            $duplicateUser = User::query()->where('US_CONo', $actor->US_CONo)->where(fn ($q) => $q->where('USWorkerID', $worker)->orWhere('USContactEmail', $email)->orWhere('USRecoveryEmail', $email))->exists();
            $duplicateInvite = Invitation::query()->whereHas('team', fn ($q) => $q->where('TM_CONo', $actor->US_CONo))
                ->where(fn ($q) => $q->where('IVWorkerID', $worker)->orWhere('IVEmail', $email))
                ->where(fn ($q) => $q->whereIn('IVStatus', ['awaiting_review', 'approved'])->orWhere(fn ($q) => $q->where('IVStatus', 'invited')->where('IVExpiresAt', '>', now())))->exists();
            if ($duplicateUser || $duplicateInvite) {
                throw ValidationException::withMessages(['worker_id' => 'An account or current invitation already uses these details. Open the existing record or resend its invitation.']);
            }
            $token = Str::random(64);
            $invitation = $changes->create(Invitation::class, ['IV_TMNo' => $team->getKey(), 'IVInviter_USNo' => $actor->getKey(), 'IVName' => $data['name'], 'IVEmail' => $email, 'IVWorkerID' => $worker, 'IVStatus' => 'invited', 'IVTokenHash' => hash('sha256', $token), 'IVExpiresAt' => now()->addDays(2)]);

            return [$invitation, $token];
        });
        $this->sendInvitation($actor, $invitation, $token);

        return $invitation;
    }

    public function resend(User $actor, Invitation $invitation, bool $revoke = false): void
    {
        [$invitation, $token] = $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $invitation, $revoke) {
            $actor = $this->actor($actor);
            $invitation = Invitation::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();
            $this->invitationScope($actor, $invitation);
            if (! in_array($invitation->IVStatus, ['invited', 'expired'])) {
                throw ValidationException::withMessages(['invitation' => 'Only an unaccepted invitation can be resent or revoked.']);
            }
            if (! $revoke) {
                $existingAccount = User::query()->where('US_CONo', $actor->US_CONo)->where(fn ($q) => $q->where('USWorkerID', $invitation->IVWorkerID)->orWhere('USContactEmail', $invitation->IVEmail)->orWhere('USRecoveryEmail', $invitation->IVEmail))->exists();
                $otherInvitation = Invitation::query()->whereKeyNot($invitation->getKey())->whereHas('team', fn ($q) => $q->where('TM_CONo', $actor->US_CONo))
                    ->where(fn ($q) => $q->where('IVWorkerID', $invitation->IVWorkerID)->orWhere('IVEmail', $invitation->IVEmail))
                    ->where(fn ($q) => $q->whereIn('IVStatus', ['awaiting_review', 'approved'])->orWhere(fn ($q) => $q->where('IVStatus', 'invited')->where('IVExpiresAt', '>', now())))->exists();
                if ($existingAccount || $otherInvitation) {
                    throw ValidationException::withMessages(['invitation' => 'Another account or current invitation uses these details. Use that record instead.']);
                }
            }
            $token = $revoke ? null : Str::random(64);
            $updated = $changes->update($invitation, ['IVStatus' => $revoke ? 'revoked' : 'invited', 'IVTokenHash' => $token ? hash('sha256', $token) : null, 'IVExpiresAt' => now()->addDays(2), 'IVRevokedAt' => $revoke ? now() : null]);

            return [$updated, $token];
        });
        if ($token) {
            $this->sendInvitation($actor, $invitation, $token);
        }
    }

    private function invitationScope(User $actor, Invitation $invitation): void
    {
        abort_unless($invitation->team->TM_CONo === $actor->US_CONo && $invitation->team->TMActive
            && ($this->access->administrator($actor) || $actor->US_TMNo === $invitation->IV_TMNo), 403);
    }

    private function sendInvitation(User $actor, Invitation $invitation, string $token): void
    {
        if (! $this->mailer->send($invitation->IVEmail, new AccountMail('invitation', $this->mailer->url('invitation.open', ['token' => $token])))) {
            $this->writer->transaction($actor, function ($changes) use ($invitation, $token) {
                $locked = Invitation::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();
                if ($locked->IVTokenHash === hash('sha256', $token) && $locked->IVStatus === 'invited') {
                    $changes->update($locked, ['IVTokenHash' => null]);
                }
            });
            throw ValidationException::withMessages(['invitation' => 'Invitation saved, but email delivery failed. Resend from the invitation list.']);
        }
    }

    public function accept(string $token): void
    {
        $this->writer->transaction(null, function ($changes) use ($token) {
            $invitation = Invitation::query()->where('IVTokenHash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $invitation || $invitation->IVStatus !== 'invited' || $invitation->IVExpiresAt->isPast() || ! $invitation->team->TMActive) {
                throw ValidationException::withMessages(['token' => 'This invitation is invalid, expired, or already used. Ask your PIC to resend it.']);
            }
            $changes->update($invitation, ['IVStatus' => 'awaiting_review', 'IVAcceptedAt' => now(), 'IVTokenHash' => null]);
        });
    }

    public function review(User $actor, Invitation $invitation, string $decision, ?string $reason): void
    {
        $this->writer->transaction($actor, function ($changes) use ($actor, $invitation, $decision, $reason) {
            $actor = $this->actor($actor, true);
            $invitation = Invitation::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();
            $this->invitationScope($actor, $invitation);
            abort_if($invitation->IV_USNo === $actor->getKey() || $invitation->IVWorkerID === $actor->USWorkerID, 403);
            Validator::make(['decision' => $decision, 'reason' => $reason], ['decision' => ['required', 'in:approved,rejected'], 'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000']])->validate();
            if ($invitation->IVStatus !== 'awaiting_review') {
                throw ValidationException::withMessages(['invitation' => 'This invitation is no longer awaiting review.']);
            }
            $values = ['IVStatus' => $decision];
            if ($decision === 'approved') {
                if (User::query()->where('US_CONo', $actor->US_CONo)->where(fn ($q) => $q->where('USWorkerID', $invitation->IVWorkerID)->orWhere('USContactEmail', $invitation->IVEmail)->orWhere('USRecoveryEmail', $invitation->IVEmail))->exists()) {
                    throw ValidationException::withMessages(['invitation' => 'An account now uses these details. Resolve the duplicate before approval.']);
                }
                $user = $changes->create(User::class, ['US_CONo' => $actor->US_CONo, 'US_TMNo' => $invitation->IV_TMNo, 'USWorkerID' => $invitation->IVWorkerID, 'USName' => $invitation->IVName,
                    'USContactEmail' => $invitation->IVEmail, 'USRecoveryEmail' => $invitation->IVEmail, 'USStatus' => 'pending_activation', 'USApprovedAt' => now()]);
                $values['IV_USNo'] = $user->getKey();
                $changes->create(Mailbox::class, ['MX_USNo' => $user->getKey(), 'MX_IVNo' => $invitation->getKey(), 'MXProvider' => 'manual', 'MXIsMock' => false, 'MXStatus' => 'pending', 'MXIdempotencyKey' => Str::uuid()->toString()]);
            }
            $changes->update($invitation, $values);
            $changes->create(Review::class, ['RV_IVNo' => $invitation->getKey(), 'RV_USNo' => $actor->getKey(), 'RVDecision' => $decision, 'RVReason' => $reason, 'RVDecidedAt' => now()]);
        });
    }

    public function mailbox(User $actor, User $target, array $data): void
    {
        $data = Validator::make($data, ['email' => ['required', 'email', 'max:254'], 'verified' => ['accepted']])->validate();
        $this->writer->transaction($actor, function ($changes) use ($actor, $target, $data) {
            $actor = $this->actor($actor, true);
            $target = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($target->US_CONo === $actor->US_CONo, 403);
            $mailbox = $target->mailbox()->lockForUpdate()->firstOrFail();
            if ($target->USStatus !== 'pending_activation' || ! $target->USApprovedAt) {
                throw ValidationException::withMessages(['mailbox' => 'Only approved accounts awaiting activation may use initial mailbox verification.']);
            }
            $email = strtolower(trim($data['email']));
            if (Mailbox::query()->where('MXEmail', $email)->whereKeyNot($mailbox->getKey())->exists()
                || User::query()->where('US_CONo', $actor->US_CONo)->where('USEmail', $email)->whereKeyNot($target->getKey())->exists()) {
                throw ValidationException::withMessages(['email' => 'This company mailbox is already assigned.']);
            }
            $changes->update($mailbox, ['MXEmail' => $email, 'MXProvider' => 'manual', 'MXIsMock' => false, 'MXStatus' => 'ready', 'MXReadyAt' => now(), 'MXFailureCode' => null, 'MXAttempts' => $mailbox->MXAttempts + 1]);
            $changes->update($target, ['USEmail' => $email]);
        });
        $this->sendActivation($actor, $target);
    }

    public function sendActivation(User $actor, User $target): void
    {
        [$target, $activation, $token] = $this->writer->transaction($actor, function ($changes) use ($actor, $target) {
            $actor = $this->actor($actor, true);
            $target = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($target->US_CONo === $actor->US_CONo, 403);
            if ($target->USStatus !== 'pending_activation' || ! $target->USApprovedAt || $target->mailbox?->MXStatus !== 'ready' || $target->mailbox->MXIsMock) {
                throw ValidationException::withMessages(['activation' => 'Approval and a manually verified real mailbox are required.']);
            }
            foreach ($target->activations()->whereNull('AVUsedAt')->whereNull('AVRevokedAt')->get() as $previous) {
                $changes->update($previous, ['AVRevokedAt' => now()]);
            }
            $token = Str::random(64);
            $activation = $changes->create(Activation::class, ['AV_USNo' => $target->getKey(), 'AVTokenHash' => hash('sha256', $token), 'AVExpiresAt' => now()->addHours(24)]);

            return [$target, $activation, $token];
        });
        if (! $this->mailer->send($target->USContactEmail, new AccountMail('activation', $this->mailer->url('activation.open', ['token' => $token]), $target->USEmail))) {
            $this->writer->update($activation, ['AVRevokedAt' => now()], $actor);
            throw ValidationException::withMessages(['activation' => 'Mailbox verified, but activation email delivery failed. Retry activation delivery from the mailbox page.']);
        }
    }

    public function activate(string $token, string $worker, string $password): void
    {
        Validator::make(['password' => $password], ['password' => ['required', 'string', new ApplicationPassword]])->validate();
        $this->writer->transaction(null, function ($changes) use ($token, $worker, $password) {
            $activation = Activation::query()->where('AVTokenHash', hash('sha256', $token))->first();
            if (! $activation) {
                throw ValidationException::withMessages(['token' => 'This activation link is invalid or no longer available.']);
            }
            $user = User::query()->whereKey($activation->AV_USNo)->lockForUpdate()->firstOrFail();
            $activation = Activation::query()->whereKey($activation->getKey())->lockForUpdate()->firstOrFail();
            if ($activation->AVUsedAt || $activation->AVRevokedAt || $activation->AVExpiresAt->isPast() || $user->USStatus !== 'pending_activation'
                || $user->USWorkerID !== $worker || ! $user->USApprovedAt || $user->mailbox?->MXStatus !== 'ready' || $user->mailbox->MXIsMock || ! $user->team?->TMActive) {
                throw ValidationException::withMessages(['token' => 'Activation could not be completed. Check your Worker ID or ask your administrator for a new link.']);
            }
            $changes->update($user, ['USPassword' => $password, 'USStatus' => 'active', 'USActivatedAt' => now(), 'USAuthVersion' => $user->USAuthVersion + 1]);
            $changes->update($activation, ['AVUsedAt' => now()]);
        });
    }
}
