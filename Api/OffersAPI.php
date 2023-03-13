<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;

class OffersAPI
{
    private ShippedSuiteAPI $client;

    public function __construct(
        ShippedSuiteAPI $client
    ) {
        $this->client = $client;
    }

    public function getOffer(string $orderValue): string
    {
        $response = $this->client->doRequest(
            'v1/offers',
            ['json' => ['order_value' => $orderValue]],
            Request::HTTP_METHOD_POST
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
