<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Sort;
use FINDOLOGIC\Export\Helpers\Serializable;

class SortField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return Sort
     */
    public function parse(): Serializable
    {
        $sort = new Sort();
        $sort->setValue(0);

        return $sort;
    }
}
