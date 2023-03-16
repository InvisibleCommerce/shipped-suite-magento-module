<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model;

use Exception;
use InvisibleCommerce\ShippedSuite\Service\ShippedSuiteAPI;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class WidgetConfigProvider implements ConfigProviderInterface
{
    const STAGING_URL = 'https://js-staging.shippedsuite.com/api/widget.js';
    const PRODUCTION_URL = 'https://js.shippedsuite.com/api/widget.js';

    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;
    private Image $imageHelper;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->productRepository = $productRepository;
    }
    public function getConfig()
    {
        try {
            $currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (\Exception $e) {
            $currency = 'USD';
        }

        return [
            'shippedSuite' => [
                'shieldName' => Product::SHIPPED_SHIELD_NAME,
                'greenName' => Product::SHIPPED_GREEN_NAME,
                'shieldImageData' => $this->getImageData( $this->productRepository->get(Product::SHIPPED_SHIELD_SKU)),
                'greenImageData' => $this->getImageData( $this->productRepository->get(Product::SHIPPED_GREEN_SKU)),
                'shippedConfig' => [
                    'isWidgetOff' => $this->scopeConfig->getValue('shipped_suite_widget/widget/widget_display') === '0',
                    'isShield' => $this->scopeConfig->getValue('shipped_suite_widget/widget/shield') === '1',
                    'isGreen' => $this->scopeConfig->getValue('shipped_suite_widget/widget/green') === '1',
                    'isOffByDefault' => $this->scopeConfig->getValue('shipped_suite_widget/widget/default') == '0',
                    'appearance' => $this->scopeConfig->getValue('shipped_suite_widget/widget/appearance'),
                    'isInformational' => $this->scopeConfig->getValue('shipped_suite_widget/widget/informational') == '1',
                    'isMandatory' => $this->scopeConfig->getValue('shipped_suite_widget/widget/mandatory') == '1',
                    'publicKey' => $this->scopeConfig->getValue('shipped_suite_api/api/public_key'),
                    'currency' => $currency,
                    'widgetSelector' => '.shipped-widget',
                    'widgetStyle' => [
                        'marginRight' => 'auto',
                        'marginLeft' => '0'
                    ]
                ],
                'jsUrl' => $this->jsUrl()
            ]
        ];
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

    private function getImageData(ProductInterface $product): array
    {
        $imageHelper = $this->imageHelper->init(
            $product,
            'mini_cart_product_thumbnail'
        );

        return [
            'src' => $imageHelper->getUrl(),
            'alt' => $imageHelper->getLabel(),
            'width' => $imageHelper->getWidth(),
            'height' => $imageHelper->getHeight(),
        ];
    }
}
