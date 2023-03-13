<?php

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Psr\Log\LoggerInterface;

class CreditMemo
{
    private OrderItem $orderItemModel;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        OrderItem $orderItemModel
    ) {
        $this->logger = $logger;
        $this->orderItemModel = $orderItemModel;
    }

    public function json(CreditmemoInterface $creditMemo): array
    {
        return [
            'external_order_id' => $creditMemo->getOrder()->getId(),
            'external_id' => $creditMemo->getIncrementId(),
            'reversal_items' => $this->reversalItemsJson($creditMemo),
            'reversal_adjustments' => $this->reversalAdjustmentsJson($creditMemo),
            'processed_at' => $creditMemo->getCreatedAt()
        ];
    }

    private function reversalItemsJson(CreditmemoInterface $creditMemo): array
    {
        $items = array_values($creditMemo->getAllItems());
        return array_values(array_filter(array_map([$this, 'reversalItemJson'], $items)));
    }

    private function reversalItemJson(CreditmemoItemInterface $item): ?array
    {
        if (in_array($item->getOrderItem()->getProductType(), [Configurable::TYPE_CODE, Type::TYPE_BUNDLE])) {
            return null;
        } else {
            return [
                'external_id' => $item->getEntityId(),
                'external_order_item_id' => $this->orderItemModel->getExternalId($item->getOrderItem()),
                'quantity' => (int)$item->getQty(),
                'unit_price' => $item->getBasePrice(),
                'discount' => $item->getBaseDiscountAmount() ?: 0,
                'tax' => $item->getBaseTaxAmount() ?: 0,
                'display_unit_price' => $item->getPrice(),
                'display_discount' => $item->getDiscountAmount() ?: 0,
                'display_tax' => $item->getTaxAmount() ?: 0
            ];
        }
    }

    private function reversalAdjustmentsJson(CreditmemoInterface $creditMemo): ?array
    {
        if ($creditMemo->getBaseShippingAmount() == 0) {
            return null;
        } else {
            return [
                [
                    'external_id' => $creditMemo->getIncrementId() . '-shipping',
                    'category' => 'shipping',
                    'description' => 'shipping',
                    'amount' => $creditMemo->getBaseShippingAmount(),
                    'tax' => $creditMemo->getBaseShippingTaxAmount(),
                    'display_amount' => $creditMemo->getShippingAmount(),
                    'display_tax' => $creditMemo->getShippingTaxAmount()
                ]
            ];
        }
    }
}
