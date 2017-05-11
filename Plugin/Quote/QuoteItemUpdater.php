<?php

namespace Swarming\SubscribePro\Plugin\Quote;

use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class QuoteItemUpdater
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater
     */
    protected $subscriptionOptionUpdater;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $appState
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->platformProductManager = $platformProductManager;
        $this->subscriptionOptionUpdater = $subscriptionOptionUpdater;
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->messageManager = $messageManager;
        $this->appState = $appState;
        $this->logger = $logger;
    }
    /**
     * @param \Magento\Quote\Model\Quote\Item\Updater $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $info
     * @return bool
     */
    public function aroundUpdate(
        \Magento\Quote\Model\Quote\Item\Updater $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item,
        array $info
    ) {
        $return = $proceed($item, $info);
        $this->updateAdminQuoteItem($item, $info);
        return $return;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param array $quoteItemParams
     */
    protected function updateAdminQuoteItem(QuoteItem $quoteItem, array $quoteItemParams)
    {
        $string = "\n\n --------0------ \n\n";
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);
        $string = "\n\n --------0------ \n" . json_encode($quoteItemParams['admin_subscription_option']) . "\n";
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);

        if (!$this->getSubscriptionOption($quoteItemParams) || !$this->getInterval($quoteItemParams)) {
            return;
        }
        $string = "\n\n --------1------ " . $this->getSubscriptionOption($quoteItemParams);
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);
        $string = "\n\n --------1------ " . $this->getInterval($quoteItemParams);
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);

        $product = $quoteItem->getProduct();
        if ($quoteItem->getParentItem() && $quoteItem->getParentItem()->getProduct()) {
            $product = $quoteItem->getParentItem()->getProduct();
        }
        $string = "\n\n --------2------ \n\n";
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);

        if (!$this->productHelper->isSubscriptionEnabled($product)) {
            return;
        }

        $string = "\n\n --------3------ \n\n";
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);

        $platformProduct = $this->getPlatformProduct($product);
        if (!$platformProduct) {
            return;
        }

        $string = "\n\n -------4------- \n\n";
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);

        $warnings = $this->subscriptionOptionUpdater->update(
            $quoteItem,
            $platformProduct,
            $this->getSubscriptionOption($quoteItemParams),
            $this->getInterval($quoteItemParams)
        );

        $string = "\n\n -------5------- \n" . json_encode(['data' => $warnings]) . "\n";
        file_put_contents('/var/www/magento2/var/log/debugger.log', $string , FILE_APPEND | LOCK_EX);

        foreach ($warnings as $message) {
            $this->messageManager->addWarningMessage($message);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPlatformProduct($product)
    {
        try {
            $platformProduct = $this->platformProductManager->getProduct($product->getData(ProductInterface::SKU));
        } catch (NoSuchEntityException $e) {
            if ($this->appState->getMode() === AppState::MODE_DEVELOPER) {
                throw $e;
            }
            $this->logger->critical($e->getLogMessage());
            $platformProduct = null;
        }
        return $platformProduct;
    }

    protected function getSubscriptionOption(array $quoteItemParams)
    {
        if (
            !isset($quoteItemParams['admin_subscription_option'])
            || !isset($quoteItemParams['admin_subscription_option']['option'])
            || $quoteItemParams['admin_subscription_option']['option'] == ""
        ) {
            return 'onetime_purchase';
        }
        return $quoteItemParams['admin_subscription_option']['option'];
    }

    protected function getInterval(array $quoteItemParams)
    {

        if (
            !isset($quoteItemParams['admin_subscription_option'])
            || !isset($quoteItemParams['admin_subscription_option']['interval'])
        ) {
            return false;
        }
        return $quoteItemParams['admin_subscription_option']['interval'];
    }
}
