<?php

namespace Swarming\SubscribePro\Block\Cart;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class Subscription extends \Magento\Checkout\Block\Cart\Additional\Info
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Swarming\SubscribePro\Api\Data\ProductInterface
     */
    protected $platformProduct;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        array $data = []
    ) {
        $this->generalConfig = $generalConfig;
        $this->platformProductManager = $platformProductManager;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->productHelper = $productHelper;
        parent::__construct($context, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _beforeToHtml()
    {
        if (!$this->generalConfig->isEnabled() || !$this->productHelper->isSubscriptionEnabled($this->getItem()->getProduct())) {
            $this->setTemplate('');
        } else {
            $this->initJsLayout();
        }
        return parent::_beforeToHtml();
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function initJsLayout()
    {
        try {
            $this->jsLayout = $this->generateJsLayout();
        } catch (NoSuchEntityException $e) {
            if ($this->_appState->getMode() === AppState::MODE_DEVELOPER) {
                throw $e;
            }
            $this->setTemplate('');
        }
    }

    /**
     * @return array
     */
    protected function generateJsLayout()
    {
        $subscriptionContainerId = 'subscription-container-' . $this->getItem()->getId();
        $subscriptionContainerComponent = [
            'config' => [
                'oneTimePurchaseOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionOnlyMode' => PlatformProductInterface::SOM_SUBSCRIPTION_ONLY,
                'subscriptionAndOneTimePurchaseMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'product' => $this->getSubscriptionProduct()->toArray(),
                'quoteItemId' => $this->getItem()->getId(),
                'qtyFieldSelector' => '#cart-' . $this->getItem()->getId() . '-qty'
            ]
        ];
        $subscriptionContainerComponent = array_merge_recursive($subscriptionContainerComponent, (array)$this->getData('subscription-container-component'));

        $jsLayout = [
            'components' => [$subscriptionContainerId => $subscriptionContainerComponent]
        ];
        return $jsLayout;
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionProduct()
    {
        $sku = $this->getItem()->getProduct()->getData(ProductInterface::SKU);
        $subscriptionProduct = $this->platformProductManager->getProduct($sku);

        if ($intervalOption = $this->quoteItemHelper->getSubscriptionInterval($this->getItem())) {
            $subscriptionProduct->setDefaultInterval($intervalOption);
        }

        $subscriptionOption = $this->quoteItemHelper->getSubscriptionOption($this->getItem());
        $subscriptionProduct->setDefaultSubscriptionOption($subscriptionOption);

        return $subscriptionProduct;
    }
}
