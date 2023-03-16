<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use InvisibleCommerce\ShippedSuite\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class ProductsAPI
{
    private ShippedSuiteAPI $client;
    private Product $productModel;

    public function __construct(
        ShippedSuiteAPI $client,
        Product $productModel
    ) {
        $this->client = $client;
        $this->productModel = $productModel;
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
        return $responseBody->getContents();
    }
}
