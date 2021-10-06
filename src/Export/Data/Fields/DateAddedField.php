<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\DateAdded;
use FINDOLOGIC\Export\Helpers\Serializable;

class DateAddedField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return DateAdded
     */
    public function parse(): Serializable
    {
        $value = $this->product->getCreatedAt();

        $dateAdded = new DateAdded();
        if ($value) {
            $dateAdded->setDateValue($value);
        }

        return $dateAdded;
    }
}
