<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Checkout;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Swarming\SubscribePro\Helper\Quote as QuoteHelper;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Swarming\SubscribePro\Observer\Checkout\IsAllowedGuest;

class IsAllowedGuestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Checkout\IsAllowedGuest
     */
    protected $isAllowedGuest;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteHelperMock;

    protected function setUp(): void
    {
        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteHelperMock = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->isAllowedGuest = new IsAllowedGuest(
            $this->generalConfigMock,
            $this->quoteHelperMock
        );
    }

    public function testExecuteIfSubscribeProNotEnabled()
    {
        $websiteCode = 'code';
        $resultMock = $this->createResultMock();

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('result')
            ->willReturn($resultMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(false);

        $this->quoteHelperMock->expects($this->never())->method('hasSubscription');

        $this->isAllowedGuest->execute($observerMock);
    }

    public function testExecuteIfQuoteWithoutSubscription()
    {
        $websiteCode = 'code';

        $resultMock = $this->createResultMock();
        $resultMock->expects($this->never())->method('setIsAllowed');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('result')
            ->willReturn($resultMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->quoteHelperMock->expects($this->once())
            ->method('hasSubscription')
            ->with($quoteMock)
            ->willReturn(false);

        $this->isAllowedGuest->execute($observerMock);
    }

    public function testExecute()
    {
        $websiteCode = 'code';

        $resultMock = $this->createResultMock();
        $resultMock->expects($this->once())->method('setIsAllowed')->with(false);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('result')
            ->willReturn($resultMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->quoteHelperMock->expects($this->once())
            ->method('hasSubscription')
            ->with($quoteMock)
            ->willReturn(true);

        $this->isAllowedGuest->execute($observerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createResultMock()
    {
        return $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setIsAllowed'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\Store
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\WebsiteInterface
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
    }
}
