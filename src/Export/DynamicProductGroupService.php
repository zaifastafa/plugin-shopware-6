<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export;

use FINDOLOGIC\FinSearch\Utils\Utils;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\ProductStream\ProductStreamEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

use function serialize;
use function unserialize;

class DynamicProductGroupService
{
    public const CONTAINER_ID = 'fin_search.dynamic_product_group';
    private const CACHE_ID_PRODUCT_GROUP = 'fl_product_groups';
    private const CACHE_LIFETIME_PRODUCT_GROUP = 60 * 11;

    /**
     * @var ProductStreamBuilderInterface
     */
    protected $productStreamBuilder;

    /**
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $shopkey;

    /**
     * @var int
     */
    protected $start;

    /**
     * @var SalesChannelEntity
     */
    private $salesChannel;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    private function __construct(
        ContainerInterface $container,
        CacheItemPoolInterface $cache,
        Context $context,
        string $shopkey,
        int $start
    ) {
        $this->container = $container;
        $this->cache = $cache;
        $this->shopkey = $shopkey;
        $this->start = $start;
        $this->productStreamBuilder = $container->get(ProductStreamBuilder::class);
        $this->productRepository = $container->get('product.repository');
        $this->categoryRepository = $container->get('category.repository');
        $this->context = $context;
    }

    public static function getInstance(
        ContainerInterface $container,
        CacheItemPoolInterface $cache,
        Context $context,
        string $shopkey,
        int $start
    ): DynamicProductGroupService {
        if ($container->has(self::CONTAINER_ID)) {
            $dynamicProductGroupService = $container->get(self::CONTAINER_ID);
        } else {
            $dynamicProductGroupService = new DynamicProductGroupService(
                $container,
                $cache,
                $context,
                $shopkey,
                $start
            );
            $container->set(self::CONTAINER_ID, $dynamicProductGroupService);
        }

        return $dynamicProductGroupService;
    }

    public function setSalesChannel(SalesChannelEntity $salesChannelEntity): void
    {
        $this->salesChannel = $salesChannelEntity;
    }

    public function warmUp(): void
    {
        $cacheItem = $this->getCacheItem();
        $products = $this->parseProductGroups();
        if (Utils::isEmpty($products)) {
            return;
        }
        $cacheItem->set(serialize($products));
        $cacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
        $this->cache->save($cacheItem);
    }

    public function isWarmedUp(): bool
    {
        if ($this->start === 0) {
            return false;
        }

        $cacheItem = $this->getCacheItem();
        if ($cacheItem && $cacheItem->isHit()) {
            $cacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
            $this->cache->save($cacheItem);

            return true;
        }

        return false;
    }

    private function parseProductGroups(): array
    {
        $start = microtime(true);

        /** @var EntityRepositoryInterface $productStreamRepo */
        $productStreamRepo = $this->container->get('product_stream.repository');
        $criteria = new Criteria();
        $criteria->addAssociations(['categories', 'categories.seoUrls']);
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_OR,
            [
                new EqualsFilter('categories.id', null)
            ]
        ));
        $total = $productStreamRepo->searchIds($criteria, $this->context)->getTotal();
        $offset = 0;
        $criteria->setLimit(20);
        $all = [];

        do {
            $result = $productStreamRepo->search($criteria, $this->context);

            /** @var ProductStreamEntity $productStream */
            foreach ($result->getElements() as $productStream) {
//                dump('1: ', microtime(true) - $start);
                $productStreamFilters = $this->productStreamBuilder->buildFilters(
                    $productStream->getId(),
                    $this->context
                );

                $productStreamCriteria = new Criteria();
                $productStreamCriteria->addFilter(...$productStreamFilters);
//                dump('2: ', microtime(true) - $start);

                $products = $this->productRepository->searchIds($productStreamCriteria, $this->context);
//                dump('3: ', microtime(true) - $start);
                $categoryIds = $productStream->getCategories()->getIds();
                foreach ($products->getIds() as $productId) {
//                    $categoryIds = array_map(function (CategoryEntity $category) {
//                        $item = $this->cache->getItem(sprintf('fl_product_stream_category_%s', $category->getId()));
//                        if (!$item->isHit()) {
//                            $item->set(serialize($category));
//                            $this->cache->save($item);
//                        }
//
//                        return $category->getId();
//                    }, $productStream->getCategories()->getElements());
//                    dump('After map: ', microtime(true) - $start);

                    if (!isset($all[$productId])) {
                        $all[$productId] = [];
                    }
                    $this->simpleMerge($all[$productId], $categoryIds);
//                    $new = array_unique(array_merge($all[$productId] ?? [], $categoryIds), SORT_REGULAR);
//                    dump('After merge: ', microtime(true) - $start);
//                    $all[$productId] = $new;
                }
//                dump('4: ', microtime(true) - $start);
            }

            $offset += $criteria->getLimit();
            $criteria->setOffset($offset);
        } while ($total > $offset);

        return $all;
    }

    private function simpleMerge(&$array1, &$array2) {
        foreach($array2 as $i) {
            $array1[] = $i;
        }
    }

    /**
     * @return CategoryEntity[]
     */
    public function getCategories(string $productId): array
    {
        $categories = [];
        $cacheItem = $this->getCacheItem();
        if ($cacheItem->isHit()) {
            $categories = unserialize($cacheItem->get());
        }

        if (!Utils::isEmpty($categories) && isset($categories[$productId])) {
            $categoryIds = $categories[$productId];
            $criteria = $this->buildCriteria();
            $criteria->setIds($categoryIds);

            return $this->categoryRepository->search($criteria, $this->context)->getElements();
//            return array_map(function (string $categoryId) {
//                $item = $this->cache->getItem(sprintf('fl_product_stream_category_%s', $categoryId));
//                return unserialize($item->get());
//            }, $categoryIds);
        }

        return [];
    }

    private function buildCriteria(): Criteria
    {
//        $mainCategoryId = $this->salesChannel->getNavigationCategoryId();

        $criteria = new Criteria();
//        $criteria->addFilter(new ContainsFilter('path', $mainCategoryId));
        $criteria->addAssociation('seoUrls');
//        $criteria->addAssociation('productStream');
//        $criteria->addFilter(
//            new NotFilter(
//                NotFilter::CONNECTION_AND,
//                [new EqualsFilter('productStreamId', null)]
//            )
//        );

        return $criteria;
    }

    private function getCacheItem(): CacheItemInterface
    {
        $id = sprintf('%s_%s', self::CACHE_ID_PRODUCT_GROUP, $this->shopkey);

        return $this->cache->getItem($id);
    }
}
