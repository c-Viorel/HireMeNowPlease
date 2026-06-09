<?php

namespace App\Support\Salary;

/**
 * Computes Romanian net/gross salary using the 2026 contribution scheme.
 *
 * Employee contributions on gross:
 *   - CAS (pension)      25%
 *   - CASS (health)      10%
 *   - Income tax         10% on (gross - CAS - CASS - personal deduction)
 *
 * Employer contribution on gross:
 *   - CAM (work insurance) 2.25%
 */
class RomanianSalaryCalculator
{
    private const CAS_RATE = 0.25;

    private const CASS_RATE = 0.10;

    private const INCOME_TAX_RATE = 0.10;

    private const EMPLOYER_CAM_RATE = 0.0225;

    public function grossToNet(int $gross, int $personalDeduction = 0): SalaryBreakdown
    {
        $gross = max(0, $gross);
        $cas = (int) round($gross * self::CAS_RATE);
        $cass = (int) round($gross * self::CASS_RATE);
        $taxable = max(0, $gross - $cas - $cass - $personalDeduction);
        $incomeTax = (int) round($taxable * self::INCOME_TAX_RATE);
        $net = $gross - $cas - $cass - $incomeTax;
        $employerCost = $gross + (int) round($gross * self::EMPLOYER_CAM_RATE);

        return new SalaryBreakdown(
            gross: $gross,
            net: $net,
            cas: $cas,
            cass: $cass,
            incomeTax: $incomeTax,
            employerCost: $employerCost,
        );
    }

    public function netToGross(int $net, int $personalDeduction = 0): int
    {
        // net = gross * (1 - CAS - CASS) - INCOME_TAX_RATE * (gross * (1 - CAS - CASS) - deduction)
        // net = gross * factor * (1 - tax) + tax * deduction
        $afterContributions = 1 - self::CAS_RATE - self::CASS_RATE;
        $coefficient = $afterContributions * (1 - self::INCOME_TAX_RATE);

        if ($coefficient <= 0) {
            return $net;
        }

        $gross = ($net - (self::INCOME_TAX_RATE * $personalDeduction)) / $coefficient;

        return max(0, (int) round($gross));
    }

    public function convertToNet(int $amount, bool $isGross, int $personalDeduction = 0): SalaryBreakdown
    {
        $gross = $isGross ? $amount : $this->netToGross($amount, $personalDeduction);

        return $this->grossToNet($gross, $personalDeduction);
    }
}
