<?php

namespace Swarming\SubscribePro\Block\Cart;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteAbstractItem;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;

class Subscription extends \Magento\Checkout\Block\Cart\Additional\Info
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    protected $product;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        array $data = []
    ) {
        $this->generalConfig = $generalConfig;
        $this->platformProductService = $platformProductService;
        $this->productRepository = $productRepository;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->productHelper = $productHelper;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        if (!$this->generalConfig->isEnabled() || !$this->productHelper->isSubscriptionEnabled($this->product)) {
            $this->setTemplate('');
        }
        return parent::_beforeToHtml();
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $this->jsLayout = $this->generateJsLayout();
        return parent::getJsLayout();
    }

    /**
     * @return array
     */
    protected function generateJsLayout()
    {
        $subscriptionContainerId = 'subscription-container-' . $this->getItem()->getId();
        $subscriptionContainerComponent = [
            'config' => [
                'oneTimePurchaseOption' => ProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionOption' => ProductInterface::SO_SUBSCRIPTION,
                'subscriptionOnlyMode' => ProductInterface::SOM_SUBSCRIPTION_ONLY,
                'subscriptionAndOneTimePurchaseMode' => ProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productData' => $this->getSubscriptionProduct()->toArray(),
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
        $subscriptionProduct = $this->platformProductService->getProduct($this->product->getSku());

        if ($intervalOption = $this->getItem()->getOptionByCode(ItemOptionsManager::SUBSCRIPTION_INTERVAL)) {
            $subscriptionProduct->setDefaultInterval($intervalOption->getValue());
        }

        $subscriptionOption = $this->quoteItemHelper->isSubscriptionEnabled($this->getItem())
            ? ProductInterface::SO_SUBSCRIPTION
            : ProductInterface::SO_ONETIME_PURCHASE;
        $subscriptionProduct->setDefaultSubscriptionOption($subscriptionOption);

        return $subscriptionProduct;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     */
    public function setItem(QuoteAbstractItem $item)
    {
        parent::setItem($item);
        $this->setProduct($this->getOriginalProduct($item->getProduct()));
        return $this;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return $this
     */
    protected function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    protected function getOriginalProduct($product)
    {
        return $this->productRepository->getById($product->getId());
    }
}
