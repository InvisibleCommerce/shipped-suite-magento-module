<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use InvisibleCommerce\ShippedSuite\Model\Refund;
use Psr\Log\LoggerInterface;

class RefundsAPI
{
    private LoggerInterface $logger;
    private ShippedSuiteAPI $client;
    private Refund $refundModel;

    public function __construct(
        LoggerInterface $logger,
        ShippedSuiteAPI $client,
        Refund $refundModel
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->refundModel = $refundModel;
    }

    public function upsert(string $refundRequestId, array $creditMemos): ?string
    {
        $response = $this->client->doRequest(
            'v1/refunds/' . $refundRequestId,
            ['json' => $this->refundModel->json($creditMemos)],
            'PATCH'
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
