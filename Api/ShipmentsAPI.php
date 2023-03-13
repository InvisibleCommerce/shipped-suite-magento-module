<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use InvisibleCommerce\ShippedSuite\Model\Track;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\TrackInterface;
use Psr\Log\LoggerInterface;

class ShipmentsAPI
{
    private LoggerInterface $logger;
    private ShippedSuiteAPI $client;
    private Track $trackModel;

    public function __construct(
        LoggerInterface $logger,
        ShippedSuiteAPI $client,
        Track $trackModel
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->trackModel = $trackModel;
    }

    public function upsert(TrackInterface $track): ?string
    {
        $response = $this->client->doRequest(
            'v1/shipments',
            ['json' => $this->trackModel->json($track)],
            Request::HTTP_METHOD_POST
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
