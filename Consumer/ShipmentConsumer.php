<?php

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Api\ShipmentsAPI;
use InvisibleCommerce\ShippedSuite\Observer\TrackObserver;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;

class ShipmentConsumer extends AbstractConsumer
{
    private ShipmentRepositoryInterface $shipmentRepository;
    private ShipmentsAPI $shipmentsAPI;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentsAPI $shipmentsAPI
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentsAPI = $shipmentsAPI;
        parent::__construct($logger, $publisher);
    }

    public function execute(string $shipmentId): void
    {
        try {
            $shipment = $this->shipmentRepository->get((int)$shipmentId);
            $tracks = array_values($shipment->getTracks());

            if (isset($tracks[0])) {
                $this->shipmentsAPI->upsert($tracks[0]);
            } else {
                return;
            }
        } catch (\Exception $e) {
            // TODO: add retry logic if failed

            $this->logger->error($e);
            return;
        }
    }

    protected function topicName(): string
    {
        return TrackObserver::TOPIC_NAME;
    }
}
