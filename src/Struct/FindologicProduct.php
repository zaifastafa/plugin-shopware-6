<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Struct;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Data\Bonus;
use FINDOLOGIC\Export\Data\DateAdded;
use FINDOLOGIC\Export\Data\Description;
use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Data\Keyword;
use FINDOLOGIC\Export\Data\Name;
use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\Export\Data\Price;
use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\Export\Data\SalesFrequency;
use FINDOLOGIC\Export\Data\Sort;
use FINDOLOGIC\Export\Data\Url;
use FINDOLOGIC\Export\Data\Usergroup;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\AccessEmptyPropertyException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoNameException;
use FINDOLOGIC\FinSearch\Exceptions\Export\Product\ProductHasNoPricesException;
use FINDOLOGIC\FinSearch\Export\Data\ExportFieldInitializer;
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
use FINDOLOGIC\FinSearch\Export\DynamicProductGroupService;
use FINDOLOGIC\FinSearch\Export\ProductImageService;
use FINDOLOGIC\FinSearch\Export\UrlBuilderService;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Psr\Container\ContainerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FindologicProduct extends Struct
{
    /** @var NameField */
    protected $nameField;

    /** @var DescriptionField */
    protected $descriptionField;

    /** @var PriceField */
    protected $priceField;

    /** @var UrlField */
    protected $urlField;

    /** @var BonusField */
    protected $bonusField;

    /** @var SalesFrequencyField */
    protected $salesFrequencyField;

    /** @var DateAddedField */
    protected $dateAddedField;

    /** @var SortField */
    protected $sortField;

    /** @var KeywordField */
    protected $keywordField;

    /** @var OrdernumberField */
    protected $ordernumberField;

    /** @var PropertyField */
    protected $propertyField;

    /** @var AttributeField */
    protected $attributeField;

    /** @var ImageField */
    protected $imageField;

    /** @var UsergroupField */
    protected $usergroupField;

    /** @var ProductEntity */
    protected $product;

    /** @var RouterInterface */
    protected $router;

    /** @var ContainerInterface */
    protected $container;

    /** @var SalesChannelContext */
    protected $salesChannelContext;

    /** @var string */
    protected $shopkey;

    /** @var CustomerGroupEntity[] */
    protected $customerGroups;

    /** @var Name */
    protected $name;

    /** @var Sort */
    protected $sort;

    /** @var Attribute[] */
    protected $attributes;

    /** @var Price[] */
    protected $prices;

    /** @var Description */
    protected $description;

    /** @var DateAdded|null */
    protected $dateAdded;

    /** @var Url */
    protected $url;

    /** @var Bonus */
    protected $bonus;

    /** @var Keyword[] */
    protected $keywords;

    /** @var Image[] */
    protected $images;

    /** @var SalesFrequency */
    protected $salesFrequency;

    /** @var Usergroup[] */
    protected $userGroups;

    /** @var Ordernumber[] */
    protected $ordernumbers;

    /** @var Property[] */
    protected $properties;

    /** @var Attribute[] */
    protected $customFields = [];

    /** @var Item */
    protected $item;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DynamicProductGroupService|null */
    protected $dynamicProductGroupService;

    /** @var CategoryEntity */
    protected $navigationCategory;

    /** @var ProductImageService */
    protected $productImageService;

    /** @var Config */
    protected $config;

    /** @var UrlBuilderService */
    protected $urlBuilderService;

    /**
     * @param CustomerGroupEntity[] $customerGroups
     *
     * @throws ProductHasNoCategoriesException
     * @throws ProductHasNoNameException
     * @throws ProductHasNoPricesException
     */
    public function __construct(
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
    ) {
        $this->product = $product;
        $this->router = $router;
        $this->container = $container;
        $this->shopkey = $shopkey;
        $this->customerGroups = $customerGroups;
        $this->item = $item;
        $this->prices = [];
        $this->attributes = [];
        $this->properties = [];
        $this->translator = $container->get('translator');
        $this->salesChannelContext = $this->container->get('fin_search.sales_channel_context');
        $this->config = $config ?? $container->get(Config::class);
        $this->nameField = $nameField ?? $container->get(NameField::class);
        $this->descriptionField = $descriptionField ?? $container->get(DescriptionField::class);
        $this->priceField = $priceField ?? $container->get(PriceField::class);
        $this->urlField = $urlField ?? $container->get(UrlField::class);
        $this->bonusField = $bonusField ?? $container->get(BonusField::class);
        $this->salesFrequencyField = $salesFrequencyField ?? $container->get(SalesFrequencyField::class);
        $this->dateAddedField = $dateAddedField ?? $container->get(DateAddedField::class);
        $this->sortField = $sortField ?? $container->get(SortField::class);
        $this->keywordField = $keywordField ?? $container->get(KeywordField::class);
        $this->ordernumberField = $ordernumberField ?? $container->get(OrdernumberField::class);
        $this->propertyField = $propertyField ?? $container->get(PropertyField::class);
        $this->attributeField = $attributeField ?? $container->get(AttributeField::class);
        $this->imageField = $imageField ?? $container->get(ImageField::class);
        $this->usergroupField = $usergroupField ?? $container->get(UsergroupField::class);

        if (!$this->config->isInitialized()) {
            $this->config->initializeBySalesChannel($this->salesChannelContext);
        }
        if ($this->container->has('fin_search.dynamic_product_group')) {
            $this->dynamicProductGroupService = $this->container->get('fin_search.dynamic_product_group');
        }
        $this->navigationCategory = Utils::fetchNavigationCategoryFromSalesChannel(
            $this->container->get('category.repository'),
            $this->salesChannelContext->getSalesChannel()
        );
        $this->urlBuilderService = $this->container->get(UrlBuilderService::class);
        $this->urlBuilderService->setSalesChannelContext($this->salesChannelContext);
        $this->productImageService = $this->container->get(ProductImageService::class);

        $fieldInitializer = $this->container->get(ExportFieldInitializer::class);
        $fieldInitializer->initializeExportFields($this->salesChannelContext, $shopkey, $customerGroups);

        $this->setName();
        $this->setDescription();
        $this->setPrices();
        $this->setUrl();
        $this->setBonus();
        $this->setSalesFrequency();
        $this->setDateAdded();
        $this->setSort();
        $this->setKeywords();
        $this->setOrdernumbers();
        $this->setProperties();
        $this->setAttributes();
        $this->setImages();
        $this->setUserGroups();
    }

    protected function setSort(): void
    {
        $this->sortField->setProduct($this->product);
        $this->sort = $this->sortField->parse();
    }

    public function hasSort(): bool
    {
        return (
            $this->sort->getValues() &&
            isset($this->sort->getValues()['']) &&
            (int)$this->sort->getValues()[''] !== 0
        );
    }

    public function getSort(): Sort
    {
        return $this->sort;
    }

    protected function setBonus(): void
    {
        $this->bonusField->setProduct($this->product);
        $this->bonus = $this->bonusField->parse();
    }

    public function hasBonus(): bool
    {
        return (
            $this->bonus->getValues() &&
            isset($this->bonus->getValues()['']) &&
            (int)$this->bonus->getValues()[''] !== 0
        );
    }

    public function getBonus(): Bonus
    {
        return $this->bonus;
    }

    /**
     * @throws AccessEmptyPropertyException
     */
    public function getName(): Name
    {
        if (!$this->hasName()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->name;
    }

    /**
     * @throws ProductHasNoNameException
     */
    protected function setName(): void
    {
        $this->nameField->setProduct($this->product);
        $this->name = $this->nameField->parse();
    }

    public function hasName(): bool
    {
        return !Utils::isEmpty($this->name->getValues());
    }

    /**
     * @return Attribute[]
     * @throws AccessEmptyPropertyException
     */
    public function getAttributes(): array
    {
        if (!$this->hasAttributes()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->attributes;
    }

    /**
     * @throws ProductHasNoCategoriesException
     */
    protected function setAttributes(): void
    {
        if ($this->product->getProperties() !== null) {
            $this->attributeField->setPropertyGroupOptionCollection($this->product->getProperties());
        }

        $this->attributeField->setProduct($this->product);
        $this->attributes = $this->attributeField->parse();
    }

    public function hasAttributes(): bool
    {
        return !Utils::isEmpty($this->attributes);
    }

    /**
     * @return Price[]
     * @throws AccessEmptyPropertyException
     */
    public function getPrices(): array
    {
        if (!$this->hasPrices()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->prices;
    }

    /**
     * @throws ProductHasNoPricesException
     */
    protected function setPrices(): void
    {
        $this->priceField->setProduct($this->product);
        $this->prices = $this->priceField->parse();
    }

    public function hasPrices(): bool
    {
        return !Utils::isEmpty($this->prices);
    }

    /**
     * @throws AccessEmptyPropertyException
     */
    public function getDescription(): Description
    {
        if (!$this->hasDescription()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->description;
    }

    protected function setDescription(): void
    {
        $this->descriptionField->setProduct($this->product);

        $this->description = $this->descriptionField->parse();
    }

    public function hasDescription(): bool
    {
        return !Utils::isEmpty($this->description->getValues());
    }

    /**
     * @throws AccessEmptyPropertyException
     */
    public function getDateAdded(): DateAdded
    {
        if (!$this->hasDateAdded()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->dateAdded;
    }

    protected function setDateAdded(): void
    {
        $this->dateAddedField->setProduct($this->product);
        $this->dateAdded = $this->dateAddedField->parse();
    }

    public function hasDateAdded(): bool
    {
        return $this->dateAdded && !empty($this->dateAdded);
    }

    /**
     * @throws AccessEmptyPropertyException
     */
    public function getUrl(): Url
    {
        if (!$this->hasUrl()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->url;
    }

    protected function setUrl(): void
    {
        $this->urlField->setProduct($this->product);
        $this->url = $this->urlField->parse();
    }

    public function hasUrl(): bool
    {
        return $this->url && !Utils::isEmpty($this->url->getValues());
    }

    /**
     * @return Keyword[]
     * @throws AccessEmptyPropertyException
     */
    public function getKeywords(): array
    {
        if (!$this->hasKeywords()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->keywords;
    }

    protected function setKeywords(): void
    {
        $this->keywordField->setProduct($this->product);
        $this->keywords = $this->keywordField->parse();
    }

    public function hasKeywords(): bool
    {
        return $this->keywords && !empty($this->keywords);
    }

    /**
     * @return Image[]
     * @throws AccessEmptyPropertyException
     */
    public function getImages(): array
    {
        if (!$this->hasImages()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->images;
    }

    protected function setImages(): void
    {
        $this->imageField->setProduct($this->product);
        $this->images = $this->imageField->parse();
    }

    public function hasImages(): bool
    {
        return $this->images && !empty($this->images);
    }

    public function getSalesFrequency(): SalesFrequency
    {
        return $this->salesFrequency;
    }

    protected function setSalesFrequency(): void
    {
        $this->salesFrequencyField->setProduct($this->product);
        $this->salesFrequency = $this->salesFrequencyField->parse();
    }

    public function hasSalesFrequency(): bool
    {
        // In case a product has no sales, its sales frequency would still be 0.
        return true;
    }

    /**
     * @return Usergroup[]
     * @throws AccessEmptyPropertyException
     */
    public function getUserGroups(): array
    {
        if (!$this->hasUserGroups()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->userGroups;
    }

    protected function setUserGroups(): void
    {
        $this->usergroupField->setProduct($this->product);
        $this->userGroups = $this->usergroupField->parse();
    }

    public function hasUserGroups(): bool
    {
        return $this->userGroups && !empty($this->userGroups);
    }

    /**
     * @return Ordernumber[]
     * @throws AccessEmptyPropertyException
     */
    public function getOrdernumbers(): array
    {
        if (!$this->hasOrdernumbers()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->ordernumbers;
    }

    protected function setOrdernumbers(): void
    {
        $this->ordernumberField->setProduct($this->product);
        $this->ordernumbers = $this->ordernumberField->parse();
    }

    public function hasOrdernumbers(): bool
    {
        return $this->ordernumbers && !empty($this->ordernumbers);
    }

    /**
     * @return Property[]
     * @throws AccessEmptyPropertyException
     */
    public function getProperties(): array
    {
        if (!$this->hasProperties()) {
            throw new AccessEmptyPropertyException($this->product);
        }

        return $this->properties;
    }

    protected function setProperties(): void
    {
        $this->propertyField->setProduct($this->product);
        if ($this->product->getProperties() !== null) {
            $this->propertyField->setPropertyGroupOptionCollection($this->product->getProperties());
        }
        $this->properties = $this->propertyField->parse();
    }

    public function hasProperties(): bool
    {
        return $this->properties && !empty($this->properties);
    }

    /**
     * @deprecated Will be removed in 3.0.
     * @return Attribute[]
     */
    public function getCustomFields(): array
    {
        return $this->customFields;
    }
}
