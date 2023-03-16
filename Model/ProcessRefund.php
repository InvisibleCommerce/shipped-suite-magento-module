<?php

namespace InvisibleCommerce\ShippedSuite\Model;

use InvisibleCommerce\ShippedSuite\Service\RefundsAPI;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoCommentCreationInterface;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\RefundInvoiceInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\ItemCreation;
use Psr\Log\LoggerInterface;

class ProcessRefund
{
    private OrderRepositoryInterface $orderRepository;
    private CreditmemoRepositoryInterface $creditmemoRepository;
    private InvoiceRepositoryInterface $invoiceRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private CreditmemoItemCreationInterface $creditmemoItemCreation;
    private Creditmemo\CreationArguments $creationArguments;
    private LoggerInterface $logger;
    private RefundInvoiceInterface $refundInvoice;
    private RefundsAPI $refundsAPI;
    private CreditmemoCommentCreationInterface $commentCreation;

    const TOPIC_NAME = 'refund.requested';

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ItemCreation $creditmemoItemCreation,
        LoggerInterface $logger,
        RefundsAPI $refundsAPI,
        RefundInvoiceInterface $refundInvoice,
        Creditmemo\CreationArguments $creationArguments,
        CreditmemoCommentCreationInterface $commentCreation
    ) {
        $this->commentCreation = $commentCreation;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->creditmemoItemCreation = $creditmemoItemCreation;
        $this->refundInvoice = $refundInvoice;
        $this->logger = $logger;
        $this->refundsAPI = $refundsAPI;
        $this->creationArguments = $creationArguments;
    }

    public function execute(array $payload)
    {
        if ($payload['topic'] != self::TOPIC_NAME) {
            return;
        }
        $this->logger->debug('ProcessRefund called');

        $payload = $payload['payload'];

        $orderId = $payload['order']['external_id'];
        $this->logger->debug('order id ' . $orderId);
        $order = $this->orderRepository->get((int)$orderId);
        $this->logger->debug('order ' . $order->getIncrementId());

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();
        $invoices = array_values($this->invoiceRepository->getList($searchCriteria)->getItems());

        $affectedItems = $payload['affected_items'];
        $this->logger->debug('affected items ' . json_encode($affectedItems));
        $existingCreditmemos = array_values($this->creditmemoRepository->getList($searchCriteria)->getItems());

        $creditMemos = [];
        foreach ($invoices as $invoice) {
            $creditMemos[] = $this->processInvoice($invoice, $payload, $existingCreditmemos, $affectedItems);
        }

        $this->logger->debug(json_encode($creditMemos));

        if (!empty($creditMemos)) {
            $this->refundsAPI->upsert($payload['id'], $creditMemos);
        }
    }

    private function processInvoice(
        InvoiceInterface $invoice,
        array $payload,
        array $existingCreditmemos,
        array &$affectedItems
    ): ?Creditmemo {
        $this->logger->debug('invoice ' . $invoice->getIncrementId());
        $invoiceItems = array_values($invoice->getItems());
        $itemsToRefund = [];

        foreach ($invoiceItems as $invoiceItem) {
            $qtyRefundable = $this->qtyRefundable($invoice, $invoiceItem, $existingCreditmemos);
            if ($qtyRefundable <= 0) {
                // invoice item already fully refunded
                continue;
            }

            foreach ($affectedItems as $key => $affectedItem) {
                if ($affectedItem['external_id'] == $invoiceItem->getOrderItemId()) {
                    if ($affectedItem['quantity'] <= 0) {
                        // affected item already fully refunded
                        continue;
                    }

                    $this->logger->debug('want to refund ' . $affectedItem['quantity']);
                    $this->logger->debug('can refund ' . $qtyRefundable);
                    $qtyToRefund = min($affectedItem['quantity'], $qtyRefundable);
                    $this->logger->debug('will refund ' . $qtyToRefund);

                    $affectedItems[$key]['quantity'] = $affectedItem['quantity'] - $qtyToRefund;
                    $itemsToRefund[] = $this->creditmemoItemCreation->setQty($qtyToRefund)
                        ->setOrderItemId($invoiceItem->getOrderItemId());
                }
            }
        }

        if (empty($itemsToRefund)) {
            // nothing to refund, skip credit memo creation
            $this->logger->debug('no items to refund');
            return null;
        }

        return $this->executeRefund($invoice, $itemsToRefund, $payload);
    }

    private function executeRefund(InvoiceInterface $invoice, array $itemsToRefund, array $payload): Creditmemo
    {
        $arguments = $this->creationArguments->setShippingAmount(0);

        $response = $this->refundInvoice->execute(
            $invoice->getEntityId(),
            $itemsToRefund,
            true,
            true,
            true,
            $this->commentCreation->setComment('Shipped Suite RefundRequest ID ' . $payload['id']),
            $arguments
        );
        $this->logger->debug((string)$response);

        return $this->creditmemoRepository->get($response);
    }

    private function qtyRefundable(InvoiceInterface $invoice, InvoiceItemInterface $invoiceItem, array $creditmemos)
    {
        /* @var CreditMemo[] $creditmemos */

        if (empty($creditmemos)) {
            return $invoiceItem->getQty();
        }

        $tally = 0;

        foreach ($creditmemos as $creditmemo) {
            if ($creditmemo->getInvoice()->getEntityId() == $invoice->getEntityId()) {
                $creditMemoItems = array_values($creditmemo->getItems());
                foreach ($creditMemoItems as $creditMemoItem) {
                    if ($invoiceItem->getOrderItemId() == $creditMemoItem->getOrderItemId()) {
                        $tally += $creditMemoItem->getQty();
                    }
                }
            }
        }

        return $invoiceItem->getQty() - $tally;
    }
}
