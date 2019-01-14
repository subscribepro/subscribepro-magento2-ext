<?php

namespace Swarming\SubscribePro\Observer\CheckoutCart;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use SubscribePro\Exception\InvalidArgumentException;
use SubscribePro\Exception\HttpException;

class UpdateProductAfter extends CheckoutCartAbstract implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $appState
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
        parent::__construct(
            $generalConfig,
            $platformProductManager,
            $subscriptionOptionUpdater,
            $productRepository,
            $productHelper,
            $messageManager,
            $appState,
            $logger
        );
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->generalConfig->isEnabled()) {
            return;
        }

        try {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $observer->getData('quote_item');

            $subscriptionParams = (array)$this->request->getParam(OptionProcessor::KEY_SUBSCRIPTION_OPTION);

            $this->updateQuoteItem($quoteItem, $subscriptionParams);
        } catch (InvalidArgumentException $e) {
            $this->logger->debug('Cannot update subscription option on cart product.');
            $this->logger->info($e->getMessage());
        } catch (HttpException $e) {
            $this->logger->debug('Cannot update subscription option on cart product.');
            $this->logger->info($e->getMessage());
        }
    }
}
