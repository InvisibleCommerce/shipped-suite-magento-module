<?php

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Api\ReversalsAPI;
use InvisibleCommerce\ShippedSuite\Observer\CreditMemoObserver;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Psr\Log\LoggerInterface;

class ReversalConsumer extends AbstractConsumer
{
    private CreditmemoRepositoryInterface $creditMemoRepository;
    private ReversalsAPI $reversalsAPI;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        CreditmemoRepositoryInterface $creditMemoRepository,
        ReversalsAPI $reversalsAPI
    ) {
        $this->creditMemoRepository = $creditMemoRepository;
        $this->reversalsAPI = $reversalsAPI;
        parent::__construct($logger, $publisher);
    }

    protected function execute(string $creditMemoId): void
    {
        $creditMemo = $this->creditMemoRepository->get((int)$creditMemoId);
        $this->reversalsAPI->upsert($creditMemo);
    }

    protected function topicName(): string
    {
        return CreditMemoObserver::TOPIC_NAME;
    }
}
