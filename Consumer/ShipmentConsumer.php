<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Observer\TrackObserver;
use InvisibleCommerce\ShippedSuite\Service\ShipmentsAPI;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;

class ShipmentConsumer extends AbstractConsumer
{
    private ShipmentRepositoryInterface $shipmentRepository;
    private ShipmentsAPI $shipmentsAPI;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentsAPI $shipmentsAPI,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentsAPI = $shipmentsAPI;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        parent::__construct($logger, $publisher, $serializer);
    }

    public function execute(string $shipmentId): void
    {
        if ($this->scopeConfig->getValue('shipped_suite_backend/backend/shipment_sync') !== '1') {
            return;
        }

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
