<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Tests\Traits\DataHelpers;

use FINDOLOGIC\FinSearch\Findologic\Config\FindologicConfigService;
use FINDOLOGIC\FinSearch\Findologic\Resource\ServiceConfigResource;
use FINDOLOGIC\FinSearch\Struct\Config;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Defaults;

trait ConfigHelper
{
    public function getShopkey(): string
    {
        return '74B87337454200D4D33F80C4663DC5E5';
    }

    public function getConfig(bool $assoc = true, string $fileName = 'di_config.json')
    {
        $config = file_get_contents(__DIR__ . '/../../MockData/ConfigResponse/' . $fileName);
        if ($assoc) {
            return json_decode($config, true);
        }

        return $config;
    }

    /**
     * Creates a system config service mock with default findologic config values initialized
     * Passing the data array will override any default values if needed
     */
    private function getDefaultFindologicConfigServiceMock(array $overrides = []): FindologicConfigService
    {
        /** @var FindologicConfigService|MockObject $configServiceMock */
        $configServiceMock = $this->createMock(FindologicConfigService::class);
        $salesChannelId = Defaults::SALES_CHANNEL;
        $languageId = Defaults::LANGUAGE_SYSTEM;
        if (isset($overrides['salesChannelId'])) {
            $salesChannelId = $overrides['salesChannelId'];
            unset($overrides['salesChannelId']);
        }
        if (isset($overrides['languageId'])) {
            $languageId = $overrides['languageId'];
            unset($overrides['languageId']);
        }
        $defaultConfig = [
            'active' => true,
            'isStaging' => false,
            'shopkey' => $this->getShopkey(),
            'activeOnCategoryPages' => true,
            'crossSellingCategories' => [],
            'searchResultContainer' => '.fl-result',
            'navigationResultContainer' => '.fl-navigation-result',
            'integrationType' => 'Direct Integration',
            'mainVariant' => 'default',
            'advancedPricing' => 'OFF',
            'exportZeroPricedProducts' => false
        ];

        $config = array_merge($defaultConfig, $overrides);

        $configServiceMock->method('get')
            ->willReturnMap(
                [
                    [
                        'FinSearch.config.active',
                        $salesChannelId,
                        $languageId,
                        $config['active']
                    ],
                    [
                        'FinSearch.config.isStaging',
                        $salesChannelId,
                        $languageId,
                        $config['isStaging']
                    ],
                    [
                        'FinSearch.config.shopkey',
                        $salesChannelId,
                        $languageId,
                        $config['shopkey']
                    ],
                    [
                        'FinSearch.config.activeOnCategoryPages',
                        $salesChannelId,
                        $languageId,
                        $config['activeOnCategoryPages']
                    ],
                    [
                        'FinSearch.config.crossSellingCategories',
                        $salesChannelId,
                        $languageId,
                        $config['crossSellingCategories']
                    ],
                    [
                        'FinSearch.config.searchResultContainer',
                        $salesChannelId,
                        $languageId,
                        $config['searchResultContainer']
                    ],
                    [
                        'FinSearch.config.navigationResultContainer',
                        $salesChannelId,
                        $languageId,
                        $config['navigationResultContainer']
                    ],
                    [
                        'FinSearch.config.integrationType',
                        $salesChannelId,
                        $languageId,
                        $config['integrationType']
                    ],
                    [
                        'FinSearch.config.mainVariant',
                        $salesChannelId,
                        $languageId,
                        $config['mainVariant']
                    ],
                    [
                        'FinSearch.config.advancedPricing',
                        $salesChannelId,
                        $languageId,
                        $config['advancedPricing']
                    ],
                    [
                        'FinSearch.config.exportZeroPricedProducts',
                        $salesChannelId,
                        $languageId,
                        $config['exportZeroPricedProducts']
                    ]
                ]
            );

        return $configServiceMock;
    }
}
