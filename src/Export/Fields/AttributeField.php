<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Fields;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Helpers\DataHelper;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\FinSearch\Export\DynamicProductGroupService;
use FINDOLOGIC\FinSearch\Export\ExportTranslationService;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Symfony\Component\Routing\RouterInterface;

class AttributeField implements MultiValueExportFieldInterface
{
    use ExportContextAware;

    /** @var RouterInterface */
    protected $router;

    /** @var DynamicProductGroupService */
    protected $dynamicProductGroupService;

    /** @var ExportTranslationService */
    protected $translationService;

    /** @var CategoryEntity|null */
    protected $navigationCategory;

    /** @var string|null */
    protected $catUrlPrefix;

    /** @var PropertyGroupOptionEntity[] */
    protected $attributeProperties;

    public function __construct(
        RouterInterface $router,
        DynamicProductGroupService $dynamicProductGroupService,
        ExportTranslationService $translationService
    ) {
        $this->router = $router;
        $this->dynamicProductGroupService = $dynamicProductGroupService;
        $this->translationService = $translationService;
    }

    public function setDynamicProductGroupService(DynamicProductGroupService $dynamicProductGroupService): self
    {
        $this->dynamicProductGroupService = $dynamicProductGroupService;

        return $this;
    }

    public function setNavigationCategory(CategoryEntity $category): self
    {
        $this->navigationCategory = $category;

        return $this;
    }

    public function setCatUrlPrefix(string $prefix): self
    {
        $this->catUrlPrefix = $prefix;

        return $this;
    }

    /**
     * @param PropertyGroupOptionEntity[] $properties
     */
    public function setProperties(array $properties): self
    {
        $this->attributeProperties = $properties;

        return $this;
    }

    /**
     * @return Attribute[]
     * @throws ProductHasNoCategoriesException
     */
    public function parse(): array
    {
        $attributes = [];

        $this->parseCategoriesAndCatUrls($attributes);
        $this->parseVendors($attributes);
        $this->parseAttributeProperties($attributes);
        $this->parseCustomFieldAttributes($attributes);
        $this->parseAdditionalAttributes($attributes);

        return $attributes;
    }

    /**
     * @throws ProductHasNoCategoriesException
     */
    protected function parseCategoriesAndCatUrls(array &$attributes): void
    {
        $productCategories = $this->product->getCategories();
        if ($productCategories === null || empty($productCategories->count())) {
            throw new ProductHasNoCategoriesException($this->product);
        }

        $categoryAttribute = new Attribute('cat');
        $catUrlAttribute = new Attribute('cat_url');

        $catUrls = [];
        $categories = [];

        $this->parseCategoryAttributes($productCategories->getElements(), $catUrls, $categories);
        if ($this->dynamicProductGroupService) {
            $dynamicGroupCategories = $this->dynamicProductGroupService->getCategories($this->product->getId());
            $this->parseCategoryAttributes($dynamicGroupCategories, $catUrls, $categories);
        }

        if (!Utils::isEmpty($catUrls)) {
            $catUrlAttribute->setValues(array_unique($catUrls));
            $attributes[] = $catUrlAttribute;
        }

        if (!Utils::isEmpty($categories)) {
            $categoryAttribute->setValues(array_unique($categories));
            $attributes[] = $categoryAttribute;
        }
    }

    /**
     * @param Attribute[] $attributes
     */
    protected function parseVendors(array &$attributes): void
    {
        $manufacturer = $this->product->getManufacturer();
        if ($manufacturer) {
            $name = $manufacturer->getTranslation('name');
            if (!Utils::isEmpty($name)) {
                $vendorAttribute = new Attribute('vendor', [Utils::removeControlCharacters($name)]);
                $attributes[] = $vendorAttribute;
            }
        }
    }

    protected function parseAttributeProperties(array &$attributes): void
    {
        foreach ($this->attributeProperties as $propertyGroupOptionEntity) {
            $group = $propertyGroupOptionEntity->getGroup();
            if ($group && $propertyGroupOptionEntity->getTranslation('name') && $group->getTranslation('name')) {
                $groupName = Utils::removeSpecialChars($group->getTranslation('name'));
                $propertyGroupOptionName = $propertyGroupOptionEntity->getTranslation('name');
                if (!Utils::isEmpty($groupName) && !Utils::isEmpty($propertyGroupOptionName)) {
                    $properyGroupAttrib = new Attribute(Utils::removeSpecialChars($groupName));
                    $properyGroupAttrib->addValue(Utils::removeControlCharacters($propertyGroupOptionName));

                    $attributes[] = $properyGroupAttrib;
                }
            }

            foreach ($propertyGroupOptionEntity->getProductConfiguratorSettings() as $setting) {
                $settingOption = $setting->getOption();
                if ($settingOption) {
                    $group = $settingOption->getGroup();
                }

                if (!$group) {
                    continue;
                }

                $groupName = $group->getTranslation('name');
                $optionName = $settingOption->getTranslation('name');
                if (!Utils::isEmpty($groupName) && !Utils::isEmpty($optionName)) {
                    $configAttrib = new Attribute(Utils::removeSpecialChars($groupName));
                    $configAttrib->addValue(Utils::removeControlCharacters($optionName));

                    $attributes[] = $configAttrib;
                }
            }
        }
    }

