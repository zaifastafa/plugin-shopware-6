<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data;

use FINDOLOGIC\FinSearch\Export\Data\Fields\AttributeField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\BonusField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\DescriptionField;
use FINDOLOGIC\FinSearch\Export\Data\Fields\ExportFieldInterface;
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
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExportFieldInitializer
{
    private const EXPORT_FIELDS = [
        NameField::class,
        DescriptionField::class,
        PriceField::class,
        UrlField::class,
        BonusField::class,
        SalesFrequencyField::class,
        SortField::class,
        KeywordField::class,
        OrdernumberField::class,
        PropertyField::class,
        AttributeField::class,
        ImageField::class,
        UsergroupField::class,
    ];

    /** @var bool */
    private $isInitialized = false;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Initializes the basic state of all export fields.
     *
     * @param CustomerGroupEntity[] $customerGroups
     */
    public function initializeExportFields(
        SalesChannelContext $salesChannelContext,
        string $shopkey,
        array $customerGroups = []
    ): void {
        if ($this->isInitialized) {
            return;
        }

        foreach (self::EXPORT_FIELDS as $exportFieldClass) {
            /** @var ExportFieldInterface $exportField */
            $exportField = $this->container->get($exportFieldClass);
            $this->initializeField($exportField, $salesChannelContext, $shopkey, $customerGroups);
        }

        $this->isInitialized = true;
    }

    public function reset(): void
    {
        $this->isInitialized = false;
    }

    /**
     * @param CustomerGroupEntity[] $customerGroups
     */
    protected function initializeField(
        ExportFieldInterface $exportField,
        SalesChannelContext $salesChannelContext,
        string $shopkey,
        array $customerGroups
    ): void {
        $exportField->setSalesChannelContext($salesChannelContext);

        if (method_exists($exportField, 'setNavigationCategory')) {
            $exportField->setNavigationCategory(Utils::fetchNavigationCategoryFromSalesChannel(
                $this->container->get('category.repository'),
                $salesChannelContext->getSalesChannel()
            ));
        }

        if (method_exists($exportField, 'setShopkey')) {
            $exportField->setShopkey($shopkey);
        }

        if (method_exists($exportField, 'setCustomerGroups')) {
            $exportField->setCustomerGroups($customerGroups);
        }
    }
}
