<?php

namespace InvisibleCommerce\ShippedSuite\Controller\Deploy;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\MessageQueue\Publisher;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface;
use Magento\MessageQueue\Model\ConsumersRunner;
use Magento\Framework\Lock\LockManagerInterface;

class Config extends Action implements HttpGetActionInterface
{
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private Publisher $publisher;
    private DeploymentConfig $deploymentConfig;
    private ConfigInterface $consumerConfig;
    private ConsumersRunner $consumerRunner;
    private LockManagerInterface $lockManager;

    const TOPIC_NAME = 'shippedsuite.webhook.process';

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Publisher $publisher,
        DeploymentConfig $deploymentConfig,
        ConfigInterface $consumerConfig,
        ConsumersRunner $consumerRunner,
        LockManagerInterface $lockManager
    ) {
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
        $this->publisher = $publisher;
        $this->deploymentConfig = $deploymentConfig;
        $this->consumerConfig = $consumerConfig;
        $this->consumerRunner = $consumerRunner;
        $this->lockManager = $lockManager;
        parent::__construct($context);
    }
    public function execute()
    {
        $runByCron = $this->deploymentConfig->get('cron_consumers_runner/cron_run', true);
        $multipleProcesses = $this->deploymentConfig->get('cron_consumers_runner/multiple_processes', []);
        $maxMessages = (int) $this->deploymentConfig->get('cron_consumers_runner/max_messages', 10000);
        $allowedConsumers = $this->deploymentConfig->get('cron_consumers_runner/consumers', []);

        $resultJson = $this->jsonFactory->create();
        $resultJson = $resultJson->setHttpResponseCode(200);
        $consumers = [];
        foreach ($this->consumerConfig->getConsumers() as $consumer) {
            $consumers[] = [
                'canBeRun' => $this->consumerRunner->canBeRun($consumer, $allowedConsumers),
                'name' => $consumer->getName(),
                'locked' => $this->lockManager->isLocked(md5($consumer->getName()))
            ];
        }
        $resultJson = $resultJson->setData([
            '$runByCron' => $runByCron,
            '$multipleProcesses' => $multipleProcesses,
            '$maxMessages' => $maxMessages,
            '$allowedConsumers' => $allowedConsumers,
            '$consumers' => $consumers
        ]);

        return $resultJson;
    }
}