    protected function parseCustomFieldAttributes(array &$attributes): void
    {
        $this->parseCustomFieldProperties($attributes, $this->product);
        foreach ($this->product->getChildren() as $productEntity) {
            $this->parseCustomFieldProperties($attributes, $productEntity);
        }
    }

    protected function parseAdditionalAttributes(array &$attributes): void
    {
        $shippingFree = $this->translationService->translateBoolean($this->product->getShippingFree());
        $attributes[] = new Attribute('shipping_free', [$shippingFree]);
        $rating = $this->product->getRatingAverage() ?? 0.0;
        $attributes[] = new Attribute('rating', [$rating]);
    }

    protected function parseCustomFieldProperties(array &$attributes, ProductEntity $product): array
    {
        $productFields = $product->getCustomFields();
        if (empty($productFields)) {
            return [];
        }

        foreach ($productFields as $key => $value) {
            $cleanedKey = Utils::removeSpecialChars($key);
            $cleanedValue = $this->getCleanedAttributeValue($value);

            if (!Utils::isEmpty($cleanedKey) && !Utils::isEmpty($cleanedValue)) {
                $customFieldAttribute = new Attribute($cleanedKey, (array)$cleanedValue);
                $attributes[] = $customFieldAttribute;
            }
        }
    }

    protected function parseCategoryAttributes(
        array $categoryCollection,
        array &$catUrls,
        array &$categories
    ): void {
        if (!$categoryCollection) {
            return;
        }

        $navigationCategoryId = $this->salesChannelContext->getSalesChannel()->getNavigationCategoryId();

        /** @var CategoryEntity $categoryEntity */
        foreach ($categoryCollection as $categoryEntity) {
            if (!$categoryEntity->getActive()) {
                continue;
            }

            if (!$categoryEntity->getPath() || !strpos($categoryEntity->getPath(), $navigationCategoryId)) {
                continue;
            }

            $seoUrls = $this->fetchCategorySeoUrls($categoryEntity);
            if ($seoUrls->count() > 0) {
                foreach ($seoUrls->getElements() as $seoUrlEntity) {
                    $catUrl = $seoUrlEntity->getSeoPathInfo();
                    if (!Utils::isEmpty($catUrl)) {
                        $catUrls[] = $this->catUrlPrefix . sprintf('/%s', ltrim($catUrl, '/'));
                    }
                }
            }

            $catUrl = sprintf(
                '/%s',
                ltrim(
                    $this->router->generate(
                        'frontend.navigation.page',
                        ['navigationId' => $categoryEntity->getId()],
                        RouterInterface::ABSOLUTE_PATH
                    ),
                    '/'
                )
            );

            if (!Utils::isEmpty($catUrl)) {
                $catUrls[] = $catUrl;
            }

            $categoryPath = Utils::buildCategoryPath(
                $categoryEntity->getBreadcrumb(),
                $this->navigationCategory
            );

            if (!Utils::isEmpty($categoryPath)) {
                $categories[] = $categoryPath;
            }
        }
    }

    protected function fetchCategorySeoUrls(CategoryEntity $categoryEntity): SeoUrlCollection
    {
        $salesChannelId = $this->salesChannelContext->getSalesChannel()->getId();
        $seoUrls = new SeoUrlCollection();

        foreach ($categoryEntity->getSeoUrls()->getElements() as $seoUrlEntity) {
            if ($seoUrlEntity->getSalesChannelId() === $salesChannelId || $seoUrlEntity->getSalesChannelId() === null) {
                $seoUrls->add($seoUrlEntity);
            }
        }

        return $seoUrls;
    }

    /**
     * @param array<string, int, bool>|string|int|bool $value
     *
     * @return array<string, int, bool>|string|int|bool
     */
    protected function getCleanedAttributeValue($value)
    {
        if (is_array($value)) {
            $values = [];
            foreach ($value as $item) {
                $values[] = $this->getCleanedAttributeValue($item);
            }

            return $values;
        }

        if (is_string($value)) {
            if (mb_strlen($value) > DataHelper::ATTRIBUTE_CHARACTER_LIMIT) {
                return '';
            }

            return Utils::cleanString($value);
        }

        if (is_bool($value)) {
            $this->translationService->translateBoolean($value);
        }

        return $value;
    }
}
