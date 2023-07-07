<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderItem
{
    public function json(OrderItemInterface $item): ?array
    {
        // if configurable, skip, we want the simple product instead
        // if bundle, skip, we want the children simple products
        // otherwise, we want the simple product

        if (in_array($item->getProductType(), [Configurable::TYPE_CODE, Type::TYPE_BUNDLE])) {
            return null;
        }

        $representativeItem = $this->getRepresentativeProduct($item);

        return [
            'external_id' => $this->getExternalId($item),
            'external_product_id' => $this->getParentId($item),
            'external_variant_id' => $item->getProductId(),
            'sku' => $item->getSku(),
            'description' => $item->getName(),
            'quantity' => $item->getQtyOrdered() - $item->getQtyCanceled(),
            'unit_price' => $representativeItem->getBasePrice(),
            'discount' => $representativeItem->getBaseDiscountAmount(),
            'tax' => $representativeItem->getBaseTaxAmount(),
            'display_unit_price' => $representativeItem->getPrice(),
            'display_discount' => $representativeItem->getDiscountAmount(),
            'display_tax' => $representativeItem->getTaxAmount(),
            'product_type' => $this->orderItemProductType($item)
        ];
    }

    private function getRepresentativeProduct(OrderItemInterface $item): OrderItemInterface
    {
        $parent = $item->getParentItem();
        if ($parent && $parent->getProductType() == Configurable::TYPE_CODE) {
            return $parent;
        } else {
            return $item;
        }
    }

    public function getExternalId(OrderItemInterface $item): int
    {
        $parent = $item->getParentItem();
        if ($parent && $parent->getProductType() == Configurable::TYPE_CODE) {
            return (int)$parent->getItemId();
        } else {
            return (int)$item->getItemId();
        }
    }

    private function getParentId(OrderItemInterface $item): int
    {
        $parent = $item->getParentItem();
        if ($parent) {
            return (int)$parent->getProductId();
        } else {
            return (int)$item->getProductId();
        }
    }

    private function orderItemProductType(OrderItemInterface $item): string
    {
        switch ($item->getSku()) {
           case Product::SHIPPED_GREEN_SKU:
               return 'carbon';
               break;
           case Product::SHIPPED_SHIELD_SKU:
               return 'insurance';
               break;
           default:
               return 'regular';
       }
    }
}
