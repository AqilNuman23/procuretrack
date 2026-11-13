<?php

namespace App\Support\Foundation;

use App\Models\Foundation\FoundationError;
use App\Models\Foundation\FoundationModel;
use App\Models\Foundation\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class FoundationWriter
{
    public function create(string $class, array $attributes, ?User $actor = null): FoundationModel
    {
        return $this->transaction($actor, fn (FoundationChanges $changes) => $changes->create($class, $attributes));
    }

    public function update(FoundationModel $record, array $attributes, ?User $actor = null): FoundationModel
    {
        return $this->transaction($actor, fn (FoundationChanges $changes) => $changes->update($record, $attributes));
    }

    public function transaction(?User $actor, Closure $work): mixed
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('FoundationWriter must own the outer transaction so failures can be recorded after rollback.');
        }
        $changes = null;
        try {
            return DB::transaction(function () use ($actor, $work, &$changes) {
                $changes = new FoundationChanges($actor);

                return $work($changes);
            }, 3);
        } catch (Throwable $exception) {
            if ($changes?->attempt) {
                $this->recordError($changes->attempt, $actor, $exception);
            }
            throw $exception;
        }
    }

    private function recordError(array $attempt, ?User $actor, Throwable $exception): void
    {
        $correlation = (string) Str::uuid();
        try {
            // This starts a new transaction AFTER all business writes and history roll back.
            DB::transaction(function () use ($attempt, $actor, $exception, $correlation) {
                $actorNo = $actor && User::query()->whereKey($actor->getKey())->exists() ? $actor->getKey() : null;
                $error = new FoundationError([
                    'FECorrelation' => $correlation,
                    'FEOperation' => $attempt['operation'], 'FETable' => $attempt['table'], 'FERecordNo' => $attempt['record'],
                    'FEException' => $exception::class,
                    'FECode' => $exception instanceof QueryException ? 'DATABASE_WRITE_FAILED' : 'WRITE_ABORTED',
                    'FEMessage' => 'The foundation write was rolled back. No business change was committed.',
                ]);
                $error->FECB = $actorNo;
                $error->FEMB = $actorNo;
                $error->saveOrFail();
            });
        } catch (Throwable) {
            // A disconnected database cannot store its own failure. Never log SQL/bindings or raw messages.
            Log::error('Foundation error could not be stored.', ['correlation' => $correlation, 'exception_type' => $exception::class]);
        }
    }
}
