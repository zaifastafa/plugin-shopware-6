<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Url;
use FINDOLOGIC\Export\Helpers\Serializable;
use FINDOLOGIC\FinSearch\Export\UrlBuilderService;

class UrlField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /** @var UrlBuilderService */
    protected $urlBuilderService;

    public function __construct(UrlBuilderService $urlBuilderService)
    {
        $this->urlBuilderService = $urlBuilderService;
    }

    /**
     * @return Url
     */
    public function parse(): Serializable
    {
        $value = $this->urlBuilderService->buildProductUrl($this->product);

        $url = new Url();
        $url->setValue($value);

        return $url;
    }
}
