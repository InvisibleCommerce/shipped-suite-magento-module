<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\Serialize\SerializerInterface;

class ProductObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.product.upsert';
    private Publisher $publisher;
    private SerializerInterface $serializer;

    public function __construct(
        Publisher $publisher,
        SerializerInterface $serializer
    ) {
        $this->publisher = $publisher;
        $this->serializer = $serializer;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $productId = $product->getEntityId();
        if ($productId) {
            $message = [
                'message' => $productId
            ];
            $this->publisher->publish(self::TOPIC_NAME, $this->serializer->serialize($message));
        }
    }
}
