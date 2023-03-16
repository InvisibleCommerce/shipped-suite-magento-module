<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

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
        return $responseBody->getContents();
    }
}
