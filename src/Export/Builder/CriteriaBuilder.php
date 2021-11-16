<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Builder;

use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CriteriaBuilder
{
    /** @var Criteria */
    private $criteria;

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->reset();
    }

    public function reset(): void
    {
        $this->criteria = new Criteria();
    }

    public function build(): Criteria
    {
        return $this->criteria;
    }

    public function setCriteria(Criteria $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function withCreateDateSorting(): self
    {
        $this->criteria->addSorting(new FieldSorting('createdAt'));

        return $this;
    }

    /**
     * @see \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader::addGrouping()
     */
    public function withGrouping(): self
    {
        $this->criteria->addGroupField(new FieldGrouping('displayGroup'));
        $this->criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('displayGroup', null)]
            )
        );

        return $this;
    }

    /**
     * @see \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader::handleAvailableStock()
     */
    public function withAvailableStock(string $salesChannelId): self
    {
        $hide = $this->systemConfigService->get('core.listing.hideCloseoutProductsWhenOutOfStock', $salesChannelId);
        if (!$hide) {
            return $this;
        }

        $this->criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('product.isCloseout', true),
                    new EqualsFilter('product.available', false),
                ]
            )
        );

        return $this;
    }

    /**
     * Will add filters for EAN, manufacturer number, product number and internal id. All filters are connected
     * via a logical OR.
     */
    public function withFindologicOrdernumber(string $productId): self
    {
        $productFilter = [
            new EqualsFilter('ean', $productId),
            new EqualsFilter('manufacturerNumber', $productId),
            new EqualsFilter('productNumber', $productId),
        ];

        // Only add the id filter in case the provided value is a valid uuid, to prevent Shopware
        // from throwing an exception in case it is not.
        if (Uuid::isValid($productId)) {
            $productFilter[] = new EqualsFilter('id', $productId);
        }

        $this->criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                $productFilter
            )
        );

        return $this;
    }

    public function withProductAssociations(): self
    {
        Utils::addProductAssociations($this->criteria);

        return $this;
    }

    public function withSearchVisibility(string $salesChannelId): self
    {
        $this->criteria->addFilter(
            new ProductAvailableFilter(
                $salesChannelId,
                ProductVisibilityDefinition::VISIBILITY_SEARCH
            )
        );

        return $this;
    }

    public function withOffset(?int $offset): self
    {
        if ($offset !== null) {
            $this->criteria->setOffset($offset);
        }

        return $this;
    }

    public function withLimit(?int $limit): self
    {
        if ($limit !== null) {
            $this->criteria->setLimit($limit);
        }

        return $this;
    }
}
