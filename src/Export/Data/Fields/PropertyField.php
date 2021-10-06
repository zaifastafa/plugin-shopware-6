<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price as ProductPrice;
use Symfony\Contracts\Translation\TranslatorInterface;

class PropertyField implements MultiValueExportFieldInterface
{
    use ExportContextAware;
    use ProductPropertyAware;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return Property[]
     */
    public function parse(): array
    {
        /** @var Property[] $properties */
        $properties = [];

        if ($this->product->getTax()) {
            $taxRate = (string)$this->product->getTax()->getTaxRate();
            $properties[] = $this->buildProperty('tax', $taxRate);
        }

        if ($this->product->getDeliveryDate()->getLatest()) {
            $latestDeliveryDate = $this->product->getDeliveryDate()->getLatest()->format(DATE_ATOM);
            $properties[] = $this->buildProperty('latestdeliverydate', $latestDeliveryDate);
        }

        if ($this->product->getDeliveryDate()->getEarliest()) {
            $earliestDeliveryDate = $this->product->getDeliveryDate()->getEarliest()->format(DATE_ATOM);
            $properties[] = $this->buildProperty('earliestdeliverydate', $earliestDeliveryDate);
        }

        if ($this->product->getPurchaseUnit()) {
            $purchaseUnit = (string)$this->product->getPurchaseUnit();
            $properties[] = $this->buildProperty('purchaseunit', $purchaseUnit);
        }

        if ($this->product->getReferenceUnit()) {
            $referenceUnit = (string)$this->product->getReferenceUnit();
            $properties[] = $this->buildProperty('referenceunit', $referenceUnit);
        }

        if ($this->product->getPackUnit()) {
            $packUnit = (string)$this->product->getPackUnit();
            $properties[] = $this->buildProperty('packunit', $packUnit);
        }

        if ($this->product->getStock()) {
            $stock = (string)$this->product->getStock();
            $properties[] = $this->buildProperty('stock', $stock);
        }

        if ($this->product->getAvailableStock()) {
            $availableStock = (string)$this->product->getAvailableStock();
            $properties[] = $this->buildProperty('availableStock', $availableStock);
        }

        if ($this->product->getWeight()) {
            $weight = (string)$this->product->getWeight();
            $properties[] = $this->buildProperty('weight', $weight);
        }

        if ($this->product->getWidth()) {
            $width = (string)$this->product->getWidth();
            $properties[] = $this->buildProperty('width', $width);
        }

        if ($this->product->getHeight()) {
            $height = (string)$this->product->getHeight();
            $properties[] = $this->buildProperty('height', $height);
        }

        if ($this->product->getLength()) {
            $length = (string)$this->product->getLength();
            $properties[] = $this->buildProperty('length', $length);
        }

        if ($this->product->getReleaseDate()) {
            $releaseDate = $this->product->getReleaseDate()->format(DATE_ATOM);
            $properties[] = $this->buildProperty('releasedate', $releaseDate);
        }

        if ($this->product->getManufacturer() && $this->product->getManufacturer()->getMedia()) {
            $vendorLogo = $this->product->getManufacturer()->getMedia()->getUrl();
            $properties[] = $this->buildProperty('vendorlogo', $vendorLogo);
        }

        if ($this->product->getPrice()) {
            /** @var ProductPrice $price */
            $price = $this->product->getPrice()->getCurrencyPrice($this->salesChannelContext->getCurrency()->getId());
            if ($price) {
                /** @var ProductPrice $listPrice */
                $listPrice = $price->getListPrice();
                if ($listPrice) {
                    $properties[] = $this->buildProperty('old_price', (string)$listPrice->getGross());
                    $properties[] = $this->buildProperty('old_price_net', (string)$listPrice->getNet());
                }
            }
        }

        if (method_exists($this->product, 'getMarkAsTopseller')) {
            $isMarkedAsTopseller = $this->product->getMarkAsTopseller() ?? false;
            $translated = $this->translateBooleanValue($isMarkedAsTopseller);
            $properties[] = $this->buildProperty('product_promotion', $translated);
        }

        $properties = array_merge($properties, $this->parseNonFilterablePropertyGroupOption());

        return array_values(array_filter($properties));
    }

    protected function buildProperty(string $name, $value): ?Property
    {
        if (Utils::isEmpty($value)) {
            return null;
        }

        $property = new Property($name);
        $property->addValue($value);

        return $property;
    }

    protected function translateBooleanValue(bool $value)
    {
        $translationKey = $value ? 'finSearch.general.yes' : 'finSearch.general.no';

        return $this->translator->trans($translationKey);
    }

    /**
     * @return Property[]
     */
    protected function parseNonFilterablePropertyGroupOption(): array
    {
//        throw new \Exception('yeeeettt');

        $properties = [];

        $filteredCollection = $this->propertyGroupOptionCollection->filter(
            function (PropertyGroupOptionEntity $propertyGroupOptionEntity) {
                if (!$group = $propertyGroupOptionEntity->getGroup()) {
                    return false;
                }

                // Method getFilterable exists since Shopware 6.2.x.
                if (method_exists($group, 'getFilterable') && $group->getFilterable()) {
                    return false;
                }

                return true;
            }
        );

        $values = $this->parsePropertyGroups($filteredCollection);
        foreach ($values as $key => $value) {
            $property = new Property($key);
            $property->addValue($value);

            $properties[] = $property;
        }

        return $properties;
    }
}
