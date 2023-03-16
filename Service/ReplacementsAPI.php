<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use InvisibleCommerce\ShippedSuite\Model\Replacement;
use Magento\Sales\Api\Data\OrderInterface;

class ReplacementsAPI
{
    private ShippedSuiteAPI $client;
    private Replacement $replacementModel;

    public function __construct(
        ShippedSuiteAPI $client,
        Replacement $replacementModel
    ) {
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
        return $responseBody->getContents();
    }
}
