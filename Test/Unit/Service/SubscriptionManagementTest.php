<?php

namespace Swarming\SubscribePro\Test\Unit\Service;

use Magento\Framework\App\Area;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\View\DesignInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SubscribePro\Exception\HttpException;
use SubscribePro\Service\Customer\CustomerInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Api\Data\AddressInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use Swarming\SubscribePro\Service\SubscriptionManagement;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;
use Swarming\SubscribePro\Platform\Manager\Customer as CustomerManager;
use Swarming\SubscribePro\Platform\Service\Subscription as SubscriptionService;
use Swarming\SubscribePro\Platform\Manager\Address as AddressManager;
use Swarming\SubscribePro\Helper\SubscriptionProducts as SubscriptionProductsHelper;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class SubscriptionManagementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Service\SubscriptionManagement
     */
    protected $subscriptionManagement;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Address
     */
    protected $platformAddressManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\SubscriptionProducts
     */
    protected $subscriptionProductsHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\DesignInterface
     */
    protected $designMock;

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
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformCustomerManagerMock = $this->getMockBuilder(CustomerManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformSubscriptionServiceMock = $this->getMockBuilder(SubscriptionService::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformAddressManagerMock = $this->getMockBuilder(AddressManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->subscriptionProductsHelperMock = $this->getMockBuilder(SubscriptionProductsHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->designMock = $this->getMockBuilder(DesignInterface::class)->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->subscriptionManagement = new SubscriptionManagement(
            $this->platformProductManagerMock,
            $this->platformCustomerManagerMock,
            $this->platformSubscriptionServiceMock,
            $this->platformAddressManagerMock,
            $this->subscriptionProductsHelperMock,
            $this->designMock,
            $this->dateTimeFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * @expectedExceptionMessage Unable to load subscriptions.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToGetSubscriptionsIfHttpError()
    {
        $customerId = 1234;
        $platformCustomerId = 4321;

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->designMock->expects($this->once())
            ->method('getConfigurationDesignTheme')
            ->with(Area::AREA_FRONTEND)
            ->willReturn('design-theme');

        $this->designMock->expects($this->once())
            ->method('setDesignTheme')
            ->with('design-theme', Area::AREA_FRONTEND);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscriptionsByCustomer')
            ->with($platformCustomerId)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->getSubscriptions($customerId);
    }

    public function testGetSubscriptionsIfNoPlatformCustomer()
    {
        $customerId = 1234;
        $exception = new NoSuchEntityException(__('error'));

        $this->designMock->expects($this->once())
            ->method('getConfigurationDesignTheme')
            ->with(Area::AREA_FRONTEND)
            ->willReturn('design-theme');

        $this->designMock->expects($this->once())
            ->method('setDesignTheme')
            ->with('design-theme', Area::AREA_FRONTEND);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())
            ->method('loadSubscriptionsByCustomer');

        $this->assertEquals([], $this->subscriptionManagement->getSubscriptions($customerId));
    }

    public function testGetSubscriptionsIfNoSubscriptions()
    {
        $customerId = 1234;
        $platformCustomerId = 4321;

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->designMock->expects($this->once())
            ->method('getConfigurationDesignTheme')
            ->with(Area::AREA_FRONTEND)
            ->willReturn('design-theme');

        $this->designMock->expects($this->once())
            ->method('setDesignTheme')
            ->with('design-theme', Area::AREA_FRONTEND);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscriptionsByCustomer')
            ->with($platformCustomerId)
            ->willReturn([]);

        $this->subscriptionProductsHelperMock->expects($this->never())->method('linkProducts');

        $this->assertEquals([], $this->subscriptionManagement->getSubscriptions($customerId));
    }

    public function testGetSubscriptions()
    {
        $customerId = 1234;
        $platformCustomerId = 4321;
        $subscriptions = ['subscriptions'];

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->designMock->expects($this->once())
            ->method('getConfigurationDesignTheme')
            ->with(Area::AREA_FRONTEND)
            ->willReturn('design-theme');

        $this->designMock->expects($this->once())
            ->method('setDesignTheme')
            ->with('design-theme', Area::AREA_FRONTEND);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscriptionsByCustomer')
            ->with($platformCustomerId)
            ->willReturn($subscriptions);

        $this->subscriptionProductsHelperMock->expects($this->once())
            ->method('linkProducts')
            ->with($subscriptions);

        $this->assertEquals($subscriptions, $this->subscriptionManagement->getSubscriptions($customerId));
    }

    /**
     * @expectedExceptionMessage An error occurred while updating quantity.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateQtyIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $qty = 12;

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->updateQty($customerId, $subscriptionId, $qty);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToUpdateQtyIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $qty = 12;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateQty($customerId, $subscriptionId, $qty);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateQtyIfNoSubscriptionProduct()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $qty = 12;
        $platformCustomerId = 4321;
        $productSku = 'sku';
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateQty($customerId, $subscriptionId, $qty);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessageRegExp Invalid quantity, it must be in range from \d+ to \d+\.
     * @dataProvider failToUpdateQtyIfInvalidQtyDataProvider
     * @param int $qty
     * @param int $minQty
     * @param int $maxQty
     */
    public function testFailToUpdateQtyIfInvalidQty($qty, $minQty, $maxQty)
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;
        $productSku = 'sku';

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $platformProductMock = $this->getMockBuilder(ProductInterface::class)->getMock();
        $platformProductMock->expects($this->any())->method('getMinQty')->willReturn($minQty);
        $platformProductMock->expects($this->any())->method('getMaxQty')->willReturn($maxQty);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku)
            ->willReturn($platformProductMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateQty($customerId, $subscriptionId, $qty);
    }

    /**
     * @return array
     */
    public function failToUpdateQtyIfInvalidQtyDataProvider()
    {
        return [
            'Qty less than min' => [
                'qty' => 1,
                'minQty' => 3,
                'maxQty' => 5,
            ],
            'Qty greater than max' => [
                'qty' => 7,
                'minQty' => 3,
                'maxQty' => 5,
            ]
        ];
    }

    public function testUpdateQty()
    {
        $qty = 5;
        $minQty = 3;
        $maxQty = 7;
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;
        $productSku = 'sku';

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);
        $subscriptionMock->expects($this->once())->method('setQty')->with($qty);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $platformProductMock = $this->getMockBuilder(ProductInterface::class)->getMock();
        $platformProductMock->expects($this->any())->method('getMinQty')->willReturn($minQty);
        $platformProductMock->expects($this->any())->method('getMaxQty')->willReturn($maxQty);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku)
            ->willReturn($platformProductMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock);

        $this->assertTrue($this->subscriptionManagement->updateQty($customerId, $subscriptionId, $qty));
    }

    /**
     * @expectedExceptionMessage An error occurred while updating interval.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateIntervalIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $interval = 'weekly';

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->updateInterval($customerId, $subscriptionId, $interval);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateIntervalIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $interval = 'weekly';
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateInterval($customerId, $subscriptionId, $interval);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToUpdateIntervalIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $interval = 'weekly';
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateInterval($customerId, $subscriptionId, $interval);
    }

    public function testUpdateInterval()
    {
        $interval = 'weekly';
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setInterval')->with($interval);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock);

        $this->assertTrue($this->subscriptionManagement->updateInterval($customerId, $subscriptionId, $interval));
    }

    /**
     * @expectedExceptionMessage Invalid next order date, it must be not earlier than 2 days in the future.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateNextOrderDateIfInvalidDate()
    {
        $minNextOrderInterval = '2017-05-12';
        $nextOrderDate = '2017-05-11';
        $customerId = 1234;
        $subscriptionId = 555;

        $dateTimeMock = $this->getMockBuilder('DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d')
            ->willReturn($minNextOrderInterval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with(SubscriptionManagement::MINIMUM_NEXT_ORDER_INTERVAL)
            ->willReturn($dateTimeMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('loadSubscription');
        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate);
    }

    /**
     * @expectedExceptionMessage An error occurred while updating next order date.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateNextOrderDateIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $minNextOrderInterval = '2017-05-12';
        $nextOrderDate = '2017-05-15';

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $dateTimeMock = $this->getMockBuilder('DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d')
            ->willReturn($minNextOrderInterval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with(SubscriptionManagement::MINIMUM_NEXT_ORDER_INTERVAL)
            ->willReturn($dateTimeMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateNextOrderDateIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $minNextOrderInterval = '2017-05-12';
        $nextOrderDate = '2017-05-15';
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $dateTimeMock = $this->getMockBuilder('DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d')
            ->willReturn($minNextOrderInterval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with(SubscriptionManagement::MINIMUM_NEXT_ORDER_INTERVAL)
            ->willReturn($dateTimeMock);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToUpdateNextOrderDateIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $minNextOrderInterval = '2017-05-12';
        $nextOrderDate = '2017-05-15';
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $dateTimeMock = $this->getMockBuilder('DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d')
            ->willReturn($minNextOrderInterval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with(SubscriptionManagement::MINIMUM_NEXT_ORDER_INTERVAL)
            ->willReturn($dateTimeMock);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate);
    }

    public function testUpdateNextOrderDate()
    {
        $minNextOrderInterval = '2017-05-12';
        $nextOrderDate = '2017-05-15';
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setNextOrderDate')->with($nextOrderDate);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $dateTimeMock = $this->getMockBuilder('DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d')
            ->willReturn($minNextOrderInterval);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with(SubscriptionManagement::MINIMUM_NEXT_ORDER_INTERVAL)
            ->willReturn($dateTimeMock);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock);

        $this->assertTrue($this->subscriptionManagement->updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate));
    }

    /**
     * @expectedExceptionMessage An error occurred while updating payment profile.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdatePaymentProfileIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $paymentProfileId = 7721;

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdatePaymentProfileIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $paymentProfileId = 7721;
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToUpdatePaymentProfileIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $paymentProfileId = 7721;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');

        $this->subscriptionManagement->updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId);
    }

    public function testUpdatePaymentProfile()
    {
        $paymentProfileId = 7721;
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $profileMock = $this->getMockBuilder(PaymentProfileInterface::class)->getMock();

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setPaymentProfileId')->with($paymentProfileId);
        $subscriptionMock->expects($this->once())->method('getPaymentProfile')->willReturn($profileMock);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock)
            ->willReturn($subscriptionMock);

        $this->assertSame($profileMock, $this->subscriptionManagement->updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId));
    }

    /**
     * @expectedExceptionMessage An error occurred while updating subscription shipping address.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateShippingAddressIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $billingAddressMock = $this->createBillingAddressMock();

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');
        $this->platformAddressManagerMock->expects($this->never())->method('findOrSaveAddress');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->updateShippingAddress($customerId, $subscriptionId, $billingAddressMock);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToUpdateShippingAddressIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $billingAddressMock = $this->createBillingAddressMock();
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');
        $this->platformAddressManagerMock->expects($this->never())->method('findOrSaveAddress');

        $this->subscriptionManagement->updateShippingAddress($customerId, $subscriptionId, $billingAddressMock);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToUpdateShippingAddressIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $billingAddressMock = $this->createBillingAddressMock();
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->any())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('saveSubscription');
        $this->platformAddressManagerMock->expects($this->never())->method('findOrSaveAddress');

        $this->subscriptionManagement->updateShippingAddress($customerId, $subscriptionId, $billingAddressMock);
    }

    public function testUpdateShippingAddress()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;
        $platformAddressId = 911;
        $billingAddressMock = $this->createBillingAddressMock();

        $platformAddressMock = $this->getMockBuilder(AddressInterface::class)->getMock();
        $platformAddressMock->expects($this->once())->method('getId')->willReturn($platformAddressId);

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('setShippingAddressId')->with($platformAddressId);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->any())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformAddressManagerMock->expects($this->once())
            ->method('findOrSaveAddress')
            ->with($billingAddressMock, $platformCustomerId)
            ->willReturn($platformAddressMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock)
            ->willReturn($subscriptionMock);

        $this->assertSame(
            $platformAddressMock,
            $this->subscriptionManagement->updateShippingAddress($customerId, $subscriptionId, $billingAddressMock)
        );
    }

    /**
     * @expectedExceptionMessage An error occurred while skipping next delivery.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToSkipIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('skipSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->skip($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToSkipIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('skipSubscription');

        $this->subscriptionManagement->skip($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToSkipIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('skipSubscription');

        $this->subscriptionManagement->skip($customerId, $subscriptionId);
    }

    public function testSkip()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;
        $nextOrderDate = '2017-05-05';

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);
        $subscriptionMock->expects($this->once())->method('getNextOrderDate')->willReturn($nextOrderDate);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->exactly(2))
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('skipSubscription')
            ->with($subscriptionId);

        $this->assertEquals($nextOrderDate, $this->subscriptionManagement->skip($customerId, $subscriptionId));
    }

    /**
     * @expectedExceptionMessage An error occurred while canceling subscription.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToCancelIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('cancelSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->cancel($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToCancelIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('cancelSubscription');

        $this->subscriptionManagement->cancel($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToCancelIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('cancelSubscription');

        $this->subscriptionManagement->cancel($customerId, $subscriptionId);
    }

    public function testCancel()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('cancelSubscription')
            ->with($subscriptionId);

        $this->assertTrue($this->subscriptionManagement->cancel($customerId, $subscriptionId));
    }

    /**
     * @expectedExceptionMessage An error occurred while pausing subscription.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToPauseIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('pauseSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->pause($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToPauseIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('pauseSubscription');

        $this->subscriptionManagement->pause($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToPauseIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('pauseSubscription');

        $this->subscriptionManagement->pause($customerId, $subscriptionId);
    }

    public function testPause()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('pauseSubscription')
            ->with($subscriptionId);

        $this->assertTrue($this->subscriptionManagement->pause($customerId, $subscriptionId));
    }

    /**
     * @expectedExceptionMessage An error occurred while restarting subscription.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToRestartIfHttpError()
    {
        $customerId = 1234;
        $subscriptionId = 555;

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = new HttpException($responseMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('restartSubscription');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->subscriptionManagement->restart($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage The subscription is not found.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToRestartIfNoCustomer()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $exception = new NoSuchEntityException(__('error'));

        $subscriptionMock = $this->createSubscriptionMock();

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willThrowException($exception);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('restartSubscription');

        $this->subscriptionManagement->restart($customerId, $subscriptionId);
    }

    /**
     * @expectedExceptionMessage Forbidden action.
     * @expectedException \Magento\Framework\Exception\AuthorizationException
     */
    public function testFailToRestartIfNotAuthorized()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn(10000);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->never())->method('restartSubscription');

        $this->subscriptionManagement->restart($customerId, $subscriptionId);
    }

    public function testRestart()
    {
        $customerId = 1234;
        $subscriptionId = 555;
        $platformCustomerId = 4321;

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getCustomerId')->willReturn($platformCustomerId);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->platformSubscriptionServiceMock->expects($this->once())
            ->method('restartSubscription')
            ->with($subscriptionId);

        $this->assertTrue($this->subscriptionManagement->restart($customerId, $subscriptionId));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerInterface
     */
    private function createCustomerMock()
    {
        return $this->getMockBuilder(CustomerInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionInterface
     */
    private function createSubscriptionMock()
    {
        return $this->getMockBuilder(SubscriptionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    private function createBillingAddressMock()
    {
        return $this->getMockBuilder(QuoteAddress::class)->disableOriginalConstructor()->getMock();
    }
}
