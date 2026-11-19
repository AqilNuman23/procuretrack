<?php

namespace App\Services;

use App\Models\Foundation\EmailHistory;
use App\Models\Foundation\EmailVerification;
use App\Models\Foundation\PasswordReset;
use App\Models\Foundation\RecoveryRequest;
use App\Models\Foundation\User;
use App\Notifications\AccountMail;
use App\Rules\ApplicationPassword;
use App\Services\Access\ModuleAccess;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecoveryService
{
    public const GENERIC = 'If the details match an eligible account, instructions will be sent.';

    public function __construct(private FoundationWriter $writer, private ModuleAccess $access, private AccountMailer $mailer) {}

    public function lookup(string $worker): ?User
    {
        return User::query()->whereHas('company', fn ($q) => $q->where('COCode', config('foundation.company_code')))
            ->where('USWorkerID', strtoupper(trim($worker)))->first();
    }

    public function requestReset(string $worker, string $email): void
    {
        $user = $this->lookup($worker);
        if (! $user || ! $this->access->eligible($user) || ! $this->emailMatches($user->USRecoveryEmail, $email)) {
            return;
        }
        $delivery = $this->writer->transaction(null, function (FoundationChanges $changes) use ($user, $email) {
            $user = $this->lockUser($user);
            if (! $this->access->eligible($user) || ! $this->emailMatches($user->USRecoveryEmail, $email)) {
                return null;
            }

            return $this->issue($changes, $user, null, $user->USRecoveryEmail);
        });
        if ($delivery) {
            $this->deliverReset($delivery);
        }
    }

    public function requestAssistance(string $worker, ?string $newEmail): void
    {
        $user = $this->lookup($worker);
        if (! $user || ! $this->access->eligible($user)) {
            return;
        }
        $created = $this->writer->transaction(null, function (FoundationChanges $changes) use ($user, $newEmail) {
            $user = $this->lockUser($user);
            if (! $this->access->eligible($user)) {
                return null;
            }
            if ($user->recoveryRequests()->whereIn('RQStatus', ['pending', 'approved'])->where('RQAuthVersion', $user->USAuthVersion)
                ->where('RQRecoveryVersion', $user->USRecoveryVersion)->where('RQExpiresAt', '>', now())->exists()) {
                return null;
            }

            return $changes->create(RecoveryRequest::class, [
                'RQ_USNo' => $user->getKey(), 'RQStatus' => 'pending', 'RQNewEmail' => $newEmail ? strtolower(trim($newEmail)) : null,
                'RQAuthVersion' => $user->USAuthVersion, 'RQRecoveryVersion' => $user->USRecoveryVersion, 'RQExpiresAt' => now()->addDays(2),
            ]);
        });
        if (! $created) {
            return;
        }
        $reviewers = User::query()->where('US_CONo', $user->US_CONo)->get()->filter(fn ($reviewer) => $this->canReview($reviewer, $user));
        $pics = $reviewers->filter(fn ($reviewer) => $this->access->pic($reviewer) && ! $this->access->administrator($reviewer));
        foreach (($pics->isNotEmpty() ? $pics : $reviewers) as $reviewer) {
            $this->mailer->send($reviewer->USEmail, new AccountMail('review', $this->mailer->url('recovery.index')));
        }
    }

    public function reviewableRequests(User $reviewer): Builder
    {
        $query = RecoveryRequest::query()->whereHas('user', fn (Builder $users) => $users
            ->where('US_CONo', $reviewer->US_CONo)->where('USNo', '!=', $reviewer->getKey()));

        if (! $this->access->eligible($reviewer)) {
            return $query->whereRaw('1 = 0');
        }
        if ($this->access->administrator($reviewer)) {
            return $query;
        }
        if (! $this->access->pic($reviewer)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('user', fn (Builder $users) => $users->where('US_TMNo', $reviewer->US_TMNo)
            ->whereDoesntHave('roleAssignments.role', fn (Builder $roles) => $roles->where('RLCode', 'administrator'))
            ->whereNot(fn (Builder $pics) => $pics
                ->whereHas('picAssignments', fn (Builder $assignments) => $assignments->where('TP_TMNo', $reviewer->US_TMNo))
                ->whereHas('roleAssignments.role', fn (Builder $roles) => $roles->where('RLCode', 'team_pic'))));
    }

    public function canReview(User $reviewer, User $target): bool
    {
        if (! $this->access->eligible($reviewer) || $reviewer->getKey() === $target->getKey() || $reviewer->US_CONo !== $target->US_CONo) {
            return false;
        }

        return $this->access->administrator($reviewer) || ($this->access->pic($reviewer) && $reviewer->US_TMNo === $target->US_TMNo
            && ! $this->access->administrator($target) && ! $this->access->pic($target));
    }

    public function review(User $reviewer, RecoveryRequest $request, string $decision, ?string $method, string $reason): void
    {
        $delivery = $this->writer->transaction($reviewer, function (FoundationChanges $changes) use ($reviewer, $request, $decision, $method, $reason) {
            $reviewer = $this->lockUser($reviewer);
            $user = $this->lockUser($request->user);
            $request = RecoveryRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->canReview($reviewer, $user), 403);
            $this->validRequest($request, $user);
            abort_unless($request->RQStatus === 'pending' && in_array($decision, ['approve', 'reject']), 422, 'This request has already been reviewed.');
            abort_unless(trim($reason) !== '' && ($decision === 'reject' || in_array($method, ['trusted_contact', 'in_person'])), 422);
            $request = $changes->update($request, [
                'RQStatus' => $decision === 'approve' ? 'approved' : 'rejected', 'RQReviewer_USNo' => $reviewer->getKey(),
                'RQVerificationMethod' => $decision === 'approve' ? $method : null, 'RQReason' => $reason, 'RQReviewedAt' => now(),
            ]);

            return $decision === 'approve' ? $this->issue($changes, $user, $request, $user->USEmail) : null;
        });
        if ($delivery) {
            $this->deliverReset($delivery);
        }
    }

    public function resend(User $reviewer, RecoveryRequest $request): void
    {
        $delivery = $this->writer->transaction($reviewer, function (FoundationChanges $changes) use ($reviewer, $request) {
            $reviewer = $this->lockUser($reviewer);
            $user = $this->lockUser($request->user);
            $request = RecoveryRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->canReview($reviewer, $user), 403);
            $this->validRequest($request, $user);
            abort_unless($request->RQStatus === 'approved' && $request->reviewer && $this->canReview($request->reviewer, $user), 422);

            return $this->issue($changes, $user, $request, $user->USEmail);
        });
        $this->deliverReset($delivery);
    }

    private function issue(FoundationChanges $changes, User $user, ?RecoveryRequest $request, string $destination): array
    {
        foreach (PasswordReset::query()->where('PS_USNo', $user->getKey())->whereNull('PSUsedAt')->whereNull('PSRevokedAt')->get() as $old) {
            $changes->update($old, ['PSRevokedAt' => now()]);
        }
        $token = Str::random(64);
        $reset = $changes->create(PasswordReset::class, [
            'PS_USNo' => $user->getKey(), 'PS_RQNo' => $request?->getKey(), 'PSTokenHash' => hash('sha256', $token),
            'PSAuthVersion' => $user->USAuthVersion, 'PSRecoveryVersion' => $user->USRecoveryVersion, 'PSExpiresAt' => now()->addMinutes(30),
        ]);

        return compact('reset', 'token', 'destination');
    }

    private function deliverReset(array $delivery): void
    {
        if (! $this->mailer->send($delivery['destination'], new AccountMail('reset', $this->mailer->url('password.reset', ['token' => $delivery['token']])))) {
            $this->writer->update($delivery['reset'], ['PSRevokedAt' => now()]);
        }
    }

    public function replacementEmail(string $token): ?string
    {
        $reset = PasswordReset::query()->where('PSTokenHash', hash('sha256', $token))->first();
        if (! $reset || $reset->PSUsedAt || $reset->PSRevokedAt || $reset->PSExpiresAt->isPast()) {
            return null;
        }
        $user = $reset->user;
        if (! $this->access->eligible($user) || $reset->PSAuthVersion !== $user->USAuthVersion || $reset->PSRecoveryVersion !== $user->USRecoveryVersion) {
            return null;
        }

        return $reset->request?->RQStatus === 'approved' ? $reset->request->RQNewEmail : null;
    }

    public function reset(string $token, string $password, bool $confirmReplacement = false): void
    {
        Validator::make(['password' => $password], ['password' => ['required', 'string', new ApplicationPassword]])->validate();
        $initial = PasswordReset::query()->where('PSTokenHash', hash('sha256', $token))->first();
        if (! $initial) {
            $this->invalid();
        }
        $result = $this->writer->transaction(null, function (FoundationChanges $changes) use ($initial, $password, $confirmReplacement) {
            $user = $this->lockUser($initial->user);
            $reset = PasswordReset::query()->whereKey($initial->getKey())->lockForUpdate()->firstOrFail();
            if (! $this->access->eligible($user) || $reset->PSUsedAt || $reset->PSRevokedAt || $reset->PSExpiresAt->isPast()
                || $reset->PSAuthVersion !== $user->USAuthVersion || $reset->PSRecoveryVersion !== $user->USRecoveryVersion) {
                $this->invalid();
            }
            $request = $reset->request;
            if ($request) {
                $request = RecoveryRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
                $this->validRequest($request, $user);
                if ($request->RQStatus !== 'approved' || ! $request->reviewer || ! $this->canReview($request->reviewer, $user)) {
                    $this->invalid();
                }
            }
            $user = $changes->update($user, ['USPassword' => $password, 'USAuthVersion' => $user->USAuthVersion + 1]);
            $changes->update($reset, ['PSUsedAt' => now()]);
            $verificationToken = null;
            if ($request) {
                $changes->update($request, ['RQStatus' => 'completed']);
                if ($confirmReplacement && $request->RQNewEmail && ! $this->emailMatches($request->RQNewEmail, $user->USRecoveryEmail)) {
                    $verificationToken = Str::random(64);
                    $changes->create(EmailVerification::class, [
                        'EV_USNo' => $user->getKey(), 'EV_RQNo' => $request->getKey(), 'EVTokenHash' => hash('sha256', $verificationToken),
                        'EVNewEmail' => $request->RQNewEmail, 'EVAuthVersion' => $user->USAuthVersion, 'EVRecoveryVersion' => $user->USRecoveryVersion,
                        'EVExpiresAt' => now()->addMinutes(30),
                    ]);
                }
            }

            return compact('user', 'request', 'verificationToken');
        });
        $user = $result['user'];
        $this->mailer->send($user->USEmail, new AccountMail('changed'));
        if ($result['verificationToken']) {
            $this->mailer->send($result['request']->RQNewEmail,
                new AccountMail('verify', $this->mailer->url('recovery.verify', ['token' => $result['verificationToken']])));
        }
    }

    public function verifyEmail(string $token): void
    {
        $initial = EmailVerification::query()->where('EVTokenHash', hash('sha256', $token))->first();
        if (! $initial) {
            $this->invalid();
        }
        $result = $this->writer->transaction(null, function (FoundationChanges $changes) use ($initial) {
            $user = $this->lockUser($initial->user);
            $verification = EmailVerification::query()->whereKey($initial->getKey())->lockForUpdate()->firstOrFail();
            if (! $this->access->eligible($user) || $verification->EVUsedAt || $verification->EVExpiresAt->isPast()
                || $verification->EVAuthVersion !== $user->USAuthVersion || $verification->EVRecoveryVersion !== $user->USRecoveryVersion) {
                $this->invalid();
            }
            $request = $verification->request;
            if ($request->RQStatus !== 'completed' || ! $request->reviewer || ! $this->canReview($request->reviewer, $user)) {
                $this->invalid();
            }
            $old = $user->USRecoveryEmail;
            $changes->create(EmailHistory::class, [
                'EH_USNo' => $user->getKey(), 'EH_RQNo' => $request->getKey(), 'EHApprover_USNo' => $request->RQReviewer_USNo,
                'EHOldEmail' => $old, 'EHNewEmail' => $verification->EVNewEmail, 'EHVerifiedAt' => now(),
            ]);
            $changes->update($user, ['USRecoveryEmail' => $verification->EVNewEmail,
                'USRecoveryVersion' => $user->USRecoveryVersion + 1, 'USAuthVersion' => $user->USAuthVersion + 1]);
            $changes->update($verification, ['EVUsedAt' => now()]);

            return [$old, $user->USEmail];
        });
        foreach (array_unique($result) as $destination) {
            if ($destination) {
                $this->mailer->send($destination, new AccountMail('changed'));
            }
        }
    }

    private function validRequest(RecoveryRequest $request, User $user): void
    {
        if (! $this->access->eligible($user) || $request->RQExpiresAt->isPast() || $request->RQAuthVersion !== $user->USAuthVersion
            || $request->RQRecoveryVersion !== $user->USRecoveryVersion) {
            $this->invalid();
        }
    }

    private function lockUser(User $user): User
    {
        return User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
    }

    private function emailMatches(?string $a, ?string $b): bool
    {
        return $a && $b && hash_equals(strtolower(trim($a)), strtolower(trim($b)));
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['token' => 'This recovery link or request is invalid, expired, or already used. Start a new recovery request.']);
    }
}
