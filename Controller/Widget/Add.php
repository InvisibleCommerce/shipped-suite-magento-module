<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Controller\Widget;

use InvisibleCommerce\ShippedSuite\Helper\CartHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteRepository;

class Add implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private Session $session;
    private CartHelper $cartHelper;
    private QuoteRepository $quoteRepository;

    public function __construct(
        JsonFactory $jsonFactory,
        Session $session,
        CartHelper $cartHelper,
        QuoteRepository $quoteRepository
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->session = $session;
        $this->cartHelper = $cartHelper;
        $this->quoteRepository = $quoteRepository;
    }
    public function execute()
    {
        $quote = $this->session->getQuote();
        $quote = $this->cartHelper->removeManagedProducts($quote);
        $quote = $this->cartHelper->addManagedProducts($quote);
        $this->quoteRepository->save($quote);
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
