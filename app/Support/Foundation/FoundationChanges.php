<?php

namespace App\Support\Foundation;

use App\Models\Foundation\FoundationError;
use App\Models\Foundation\FoundationLog;
use App\Models\Foundation\FoundationModel;
use App\Models\Foundation\NumberSequence;
use App\Models\Foundation\User;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;

/** Only construct through FoundationWriter::transaction(). */
class FoundationChanges
{
    public ?array $attempt = null;

    public function __construct(private readonly ?User $actor) {}

    /** @param class-string<FoundationModel> $class */
    public function create(string $class, array $attributes): FoundationModel
    {
        if (! in_array($class, config('foundation.models'), true)) {
            throw new InvalidArgumentException('Unknown foundation model.');
        }
        $record = new $class;
        $this->prepare($record, 'created');
        $this->fill($record, $attributes);
        $prefix = $record::PREFIX;
        $record->setAttribute($prefix.'CB', $this->actor?->getKey());
        $record->setAttribute($prefix.'MB', $this->actor?->getKey());
        $this->persist($record);
        $record->refresh();
        $this->audit($record, 'created', null);

        return $record;
    }

    public function update(FoundationModel $record, array $attributes): FoundationModel
    {
        $this->prepare($record, 'updated');
        // Re-read under a lock so history records the actual pre-change state.
        $record = $record->newQuery()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
        $before = $this->snapshot($record);
        $this->fill($record, $attributes);
        if (! $record->isDirty()) {
            return $record;
        }
        $record->setAttribute($record::PREFIX.'MB', $this->actor?->getKey());
        $this->persist($record);
        $record->refresh();
        $this->audit($record, 'updated', $before);

        return $record;
    }

    private function prepare(FoundationModel $record, string $operation): void
    {
        if ($record instanceof FoundationLog || $record instanceof FoundationError || $record instanceof NumberSequence) {
            throw new InvalidArgumentException('History, errors, and counters are managed internally.');
        }
        $this->attempt = ['table' => $record->getTable(), 'record' => $record->getKey(), 'operation' => $operation];
    }

    private function fill(FoundationModel $record, array $attributes): void
    {
        if (array_diff(array_keys($attributes), $record->getFillable())) {
            throw new InvalidArgumentException('Only declared foundation business fields may be changed.');
        }
        $record->fill($attributes);
    }

    private function persist(FoundationModel $record): void
    {
        if (! $record->save()) {
            throw new RuntimeException('Foundation model refused the write.');
        }
    }

    private function snapshot(FoundationModel $record): array
    {
        // An allowlist, not a blacklist: secrets cannot be copied by adding a new column.
        return Arr::only($record->attributesToArray(), $record::AUDIT_FIELDS);
    }

    private function audit(FoundationModel $record, string $operation, ?array $before): void
    {
        $log = new FoundationLog([
            'FLAction' => $operation, 'FLTable' => $record->getTable(), 'FLRecordNo' => $record->getKey(),
            'FLBefore' => $before, 'FLAfter' => $this->snapshot($record),
        ]);
        $log->FLCB = $this->actor?->getKey();
        $log->FLMB = $this->actor?->getKey();
        $this->persist($log);
    }
}
