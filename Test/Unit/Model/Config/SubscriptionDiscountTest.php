<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount;
use Magento\Store\Model\ScopeInterface;

class SubscriptionDiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->subscriptionDiscountConfig = new SubscriptionDiscount($this->scopeConfigMock);
    }

    public function testIsApplyDiscountToCatalogPrice()
    {
        $store = 'store_code';
        $applyDiscount = false;

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'swarming_subscribepro/subscription_discount/apply_discount_to_catalog_price',
                ScopeInterface::SCOPE_STORE,
                $store
            )
            ->willReturn($applyDiscount);

        $this->assertEquals($applyDiscount, $this->subscriptionDiscountConfig->isApplyDiscountToCatalogPrice($store));
    }

    public function testGetCartRuleCombineType()
    {
        $store = 'main_store';
        $cartRuleCombineType = 'combine_both';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'swarming_subscribepro/subscription_discount/cartrule_combine_type',
                ScopeInterface::SCOPE_STORE,
                $store
            )
            ->willReturn($cartRuleCombineType);

        $this->assertEquals($cartRuleCombineType, $this->subscriptionDiscountConfig->getCartRuleCombineType($store));
    }

    public function testGetDiscountMessage()
    {
        $store = 'store_custom';
        $message = 'message';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'swarming_subscribepro/subscription_discount/discount_message',
                ScopeInterface::SCOPE_STORE,
                $store
            )
            ->willReturn($message);

        $this->assertEquals($message, $this->subscriptionDiscountConfig->getDiscountMessage($store));
    }
}
