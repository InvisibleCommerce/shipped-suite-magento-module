<?php

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;

class TrackObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.shipment.upsert';
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getTrack()->getShipment();
        $orderId = $shipment->getOrderId();
        if ($orderId) {
            $message = [
                'message' => $orderId
            ];
            $this->publisher->publish(OrderObserver::TOPIC_NAME, json_encode($message));
        }
    }
}
