<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use InvisibleCommerce\ShippedSuite\Model\Order;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\OrderInterface;

class OrdersAPI
{
    private ShippedSuiteAPI $client;
    private Order $orderModel;

    public function __construct(
        ShippedSuiteAPI $client,
        Order $orderModel
    ) {
        $this->client = $client;
        $this->orderModel = $orderModel;
    }

    public function upsert(OrderInterface $order): ?string
    {
        $response = $this->client->doRequest(
            'v1/orders',
            ['json' => $this->orderModel->json($order)],
            Request::HTTP_METHOD_POST
        );
        $responseBody = $response->getBody();
        return $responseBody->getContents();
    }
}
