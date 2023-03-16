<?php

namespace InvisibleCommerce\ShippedSuite\Controller\Deploy;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\MessageQueue\Consumer\Config\ConsumerConfigItemInterface;
use Magento\Framework\MessageQueue\Publisher;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface;
use Magento\MessageQueue\Model\Cron\ConsumersRunner;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\MessageQueue\Model\CheckIsAvailableMessagesInQueue;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;

class Config extends Action implements HttpGetActionInterface
{
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private Publisher $publisher;
    private DeploymentConfig $deploymentConfig;
    private ConfigInterface $consumerConfig;
    private ConsumersRunner $consumerRunner;
    private LockManagerInterface $lockManager;
    private CheckIsAvailableMessagesInQueue $checkIsAvailableMessages;
    private ConnectionTypeResolver $mqConnectionTypeResolver;

    const TOPIC_NAME = 'shippedsuite.webhook.process';

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Publisher $publisher,
        DeploymentConfig $deploymentConfig,
        ConfigInterface $consumerConfig,
        ConsumersRunner $consumerRunner,
        LockManagerInterface $lockManager,
        CheckIsAvailableMessagesInQueue $checkIsAvailableMessages,
        ConnectionTypeResolver $mqConnectionTypeResolver
    ) {
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
        $this->publisher = $publisher;
        $this->deploymentConfig = $deploymentConfig;
        $this->consumerConfig = $consumerConfig;
        $this->consumerRunner = $consumerRunner;
        $this->lockManager = $lockManager;
        $this->checkIsAvailableMessages = $checkIsAvailableMessages;
        $this->mqConnectionTypeResolver = $mqConnectionTypeResolver;
        parent::__construct($context);
    }
    public function execute()
    {
        $runByCron = $this->deploymentConfig->get('cron_consumers_runner/cron_run', true);
        $multipleProcesses = $this->deploymentConfig->get('cron_consumers_runner/multiple_processes', []);
        $maxMessages = (int) $this->deploymentConfig->get('cron_consumers_runner/max_messages', 10000);
        $allowedConsumers = $this->deploymentConfig->get('cron_consumers_runner/consumers', []);
        $globalOnlySpawnWhenMessageAvailable = (bool)$this->deploymentConfig->get(
            'queue/only_spawn_when_message_available',
            true
        );

        $resultJson = $this->jsonFactory->create();
        $resultJson = $resultJson->setHttpResponseCode(200);
        $consumers = [];
        foreach ($this->consumerConfig->getConsumers() as $consumer) {
            $consumers[] = [
                'canBeRun' => $this->canBeRun($consumer, $allowedConsumers),
                'name' => $consumer->getName(),
                'locked' => $this->lockManager->isLocked(md5($consumer->getName()))
            ];
        }
        $resultJson = $resultJson->setData([
            '$runByCron' => $runByCron,
            '$multipleProcesses' => $multipleProcesses,
            '$maxMessages' => $maxMessages,
            '$allowedConsumers' => $allowedConsumers,
            '$globalOnlySpawnWhenMessageAvailable' => $globalOnlySpawnWhenMessageAvailable,
            '$consumers' => $consumers
        ]);

        return $resultJson;
    }

    public function callPrivateMethod($object, $methodName, $consumer, $allowedConsumers)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        $this->logger->debug(json_encode(func_get_args()));
        $this->logger->debug(json_encode(array_slice(func_get_args(), 2)));
        $params = array_slice(func_get_args(), 2); //get all the parameters after $methodName
        $this->logger->debug(json_encode($params));
        return $reflectionMethod->invokeArgs($object, [$consumer, $allowedConsumers]);
    }

    private function canBeRun(ConsumerConfigItemInterface $consumerConfig, array $allowedConsumers = []): bool
    {
        $consumerName = $consumerConfig->getName();
        if ($consumerName !== 'ShippedSuiteOrderUpsert') {
            return false;
        }
        $this->logger->debug($consumerName);
        $this->logger->debug('2');
        if (!empty($allowedConsumers) && !in_array($consumerName, $allowedConsumers)) {
            $this->logger->debug('3');
            return false;
        }

        $this->logger->debug('4');
        $connectionName = $consumerConfig->getConnection();
        $this->logger->debug('5');
        $this->mqConnectionTypeResolver->getConnectionType($connectionName);
        $this->logger->debug('6');

        $globalOnlySpawnWhenMessageAvailable = (bool)$this->deploymentConfig->get(
            'queue/only_spawn_when_message_available',
            true
        );
        $this->logger->debug('7');
        if ($consumerConfig->getOnlySpawnWhenMessageAvailable() === true
            || ($consumerConfig->getOnlySpawnWhenMessageAvailable() === null && $globalOnlySpawnWhenMessageAvailable)) {
            $this->logger->debug('8');
            $this->logger->debug($this->checkIsAvailableMessages->execute(
                $connectionName,
                $consumerConfig->getQueue()
            ));
            return $this->checkIsAvailableMessages->execute(
                $connectionName,
                $consumerConfig->getQueue()
            );
        }
        $this->logger->debug('9');

        return true;
    }
}
