<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
    ) {
        $this->configGeneral = $configGeneral;
        $this->checkoutSession = $checkoutSession;
        $this->quoteItemOptionsManager = $quoteItemOptionManager;
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
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $event->getData('product');

        try {
            $this->quoteItemOptionsManager->saveProductOptions($quoteItem, $product);
        } catch (NoSuchEntityException $e) {
            $this->checkoutSession->getQuote()->removeItem($quoteItem->getId());
            throw new NoSuchEntityException(__('Product "%1" is not found on Subscribe Pro platform.', $product->getName()));
        } catch (LocalizedException $e) {
            $this->checkoutSession->getQuote()->removeItem($quoteItem->getId());
            throw new $e;
        }
    }
}
