<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Keyword;
use FINDOLOGIC\FinSearch\Utils\Utils;

class KeywordField implements MultiValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return Keyword[]
     */
    public function parse(): array
    {
        /** @var Keyword[] $keywords */
        $keywords = [];
        $tags = $this->product->getTags();

        if ($tags === null || $tags->count() === 0) {
            return $keywords;
        }

        foreach ($tags as $tag) {
            if (!Utils::isEmpty($tag->getName())) {
                $keywords[] = new Keyword($tag->getName());
            }
        }

        return $keywords;
    }
}
