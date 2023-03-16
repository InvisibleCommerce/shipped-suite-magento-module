<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use InvisibleCommerce\ShippedSuite\Model\CreditMemo;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\CreditmemoInterface;

class ReversalsAPI
{
    private ShippedSuiteAPI $client;
    private CreditMemo $creditMemoModel;

    public function __construct(
        ShippedSuiteAPI $client,
        CreditMemo $creditMemoModel
    ) {
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
        return $responseBody->getContents();
    }
}
