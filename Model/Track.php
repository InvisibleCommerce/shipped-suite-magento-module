<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\Data\TrackInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Track
{
    private LoggerInterface $logger;
    private OrderRepositoryInterface $orderRepository;
    private OrderItem $orderItemModel;

    public function __construct(
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        OrderItem $orderItemModel
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderItemModel = $orderItemModel;
    }

    public function json(TrackInterface $track): array
    {
        $shipment = $track->getShipment();
        $order = $this->orderRepository->get((int)$shipment->getOrderId());

        return [
            'external_order_id' => $order->getId(),
            'external_id' => $shipment->getIncrementId(),
            'courier_name' => $track->getTitle(),
            'tracking_number' => $track->getTrackNumber(),
            'fulfilled_at' => $track->getCreatedAt(),
            'shipment_items' => $this->shipmentItemsJson($shipment)
        ];
    }

    private function shipmentItemsJson(ShipmentInterface $shipment): array
    {
        $shipmentItems = array_values($shipment->getAllItems());
        $shipmentItemJsons = [];

        foreach ($shipmentItems as $shipmentItem) {
            if ($shipmentItem->getOrderItem()->getProductType() == Type::TYPE_BUNDLE) {
                // bundle items are shipped at the bundle level, but we track at the children level
                foreach ($shipmentItem->getOrderItem()->getChildrenItems() as $orderItem) {
                    $shipmentItemJsons[] = $this->bundleItemJson($shipmentItem, $orderItem);
                }
            } else {
                $shipmentItemJsons[] = $this->shipmentItemJson($shipmentItem);
            }
        }

        return $shipmentItemJsons;
    }

    private function shipmentItemJson(ShipmentItemInterface $item): ?array
    {
        return [
            'external_id' => $item->getEntityId(),
            'external_order_item_id' => $this->orderItemModel->getExternalId($item->getOrderItem()),
            'quantity' => (int)$item->getQty()
        ];
    }

    private function bundleItemJson(ShipmentItemInterface $item, OrderItemInterface $orderItem): array
    {
        return [
            'external_id' => 'oi' . $orderItem->getItemId(),
            'external_order_item_id' => $this->orderItemModel->getExternalId($orderItem),
            'quantity' => (int)$item->getQty()
        ];
    }
}
