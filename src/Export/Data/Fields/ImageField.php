<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\FinSearch\Export\ProductImageService;

class ImageField implements MultiValueExportFieldInterface
{
    use ExportContextAware;

    /** @var ProductImageService */
    protected $productImageService;

    public function __construct(ProductImageService $productImageService)
    {
        $this->productImageService = $productImageService;
    }

    /**
     * @return Image[]
     */
    public function parse(): array
    {
        return $this->productImageService->getProductImages($this->product);
    }
}
