<?php

namespace WOE\PhpOffice\PhpSpreadsheet;

interface IComparable
{
    /**
     * Get hash code.
     *
     * @return string Hash code
     */
    public function getHashCode(): string;
}
