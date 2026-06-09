<?php

use App\Support\Billing\EFacturaXmlBuilder;
use App\Support\Billing\Invoice\InvoiceParty;
use App\Support\Billing\Invoice\InvoicePayload;

it('builds an ANAF UBL invoice xml with vat and totals', function () {
    $payload = new InvoicePayload(
        number: 'HM-2026-0001',
        issueDate: '2026-06-09',
        currency: 'RON',
        supplier: new InvoiceParty(name: 'HireMe SRL', taxId: 'RO12345678', address: 'Bucuresti', country: 'RO'),
        customer: new InvoiceParty(name: 'Encom SRL', taxId: 'RO87654321', address: 'Cluj-Napoca', country: 'RO'),
        description: 'Abonament Pro - 1 luna',
        netAmount: 10000,
        vatRate: 19.0,
    );

    $xml = (new EFacturaXmlBuilder())->build($payload);

    expect($xml)->toContain('<cbc:ID>HM-2026-0001</cbc:ID>')
        ->toContain('RO12345678')
        ->toContain('RO87654321')
        ->toContain('<cbc:Percent>19</cbc:Percent>')
        ->toContain('<cbc:TaxAmount currencyID="RON">1900.00</cbc:TaxAmount>')
        ->toContain('<cbc:PayableAmount currencyID="RON">11900.00</cbc:PayableAmount>');

    $document = new DOMDocument();
    expect($document->loadXML($xml))->toBeTrue();
});
