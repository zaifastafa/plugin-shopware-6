<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Description;
use FINDOLOGIC\Export\Helpers\Serializable;
use FINDOLOGIC\FinSearch\Utils\Utils;

class DescriptionField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return Description
     */
    public function parse(): Serializable
    {
        $description = new Description();

        $value = $this->getCleanDescription();
        if (Utils::isEmpty($value)) {
            return $description;
        }

        $description->setValue($value);

        return $description;
    }

    protected function getCleanDescription(): ?string
    {
        $rawDescription = $this->product->getTranslation('description');

        return Utils::cleanString($rawDescription);
    }
}
