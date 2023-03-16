<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model\Adminhtml\System\Config\Source;

use InvisibleCommerce\ShippedSuite\Service\ShippedSuiteAPI;
use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return array_map([$this, 'option'], ShippedSuiteAPI::ENVIRONMENTS);
    }

    private function option($environment): array
    {
        return [
            'value' => $environment,
            'label' => __($environment)
        ];
    }
}
