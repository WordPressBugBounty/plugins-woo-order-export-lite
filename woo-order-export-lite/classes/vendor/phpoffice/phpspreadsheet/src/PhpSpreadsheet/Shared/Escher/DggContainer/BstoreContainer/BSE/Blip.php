<?php

namespace WOE\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;

use WOE\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;

class Blip
{
    /**
     * The parent BSE.
     */
    private BSE $parent;

    /**
     * Raw image data.
     */
    private string $data;

    /**
     * Get the raw image data.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Set the raw image data.
     */
    public function setData(string $data): void
    {
        $this->data = $data;
    }

    /**
     * Set parent BSE.
     */
    public function setParent(BSE $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get parent BSE.
     */
    public function getParent(): BSE
    {
        return $this->parent;
    }
}
