<?php

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;

class ProductObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.product.upsert';
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $productId = $product->getEntityId();
        if ($productId) {
            $message = [
                'message' => $productId
            ];
            $this->publisher->publish(self::TOPIC_NAME, json_encode($message));
        }
    }
}
