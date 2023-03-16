<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use InvisibleCommerce\ShippedSuite\Model\Refund;

class RefundsAPI
{
    private ShippedSuiteAPI $client;
    private Refund $refundModel;

    public function __construct(
        ShippedSuiteAPI $client,
        Refund $refundModel
    ) {
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
        return $responseBody->getContents();
    }
}
