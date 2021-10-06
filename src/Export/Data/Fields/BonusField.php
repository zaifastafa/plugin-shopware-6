<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Data\Fields;

use FINDOLOGIC\Export\Data\Bonus;
use FINDOLOGIC\Export\Helpers\Serializable;

class BonusField implements SingleValueExportFieldInterface
{
    use ExportContextAware;

    /**
     * @return Bonus
     */
    public function parse(): Serializable
    {
        $bonus = new Bonus();
        $bonus->setValue(0);

        return $bonus;
    }
}
