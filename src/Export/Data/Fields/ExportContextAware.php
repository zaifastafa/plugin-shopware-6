<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait ExportContextAware
{
    /** @var SalesChannelContext|null */
    protected $salesChannelContext;

    /** @var ProductEntity|null */
    protected $product;

    public function setSalesChannelContext(SalesChannelContext $context): self
    {
        $this->salesChannelContext = $context;

        return $this;
    }

    public function setProduct(ProductEntity $product): self
    {
        $this->product = $product;

        return $this;
    }
}
