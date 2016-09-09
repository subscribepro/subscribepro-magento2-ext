<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Swarming\SubscribePro\Model\Config\SubscriptionOptions;
use Magento\Store\Model\ScopeInterface;

class SubscriptionOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionsConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactoryMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->subscriptionOptionsConfig = new SubscriptionOptions(
            $this->scopeConfigMock,
            $this->dateTimeFactoryMock
        );
    }

    public function testIsAllowedCoupon()
    {
        $store = 'store_code';
        $isAllowedCoupon = true;

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'swarming_subscribepro/subscription_options/allow_coupon',
                ScopeInterface::SCOPE_STORE,
                $store
            )
            ->willReturn($isAllowedCoupon);

        $this->assertEquals($isAllowedCoupon, $this->subscriptionOptionsConfig->isAllowedCoupon($store));
    }

    public function testIsAllowedCancel()
    {
        $store = 'main_store';
        $isAllowedCancel = false;

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'swarming_subscribepro/subscription_options/allow_cancel',
                ScopeInterface::SCOPE_STORE,
                $store
            )
            ->willReturn($isAllowedCancel);

        $this->assertEquals($isAllowedCancel, $this->subscriptionOptionsConfig->isAllowedCancel($store));
    }

    public function testGetEarliestDateForNextOrder()
    {
        $date = '2020-12-12';

        $dateTimeMock = $this->getMockBuilder(\DateTime::class)->disableOriginalConstructor()->getMock();
        $dateTimeMock->expects($this->once())->method('format')->with('Y-m-d')->willReturn($date);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with('+' . SubscriptionOptions::QTY_MIN_DAYS_TO_NEXT_ORDER . ' days')
            ->willReturn($dateTimeMock);

        $this->assertEquals($date, $this->subscriptionOptionsConfig->getEarliestDateForNextOrder());
    }
}
