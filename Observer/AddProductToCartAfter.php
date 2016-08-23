<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Swarming\SubscribePro\Api\Data\ProductInterface as SubscriptionProductInterface;
use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class AddProductToCartAfter implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneral;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\ItemOptionsManager
     */
    protected $quoteItemOptionsManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
    ) {
        $this->configGeneral = $configGeneral;
        $this->checkoutSession = $checkoutSession;
        $this->platformProductService = $platformProductService;
        $this->quoteItemOptionsManager = $quoteItemOptionManager;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->configGeneral->isEnabled()) {
            return;
        }

        $event = $observer->getEvent();
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $event->getData('quote_item');
        $productRequestParams = $this->getRequestParamsByProduct($quoteItem->getProduct());
        $subscriptionDelivery = $this->getParamData($productRequestParams, ItemOptionsManager::OPTION_SUBSCRIPTION_OPTION);
        $interval = $this->getParamData($productRequestParams, ItemOptionsManager::OPTION_SUBSCRIPTION_INTERVAL);
        $product = $quoteItem->getProduct();
        
        if (!$this->isProductSubscriptionEnabled($product)) {
            return;
        }
        
        try {
            $platformProduct = $this->platformProductService->getProduct($product->getSku());
        } catch (NoSuchEntityException $e) {
            $this->checkoutSession->getQuote()->removeItem($quoteItem->getId());
            throw new NoSuchEntityException(__('Product "%1" is not found on Subscribe Pro platform.', $quoteItem->getProduct()->getName()));
        }
        
        try {
            $this->quoteItemOptionsManager->saveQuoteItemOptions($quoteItem, $product, $platformProduct, $interval, $subscriptionDelivery);
        } catch (LocalizedException $e) {
            if ($platformProduct->getSubscriptionOptionMode() == SubscriptionProductInterface::SOM_SUBSCRIPTION_ONLY) {
                $this->checkoutSession->getQuote()->removeItem($quoteItem->getId());
                throw $e;
            }
            
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @param string $key
     * @return string|null
     */
    protected function getParamData(array $params, $key)
    {
        return isset($params[$key]) ? $params[$key] : null;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getRequestParamsByProduct($product)
    {
        $buyRequest = $product->getCustomOption('info_buyRequest');
        return $buyRequest ? unserialize($buyRequest->getValue()) : [];
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    protected function isProductSubscriptionEnabled(ProductInterface $product)
    {
        $attribute = $product->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }
}
