<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Shortlist;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class Shortlists
{
    /**
     * @param (callable(array<string, mixed>): Shortlist)|null $creator
     */
    public static function createForApplication(Application $application, ?callable $creator = null): void
    {
        $application->loadMissing('job');

        $attributes = [
            'company_id' => $application->job->company_id,
            'job_id' => $application->job_id,
            'candidate_id' => $application->candidate_id,
        ];

        try {
            $creator
                ? $creator($attributes)
                : Shortlist::firstOrCreate($attributes);
        } catch (QueryException $exception) {
            if (! self::isUniqueConstraintViolation($exception)) {
                throw $exception;
            }
        }
    }

    private static function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = Str::lower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, ['1062', '1555', '2067'], true)
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }
}
