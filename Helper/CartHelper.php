<?php

namespace InvisibleCommerce\ShippedSuite\Helper;

use InvisibleCommerce\ShippedSuite\Model\Product;
use InvisibleCommerce\ShippedSuite\Service\OffersAPI;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

class CartHelper
{
    private LoggerInterface $logger;
    private OffersAPI $offersAPI;
    private ProductRepositoryInterface $productRepository;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        OffersAPI $offersAPI,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->offersAPI = $offersAPI;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
    }

    public function removeManagedProducts(CartInterface &$quote): CartInterface
    {
        $this->logger->debug('trying to remove managed products');
        foreach ($quote->getAllItems() as $item) {
            $this->logger->debug($item->getSku());
            if (!in_array($item->getSku(), Product::MANAGED_SKUS)) {
                $this->logger->debug('skipped');
                continue;
            }

            $itemId = $item->getItemId();
            $quote->removeItem($itemId);
            $this->logger->debug('removed');
        }

        return $quote;
    }

    public function addManagedProducts(CartInterface &$quote): CartInterface
    {
        $offers = $this->getOffers($quote);
        foreach ($offers as $sku => $price) {
            $this->addOffer($quote, $sku, $price);
        }
        $quote->save();

        return $quote;
    }

    private function addOffer(CartInterface &$quote, string $sku, float $price): void
    {
        if ($this->scopeConfig->getValue('shipped_suite_widget/widget/shield') !== '1' && $sku === Product::SHIPPED_SHIELD_SKU) {
            return;
        }
        if ($this->scopeConfig->getValue('shipped_suite_widget/widget/green') !== '1' && $sku === Product::SHIPPED_GREEN_SKU) {
            return;
        }

        $product = $this->productRepository->get($sku);
        $product->setPrice($price);
        $item = $quote->addProduct(
            $product,
            1
        );
        $item->setCustomPrice($price);
        $item->setOriginalCustomPrice($price);
        $item->setBaseOriginalPrice($price);
        $item->setOriginalPrice($price);
        $item->setPrice($price);
        $item->calcRowTotal();
    }

    private function getOffers(CartInterface $quote): array
    {
        $items = $quote->getItems();
        $subtotal = 0;
        foreach ($items as $item) {
            if (in_array($item->getSku(), Product::MANAGED_SKUS)) {
                continue;
            }

            $subtotal += $item->getPrice() * $item->getQty();
        }

        $response = $this->offersAPI->getOffer($subtotal);
        $this->logger->debug($response);
        $offersJson = json_decode($response, true);

        $offers = [];
        if (!empty($offersJson['shield_fee'])) {
            $offers[Product::SHIPPED_SHIELD_SKU] = (float)$offersJson['shield_fee'];
        }
        if (!empty($offersJson['green_fee'])) {
            $offers[Product::SHIPPED_GREEN_SKU] = (float)$offersJson['green_fee'];
        }

        return $offers;
    }
}
