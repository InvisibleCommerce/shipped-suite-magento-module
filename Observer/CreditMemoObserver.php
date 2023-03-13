<?php

namespace InvisibleCommerce\ShippedSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\Publisher;

class CreditMemoObserver implements ObserverInterface
{
    const TOPIC_NAME = 'shippedsuite.reversal.upsert';
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function execute(Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $orderId = $creditMemo->getOrderId();
        if ($orderId) {
            $message = [
                'message' => $orderId
            ];
            $this->publisher->publish(OrderObserver::TOPIC_NAME, json_encode($message));
        }
    }
}
