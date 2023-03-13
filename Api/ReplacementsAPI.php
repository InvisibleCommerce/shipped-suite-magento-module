<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use InvisibleCommerce\ShippedSuite\Model\Replacement;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class ReplacementsAPI
{
    private LoggerInterface $logger;
    private ShippedSuiteAPI $client;
    private Replacement $replacementModel;

    public function __construct(
        LoggerInterface $logger,
        ShippedSuiteAPI $client,
        Replacement $replacementModel
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->replacementModel = $replacementModel;
    }

    public function upsert(string $replacementRequestId, OrderInterface $order): ?string
    {
        $response = $this->client->doRequest(
            'v1/replacements/' . $replacementRequestId,
            ['json' => $this->replacementModel->json($order)],
            'PATCH'
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
