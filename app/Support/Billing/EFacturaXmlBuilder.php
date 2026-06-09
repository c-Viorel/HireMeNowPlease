<?php

namespace App\Support\Billing;

use App\Support\Billing\Invoice\InvoicePayload;

/**
 * Builds a UBL 2.1 invoice XML compatible with the Romanian ANAF e-Factura
 * (RO_CIUS) format. Amounts are expressed in the payload currency.
 */
class EFacturaXmlBuilder
{
    public function build(InvoicePayload $payload): string
    {
        $currency = htmlspecialchars($payload->currency, ENT_XML1);
        $net = $this->money($payload->netAmount);
        $vat = $this->money($payload->vatAmount());
        $total = $this->money($payload->payable());
        $percent = $this->percent($payload->vatRate);

        $supplier = $this->party($payload->supplier);
        $customer = $this->party($payload->customer);
        $id = htmlspecialchars($payload->number, ENT_XML1);
        $issueDate = htmlspecialchars($payload->issueDate, ENT_XML1);
        $description = htmlspecialchars($payload->description, ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:efactura.mfinante.ro:CIUS-RO:1.0.1</cbc:CustomizationID>
  <cbc:ID>{$id}</cbc:ID>
  <cbc:IssueDate>{$issueDate}</cbc:IssueDate>
  <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>{$currency}</cbc:DocumentCurrencyCode>
  <cac:AccountingSupplierParty>
{$supplier}
  </cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>
{$customer}
  </cac:AccountingCustomerParty>
  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="{$currency}">{$vat}</cbc:TaxAmount>
    <cac:TaxSubtotal>
      <cbc:TaxableAmount currencyID="{$currency}">{$net}</cbc:TaxableAmount>
      <cbc:TaxAmount currencyID="{$currency}">{$vat}</cbc:TaxAmount>
      <cac:TaxCategory>
        <cbc:ID>S</cbc:ID>
        <cbc:Percent>{$percent}</cbc:Percent>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:TaxCategory>
    </cac:TaxSubtotal>
  </cac:TaxTotal>
  <cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="{$currency}">{$net}</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="{$currency}">{$net}</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="{$currency}">{$total}</cbc:TaxInclusiveAmount>
    <cbc:PayableAmount currencyID="{$currency}">{$total}</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
  <cac:InvoiceLine>
    <cbc:ID>1</cbc:ID>
    <cbc:InvoicedQuantity unitCode="C62">1</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="{$currency}">{$net}</cbc:LineExtensionAmount>
    <cac:Item>
      <cbc:Name>{$description}</cbc:Name>
      <cac:ClassifiedTaxCategory>
        <cbc:ID>S</cbc:ID>
        <cbc:Percent>{$percent}</cbc:Percent>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:ClassifiedTaxCategory>
    </cac:Item>
    <cac:Price>
      <cbc:PriceAmount currencyID="{$currency}">{$net}</cbc:PriceAmount>
    </cac:Price>
  </cac:InvoiceLine>
</Invoice>
XML;
    }

    private function party(\App\Support\Billing\Invoice\InvoiceParty $party): string
    {
        $name = htmlspecialchars($party->name, ENT_XML1);
        $taxId = htmlspecialchars($party->taxId, ENT_XML1);
        $address = htmlspecialchars($party->address, ENT_XML1);
        $country = htmlspecialchars($party->country, ENT_XML1);

        return <<<XML
    <cac:Party>
      <cac:PartyName>
        <cbc:Name>{$name}</cbc:Name>
      </cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>{$address}</cbc:StreetName>
        <cac:Country>
          <cbc:IdentificationCode>{$country}</cbc:IdentificationCode>
        </cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID>{$taxId}</cbc:CompanyID>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:PartyTaxScheme>
      <cac:PartyLegalEntity>
        <cbc:RegistrationName>{$name}</cbc:RegistrationName>
        <cbc:CompanyID>{$taxId}</cbc:CompanyID>
      </cac:PartyLegalEntity>
    </cac:Party>
XML;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function percent(float $rate): string
    {
        $formatted = number_format($rate, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
