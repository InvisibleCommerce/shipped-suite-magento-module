<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Area;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class Product
{
    const SHIPPED_SHIELD_NAME = 'Shipped Shield Package Assurance';
    const SHIPPED_SHIELD_SKU = 'SHIPPED_ASSURANCE';
    const SHIPPED_GREEN_NAME = 'Shipped Green Carbon Neutral Shipment';
    const SHIPPED_GREEN_SKU = 'SHIPPED_CARBON';
    const MANAGED_SKUS = [self::SHIPPED_GREEN_SKU, self::SHIPPED_SHIELD_SKU];
    const MANAGE_PRODUCTS = [
        [
            'name' => self::SHIPPED_SHIELD_NAME,
            'sku' => self::SHIPPED_SHIELD_SKU,
            'image' => 'shield.png'
        ],
        [
            'name' => self::SHIPPED_GREEN_NAME,
            'sku' => self::SHIPPED_GREEN_SKU,
            'image' => 'green.png'
        ]
    ];
    private StoreManagerInterface $storeManager;
    private BlockFactory $blockFactory;
    private Emulation $appEmulation;

    public function __construct(
        StoreManagerInterface $storeManager,
        BlockFactory $blockFactory,
        Emulation $appEmulation
    ) {
        $this->storeManager = $storeManager;
        $this->blockFactory = $blockFactory;
        $this->appEmulation = $appEmulation;
    }

    public function json(ProductInterface $product): array
    {
        return [
            'name' => $product->getName(),
            'brand' => $product->getData('brand'),
            'category' => $product->getCategory(),
            'description' => $product->getData('description'),
            'external_id' => $product->getId(),
            'variants' => $this->variantsJson($product),
            'images' => [
                [
                    'external_id' => $product->getId(),
                    'url' => $this->getImageUrl($product)
                ]
            ]
        ];
    }

    private function variantJson(ProductInterface $variant, ProductInterface $product): array
    {
        return [
            'name' => $this->getVariantName($variant, $product),
            'external_id' => $variant->getId(),
            'sku' => $variant->getSku(),
            'price' => $variant->getPrice()
        ];
    }

    private function getVariantName(ProductInterface $variant, ProductInterface $product): ?string
    {
        if ($variant->getId() == $product->getId()) {
            // simple product, no variant name
            return null;
        } else {
            // real variant, remove parent name
            return str_replace($product->getName() . '-', '', $variant->getName());
        }
    }

    private function variantsJson(ProductInterface $product): array
    {
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            // product has variants (is configurable product)
            $productTypeInstance = $product->getTypeInstance();
            $variants = $productTypeInstance->getUsedProducts($product);

            return array_map(fn ($item) => $this->variantJson($item, $product), $variants);
        } else {
            // product has no variants (is simple product)
            return [
                $this->variantJson($product, $product)
            ];
        }
    }

    private function getImageUrl(ProductInterface $product, string $imageType = 'product_base_image')
    {
        $storeId = $this->storeManager->getStore()->getId();

        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $imageBlock =  $this->blockFactory->createBlock('Magento\Catalog\Block\Product\ListProduct');
        $productImage = $imageBlock->getImage($product, $imageType);
        $imageUrl = $productImage->getImageUrl();

        $this->appEmulation->stopEnvironmentEmulation();

        return $imageUrl;
    }
}
