<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;

trait ProductPropertyAware
{
    /** @var PropertyGroupOptionCollection|null */
    protected $propertyGroupOptionCollection;

    public function setPropertyGroupOptionCollection(
        ?PropertyGroupOptionCollection $propertyGroupOptionCollection
    ): self {
        $this->propertyGroupOptionCollection = $propertyGroupOptionCollection;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    protected function parsePropertyGroups(PropertyGroupOptionCollection $collection): array
    {
        /** @var array<string, string> $values */
        $values = [];

        foreach ($collection as $propertyGroupOptionEntity) {
            $group = $propertyGroupOptionEntity->getGroup();
            if ($group && $propertyGroupOptionEntity->getTranslation('name') && $group->getTranslation('name')) {
                $groupName = $this->getAttributeKey($group->getTranslation('name'));
                $propertyGroupOptionName = $propertyGroupOptionEntity->getTranslation('name');
                if (!Utils::isEmpty($groupName) && !Utils::isEmpty($propertyGroupOptionName)) {
                    $values[$groupName] = Utils::removeControlCharacters($propertyGroupOptionName);
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
                    $values[$groupName] = Utils::removeControlCharacters($optionName);
                }
            }
        }

        return $values;
    }

    /**
     * For API Integrations, we have to remove special characters from the attribute key as a requirement for
     * sending data via API.
     */
    protected function getAttributeKey(?string $key): ?string
    {
        if ($this->isApiIntegration()) {
            return Utils::removeSpecialChars($key);
        }

        return $key;
    }
}
