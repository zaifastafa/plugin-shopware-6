<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Helpers\Serializable;

interface MultiValueExportFieldInterface extends ExportFieldInterface
{
    /**
     * @return Serializable[]
     */
    public function parse(): array;
}
