<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamPic extends FoundationModel
{
    public const PREFIX = 'TP';

    public const AUDIT_FIELDS = ['TP_TMNo', 'TP_USNo'];

    protected $table = 'TRTeamPIC';

    protected $fillable = ['TP_TMNo', 'TP_USNo'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
        ]);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'TP_TMNo', 'TMNo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'TP_USNo', 'USNo');
    }
}
