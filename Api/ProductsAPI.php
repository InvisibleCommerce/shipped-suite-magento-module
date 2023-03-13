<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use InvisibleCommerce\ShippedSuite\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

class ProductsAPI
{
    private ShippedSuiteAPI $client;
    private Product $productModel;
    private LoggerInterface $logger;

    public function __construct(
        ShippedSuiteAPI $client,
        Product $productModel,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->productModel = $productModel;
        $this->logger = $logger;
    }

    public function upsert(ProductInterface $product): ?string
    {
        if ($product->getTypeId() == Grouped::TYPE_CODE) {
            // do not need to sync grouped products
            /* Although they are presented as a group, each product in the group is purchased as a separate item.
            In the shopping cart, each item and the quantity purchased is displayed as a separate line item. */
            return null;
        }

        $response = $this->client->doRequest(
            'v1/products',
            ['json' => $this->productModel->json($product)],
            Request::HTTP_METHOD_POST
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
