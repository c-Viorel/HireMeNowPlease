<?php

namespace App\Support\Onboarding;

use App\Models\Application;
use Illuminate\Support\Carbon;

class CimDraftGenerator
{
    public function forApplication(Application $application): string
    {
        $application->loadMissing(['candidate', 'job.company']);

        $candidate = $application->candidate?->name ?? 'Angajat';
        $employer = $application->job?->company?->name ?? 'Angajator';
        $role = $application->job?->title ?? 'Functie';
        $salary = $application->job?->salary_min
            ? number_format((int) $application->job->salary_min, 0, ',', '.').' lei brut'
            : 'conform negocierii';
        $start = now()->addWeek()->translatedFormat('d.m.Y');
        $probationEnd = now()->addWeek()->addDays(90)->translatedFormat('d.m.Y');

        return <<<CIM
        CONTRACT INDIVIDUAL DE MUNCA (proiect)

        Incheiat astazi, {$this->today()}, intre:
        Angajator: {$employer}
        si
        Salariat: {$candidate}

        1. Obiectul contractului: prestarea muncii in functia de {$role}.
        2. Durata contractului: nedeterminata, cu perioada de proba pana la data de {$probationEnd}.
        3. Data inceperii activitatii: {$start}.
        4. Salariul de baza brut lunar: {$salary}.
        5. Timp de munca: 8 ore/zi, 40 ore/saptamana.
        6. Concediu de odihna: minim 20 zile lucratoare/an.
        7. Drepturi si obligatii conform Codului Muncii (Legea 53/2003).

        Contractul se inregistreaza in REGES Online cel tarziu in ziua lucratoare
        anterioara inceperii activitatii.

        Angajator,                          Salariat,
        {$employer}                         {$candidate}
        CIM;
    }

    private function today(): string
    {
        return Carbon::now()->translatedFormat('d.m.Y');
    }
}
