<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model\Resolver;

use InvisibleCommerce\ShippedSuite\Helper\CartHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\QuoteRepository;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class ShippedSuite implements ResolverInterface
{
    private GetCartForUser $getCartForUser;
    private CartHelper $cartHelper;
    private QuoteRepository $quoteRepository;
    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;

    public function __construct(
        GetCartForUser $getCartForUser,
        CartHelper $cartHelper,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->cartHelper = $cartHelper;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        return $this->run($context, $args);
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

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $this->quoteRepository->save($cart);
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
            $this->logger->error('transaction failed to commit ' . $exception->getMessage());
        }

        return [
            'cart' => [
                'model' => $cart,
            ],
            'user_errors' => []
        ];
    }
}
