<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;

class Refund
{
    public function json(array $creditMemos): array
    {
        /* @var CreditmemoInterface[] $creditMemos */

        return [
            'external_id' => implode(', ', array_map(function ($item) {
                return $item->getIncrementId();
            }, $creditMemos)),
            'amount' => array_sum(array_map(function ($item) {
                return $item->getBaseGrandTotal();
            }, $creditMemos)),
            'display_amount' => array_sum(array_map(function ($item) {
                return $item->getGrandTotal();
            }, $creditMemos)),
            'currency' => $creditMemos[0]->getOrderCurrencyCode()
        ];
    }
}
