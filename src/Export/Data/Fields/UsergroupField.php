<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Usergroup;
use FINDOLOGIC\FinSearch\Utils\Utils;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;

class UsergroupField implements MultiValueExportFieldInterface
{
    use ExportContextAware;

    /** @var string */
    protected $shopkey;

    /** @var CustomerGroupEntity[] */
    protected $customerGroups = [];

    public function setShopkey(string $shopkey): void
    {
        $this->shopkey = $shopkey;
    }

    /**
     * @param CustomerGroupEntity[] $customerGroups
     */
    public function setCustomerGroups(array $customerGroups): void
    {
        $this->customerGroups = $customerGroups;
    }

    /**
     * @return Usergroup[]
     */
    public function parse(): array
    {
        /** @var Usergroup[] $usergroups */
        $usergroups = [];

        foreach ($this->customerGroups as $customerGroupEntity) {
            $usergroups[] = new Usergroup(
                Utils::calculateUserGroupHash($this->shopkey, $customerGroupEntity->getId())
            );
        }

        return $usergroups;
    }
}
