<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export;

use FINDOLOGIC\FinSearch\Export\Builder\CriteriaBuilder;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Psr\Container\ContainerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductService
{
    public const CONTAINER_ID = 'fin_search.product_service';

    /** @var ContainerInterface */
    private $container;

    /** @var SalesChannelContext|null */
    private $salesChannelContext;

    public function __construct(ContainerInterface $container, ?SalesChannelContext $salesChannelContext = null)
    {
        $this->container = $container;
        $this->salesChannelContext = $salesChannelContext;
    }

    public static function getInstance(
        ContainerInterface $container,
        ?SalesChannelContext $salesChannelContext
    ): ProductService {
        if ($container->has(self::CONTAINER_ID)) {
            $productService = $container->get(self::CONTAINER_ID);
        } else {
            $productService = new ProductService($container, $salesChannelContext);
            $container->set(self::CONTAINER_ID, $productService);
        }

        if ($salesChannelContext && !$productService->getSalesChannelContext()) {
            $productService->setSalesChannelContext($salesChannelContext);
        }

        return $productService;
    }

    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getSalesChannelContext(): ?SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getTotalProductCount(): int
    {
        $criteria = $this->buildProductCriteria();

        /** @var IdSearchResult $result */
        $result = $this->container->get('product.repository')->searchIds(
            $criteria,
            $this->salesChannelContext->getContext()
        );

        return $result->getTotal();
    }

    public function searchVisibleProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): EntitySearchResult {
        $criteria = $this->getCriteriaWithProductVisibility($limit, $offset);

        if ($productId) {
            $this->addProductIdFilters($criteria, $productId);
        }

        /** @var EntitySearchResult $result */
        $result = $this->container->get('product.repository')->search(
            $criteria,
            $this->salesChannelContext->getContext()
        );

        $finalProducts = new ProductCollection();

        /** @var ProductCollection $products */
        $products = $result->getEntities();
        foreach ($products as $product) {
            $children = $this->getChildrenOrSiblings($product);
            if ($product->getMainVariantId() !== null) {
                $newProduct = $children->get($product->getMainVariantId());

                $children = $this->getChildrenOrSiblings($newProduct);
                $newProduct->setChildren($children);
                $finalProducts->add($newProduct);
                continue;
            }

            $product->setChildren($children);

            $finalProducts->add($product);
        }

        return new EntitySearchResult(
            ProductEntity::class,
            $finalProducts->count(),
            $finalProducts,
            null,
            $criteria,
            $this->salesChannelContext->getContext()
        );
    }

    public function searchAllProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): EntitySearchResult {
        $criteria = $this->buildProductCriteria($limit, $offset);

        if ($productId) {
            $this->addProductIdFilters($criteria, $productId);
        }

        return $this->container->get('product.repository')->search(
            $criteria,
            $this->salesChannelContext->getContext()
        );
    }

    public function getAllCustomerGroups(): array
    {
        return $this->container->get('customer_group.repository')
            ->search(new Criteria(), $this->salesChannelContext->getContext())
            ->getElements();
    }

    protected function findVisibleProductsWithAssociations(array $ids): EntitySearchResult
    {
        $criteria = $this->getCriteriaWithProductVisibility();
        $criteria->setIds($ids);
        $criteria->resetGroupFields();

        /** @var EntitySearchResult $result */
        return $this->container->get('product.repository')->search(
            $criteria,
            $this->salesChannelContext->getContext()
        );
    }

    /**
     * If the given product is a parent product, returns all children of the product.
     * In case the given product already is a child, all siblings and the parent are returned. The siblings
     * do not include the given product itself.
     */
    protected function getChildrenOrSiblings(ProductEntity $product): ?ProductCollection
    {
        if (!$product->getParentId()) {
            return $product->getChildren();
        }

        $productRepository = $this->container->get('product.repository');
        $criteria = new Criteria([$product->getParentId()]);

        // Only get children of the same display group.
        $childrenCriteria = $criteria->getAssociation('children');
        $childrenCriteria->addFilter(
            new EqualsFilter('displayGroup', $product->getDisplayGroup())
        );

        $this->addProductAssociations($criteria);
        $this->addProductAssociations($childrenCriteria);

        /** @var ProductCollection $result */
        $result = $productRepository->search($criteria, $this->salesChannelContext->getContext());

        // Remove the given children, as the child product is considered as the product, which is shown
        // in the storefront. As we also want to get the data from the parent, we also manually add it here.
        $children = $result->first()->getChildren();
        $children->remove($product->getId());
        $children->add($result->first());

        return $children;
    }

    protected function getCriteriaWithProductVisibility(?int $limit = null, ?int $offset = null): Criteria
    {
        /** @var CriteriaBuilder $criteriaBuilder */
        $criteriaBuilder = $this->container->get(CriteriaBuilder::class);
        $criteriaBuilder->setCriteria($this->buildProductCriteria($limit, $offset));
        $criteriaBuilder->withSearchVisibility($this->salesChannelContext->getSalesChannel()->getId());

        return $criteriaBuilder->build();
    }

    protected function buildProductCriteria(?int $limit = null, ?int $offset = null): Criteria
    {
        /** @var CriteriaBuilder $criteriaBuilder */
        $criteriaBuilder = $this->container->get(CriteriaBuilder::class);
        $criteriaBuilder->reset();

        $criteriaBuilder->withCreateDateSorting();
        $criteriaBuilder->withGrouping();
        $criteriaBuilder->withAvailableStock($this->salesChannelContext->getSalesChannel()->getId());
        $criteriaBuilder->withProductAssociations();
        $criteriaBuilder->withOffset($offset);
        $criteriaBuilder->withLimit($limit);

        return $criteriaBuilder->build();
    }

    /**
     * @deprecated tag:v3.0.0 Use \FINDOLOGIC\FinSearch\Export\Builder\CriteriaBuilder::withGrouping instead.
     * @see \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader::addGrouping()
     */
    protected function addGrouping(Criteria $criteria): void
    {
        $criteria->addGroupField(new FieldGrouping('displayGroup'));

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('displayGroup', null)]
            )
        );
    }

    /**
     * @deprecated tag:v3.0.0 Use \FINDOLOGIC\FinSearch\Export\Builder\CriteriaBuilder::withAvailableStock instead.
     * @see \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader::handleAvailableStock()
     */
    protected function handleAvailableStock(Criteria $criteria): void
    {
        $salesChannelId = $this->salesChannelContext->getSalesChannel()->getId();
        $systemConfigService = $this->container->get(SystemConfigService::class);

        $hide = $systemConfigService->get('core.listing.hideCloseoutProductsWhenOutOfStock', $salesChannelId);
        if (!$hide) {
            return;
        }

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('product.isCloseout', true),
                    new EqualsFilter('product.available', false),
                ]
            )
        );
    }

    /**
     * @deprecated tag:v3.0.0 Use \FINDOLOGIC\FinSearch\Export\Builder\CriteriaBuilder::withFindologicOrdernumber
     * instead.
     */
    protected function addProductIdFilters(Criteria $criteria, string $productId): void
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

        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                $productFilter
            )
        );
    }

    /**
     * @deprecated tag:v3.0.0 Use \FINDOLOGIC\FinSearch\Export\Builder\CriteriaBuilder::withProductAssociations instead.
     */
    protected function addProductAssociations(Criteria $criteria): void
    {
        Utils::addProductAssociations($criteria);
    }
}
