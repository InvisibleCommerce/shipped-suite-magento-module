<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\Serialize\SerializerInterface;

class OrderObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.order.upsert';
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
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getEntityId();
        if ($orderId) {
            $message = [
                'message' => $orderId
            ];
            $this->publisher->publish(self::TOPIC_NAME, $this->serializer->serialize($message));
        }
    }
}
