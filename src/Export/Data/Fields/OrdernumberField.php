<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Content\Product\ProductEntity;

class OrdernumberField implements MultiValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return Ordernumber[]
     */
    public function parse(): array
    {
        $ordernumbers = $this->parseOrdernumbers($this->product);
        foreach ($this->product->getChildren() as $productEntity) {
            $ordernumbers = array_merge($ordernumbers, $this->parseOrdernumbers($productEntity));
        }

        return $ordernumbers;
    }

    /**
     * @return Ordernumber[]
     */
    protected function parseOrdernumbers(ProductEntity $product): array
    {
        /** @var Ordernumber[] $ordernumbers */
        $ordernumbers = [];

        if (!Utils::isEmpty($product->getProductNumber())) {
            $ordernumbers[] = new Ordernumber($product->getProductNumber());
        }
        if (!Utils::isEmpty($product->getEan())) {
            $ordernumbers[] = new Ordernumber($product->getEan());
        }
        if (!Utils::isEmpty($product->getManufacturerNumber())) {
            $ordernumbers[] = new Ordernumber($product->getManufacturerNumber());
        }

        return $ordernumbers;
    }
}
