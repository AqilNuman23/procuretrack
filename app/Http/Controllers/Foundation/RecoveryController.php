<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\RecoveryRequest;
use App\Rules\ApplicationPassword;
use App\Services\RecoveryService;
use App\Support\Foundation\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RecoveryController extends Controller
{
    public function forgot()
    {
        return view('foundation.auth', ['form' => 'forgot', 'title' => 'Forgot your password?', 'description' => 'Use your Worker ID and current recovery email. Initially, this is your invitation email.']);
    }

    public function assistance()
    {
        return view('foundation.auth', ['form' => 'assistance', 'title' => 'Lost access to your recovery email?', 'description' => 'Your PIC or administrator will verify your identity. An approved reset link goes to your company mailbox.']);
    }

    public function resetForm(string $token, RecoveryService $recovery)
    {
        return view('foundation.auth', ['form' => 'reset', 'token' => $token, 'replacement' => $recovery->replacementEmail($token), 'title' => 'Choose a new password.', 'description' => 'This changes your application password, not your mailbox password.']);
    }

    public function verifyForm(string $token)
    {
        return view('foundation.auth', ['form' => 'verify', 'token' => $token, 'title' => 'Verify your recovery email.', 'description' => 'Confirm that you can access the replacement recovery address.']);
    }

    public function send(Request $request, RecoveryService $recovery)
    {
        $data = $request->validate(['worker_id' => ['required', 'string', 'max:50'], 'email' => ['required', 'email', 'max:254']]);
        $recovery->requestReset($data['worker_id'], $data['email']);

        return back()->with('status', RecoveryService::GENERIC);
    }

    public function requestAssistance(Request $request, RecoveryService $recovery)
    {
        $data = $request->validate(['worker_id' => ['required', 'string', 'max:50'], 'new_email' => ['nullable', 'email', 'max:254']]);
        $recovery->requestAssistance($data['worker_id'], $data['new_email'] ?? null);

        return back()->with('status', 'If the account is eligible, a verification request will be sent to its PIC or administrator. If neither mailbox is accessible, contact your administrator through an established channel.');
    }

    public function reset(Request $request, RecoveryService $recovery)
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64'], 'password' => ['required', 'string', new ApplicationPassword, 'confirmed'], 'confirm_recovery_email' => ['nullable', 'boolean']]);
        $recovery->reset($data['token'], $data['password'], (bool) ($data['confirm_recovery_email'] ?? false));
        $this->endSession($request);

        return redirect()->route('login')->with('status', 'Password changed. Sign in again. If you confirmed a replacement recovery email, check that inbox for its verification link.');
    }

    public function verify(Request $request, RecoveryService $recovery)
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $recovery->verifyEmail($data['token']);
        $this->endSession($request);

        return redirect()->route('login')->with('status', 'Recovery email verified and updated. Your original invitation email remains in history. Sign in again.');
    }

    public function index(Request $request, RecoveryService $recovery)
    {
        $actor = $request->user();
        Gate::authorize('manage-recovery');
        $table = new TableQuery($request);
        $status = $table->value('status', ['pending', 'approved', 'rejected', 'completed']);
        $query = $recovery->reviewableRequests($actor)
            ->when($status !== '', fn ($q) => $q->where('RQStatus', $status))
            ->with('user')->orderByDesc('RQID');
        $requests = $table->paginate($table->search($query, ['RQNo'], ['user' => ['USName', 'USWorkerID']]));

        return view('foundation.recovery', ['title' => 'Recovery requests', 'description' => 'Verify identity before approving a company-mailbox reset.', 'requests' => $requests]);
    }

    public function show(RecoveryRequest $recoveryRequest)
    {
        Gate::authorize('review', $recoveryRequest);

        return view('foundation.recovery-detail', ['title' => 'Review recovery request', 'description' => 'Worker ID alone does not prove identity.', 'item' => $recoveryRequest]);
    }

    public function review(Request $request, RecoveryRequest $recoveryRequest, RecoveryService $recovery)
    {
        Gate::authorize('review', $recoveryRequest);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'method' => ['required_if:decision,approve', 'nullable', 'in:trusted_contact,in_person'],
            'verified' => ['required_if:decision,approve', 'accepted_if:decision,approve'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $recovery->review($request->user(), $recoveryRequest, $data['decision'], $data['method'] ?? null, $data['reason']);

        return redirect()->route('recovery.index')->with('status', $data['decision'] === 'approve' ? 'Approved. A single-use reset link was requested for the employee’s company mailbox.' : 'Recovery request rejected.');
    }

    public function resend(Request $request, RecoveryRequest $recoveryRequest, RecoveryService $recovery)
    {
        Gate::authorize('review', $recoveryRequest);
        $recovery->resend($request->user(), $recoveryRequest);

        return back()->with('status', 'A new company-mailbox reset link was requested. Earlier reset links are invalid.');
    }

    private function endSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
