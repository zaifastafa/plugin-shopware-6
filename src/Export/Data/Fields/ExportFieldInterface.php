<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\FinSearch\Exceptions\Export\ExportException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ExportFieldInterface
{
    /**
     * @throws ExportException
     */
    public function parse();

    public function setSalesChannelContext(SalesChannelContext $context): self;

    public function setProduct(ProductEntity $product): self;
}
