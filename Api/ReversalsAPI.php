<?php

namespace InvisibleCommerce\ShippedSuite\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use InvisibleCommerce\ShippedSuite\Model\CreditMemo;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Psr\Log\LoggerInterface;

class ReversalsAPI
{
    private LoggerInterface $logger;
    private ShippedSuiteAPI $client;
    private CreditMemo $creditMemoModel;

    public function __construct(
        LoggerInterface $logger,
        ShippedSuiteAPI $client,
        CreditMemo $creditMemoModel
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->creditMemoModel = $creditMemoModel;
    }

    public function upsert(CreditmemoInterface $creditMemo): ?string
    {
        $response = $this->client->doRequest(
            'v1/reversals',
            ['json' => $this->creditMemoModel->json($creditMemo)],
            Request::HTTP_METHOD_POST
        );
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents(); // here you will have the API response in JSON format
//        $this->logger->debug($responseContent);

        return $responseContent;
    }
}
