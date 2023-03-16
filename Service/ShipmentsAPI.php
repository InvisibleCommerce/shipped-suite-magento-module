<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use InvisibleCommerce\ShippedSuite\Model\Track;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\TrackInterface;

class ShipmentsAPI
{
    private ShippedSuiteAPI $client;
    private Track $trackModel;

    public function __construct(
        ShippedSuiteAPI $client,
        Track $trackModel
    ) {
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
        return $responseBody->getContents();
    }
}
