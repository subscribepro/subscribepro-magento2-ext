<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Plugin\Sales\Model\Reorder;

use Magento\Sales\Model\Reorder\Reorder;
use SubscribePro\Service\Product\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Helper\QuoteItem;

class ReorderPlugin
{

    /**
     * @var QuoteItem
     */
    protected QuoteItem $quoteItemHelper;

    /**
     * @param QuoteItem $quoteItemHelper
     */
    public function __construct(
        QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param Reorder $subject
     * @param $output
     * @return mixed
     */
    public function afterExecute(Reorder $subject, $output): mixed
    {
        $cart = $output->getCart();
        foreach ($cart->getAllItems() as $quoteItem) {
            $buyRequestOption = $quoteItem->getOptionByCode('info_buyRequest');
            $buyRequest = ['qty' => (float)$quoteItem->getQty()];
            $buyRequestOption->setValue(json_encode($buyRequest));
            $quoteItem->addOption($buyRequestOption);
            $this->quoteItemHelper->setSubscriptionParam(
                $quoteItem,
                SubscriptionOptionInterface::OPTION,
                ProductInterface::SO_ONETIME_PURCHASE
            );
            $this->quoteItemHelper->setSubscriptionParam(
                $quoteItem,
                SubscriptionOptionInterface::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT,
                false
            );
            $this->quoteItemHelper->setSubscriptionParam(
                $quoteItem,
                SubscriptionOptionInterface::INTERVAL,
                null
            );

        }
        $cart->save();
        return $output;
    }
}
