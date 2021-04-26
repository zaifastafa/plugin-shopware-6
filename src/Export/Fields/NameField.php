<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Fields;

use FINDOLOGIC\Export\Data\Name;
use FINDOLOGIC\Export\Helpers\Serializable;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoNameException;
use FINDOLOGIC\FinSearch\Utils\Utils;

class NameField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return Name
     * @throws ProductHasNoNameException
     */
    public function parse(): Serializable
    {
        $name = new Name();
        $productName = $this->product->getTranslation('name');
        if (Utils::isEmpty($productName)) {
            throw new ProductHasNoNameException($this->product);
        }

        $name->setValue(Utils::removeControlCharacters($productName));

        return $name;
    }
}
