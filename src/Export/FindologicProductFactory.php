<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoNameException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoPricesException;
use FINDOLOGIC\FinSearch\Export\Fields\AttributeField;
use FINDOLOGIC\FinSearch\Export\Fields\NameField;
use FINDOLOGIC\FinSearch\Struct\FindologicProduct;
use Psr\Container\ContainerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\RouterInterface;

class FindologicProductFactory
{
    /**
     * @param CustomerGroupEntity[] $customerGroups
     *
     * @throws ProductHasNoCategoriesException
     * @throws ProductHasNoNameException
     * @throws ProductHasNoPricesException
     */
    public function buildInstance(
        ProductEntity $product,
        RouterInterface $router,
        ContainerInterface $container,
        string $shopkey,
        array $customerGroups,
        Item $item,
        NameField $nameField,
        AttributeField $attributeField
    ): FindologicProduct {
        return new FindologicProduct(
            $product,
            $router,
            $container,
            $shopkey,
            $customerGroups,
            $item,
            $nameField,
            $attributeField
        );
    }
}
