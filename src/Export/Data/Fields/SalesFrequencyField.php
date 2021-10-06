<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use DateTimeImmutable;
use FINDOLOGIC\Export\Data\SalesFrequency;
use FINDOLOGIC\Export\Helpers\Serializable;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class SalesFrequencyField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /** @var EntityRepository */
    protected $orderLineItemRepository;

    public function __construct(EntityRepository $orderLineItemRepository)
    {
        $this->orderLineItemRepository = $orderLineItemRepository;
    }

    /**
     * @return SalesFrequency
     */
    public function parse(): Serializable
    {
        $criteria = $this->buildCriteria();

        $orders = $this->orderLineItemRepository->searchIds($criteria, $this->salesChannelContext->getContext());
        $value = $orders->getTotal();

        $salesFrequency = new SalesFrequency();
        $salesFrequency->setValue($value);

        return $salesFrequency;
    }

    protected function buildCriteria(): Criteria
    {
        $lastMonthDate = new DateTimeImmutable('-1 month');

        $criteria = new Criteria();
        $criteria->addAssociation('order')
            ->addFilter(new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('productId', $this->product->getId()),
                    new RangeFilter(
                        'order.orderDateTime',
                        [RangeFilter::GTE => $lastMonthDate->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
                    )
                ]
            ));

        return $criteria;
    }
}
