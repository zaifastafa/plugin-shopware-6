<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Fields;

use FINDOLOGIC\Export\Helpers\Serializable;

interface SingleValueExportFieldInterface extends ExportFieldInterface
{
    public function parse(): Serializable;
}
