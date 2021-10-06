<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoNameException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoPricesException;
use FINDOLOGIC\FinSearch\Export\Data\Fields\AttributeField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\BonusField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\DateAddedField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\DescriptionField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\ImageField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\KeywordField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\NameField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\OrdernumberField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\PriceField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\PropertyField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\SalesFrequencyField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\SortField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\UrlField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\UsergroupField;
use FINDOLOGIC\FinSearch\Struct\Config;
use FINDOLOGIC\FinSearch\Struct\FindologicProduct;
use Psr\Container\ContainerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Content\Product\ProductEntity;
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
        ?Config $config = null,
        ?NameField $nameField = null,
        ?DescriptionField $descriptionField = null,
        ?PriceField $priceField = null,
        ?UrlField $urlField = null,
        ?BonusField $bonusField = null,
        ?SalesFrequencyField $salesFrequencyField = null,
        ?DateAddedField $dateAddedField = null,
        ?SortField $sortField = null,
        ?KeywordField $keywordField = null,
        ?OrdernumberField $ordernumberField = null,
        ?PropertyField $propertyField = null,
        ?AttributeField $attributeField = null,
        ?ImageField $imageField = null,
        ?UsergroupField $usergroupField = null
    ): FindologicProduct {
        return new FindologicProduct(
            $product,
            $router,
            $container,
            $shopkey,
            $customerGroups,
            $item,
            $config,
            $nameField,
            $descriptionField,
            $priceField,
            $urlField,
            $bonusField,
            $salesFrequencyField,
            $dateAddedField,
            $sortField,
            $keywordField,
            $ordernumberField,
            $propertyField,
            $attributeField,
            $imageField,
            $usergroupField
        );
    }
}
