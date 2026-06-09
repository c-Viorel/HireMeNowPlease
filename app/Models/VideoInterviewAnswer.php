<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoInterviewAnswer extends Model
{
    protected $guarded = [];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(VideoInterview::class, 'video_interview_id');
    }
}
