<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('sort_order');
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }
}
