<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use InvisibleCommerce\ShippedSuite\Model\Order;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class OrdersAPI
{
    private ShippedSuiteAPI $client;
    private Order $orderModel;
    private LoggerInterface $logger;

    public function __construct(
        ShippedSuiteAPI $client,
        Order $orderModel,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->orderModel = $orderModel;
        $this->logger = $logger;
    }

    public function upsert(OrderInterface $order): ?string
    {
        $response = $this->client->doRequest(
            'v1/orders',
            ['json' => $this->orderModel->json($order)],
            Request::HTTP_METHOD_POST
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
