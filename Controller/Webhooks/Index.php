<?php

namespace InvisibleCommerce\ShippedSuite\Controller\Webhooks;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\MessageQueue\Publisher;
use Psr\Log\LoggerInterface;

class Index extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private Publisher $publisher;
    private ScopeConfigInterface $scopeConfig;

    const TOPIC_NAME = 'shippedsuite.webhook.process';

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Publisher $publisher,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
        $this->publisher = $publisher;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    public function execute()
    {
        $request = $this->getRequest();

        $resultJson = $this->jsonFactory->create();
        if ($this->authenticated($request)) {
            $this->publisher->publish(self::TOPIC_NAME, $request->getContent());
            $resultJson = $resultJson->setData(['status' => 'ok']);
        } else {
            $resultJson = $resultJson->setHttpResponseCode(401);
            $resultJson = $resultJson->setData(['status' => 'unauthorized']);
        }

        return $resultJson;
    }

    private function authenticated(RequestInterface $request): bool
    {
        $hmac = $request->getHeader('X_SHIPPED_SUITE_HMAC_SHA256');

        $secret = $this->scopeConfig->getValue('shipped_suite_api/api/webhook_secret');
        $calculatedHmac = $this->calculatedHmac($request, $secret);
        if (hash_equals($calculatedHmac, $hmac)) {
            return true;
        }

        return false;
    }

    private function calculatedHmac(RequestInterface $request, string $secret): string
    {
        $data = $request->getContent();
        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
