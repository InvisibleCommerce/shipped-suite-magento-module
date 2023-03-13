<?php

namespace InvisibleCommerce\ShippedSuite\Model;

use InvisibleCommerce\ShippedSuite\Api\ReplacementsAPI;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;

class ProcessReplacement
{
    private StoreManager $storeManager;
    private OrderRepositoryInterface $orderRepository;
    private CustomerRepositoryInterface $customerRepository;
    private \Magento\Catalog\Model\Product $product;
    private ProductRepositoryInterface $productRepository;
    private Configurable $configurable;
    private ReplacementsAPI $replacementsAPI;
    private CartManagementInterface $cartManagement;
    private CartRepositoryInterface $cartRepository;
    private LoggerInterface $logger;

    const TOPIC_NAME = 'replacement.requested';

    public function __construct(
        StoreManager $storeManager,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Model\Product $product,
        ProductRepository $productRepository,
        Configurable $configurable,
        ReplacementsAPI $replacementsAPI,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->product = $product;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
        $this->replacementsAPI = $replacementsAPI;
    }

    public function execute(array $payload): void
    {
        if ($payload['topic'] != self::TOPIC_NAME) {
            return;
        }
        $this->logger->debug('ProcessReplacement called');

        $payload = $payload['payload'];
        $orderId = $payload['order']['external_id'];
        $this->logger->debug('order id ' . $orderId);
        $order = $this->orderRepository->get((int)$orderId);
        $this->logger->debug('order ' . $order->getIncrementId());

        $affectedItems = $payload['affected_items'];
        $this->logger->debug('affected items ' . json_encode($affectedItems));

        $quote = $this->createQuote();

        if (str_contains($payload['order']['customer']['external_id'], 'order-')) {
            $this->setupGuestCheckout($quote, $payload);
        } else {
            $this->setupCustomerCheckout($quote, $order, $payload);
        }

        foreach ($affectedItems as $affectedItem) {
            $this->addProduct($affectedItem, $quote);
        }

        $this->setupShipping($quote);
        $this->setupPayment($quote);
        $this->finalizeQuote($quote);

        $replacementOrder = $this->placeOrder($quote);

        if ($replacementOrder->getEntityId()) {
            $replacementOrder->addCommentToStatusHistory(
                'Shipped Suite ReplacementRequest ID ' . $payload['id']
            );
            $this->orderRepository->save($replacementOrder);
            $this->replacementsAPI->upsert($payload['id'], $replacementOrder);
        } else {
            echo 'failed to create order';
        }
    }

    private function createQuote(): CartInterface
    {
        $cartId = $this->cartManagement->createEmptyCart();
        $quote = $this->cartRepository->get($cartId);
        $quote->setStore($this->storeManager->getStore());
        $quote->setCurrency();

        return $quote;
    }
    private function addProduct(
        array $affectedItem,
        CartInterface &$quote
    ): void {
        $product = $this->product->load((int)$affectedItem['external_variant_id']);

        $this->logger->debug('adding ' . $product->getName() . ' qty ' . $affectedItem['quantity'] . ' price ' . $affectedItem['unit_price']);
        $product->setPrice($affectedItem['unit_price']);

        $parentIds = $this->configurable->getParentIdsByChild($product->getId());
        if ($parentIds[0]) {
            $result = $this->addConfigurableProduct($product, $affectedItem, $parentIds, $quote);
        } else {
            $result = $quote->addProduct(
                $product,
                (int)$affectedItem['quantity']
            );
        }

        if (is_string($result)) {
            $this->logger->debug($result);
        }
    }

    private function addConfigurableProduct(
        ProductInterface &$product,
        array $affectedItem,
        array $parentIds,
        CartInterface &$quote
    ): CartItemInterface|string {
        $childProduct = $product;
        $product = $this->productRepository->getById((int)$parentIds[0]);

        $params = [];
        $params['product'] = $product->getId();
        $params['qty'] = $affectedItem['quantity'];
        $options = [];

        $productAttributeOptions = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        foreach ($productAttributeOptions as $option) {
            $options[$option['attribute_id']] = $childProduct->getData($option['attribute_code']);
        }
        $params['super_attribute'] = $options;

        $this->logger->debug(json_encode($params));
        return $quote->addProduct(
            $product,
            new \Magento\Framework\DataObject($params)
        );
    }

    private function finalizeQuote(CartInterface &$quote): void
    {
        $quote->setInventoryProcessed(false);
        $quote->save();
        $quote->collectTotals();
    }

    private function placeOrder(&$quote): OrderInterface
    {
        $orderId = $this->cartManagement->placeOrder($quote->getId());
        $replacementOrder = $this->orderRepository->get($orderId);
        $replacementOrder->setEmailSent(0);

        return $replacementOrder;
    }

    private function setupPayment(CartInterface &$quote): void
    {
        // TODO: use 'free' payment method instead?
        $quote->getPayment()->importData(['method' => 'checkmo']);
    }

    private function setupShipping(CartInterface &$quote): void
    {
        $quote->getShippingAddress()->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');
    }

    private function setupGuestCheckout(CartInterface &$quote, array $payload): void
    {
        $this->logger->debug('customer does not exist');

        $quote->setCustomerFirstname($payload['order']['customer']['first_name']);
        $quote->setCustomerLastname($payload['order']['customer']['last_name']);
        $quote->setCustomerEmail($payload['order']['customer']['email']);
        $quote->setCustomerIsGuest(true);

        $this->setUpGuestAddresses($quote, $payload);
    }

    private function setupCustomerCheckout(CartInterface &$quote, OrderInterface $order, array $payload): void
    {
        $this->logger->debug('existing customer');

        $customer = $this->customerRepository->getById((int)$payload['order']['customer']['external_id']);
        $quote->assignCustomer($customer);
        $quote->setCustomerIsGuest(false);
        $this->logger->debug('existing customer ' . $customer->getEmail());

        $this->setUpGuestAddresses($quote, $payload);
    }

    private function setUpGuestAddresses(CartInterface &$quote, array $payload): void {
        $quote->getShippingAddress()->addData($this->formattedAddressData($payload['order']['shipping_address']));
        $quote->getBillingAddress()->addData($this->formattedAddressData($payload['order']['shipping_address']));
    }

    private function formattedAddressData(array $address): array
    {
        return [
            'firstname' => $address['first_name'],
            'lastname' => $address['last_name'],
            'street' => array_filter([
                $address['address1'],
                $address['address2']
            ]),
            'city' => $address['city'],
            'region' => $address['state'],
            'postcode' => $address['zip'],
            'telephone' => $address['phone'],
            'country_id' => $address['country']
        ];
    }
}
