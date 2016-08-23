<?php

namespace Swarming\SubscribePro\Block\Cart;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use \Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class Subscription extends \Magento\Checkout\Block\Cart\Additional\Info
{
    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Product
     */
    protected $platformProductHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    protected $product;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->platformProductHelper = $platformProductHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $jsLayoutData = $this->jsLayout;
        $data = [
            'components' => [
                'subscription-container' => [
                    'config' => [
                        'oneTimePurchaseOption' => ProductInterface::SO_ONETIME_PURCHASE,
                        'subscriptionOption' => ProductInterface::SO_SUBSCRIPTION,
                        'subscriptionOnlyMode' => ProductInterface::SOM_SUBSCRIPTION_ONLY,
                        'subscriptionAndOneTimePurchaseMode' => ProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                        'productData' => $this->getSubscriptionProduct()->toArray(),
                        'quoteItemId' => $this->getItem()->getId(),
                        'qtyFieldSelector' => '#cart-'.$this->getItem()->getId().'-qty'
                    ]
                ]
            ]
        ];
        $this->jsLayout = array_merge_recursive($jsLayoutData, $data);
        $this->jsLayout['components']['subscription-container-'.$this->getItem()->getId()] = $this->jsLayout['components']['subscription-container'];
        unset($this->jsLayout['components']['subscription-container']);
        $jsLayout = parent::getJsLayout();
        $this->jsLayout = $jsLayoutData;

        return $jsLayout;
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscriptionProduct()
    {
        $subscriptionProduct = $this->platformProductHelper->getProduct($this->product->getSku());
        if ($intervalOption = $this->getItem()->getOptionByCode('subscription_interval')) {
            $subscriptionProduct->setDefaultInterval($intervalOption->getValue());
        }
        $createSubscriptionOption = $this->getItem()->getOptionByCode('create_subscription');
        $subscriptionOption = $createSubscriptionOption && $createSubscriptionOption->getValue()
            ? ProductInterface::SO_SUBSCRIPTION
            : ProductInterface::SO_ONETIME_PURCHASE;
        $subscriptionProduct->setDefaultSubscriptionOption($subscriptionOption);

        return $subscriptionProduct;
    }

    /**
     * @return bool
     */
    public function isSubscribeProEnabled()
    {
        return (bool) -$this->_scopeConfig->getValue('swarming_subscribepro/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return bool
     */
    public function isProductSubscriptionEnabled()
    {
        $attribute = $this->product->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     */
    public function setItem(\Magento\Quote\Model\Quote\Item\AbstractItem $item)
    {
        parent::setItem($item);
        $productId = $item->getProduct()->getId();
        if ($item->getProduct()->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $parentIds = $item->getProduct()->getTypeInstance()->getParentIdsByChild($productId);
            if (!empty($parentIds)) {
                $productId = array_shift($parentIds);
            }
        }
        $this->product = $this->productRepository->getById($productId);
        
        return $this;
    }
}
