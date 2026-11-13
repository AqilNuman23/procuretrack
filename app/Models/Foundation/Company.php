<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends FoundationModel
{
    public const PREFIX = 'CO';

    public const AUDIT_FIELDS = ['COCode', 'COName', 'COTimezone', 'COCurrency'];

    protected $table = 'MSCompany';

    protected $fillable = ['COCode', 'COName', 'COTimezone', 'COCurrency'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
        ]);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'TM_CONo', 'CONo');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'US_CONo', 'CONo');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class, 'CS_CONo', 'CONo');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'DP_CONo', 'CONo');
    }
}
