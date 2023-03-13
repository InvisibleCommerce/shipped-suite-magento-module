<?php

namespace InvisibleCommerce\ShippedSuite\Consumer;

use Magento\Framework\MessageQueue\Publisher;
use Psr\Log\LoggerInterface;

abstract class AbstractConsumer
{
    const MAX_RETRIES = 3;

    protected LoggerInterface $logger;
    protected Publisher $publisher;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    abstract protected function execute(string $message): void;
    abstract protected function topicName(): string;

    public function process(string $message): void
    {
        $this->logger->debug('raw message ' . $message);
        $params = json_decode($message, true);
        if (is_int($params)) {
            return;
        }

        $metadata = $params['metadata'] ?? [
            'attempts' => 1
        ];
        $this->logger->debug('attempt no ' . $metadata['attempts']);

//        if (isset($metadata['run_at']) && time() < (int) $metadata['run_at']) {
//            $this->logger->debug('time not yet met. now ' . time() . ' waiting until ' . $metadata['run_at']);
//            // not yet time, re-enqueue for later without modification
//            $this->enqueue($params);
//            return;
//        }

        try {
            // child class to execute
            $this->execute($params['message']);
        } catch (\Exception $e) {
            $this->logger->debug('params ' . json_encode($params));
            $this->retry($e, $params, $metadata, $message);
        }
    }

    private function retry(\Exception$error, array $params, array $metadata, string $message): void
    {
        $metadata['attempts'] += 1;

        if ((int) $metadata['attempts'] > self::MAX_RETRIES) {
//            $this->logger->error($error);
            $this->logger->error('Terminal failure for payload ' . $message . ' ' . $error);
            return;
        } else {
            $this->logger->debug('message failed, retrying...');
        }

//        $metadata['run_at'] = $this->calculateRunAt($metadata['attempts']);
        $params['metadata'] = $metadata;
        // give it a little time before retrying?
        sleep(5);

        $this->enqueue($params);
    }

    private function enqueue(array $params): void
    {
        $this->publisher->publish($this->topicName(), json_encode($params));
    }

    private function calculateRunAt(int $attempts): int
    {
        $jitter = rand(30, 600);

        return ($attempts**5) + $jitter + time();
    }
}
