<?php

namespace App\Exceptions;

use RuntimeException;

class DhlInvoiceParserException extends RuntimeException
{
    public static function unreadablePdf(): self
    {
        return new self('PDF is onleesbaar of bevat geen tekst.');
    }

    public static function missingWeek(): self
    {
        return new self('Weeknummer en jaar zijn niet gevonden in de factuur.');
    }

    public static function missingDrivers(): self
    {
        return new self('Geen ondersteund DHL-factuurformaat gevonden.');
    }
}
