<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\EmailHistory;
use App\Models\Foundation\FoundationError;
use App\Models\Foundation\FoundationLog;
use App\Models\Foundation\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-security-history');
        $company = $request->user()->US_CONo;
        $scope = function ($query, string $prefix) use ($company) {
            $query->whereHas('createdBy', fn ($q) => $q->where('US_CONo', $company))
                ->orWhere(fn ($q) => $q->where($prefix.'Table', 'MSCompany')->where($prefix.'RecordNo', $company))
                ->orWhere(fn ($q) => $q->where($prefix.'Table', 'MSUser')->whereIn($prefix.'RecordNo', User::query()->where('US_CONo', $company)->select('USNo')));
            foreach (['TRRecoveryRequest' => 'RQ', 'TRPasswordReset' => 'PS', 'TREmailVerification' => 'EV', 'TREmailHistory' => 'EH'] as $table => $key) {
                $query->orWhere(fn ($q) => $q->where($prefix.'Table', $table)->whereIn($prefix.'RecordNo', DB::table($table)->whereIn($key.'_USNo', User::query()->where('US_CONo', $company)->select('USNo'))->select($key.'No')));
            }
        };

        return view('foundation.activity', [
            'title' => 'Activity & error history', 'description' => 'Committed changes, failed writes, and restricted recovery-email history.',
            'logs' => FoundationLog::query()->where(fn ($q) => $scope($q, 'FL'))->orderByDesc('FLID')->limit(100)->get(),
            'failures' => FoundationError::query()->where(fn ($q) => $scope($q, 'FE'))->orderByDesc('FEID')->limit(50)->get(),
            'emailHistory' => EmailHistory::query()->whereHas('user', fn ($q) => $q->where('US_CONo', $company))->with(['user', 'approver'])->orderByDesc('EHID')->limit(50)->get(),
        ]);
    }
}
