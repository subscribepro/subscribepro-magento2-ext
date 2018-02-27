<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote\QuoteItem;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Api\Data\AddressInterface as PlatformAddressInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface as PlatformSubscriptionInterface;
use Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Swarming\SubscribePro\Model\Config\SubscriptionOptions as SubscriptionOptionsConfig;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Swarming\SubscribePro\Platform\Service\Subscription as SubscriptionService;
use Swarming\SubscribePro\Helper\ProductOption as QuoteItemProductOption;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class SubscriptionCreatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator
     */
    protected $subscriptionCreator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\ProductOption
     */
    protected $productOptionHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\ManagerInterface
     */
    protected $eventManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp()
    {
        $this->subscriptionOptionConfigMock = $this->getMockBuilder(SubscriptionOptionsConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platformSubscriptionServiceMock = $this->getMockBuilder(SubscriptionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productOptionHelperMock = $this->getMockBuilder(QuoteItemProductOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManagerMock = $this->getMockBuilder(EventManagerInterface::class)
            ->getMock();

        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->subscriptionCreator = new SubscriptionCreator(
            $this->subscriptionOptionConfigMock,
            $this->platformSubscriptionServiceMock,
            $this->quoteItemHelperMock,
            $this->productOptionHelperMock,
            $this->eventManagerMock,
            $this->dateTimeFactoryMock,
            $this->loggerMock
        );
    }

    public function testFailToCreateSubscription()
    {
        $exception = new \Exception('error');

        $platformCustomerId = 32123;
        $paymentProfileId = 4324;
        $shippingMethod = 'table_rate';
        $qty = 4234;
        $interval = 'daily';
        $nextOrderDate = '2018-12-12';

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('format')->with('Y-m-d')->willReturn($nextOrderDate);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->any())->method('getShippingMethod')->willReturn($shippingMethod);
        $addressMock->expects($this->once())->method('getFirstname')->willReturn('firstname');
        $addressMock->expects($this->once())->method('getLastname')->willReturn('Last-name');
        $addressMock->expects($this->once())->method('getCity')->willReturn('New York');
        $addressMock->expects($this->once())->method('getRegionCode')->willReturn('region-code');
        $addressMock->expects($this->once())->method('getPostcode')->willReturn('111FFF');
        $addressMock->expects($this->once())->method('getCountryId')->willReturn('USA');
        $addressMock->expects($this->once())->method('getTelephone')->willReturn('999888');
        $addressMock->expects($this->exactly(2))
            ->method('getStreetLine')
            ->willReturnMap([
                [1, 'street 1'],
                [2, 'street 2']
            ]);

        $platformAddressMock = $this->createPlatformAddressMock();
        $platformAddressMock->expects($this->once())->method('setFirstName')->with('firstname');
        $platformAddressMock->expects($this->once())->method('setLastName')->with('Last-name');
        $platformAddressMock->expects($this->once())->method('setStreet1')->with('street 1');
        $platformAddressMock->expects($this->once())->method('setStreet2')->with('street 2');
        $platformAddressMock->expects($this->once())->method('setCity')->with('New York');
        $platformAddressMock->expects($this->once())->method('setRegion')->with('region-code');
        $platformAddressMock->expects($this->once())->method('setPostcode')->with('111FFF');
        $platformAddressMock->expects($this->once())->method('setCountry')->with('USA');
        $platformAddressMock->expects($this->once())->method('setPhone')->with('999888');

        $storeCode = 'main';
        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->any())->method('getCode')->willReturn($storeCode);

        $productSku = 'sku22';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $productOption = ['some_key' => 'some_value'];
        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getShippingAddress')->willReturn($platformAddressMock);
        $subscriptionMock->expects($this->once())->method('setCustomerId')->with($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setPaymentProfileId')->with($paymentProfileId);
        $subscriptionMock->expects($this->once())->method('setProductSku')->with($productSku);
        $subscriptionMock->expects($this->once())->method('setProductOption')->with($productOption);
        $subscriptionMock->expects($this->once())->method('setQty')->with($qty);
        $subscriptionMock->expects($this->once())->method('setUseFixedPrice')->with(false);
        $subscriptionMock->expects($this->once())->method('setInterval')->with($interval);
        $subscriptionMock->expects($this->once())->method('setNextOrderDate')->with($nextOrderDate);
        $subscriptionMock->expects($this->once())->method('setFirstOrderAlreadyCreated')->with(true);
        $subscriptionMock->expects($this->once())->method('setMagentoStoreCode')->with($storeCode);
        $subscriptionMock->expects($this->once())->method('setMagentoShippingMethodCode')->with($shippingMethod);
        $subscriptionMock->expects($this->once())->method('setRequiresShipping')->with(true);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionInterval')
            ->with($quoteItemMock)
            ->willReturn($interval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);

        $this->subscriptionOptionConfigMock->expects($this->once())
            ->method('isAllowedCoupon')
            ->with($storeCode)
            ->willReturn(false);

        $this->productOptionHelperMock->expects($this->once())
            ->method('getProductOption')
            ->with($quoteItemMock)
            ->willReturn($productOption);

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with(
                'subscribe_pro_before_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('createSubscription')
            ->willReturn($subscriptionMock);
        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->assertFalse(
            $this->subscriptionCreator->create($quoteItemMock, $platformCustomerId, $paymentProfileId, $addressMock)
        );
    }

    public function testCreateSubscription()
    {
        $platformCustomerId = 32123;
        $paymentProfileId = 4324;
        $shippingMethod = 'table_rate';
        $qty = 4234;
        $interval = 'daily';
        $nextOrderDate = '2018-12-12';
        $newSubscriptionId = 23123;

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('format')->with('Y-m-d')->willReturn($nextOrderDate);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->any())->method('getShippingMethod')->willReturn($shippingMethod);
        $addressMock->expects($this->once())->method('getFirstname')->willReturn('firstname');
        $addressMock->expects($this->once())->method('getLastname')->willReturn('Last-name');
        $addressMock->expects($this->once())->method('getCity')->willReturn('New York');
        $addressMock->expects($this->once())->method('getRegionCode')->willReturn('region-code');
        $addressMock->expects($this->once())->method('getPostcode')->willReturn('111FFF');
        $addressMock->expects($this->once())->method('getCountryId')->willReturn('USA');
        $addressMock->expects($this->once())->method('getTelephone')->willReturn('999888');
        $addressMock->expects($this->exactly(2))
            ->method('getStreetLine')
            ->willReturnMap([
                [1, 'street 1'],
                [2, 'street 2']
            ]);

        $platformAddressMock = $this->createPlatformAddressMock();
        $platformAddressMock->expects($this->once())->method('setFirstName')->with('firstname');
        $platformAddressMock->expects($this->once())->method('setLastName')->with('Last-name');
        $platformAddressMock->expects($this->once())->method('setStreet1')->with('street 1');
        $platformAddressMock->expects($this->once())->method('setStreet2')->with('street 2');
        $platformAddressMock->expects($this->once())->method('setCity')->with('New York');
        $platformAddressMock->expects($this->once())->method('setRegion')->with('region-code');
        $platformAddressMock->expects($this->once())->method('setPostcode')->with('111FFF');
        $platformAddressMock->expects($this->once())->method('setCountry')->with('USA');
        $platformAddressMock->expects($this->once())->method('setPhone')->with('999888');

        $storeCode = 'main';
        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->any())->method('getCode')->willReturn($storeCode);

        $productSku = 'sku22';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $productOption = ['some_option_key' => 'some_option_value'];
        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getShippingAddress')->willReturn($platformAddressMock);
        $subscriptionMock->expects($this->once())->method('setCustomerId')->with($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setPaymentProfileId')->with($paymentProfileId);
        $subscriptionMock->expects($this->once())->method('setProductSku')->with($productSku);
        $subscriptionMock->expects($this->once())->method('setProductOption')->with($productOption);
        $subscriptionMock->expects($this->once())->method('setQty')->with($qty);
        $subscriptionMock->expects($this->once())->method('setUseFixedPrice')->with(false);
        $subscriptionMock->expects($this->once())->method('setInterval')->with($interval);
        $subscriptionMock->expects($this->once())->method('setNextOrderDate')->with($nextOrderDate);
        $subscriptionMock->expects($this->once())->method('setFirstOrderAlreadyCreated')->with(true);
        $subscriptionMock->expects($this->once())->method('setMagentoStoreCode')->with($storeCode);
        $subscriptionMock->expects($this->once())->method('setMagentoShippingMethodCode')->with($shippingMethod);
        $subscriptionMock->expects($this->once())->method('getId')->willReturn($newSubscriptionId);
        $subscriptionMock->expects($this->once())->method('setRequiresShipping')->with(true);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionInterval')
            ->with($quoteItemMock)
            ->willReturn($interval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);

        $this->subscriptionOptionConfigMock->expects($this->once())
            ->method('isAllowedCoupon')
            ->with($storeCode)
            ->willReturn(false);

        $this->productOptionHelperMock->expects($this->once())
            ->method('getProductOption')
            ->with($quoteItemMock)
            ->willReturn($productOption);

        $this->eventManagerMock->expects($this->at(0))
            ->method('dispatch')
            ->with(
                'subscribe_pro_before_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );
        $this->eventManagerMock->expects($this->at(1))
            ->method('dispatch')
            ->with(
                'subscribe_pro_after_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('createSubscription')
            ->willReturn($subscriptionMock);
        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock);

        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->assertEquals(
            $newSubscriptionId,
            $this->subscriptionCreator->create($quoteItemMock, $platformCustomerId, $paymentProfileId, $addressMock)
        );
    }

    public function testCreateSubscriptionWithVirtual()
    {
        $platformCustomerId = 32123;
        $paymentProfileId = 4324;
        $qty = 4234;
        $interval = 'daily';
        $nextOrderDate = '2018-12-12';
        $newSubscriptionId = 23123;

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('format')->with('Y-m-d')->willReturn($nextOrderDate);

        $storeCode = 'main';
        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->any())->method('getCode')->willReturn($storeCode);

        $productSku = 'sku22';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $productOption = ['some_option_key' => 'some_option_value'];
        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('setCustomerId')->with($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setPaymentProfileId')->with($paymentProfileId);
        $subscriptionMock->expects($this->once())->method('setProductSku')->with($productSku);
        $subscriptionMock->expects($this->once())->method('setProductOption')->with($productOption);
        $subscriptionMock->expects($this->once())->method('setQty')->with($qty);
        $subscriptionMock->expects($this->once())->method('setUseFixedPrice')->with(false);
        $subscriptionMock->expects($this->once())->method('setInterval')->with($interval);
        $subscriptionMock->expects($this->once())->method('setNextOrderDate')->with($nextOrderDate);
        $subscriptionMock->expects($this->once())->method('setFirstOrderAlreadyCreated')->with(true);
        $subscriptionMock->expects($this->once())->method('setMagentoStoreCode')->with($storeCode);
        $subscriptionMock->expects($this->once())->method('setRequiresShipping')->with(false);
        $subscriptionMock->expects($this->never())->method('setMagentoShippingMethodCode');
        $subscriptionMock->expects($this->never())->method('getShippingAddress');
        $subscriptionMock->expects($this->once())->method('getId')->willReturn($newSubscriptionId);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionInterval')
            ->with($quoteItemMock)
            ->willReturn($interval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);

        $this->subscriptionOptionConfigMock->expects($this->once())
            ->method('isAllowedCoupon')
            ->with($storeCode)
            ->willReturn(false);

        $this->productOptionHelperMock->expects($this->once())
            ->method('getProductOption')
            ->with($quoteItemMock)
            ->willReturn($productOption);

        $this->eventManagerMock->expects($this->at(0))
            ->method('dispatch')
            ->with(
                'subscribe_pro_before_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );
        $this->eventManagerMock->expects($this->at(1))
            ->method('dispatch')
            ->with(
                'subscribe_pro_after_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('createSubscription')
            ->willReturn($subscriptionMock);
        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock);

        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->assertEquals(
            $newSubscriptionId,
            $this->subscriptionCreator->create($quoteItemMock, $platformCustomerId, $paymentProfileId, null)
        );
    }

    public function testCreateSubscriptionWithCouponCode()
    {
        $platformCustomerId = 32123;
        $paymentProfileId = 4324;
        $shippingMethod = 'table_rate';
        $qty = 4234;
        $interval = 'daily';
        $nextOrderDate = '2018-12-12';
        $newSubscriptionId = 23123;
        $couponCode = 'coupon_code';

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('format')->with('Y-m-d')->willReturn($nextOrderDate);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->any())->method('getShippingMethod')->willReturn($shippingMethod);
        $addressMock->expects($this->once())->method('getFirstname')->willReturn('firstname');
        $addressMock->expects($this->once())->method('getLastname')->willReturn('Last-name');
        $addressMock->expects($this->once())->method('getCity')->willReturn('New York');
        $addressMock->expects($this->once())->method('getRegionCode')->willReturn('region-code');
        $addressMock->expects($this->once())->method('getPostcode')->willReturn('111FFF');
        $addressMock->expects($this->once())->method('getCountryId')->willReturn('USA');
        $addressMock->expects($this->once())->method('getTelephone')->willReturn('999888');
        $addressMock->expects($this->exactly(2))
            ->method('getStreetLine')
            ->willReturnMap([
                [1, 'street 1'],
                [2, 'street 2']
            ]);

        $platformAddressMock = $this->createPlatformAddressMock();
        $platformAddressMock->expects($this->once())->method('setFirstName')->with('firstname');
        $platformAddressMock->expects($this->once())->method('setLastName')->with('Last-name');
        $platformAddressMock->expects($this->once())->method('setStreet1')->with('street 1');
        $platformAddressMock->expects($this->once())->method('setStreet2')->with('street 2');
        $platformAddressMock->expects($this->once())->method('setCity')->with('New York');
        $platformAddressMock->expects($this->once())->method('setRegion')->with('region-code');
        $platformAddressMock->expects($this->once())->method('setPostcode')->with('111FFF');
        $platformAddressMock->expects($this->once())->method('setCountry')->with('USA');
        $platformAddressMock->expects($this->once())->method('setPhone')->with('999888');

        $storeCode = 'main';
        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->any())->method('getCode')->willReturn($storeCode);

        $productSku = 'sku22';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $quoteMock->expects($this->once())->method('getCouponCode')->willReturn($couponCode);

        $productOption = [];
        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getShippingAddress')->willReturn($platformAddressMock);
        $subscriptionMock->expects($this->once())->method('setCustomerId')->with($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setPaymentProfileId')->with($paymentProfileId);
        $subscriptionMock->expects($this->once())->method('setProductSku')->with($productSku);
        $subscriptionMock->expects($this->once())->method('setProductOption')->with($productOption);
        $subscriptionMock->expects($this->once())->method('setQty')->with($qty);
        $subscriptionMock->expects($this->once())->method('setUseFixedPrice')->with(false);
        $subscriptionMock->expects($this->once())->method('setInterval')->with($interval);
        $subscriptionMock->expects($this->once())->method('setNextOrderDate')->with($nextOrderDate);
        $subscriptionMock->expects($this->once())->method('setFirstOrderAlreadyCreated')->with(true);
        $subscriptionMock->expects($this->once())->method('setMagentoStoreCode')->with($storeCode);
        $subscriptionMock->expects($this->once())->method('setMagentoShippingMethodCode')->with($shippingMethod);
        $subscriptionMock->expects($this->once())->method('setRequiresShipping')->with(true);
        $subscriptionMock->expects($this->once())->method('setCouponCode')->with($couponCode);
        $subscriptionMock->expects($this->once())->method('getId')->willReturn($newSubscriptionId);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionInterval')
            ->with($quoteItemMock)
            ->willReturn($interval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);

        $this->subscriptionOptionConfigMock->expects($this->once())
            ->method('isAllowedCoupon')
            ->with($storeCode)
            ->willReturn(true);

        $this->productOptionHelperMock->expects($this->once())
            ->method('getProductOption')
            ->with($quoteItemMock)
            ->willReturn($productOption);

        $this->eventManagerMock->expects($this->at(0))
            ->method('dispatch')
            ->with(
                'subscribe_pro_before_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );
        $this->eventManagerMock->expects($this->at(1))
            ->method('dispatch')
            ->with(
                'subscribe_pro_after_create_subscription_from_quote_item',
                ['subscription' => $subscriptionMock, 'quote_item' => $quoteItemMock]
            );

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('createSubscription')
            ->willReturn($subscriptionMock);
        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock);

        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->assertEquals(
            $newSubscriptionId,
            $this->subscriptionCreator->create($quoteItemMock, $platformCustomerId, $paymentProfileId, $addressMock)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\StoreInterface
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(StoreInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getCouponCode', 'getStore'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    private function createAddressMock()
    {
        return $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionInterface
     */
    private function createSubscriptionMock()
    {
        return $this->getMockBuilder(PlatformSubscriptionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\AddressInterface
     */
    private function createPlatformAddressMock()
    {
        return $this->getMockBuilder(PlatformAddressInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\DateTime
     */
    private function createDateTimeMock()
    {
        return $this->getMockBuilder(\DateTime::class)->getMock();
    }
}
