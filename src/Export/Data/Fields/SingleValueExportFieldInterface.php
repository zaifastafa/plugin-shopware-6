<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Helpers\Serializable;

interface SingleValueExportFieldInterface extends ExportFieldInterface
{
    public function parse(): Serializable;
}
