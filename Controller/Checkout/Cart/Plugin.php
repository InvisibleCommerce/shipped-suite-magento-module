<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Controller\Checkout\Cart;

use InvisibleCommerce\ShippedSuite\Helper\CartHelper;
use Magento\Checkout\Controller\Cart\Index;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class Plugin
{
    private LoggerInterface $logger;
    private Session $session;
    private CartHelper $cartHelper;
    private QuoteRepository $quoteRepository;

    public function __construct(
        LoggerInterface $logger,
        Session $session,
        CartHelper $cartHelper,
        QuoteRepository $quoteRepository
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->cartHelper = $cartHelper;
        $this->quoteRepository = $quoteRepository;
    }

    public function beforeExecute(Index $subject)
    {
        $this->logger->debug('before plugin executing');

        $quote = $this->session->getQuote();
        $quote = $this->cartHelper->removeManagedProducts($quote);
        $this->quoteRepository->save($quote);
        $this->session->replaceQuote($quote);
    }
}
