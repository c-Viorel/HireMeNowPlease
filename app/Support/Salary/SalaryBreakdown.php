<?php

namespace App\Support\Salary;

class SalaryBreakdown
{
    public function __construct(
        public readonly int $gross,
        public readonly int $net,
        public readonly int $cas,
        public readonly int $cass,
        public readonly int $incomeTax,
        public readonly int $employerCost,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'gross' => $this->gross,
            'net' => $this->net,
            'cas' => $this->cas,
            'cass' => $this->cass,
            'income_tax' => $this->incomeTax,
            'employer_cost' => $this->employerCost,
        ];
    }
}
