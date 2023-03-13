<?php

namespace InvisibleCommerce\ShippedSuite\Controller\Checkout\Cart;

use InvisibleCommerce\ShippedSuite\Helper\CartHelper;
use Magento\Checkout\Controller\Cart\Index;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;

class Plugin
{
    private LoggerInterface $logger;
    private Session $session;
    private CartHelper $cartHelper;

    public function __construct(
        LoggerInterface $logger,
        Session $session,
        CartHelper $cartHelper
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->cartHelper = $cartHelper;
    }

    public function beforeExecute(Index $subject)
    {
        $this->logger->debug('before plugin executing');

        $quote = $this->session->getQuote();
        $quote = $this->cartHelper->removeManagedProducts($quote);
        $quote->save();
        $this->session->replaceQuote($quote);
    }
}
