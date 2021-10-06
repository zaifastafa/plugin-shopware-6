<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Price;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoPricesException;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Content\Product\ProductEntity;

class PriceField implements MultiValueExportFieldInterface
{
    use ExportContextAware;

    /** @var CustomerGroupEntity[] */
    protected $customerGroups;

    /** @var string */
    protected $shopkey;

    /**
     * @param CustomerGroupEntity[] $customerGroups
     */
    public function setCustomerGroups(array $customerGroups): void
    {
        $this->customerGroups = $customerGroups;
    }

    public function setShopkey(string $shopkey): void
    {
        $this->shopkey = $shopkey;
    }

    /**
     * @return Price[]
     * @throws ProductHasNoPricesException
     */
    public function parse(): array
    {
        /** @var Price[] $prices */
        $prices = [];

        $this->parseVariantPrices($prices);
        $this->parseProductPrices($prices);

        return $prices;
    }

    /**
     * @param Price[] $prices
     */
    protected function parseVariantPrices(array &$prices): void
    {
        if ($this->product->getChildCount() === 0) {
            return;
        }

        foreach ($this->product->getChildren() as $variant) {
            if (!$variant->getActive() || $variant->getStock() <= 0) {
                continue;
            }

            $prices = array_merge($prices, $this->getPricesFromProduct($variant));
        }
    }

    /**
     * @param Price[] $prices
     * @throws ProductHasNoPricesException
     */
    protected function parseProductPrices(array &$prices): void
    {
        $productPrices = $this->getPricesFromProduct($this->product);
        if (Utils::isEmpty($productPrices)) {
            throw new ProductHasNoPricesException($this->product);
        }

        $prices = array_merge($prices, $productPrices);
    }

    /**
     * @return Price[]
     */
    protected function getPricesFromProduct(ProductEntity $variant): array
    {
        $prices = [];

        foreach ($variant->getPrice() as $item) {
            foreach ($this->customerGroups as $customerGroup) {
                $userGroupHash = Utils::calculateUserGroupHash($this->shopkey, $customerGroup->getId());
                if (Utils::isEmpty($userGroupHash)) {
                    continue;
                }

                $price = new Price();
                if ($customerGroup->getDisplayGross()) {
                    $price->setValue($item->getGross(), $userGroupHash);
                } else {
                    $price->setValue($item->getNet(), $userGroupHash);
                }

                $prices[] = $price;
            }

            $price = new Price();
            $price->setValue($item->getGross());
            $prices[] = $price;
        }

        return $prices;
    }
}
