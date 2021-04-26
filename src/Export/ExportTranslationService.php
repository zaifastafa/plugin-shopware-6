<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export;

use Symfony\Contracts\Translation\TranslatorInterface;

class ExportTranslationService
{
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function translateBoolean(bool $value): string
    {
        $translationKey = $value ? 'finSearch.general.yes' : 'finSearch.general.no';

        return $this->translator->trans($translationKey);
    }
}
