<?php

namespace App\Support\Billing\Invoice;

class InvoicePayload
{
    public function __construct(
        public readonly string $number,
        public readonly string $issueDate,
        public readonly string $currency,
        public readonly InvoiceParty $supplier,
        public readonly InvoiceParty $customer,
        public readonly string $description,
        public readonly int $netAmount,
        public readonly float $vatRate = 19.0,
    ) {
    }

    public function vatAmount(): float
    {
        return round($this->netAmount * ($this->vatRate / 100), 2);
    }

    public function payable(): float
    {
        return round($this->netAmount + $this->vatAmount(), 2);
    }
}
