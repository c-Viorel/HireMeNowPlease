<?php

namespace App\Support;

use App\Models\Application;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationSubmissions
{
    /**
     * @param array<string, mixed> $attributes
     * @param (callable(array<string, mixed>): Application)|null $creator
     */
    public static function create(array $attributes, ?callable $creator = null): Application
    {
        try {
            return $creator
                ? $creator($attributes)
                : Application::create($attributes);
        } catch (QueryException $exception) {
            if (! self::isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'job' => 'You have already applied to this job.',
            ]);
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
