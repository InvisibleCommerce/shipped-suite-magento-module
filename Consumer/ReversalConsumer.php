<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Observer\CreditMemoObserver;
use InvisibleCommerce\ShippedSuite\Service\ReversalsAPI;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Psr\Log\LoggerInterface;

class ReversalConsumer extends AbstractConsumer
{
    private CreditmemoRepositoryInterface $creditMemoRepository;
    private ReversalsAPI $reversalsAPI;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        CreditmemoRepositoryInterface $creditMemoRepository,
        ReversalsAPI $reversalsAPI,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->creditMemoRepository = $creditMemoRepository;
        $this->reversalsAPI = $reversalsAPI;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        parent::__construct($logger, $publisher, $serializer);
    }

    protected function execute(string $creditMemoId): void
    {
        if ($this->scopeConfig->getValue('shipped_suite_backend/backend/reversal_sync') !== '1') {
            return;
        }

        $creditMemo = $this->creditMemoRepository->get((int)$creditMemoId);
        $this->reversalsAPI->upsert($creditMemo);
    }

    protected function topicName(): string
    {
        return CreditMemoObserver::TOPIC_NAME;
    }
}
