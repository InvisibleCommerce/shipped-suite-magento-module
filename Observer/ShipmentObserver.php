<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\Serialize\SerializerInterface;

class ShipmentObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.shipment.upsert';
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
        $shipment = $observer->getEvent()->getShipment();
        $orderId = $shipment->getOrderId();
        if ($orderId) {
            $message = [
                'message' => $orderId
            ];
            $this->publisher->publish(OrderObserver::TOPIC_NAME, $this->serializer->serialize($message));
        }
    }
}
