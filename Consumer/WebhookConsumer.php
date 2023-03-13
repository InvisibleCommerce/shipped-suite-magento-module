<?php

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Controller\Webhooks\Index;
use InvisibleCommerce\ShippedSuite\Model\ProcessRefund;
use InvisibleCommerce\ShippedSuite\Model\ProcessReplacement;
use Magento\Framework\MessageQueue\Publisher;
use Psr\Log\LoggerInterface;

class WebhookConsumer extends AbstractConsumer
{
    private ProcessRefund $processRefund;
    private ProcessReplacement $processReplacement;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        ProcessRefund $processRefund,
        ProcessReplacement $processReplacement
    ) {
        $this->processRefund = $processRefund;
        $this->processReplacement = $processReplacement;
        parent::__construct($logger, $publisher);
    }

    public function execute(string $payloadString): void
    {
        $this->logger->debug('processing webhook');

        $payload = json_decode($payloadString, true);
        switch ($payload['topic']) {
            case ProcessRefund::TOPIC_NAME:
                $this->processRefund->execute($payload);
            case ProcessReplacement::TOPIC_NAME:
                $this->processReplacement->execute($payload);
        }

        $this->logger->debug($payloadString);
    }

    protected function topicName(): string
    {
        return Index::TOPIC_NAME;
    }
}
