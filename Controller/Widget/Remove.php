<?php

namespace InvisibleCommerce\ShippedSuite\Controller\Widget;

use InvisibleCommerce\ShippedSuite\Helper\CartHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Remove extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private Session $session;
    private CartHelper $cartHelper;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Session $session,
        CartHelper $cartHelper
    ) {
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
        $this->session = $session;
        $this->cartHelper = $cartHelper;
        parent::__construct($context);
    }
    public function execute()
    {
        $quote = $this->session->getQuote();
        $quote = $this->cartHelper->removeManagedProducts($quote);
        $quote->save();
        $this->session->replaceQuote($quote);

        $resultJson = $this->jsonFactory->create();

        return $resultJson->setData(['status' => 'ok']);
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
