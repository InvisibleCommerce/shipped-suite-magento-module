<?php

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Sales\Api\Data\OrderInterface;

class Replacement
{
    public function json(OrderInterface $order): array
    {
        return [
            'number' => $order->getIncrementId(),
            'external_order_id' => $order->getEntityId(),
            'amount' => $order->getBaseGrandTotal()
        ];
    }
}
