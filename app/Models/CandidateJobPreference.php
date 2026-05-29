<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateJobPreference extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'preferred_workplace_types' => 'array',
            'preferred_employment_types' => 'array',
        ];
    }

    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
