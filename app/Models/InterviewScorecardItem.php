<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewScorecardItem extends Model
{
    protected $guarded = [];

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(InterviewScorecard::class, 'interview_scorecard_id');
    }
}
