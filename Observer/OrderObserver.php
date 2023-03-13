<?php

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;

class OrderObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.order.upsert';
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getEntityId();
        if ($orderId) {
            $message = [
                'message' => $orderId
            ];
            $this->publisher->publish(self::TOPIC_NAME, json_encode($message));
        }
    }
}
