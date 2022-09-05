<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Events;

use FINDOLOGIC\Export\Data\Item;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Contracts\EventDispatcher\Event;

class AfterItemAdaptEvent extends Event
{
    public const NAME = 'fin_search.export.after_item_adapt';

    protected ProductEntity $product;

    protected Item $item;

    public function __construct(ProductEntity $product, Item $item)
    {
        $this->product = $product;
        $this->item = $item;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function getItem(): Item
    {
        return $this->item;
    }
}
