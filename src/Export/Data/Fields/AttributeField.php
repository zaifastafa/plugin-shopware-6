<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Helpers\DataHelper;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\FinSearch\Export\DynamicProductGroupService;
use FINDOLOGIC\FinSearch\Export\ExportTranslationService;
use FINDOLOGIC\FinSearch\Export\UrlBuilderService;
use FINDOLOGIC\FinSearch\Findologic\IntegrationType;
use FINDOLOGIC\FinSearch\Struct\Config;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Symfony\Component\Routing\RouterInterface;

class AttributeField implements MultiValueExportFieldInterface
{
    use ExportContextAware;
    use ProductPropertyAware;

    /** @var RouterInterface */
    protected $router;

    /** @var DynamicProductGroupService|null */
    protected $dynamicProductGroupService;

    /** @var ExportTranslationService */
    protected $translationService;

    /** @var UrlBuilderService */
    protected $urlBuilderService;

    /** @var Config */
    protected $config;

    /** @var CategoryEntity|null */
    protected $navigationCategory;

    public function __construct(
        RouterInterface $router,
        ?DynamicProductGroupService $dynamicProductGroupService,
        ExportTranslationService $translationService,
        UrlBuilderService $urlBuilderService,
        Config $config
    ) {
        $this->router = $router;
        $this->dynamicProductGroupService = $dynamicProductGroupService;
        $this->translationService = $translationService;
        $this->urlBuilderService = $urlBuilderService;
        $this->config = $config;
    }

    public function setNavigationCategory(CategoryEntity $category): self
    {
        $this->navigationCategory = $category;

        return $this;
    }

    /**
     * @return Attribute[]
     * @throws ProductHasNoCategoriesException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function parse(): array
    {
        if (!$this->config->isInitialized()) {
            $this->config->initializeBySalesChannel($this->salesChannelContext);
        }
        $this->urlBuilderService->setSalesChannelContext($this->salesChannelContext);

        /** @var Attribute[] $attributes */
        $attributes = [];

        $this->parseCategoriesAndCatUrls($attributes);
        $this->parseVendors($attributes);
        $this->parseAttributeProperties($attributes);
        $this->parseCustomFieldAttributes($attributes);
        $this->parseAdditionalAttributes($attributes);

