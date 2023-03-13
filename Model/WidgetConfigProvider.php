<?php

namespace InvisibleCommerce\ShippedSuite\Model;

use Exception;
use InvisibleCommerce\ShippedSuite\Api\ShippedSuiteAPI;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class WidgetConfigProvider implements ConfigProviderInterface
{
    const STAGING_URL = 'https://js-staging.shippedsuite.com/api/widget.js';
    const PRODUCTION_URL = 'https://js.shippedsuite.com/api/widget.js';

    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }
    public function getConfig()
    {
        $config = [
            'shippedSuite' => [
                'shippedConfig' => [
                    'isWidgetOff' => $this->scopeConfig->getValue('shipped_suite_widget/widget/widget_display') === '0',
                    'isShield' => $this->scopeConfig->getValue('shipped_suite_widget/widget/shield') === '1',
                    'isGreen' => $this->scopeConfig->getValue('shipped_suite_widget/widget/green') === '1',
                    'isOffByDefault' => $this->scopeConfig->getValue('shipped_suite_widget/widget/default') == '0',
                    'isInformational' => $this->scopeConfig->getValue('shipped_suite_widget/widget/informational') == '1',
                    'isMandatory' => $this->scopeConfig->getValue('shipped_suite_widget/widget/mandatory') == '1',
                    'publicKey' => $this->scopeConfig->getValue('shipped_suite_api/api/public_key'),
                    'currency' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                    'widgetSelector' => '.shipped-widget'
                ],
                'jsUrl' => $this->jsUrl()
            ]
        ];

        return $config;
    }

    /**
     * @throws Exception
     */
    private function jsUrl(): string
    {
        $environment = $this->scopeConfig->getValue('shipped_suite_api/api/environment');

        return match ($environment) {
            ShippedSuiteAPI::ENVIRONMENT_STAGING => self::STAGING_URL,
            ShippedSuiteAPI::ENVIRONMENT_PRODUCTION => self::PRODUCTION_URL,
            default => throw new Exception('Unknown environment'),
        };
    }
}
