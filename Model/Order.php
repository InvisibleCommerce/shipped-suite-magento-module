<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;

class Order
{
    private TransactionRepositoryInterface $repository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private OrderItem $orderItemModel;

    public function __construct(
        TransactionRepositoryInterface $repository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderItem $orderItemModel
    ) {
        $this->repository = $repository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderItemModel = $orderItemModel;
    }
    public function json(OrderInterface $order): array
    {
        return [
            'external_id' => $order->getEntityId(),
            'number' => $order->getIncrementId(),
            'email' => $order->getCustomerEmail(),
            'placed_at' => $order->getCreatedAt(),
            'canceled_at' => $this->canceledAt($order),
            'order_items' => $this->orderItemsJson($order),
            'customer' => $this->customerJson($order),
            'shipping_address' => $this->shippingAddressJson($order),
            'order_adjustments' => $this->adjustmentsJson($order),
            'transactions' => $this->transactionsJson($order),
            'display_currency' => $order->getOrderCurrencyCode(),
            'transaction_currency' => $order->getOrderCurrencyCode(),
            'shield_selected' => false, // implemented through order type product type already
            'green_selected' => false // implemented through order type product type already
        ];
    }

    private function canceledAt(OrderInterface $order): ?string
    {
        $commentCollection = $order->getStatusHistoryCollection();

        $orderCancelDate = null;
        foreach ($commentCollection as $comment) {
            if ($comment->getStatus() === \Magento\Sales\Model\Order::STATE_CANCELED) {
                $orderCancelDate = $comment->getCreatedAt();
            }
        }

        return $orderCancelDate;
    }

    private function orderItemsJson(OrderInterface $order): array
    {
        $items = $order->getAllItems();
        $itemsJson = array_map([$this->orderItemModel, 'json'], array_values($items));

        return array_values(array_filter($itemsJson));
    }

    private function customerJson(OrderInterface $order): array
    {
        return [
            'external_id' => $order->getCustomerId() == null ? "order-" . $order->getIncrementId() : $order->getCustomerId(),
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname(),
            'email' => $order->getCustomerEmail(),
            'notes' => $order->getCustomerNote(),
            'accepts_email_marketing' => false,
            'accepts_sms_marketing' => false
        ];
    }

    private function shippingAddressJson(OrderInterface $order): array
    {
        $shippingAddress = $order->getShippingAddress();

        return [
            'first_name' => $shippingAddress->getFirstname(),
            'last_name' => $shippingAddress->getLastname(),
            'address1' => $shippingAddress->getStreetLine(1),
            'address2' => $shippingAddress->getStreetLine(2),
            'city' => $shippingAddress->getCity(),
            'state' => $shippingAddress->getRegion(),
            'zip' => $shippingAddress->getPostcode(),
            'country' => $shippingAddress->getCountryId(),
            'phone' => $shippingAddress->getTelephone()
        ];
    }

    private function adjustmentsJson(OrderInterface $order): array
    {
        return [
            [
                'external_id' => $order->getIncrementId() . '-shipping',
                'category' => 'shipping',
                'description' => $order->getShippingDescription(),
                'amount' => $order->getBaseShippingAmount(),
                'tax' => $order->getBaseShippingTaxAmount(),
                'discount' => $order->getBaseShippingDiscountAmount(),
                'display_amount' => $order->getShippingAmount(),
                'display_tax' => $order->getShippingTaxAmount(),
                'display_discount' => $order->getShippingDiscountAmount()
            ]
        ];
    }

    private function transactionsJson(OrderInterface $order): array
    {
        $this->searchCriteriaBuilder->addFilter('order_id', $order->getEntityId());
        $list = $this->repository->getList(
            $this->searchCriteriaBuilder->create()
        );

        return array_filter(array_map(
            fn ($item) => $this->transactionJson($order, $item),
            array_values($list->getItems())
        ));
    }

    private function transactionJson(OrderInterface $order, TransactionInterface $item): ?array
    {
        if ($item->getTxnType() != 'capture' && $item->getTxnType() != 'refund') {
            return null;
        }

        $payment = $order->getPayment();

        return [
            'external_id' => $item->getTransactionId(),
            'gateway' => $payment->getMethod(),
            'gateway_id' => $item->getTxnId(),
            'category' => $item->getTxnType() == 'refund' ? 'refund' : 'sale',
            'currency' => $order->getOrderCurrencyCode(),
            // magento does not keep information about individual refunds, only total amount refunded on the payment
            'amount' => $item->getTxnType() == 'refund' ? $payment->getAmountRefunded() : $payment->getAmountPaid(),
            'status' => 'success',
            'processed_at' => $item->getCreatedAt()
        ];
    }
}
