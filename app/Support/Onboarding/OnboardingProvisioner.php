<?php

namespace App\Support\Onboarding;

use App\Models\Application;
use App\Models\OnboardingChecklist;

class OnboardingProvisioner
{
    /**
     * Default ITM / REGES onboarding tasks for a new Romanian hire.
     *
     * @var array<int, string>
     */
    private const DEFAULT_TASKS = [
        'Semneaza Contractul Individual de Munca (CIM) inainte de prima zi',
        'Inregistreaza contractul in REGES Online (fost Revisal)',
        'Colecteaza actele angajatului (CI, fisa medicala, diplome)',
        'Efectueaza instructajul de Securitate si Sanatate in Munca (SSM)',
        'Stabileste perioada de proba si obiectivele primelor 90 de zile',
        'Configureaza accesul la salarizare si beneficii',
    ];

    public function provision(Application $application): OnboardingChecklist
    {
        $checklist = OnboardingChecklist::firstOrCreate(
            ['application_id' => $application->id],
            [
                'status' => 'in_progress',
                'start_date' => now()->addWeek()->toDateString(),
                'probation_end_date' => now()->addWeek()->addDays(90)->toDateString(),
            ]
        );

        if ($checklist->wasRecentlyCreated) {
            foreach (self::DEFAULT_TASKS as $index => $label) {
                $checklist->tasks()->create([
                    'label' => $label,
                    'is_done' => false,
                    'sort_order' => $index,
                ]);
            }
        }

        return $checklist;
    }
}