        return $attributes;
    }

    /**
     * @param Attribute[] $attributes
     * @throws ProductHasNoCategoriesException
     */
    protected function parseCategoriesAndCatUrls(array &$attributes): void
    {
        $productCategories = $this->product->getCategories();
        if ($productCategories === null || empty($productCategories->count())) {
            throw new ProductHasNoCategoriesException($this->product);
        }

        $catUrls = [];
        $categories = [];

        $this->parseCategoryAttributes($productCategories->getElements(), $catUrls, $categories);
        if ($this->dynamicProductGroupService) {
            $dynamicGroupCategories = $this->dynamicProductGroupService->getCategories($this->product->getId());
            $this->parseCategoryAttributes($dynamicGroupCategories, $catUrls, $categories);
        }

        if ($this->isDirectIntegration() && !Utils::isEmpty($catUrls)) {
            $catUrlAttribute = new Attribute('cat_url');
            $catUrlAttribute->setValues(Utils::flat($catUrls));
            $attributes[] = $catUrlAttribute;
        }

        if (!Utils::isEmpty($categories)) {
            $categoryAttribute = new Attribute('cat');
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

    /**
     * @param Attribute[] $attributes
     */
    protected function parseAttributeProperties(array &$attributes): void
    {
        $filteredCollection = $this->propertyGroupOptionCollection->filter(
            function (PropertyGroupOptionEntity $propertyGroupOptionEntity) {
                if (!$group = $propertyGroupOptionEntity->getGroup()) {
                    return false;
                }

                // Method getFilterable exists since Shopware 6.2.x.
                // Non-filterable properties will be available in the properties field.
                if (method_exists($group, 'getFilterable') && !$group->getFilterable()) {
                    return false;
                }

                return true;
            }
        );

        foreach ($filteredCollection as $propertyGroupOptionEntity) {
            $group = $propertyGroupOptionEntity->getGroup();
            if ($group && $propertyGroupOptionEntity->getTranslation('name') && $group->getTranslation('name')) {
                $groupName = $this->getAttributeKey($group->getTranslation('name'));
                $propertyGroupOptionName = $propertyGroupOptionEntity->getTranslation('name');
                if (!Utils::isEmpty($groupName) && !Utils::isEmpty($propertyGroupOptionName)) {
                    $propertyGroupAttrib = new Attribute($groupName);
                    $propertyGroupAttrib->addValue(Utils::removeControlCharacters($propertyGroupOptionName));

                    $attributes[] = $propertyGroupAttrib;
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

                $groupName = $this->getAttributeKey($group->getTranslation('name'));
                $optionName = $settingOption->getTranslation('name');
                if (!Utils::isEmpty($groupName) && !Utils::isEmpty($optionName)) {
                    $configAttrib = new Attribute($groupName);
                    $configAttrib->addValue(Utils::removeControlCharacters($optionName));

                    $attributes[] = $configAttrib;
                }
            }
        }
    }

    /**
     * @param Attribute[] $attributes
     */
    protected function parseCustomFieldAttributes(array &$attributes): void
    {
        $this->parseCustomFieldProperties($attributes, $this->product);
        foreach ($this->product->getChildren() as $productEntity) {
            $this->parseCustomFieldProperties($attributes, $productEntity);
        }
    }

    /**
     * @param Attribute[] $attributes
     */
    protected function parseAdditionalAttributes(array &$attributes): void
    {
        $shippingFree = $this->translationService->translateBoolean($this->product->getShippingFree());
        $attributes[] = new Attribute('shipping_free', [$shippingFree]);
        $rating = $this->product->getRatingAverage() ?? 0.0;
        $attributes[] = new Attribute('rating', [$rating]);
    }

    protected function parseCustomFieldProperties(array &$attributes, ProductEntity $product): void
    {
        $productFields = $product->getCustomFields();
        if (empty($productFields)) {
            return;
        }

        foreach ($productFields as $key => $value) {
            $key = $this->getAttributeKey($key);
            $cleanedValue = $this->getCleanedAttributeValue($value);

            if (!Utils::isEmpty($key) && !Utils::isEmpty($cleanedValue)) {
                // Third-Party plugins may allow setting multidimensional custom-fields. As those can not really
                // be properly sanitized, they need to be skipped.
                if (is_array($cleanedValue) && is_array(array_values($cleanedValue)[0])) {
                    continue;
                }

                $customFieldAttribute = new Attribute($key, (array)$cleanedValue);
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

            // If the category is not in the current sales channel's root category, we do not need to export it.
            if (!$categoryEntity->getPath() || !strpos($categoryEntity->getPath(), $navigationCategoryId)) {
                continue;
            }

            $categoryPath = Utils::buildCategoryPath(
                $categoryEntity->getBreadcrumb(),
                $this->navigationCategory
            );

            if (!Utils::isEmpty($categoryPath)) {
                $categories = array_merge($categories, [$categoryPath]);
            }

            // Only export `cat_url`s recursively if integration type is Direct Integration.
            // Note that this also applies for the `cat` attribute.
            if ($this->isDirectIntegration()) {
                $catUrls = array_merge(
                    $catUrls,
                    $this->urlBuilderService->getCategoryUrls($categoryEntity, $this->salesChannelContext->getContext())
                );

                $categories = $this->addCategoryNamesRecursively($categoryPath, $categories);
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

    protected function isDirectIntegration(): bool
    {
        return $this->config->getIntegrationType() === IntegrationType::DI;
    }

    protected function isApiIntegration(): bool
    {
        return $this->config->getIntegrationType() === IntegrationType::API;
    }

    protected function addCategoryNamesRecursively(string $categoryPath, array $categories): array
    {
        $parentCategory = explode('_', $categoryPath);

        return array_merge($categories, $parentCategory);
    }
}
