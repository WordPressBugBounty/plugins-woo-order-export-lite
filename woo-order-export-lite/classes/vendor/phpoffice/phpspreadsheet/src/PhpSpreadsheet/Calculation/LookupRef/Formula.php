<?php

namespace WOE\PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use WOE\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use WOE\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use WOE\PhpOffice\PhpSpreadsheet\Cell\Cell;

class Formula
{
    /**
     * FORMULATEXT.
     *
     * @param mixed $cellReference The cell to check
     * @param ?Cell $cell The current cell (containing this formula)
     */
    public static function text(mixed $cellReference = '', ?Cell $cell = null): string
    {
        if ($cell === null) {
            return ExcelError::REF();
        }

        $worksheet = null;
        if (1 === preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/i', $cellReference, $matches)) {
            $cellReference = $matches[6] . $matches[7];
            $worksheetName = trim($matches[3], "'");
            $worksheet = (!empty($worksheetName))
                ? $cell->getWorksheet()->getParentOrThrow()->getSheetByName($worksheetName)
                : $cell->getWorksheet();
        }

        if (
            $worksheet === null
            || !$worksheet->cellExists($cellReference)
            || !$worksheet->getCell($cellReference)->isFormula()
        ) {
            return ExcelError::NA();
        }

        return $worksheet->getCell($cellReference)->getValue();
    }
}
