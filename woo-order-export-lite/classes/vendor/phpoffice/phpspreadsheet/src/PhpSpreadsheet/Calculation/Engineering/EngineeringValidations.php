<?php

namespace WOE\PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use WOE\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use WOE\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class EngineeringValidations
{
    public static function validateFloat(mixed $value): float
    {
        if (!is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (float) $value;
    }

    public static function validateInt(mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) floor((float) $value);
    }
}
