<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;

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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
    ) {
        $this->configGeneral = $configGeneral;
        $this->checkoutSession = $checkoutSession;
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

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getData('quote_item');

        $buyRequestParams = $this->getBuyRequestParams($quoteItem);
        try {
            $this->quoteItemOptionsManager->addQuoteItemOptions(
                $quoteItem,
                $quoteItem->getProduct(),
                $this->getParamData($buyRequestParams, ItemOptionsManager::OPTION_SUBSCRIPTION_INTERVAL),
                $this->getParamData($buyRequestParams, ItemOptionsManager::OPTION_SUBSCRIPTION_OPTION),
                $this->getCatchCallback($quoteItem)
            );
        } catch (NoSuchEntityException $e) {
            $quote = $this->checkoutSession->getQuote();
            $quote->removeItem($quoteItem->getId());
            throw $e;
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return callable
     */
    protected function getCatchCallback($quoteItem)
    {
        return function (LocalizedException $e, PlatformProductInterface $platformProduct) use ($quoteItem) {
            if ($platformProduct->getSubscriptionOptionMode() == PlatformProductInterface::SOM_SUBSCRIPTION_ONLY) {
                $this->checkoutSession->getQuote()->removeItem($quoteItem->getId());
                throw $e;
            }

            $this->quoteItemOptionsManager->addQuoteItemOption(
                $quoteItem,
                ItemOptionsManager::SUBSCRIPTION_CREATING,
                false
            );
            $this->messageManager->addErrorMessage($e->getMessage());
        };
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
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    protected function getBuyRequestParams($quoteItem)
    {
        $buyRequest = $quoteItem->getOptionByCode('info_buyRequest');
        return $buyRequest ? unserialize($buyRequest->getValue()) : [];
    }
}
