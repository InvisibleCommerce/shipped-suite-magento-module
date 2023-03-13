<?php

namespace InvisibleCommerce\ShippedSuite\Model\Resolver;

use InvisibleCommerce\ShippedSuite\Helper\CartHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

class ShippedSuite implements ResolverInterface
{
    private GetCartForUser $getCartForUser;
    private QuoteMutexInterface $quoteMutex;
    private CartHelper $cartHelper;

    public function __construct(
        GetCartForUser $getCartForUser,
        QuoteMutexInterface $quoteMutex,
        CartHelper $cartHelper
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->quoteMutex = $quoteMutex;
        $this->cartHelper = $cartHelper;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        return $this->quoteMutex->execute(
            [$args['input']['cart_id']],
            \Closure::fromCallable([$this, 'run']),
            [$context, $args]
        );
    }

    private function run($context, ?array $args): array
    {
        $maskedCartId = $args['input']['cart_id'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        $cart = $this->cartHelper->removeManagedProducts($cart);
        if ($args['input']['selected']) {
            $cart = $this->cartHelper->addManagedProducts($cart);
        }
        $cart->save();

        return [
            'cart' => [
                'model' => $cart,
            ],
            'user_errors' => []
        ];
    }
}
