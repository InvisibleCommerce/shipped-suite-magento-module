<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Framework\MessageQueue\Publisher;

class OrderPlaced
{
    const TOPIC_NAME = 'shippedsuite.order.upsert';
    private $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function afterPlace(
        OrderManagementInterface $subject,
        OrderInterface $return
    ) {
        $message = [
            'message' => $return->getId()
        ];
        $this->publisher->publish(self::TOPIC_NAME, json_encode($message));

        return $return;
    }
}
