<?php

namespace App\Models\Foundation;

use App\Support\Foundation\NumberAllocator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

abstract class FoundationModel extends Model
{
    public const PREFIX = '';

    public const AUDIT_FIELDS = [];

    public $incrementing = false;

    protected $keyType = 'string';

    public function getKeyName(): string
    {
        return static::PREFIX.'No';
    }

    public function getCreatedAtColumn(): string
    {
        return static::PREFIX.'CD';
    }

    public function getUpdatedAtColumn(): string
    {
        return static::PREFIX.'MD';
    }

    protected function casts(): array
    {
        return [static::PREFIX.'ID' => 'integer', $this->getCreatedAtColumn() => 'datetime', $this->getUpdatedAtColumn() => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if ($record->getKey() !== null) {
                throw new LogicException('Foundation No values are allocated by the database counter.');
            }
            $record->setAttribute($record->getKeyName(), app(NumberAllocator::class)->next(static::PREFIX));
        });
        static::updating(function (self $record) {
            foreach ([static::PREFIX.'ID', $record->getKeyName(), static::PREFIX.'CB', static::PREFIX.'CD'] as $column) {
                if ($record->isDirty($column)) {
                    throw new LogicException('Foundation identity and creation metadata are immutable.');
                }
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, static::PREFIX.'CB', 'USNo');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, static::PREFIX.'MB', 'USNo');
    }
}
