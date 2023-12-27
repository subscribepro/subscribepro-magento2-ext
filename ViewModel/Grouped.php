<?php
namespace Swarming\SubscribePro\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Swarming\SubscribePro\Helper\Product;

class Grouped implements ArgumentInterface
{
    /**
     * @var Product
     */
    private Product $helper;

    /**
     * @param Product $helper
     */
    public function __construct(
        Product $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param $item
     * @return bool
     */
    public function isSubscriptionEnabled($item): bool
    {
        return $this->helper->isSubscriptionEnabled($item);
    }
}
