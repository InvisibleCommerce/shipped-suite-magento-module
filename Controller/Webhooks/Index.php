<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Controller\Webhooks;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\MessageQueue\Publisher;
use Psr\Log\LoggerInterface;

class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private Publisher $publisher;
    private ScopeConfigInterface $scopeConfig;
    private RequestInterface $request;

    const TOPIC_NAME = 'shippedsuite.webhook.process';

    public function __construct(
        JsonFactory $jsonFactory,
        Publisher $publisher,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->publisher = $publisher;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
    }
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        if ($this->authenticated()) {
            $this->publisher->publish(self::TOPIC_NAME, $this->request->getContent());
            $resultJson = $resultJson->setData(['status' => 'ok']);
        } else {
            $resultJson = $resultJson->setHttpResponseCode(401);
            $resultJson = $resultJson->setData(['status' => 'unauthorized']);
        }

        return $resultJson;
    }

    private function authenticated(): bool
    {
        $hmac = $this->request->getHeader('X_SHIPPED_SUITE_HMAC_SHA256');

        $secret = $this->scopeConfig->getValue('shipped_suite_api/api/webhook_secret');
        $calculatedHmac = $this->calculatedHmac($secret);
        if (hash_equals($calculatedHmac, $hmac)) {
            return true;
        }

        return false;
    }

    private function calculatedHmac(string $secret): string
    {
        $data = $this->request->getContent();
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
