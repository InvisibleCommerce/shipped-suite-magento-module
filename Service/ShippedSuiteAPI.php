<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Service;

use Exception;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

class ShippedSuiteAPI
{
    const STAGING_URI = 'https://api-staging.shippedsuite.com/';
    const PRODUCTION_URI = 'https://api.shippedsuite.com/';
    const ENVIRONMENT_STAGING = 'Staging';
    const ENVIRONMENT_PRODUCTION = 'Production';
    const ENVIRONMENTS = [self::ENVIRONMENT_STAGING, self::ENVIRONMENT_PRODUCTION];

    private ResponseFactory $responseFactory;
    private ClientFactory $clientFactory;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = Request::HTTP_METHOD_GET
    ): Response {
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => $this->endpointUri(),
            'headers' => ['Authorization' => 'Bearer ' . $this->secretKey()]
        ]]);

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            $this->logger->error(json_encode($params));
            $this->logger->error($exception->getMessage());

            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    private function endpointUri(): string
    {
        $environment = $this->scopeConfig->getValue('shipped_suite_api/api/environment');
        $this->logger->debug('environment: ' . $environment);

        return match ($environment) {
            self::ENVIRONMENT_STAGING => self::STAGING_URI,
            self::ENVIRONMENT_PRODUCTION => self::PRODUCTION_URI,
            default => throw new Exception('Unknown environment'),
        };
    }

    private function secretKey(): string
    {
        return $this->scopeConfig->getValue('shipped_suite_api/api/secret_key');
    }
}
