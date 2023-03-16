<?php

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Observer\CreditMemoObserver;
use InvisibleCommerce\ShippedSuite\Observer\OrderObserver;
use InvisibleCommerce\ShippedSuite\Observer\ProductObserver;
use InvisibleCommerce\ShippedSuite\Observer\ShipmentObserver;
use InvisibleCommerce\ShippedSuite\Service\OrdersAPI;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\OrderRepositoryFactory;
use Psr\Log\LoggerInterface;

class OrderConsumer extends AbstractConsumer
{
    private OrderRepositoryFactory $orderRepository;
    private OrdersAPI $ordersAPI;
    private ShipmentRepositoryInterface $shipmentRepository;
    private CreditmemoRepositoryInterface $creditmemoRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        OrderRepositoryFactory $orderRepository,
        OrdersAPI $ordersAPI,
        ShipmentRepositoryInterface $shipmentRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->ordersAPI = $ordersAPI;
        $this->shipmentRepository = $shipmentRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($logger, $publisher);
    }

    protected function execute(string $orderId): void
    {
        if ($this->scopeConfig->getValue('shipped_suite_backend/backend/order_sync') !== '1') {
            return;
        }

        $orderRepository = $this->orderRepository->create();
        $order = $orderRepository->get($orderId);

        // update order
        $this->ordersAPI->upsert($order);

        // enqueue product updates
        foreach ($order->getItems() as $item) {
            $message = [
                'message' => $item->getProductId()
            ];
            $this->publisher->publish(ProductObserver::TOPIC_NAME, json_encode($message));
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        // enqueue shipment updates
        $shipments = $this->shipmentRepository->getList($searchCriteria);
        foreach ($shipments->getItems() as $shipment) {
            $message = [
                'message' => $shipment->getId()
            ];
            $this->publisher->publish(ShipmentObserver::TOPIC_NAME, json_encode($message));
        }

        // enqueue credit memo updates
        $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
        foreach ($creditmemos->getItems() as $creditmemo) {
            $message = [
                'message' => $creditmemo->getId()
            ];
            $this->publisher->publish(CreditMemoObserver::TOPIC_NAME, json_encode($message));
        }
    }

    protected function topicName(): string
    {
        return OrderObserver::TOPIC_NAME;
    }
}
