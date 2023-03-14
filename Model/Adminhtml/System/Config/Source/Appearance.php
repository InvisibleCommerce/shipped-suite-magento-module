<?php

namespace InvisibleCommerce\ShippedSuite\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Appearance implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'auto',
                'label' => __('Auto')
            ],
            [
                'value' => 'light',
                'label' => __('Light')
            ],
            [
                'value' => 'dark',
                'label' => __('Dark')
            ],
        ];
    }
}
