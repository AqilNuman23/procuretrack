<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\Invitation;
use App\Models\Foundation\Mailbox;
use App\Models\Foundation\Team;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Services\OnboardingService;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index(Request $request, ModuleAccess $access)
    {
        $actor = $request->user();
        abort_unless($access->administrator($actor) || $access->pic($actor), 403);
        $filters = $request->validate(['status' => ['nullable', 'in:invited,awaiting_review,approved,rejected,expired,revoked']]);
        $query = Invitation::query()->whereHas('team', fn ($q) => $q->where('TM_CONo', $actor->US_CONo))
            ->when(! $access->administrator($actor), fn ($q) => $q->where('IV_TMNo', $actor->US_TMNo));
        if (($filters['status'] ?? null) === 'expired') {
            $query->where(fn ($q) => $q->where('IVStatus', 'expired')->orWhere(fn ($q) => $q->where('IVStatus', 'invited')->where('IVExpiresAt', '<=', now())));
        } elseif ($filters['status'] ?? null) {
            $query->where('IVStatus', $filters['status'])->when($filters['status'] === 'invited', fn ($q) => $q->where('IVExpiresAt', '>', now()));
        }
        $teams = Team::query()->where('TM_CONo', $actor->US_CONo)->where('TMActive', true)
            ->when(! $access->administrator($actor), fn ($q) => $q->whereKey($actor->US_TMNo))->orderBy('TMName')->get();

        return view('foundation.invitations', ['title' => 'Invitations & reviews', 'description' => 'Invite members and follow their account setup.',
            'invitations' => $query->with('team')->orderByDesc('IVCD')->paginate(15)->withQueryString(), 'teams' => $teams]);
    }

    public function store(Request $request, OnboardingService $onboarding)
    {
        $onboarding->invite($request->user(), $request->all());

        return back()->with('status', 'Invitation sent. It expires in two days.');
    }

    public function action(Request $request, Invitation $invitation, OnboardingService $onboarding)
    {
        $data = $request->validate(['action' => ['required', 'in:resend,revoke,approved,rejected'], 'reason' => ['nullable', 'string', 'max:2000']]);
        if (in_array($data['action'], ['approved', 'rejected'])) {
            $onboarding->review($request->user(), $invitation, $data['action'], $data['reason'] ?? null);
        } else {
            $onboarding->resend($request->user(), $invitation, $data['action'] === 'revoke');
        }

        return back()->with('status', 'Invitation updated.');
    }

    public function mailboxes(Request $request, ModuleAccess $access)
    {
        abort_unless($access->administrator($request->user()), 403);

        return view('foundation.mailboxes', ['title' => 'Mailbox readiness', 'description' => 'Verify an existing real mailbox before issuing activation instructions.',
            'mailboxes' => Mailbox::query()->whereHas('user', fn ($q) => $q->where('US_CONo', $request->user()->US_CONo))->with('user')->orderByDesc('MXCD')->paginate(15)]);
    }

    public function mailbox(Request $request, User $user, OnboardingService $onboarding)
    {
        $data = $request->validate(['action' => ['required', 'in:verify,resend']]);
        if ($data['action'] === 'verify') {
            $onboarding->mailbox($request->user(), $user, $request->all());
        } else {
            $onboarding->sendActivation($request->user(), $user);
        }

        return back()->with('status', 'Activation instructions sent to the original invitation email.');
    }

    public function openInvitation(string $token)
    {
        return view('foundation.onboarding', ['title' => 'Accept your invitation', 'authPage' => true, 'step' => 'invitation', 'token' => $token]);
    }

    public function accept(Request $request, OnboardingService $onboarding)
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $onboarding->accept($data['token']);

        return redirect()->route('login')->with('status', 'Invitation accepted. Your administrator will review your application. Activation instructions follow once your company mailbox is verified.');
    }

    public function openActivation(string $token)
    {
        return view('foundation.onboarding', ['title' => 'Set up your account', 'authPage' => true, 'step' => 'activation', 'token' => $token]);
    }

    public function activate(Request $request, OnboardingService $onboarding)
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64'], 'worker_id' => ['required', 'string', 'max:50'], 'password' => ['required', 'string', 'confirmed']]);
        $onboarding->activate($data['token'], $data['worker_id'], $data['password']);

        return redirect()->route('login')->with('status', 'Account activated. Sign in with your Worker ID and private password.');
    }
}
