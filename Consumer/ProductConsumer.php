<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Consumer;

use InvisibleCommerce\ShippedSuite\Observer\ProductObserver;
use InvisibleCommerce\ShippedSuite\Service\ProductsAPI;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class ProductConsumer extends AbstractConsumer
{
    private ProductRepositoryInterface $productRepository;
    private ProductsAPI $productsAPI;
    private Configurable $configurable;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        ProductRepositoryInterface $productRepository,
        ProductsAPI $productsAPI,
        Configurable $configurable,
        SerializerInterface $serializer
    ) {
        $this->productRepository = $productRepository;
        $this->productsAPI = $productsAPI;
        $this->configurable = $configurable;
        $this->serializer = $serializer;
        parent::__construct($logger, $publisher, $serializer);
    }

    protected function execute(string $productId): void
    {
        $parentIds = $this->configurable->getParentIdsByChild($productId);
        $realProductId = $parentIds[0] ?? $productId; // get the parent product if configurable
        $product = $this->productRepository->getById((int)$realProductId);
        $this->productsAPI->upsert($product);
    }

    protected function topicName(): string
    {
        return ProductObserver::TOPIC_NAME;
    }
}
