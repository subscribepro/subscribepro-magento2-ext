<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\Customer\CustomerInterface;
use SubscribePro\Service\Customer\CustomerService;
use Swarming\SubscribePro\Platform\Service\Customer;

class CustomerTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Customer
     */
    protected $customerService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerService
     */
    protected $customerPlatformService;

    protected function setUp()
    {
        $this->platformMock = $this->createPlatformMock();
        $this->customerPlatformService = $this->getMockBuilder(CustomerService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerService = new Customer($this->platformMock, $this->name);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createCustomerDataProvider
     */
    public function testCreateCustomer($websiteId, $expectedWebsiteId)
    {
        $platformCustomerMock = $this->createPlatformCustomerMock();
        
        $this->initService($this->customerPlatformService, $expectedWebsiteId);
        $this->customerPlatformService->expects($this->once())
            ->method('createCustomer')
            ->with(['customer data'])
            ->willReturn($platformCustomerMock);
        
        $this->assertSame(
            $platformCustomerMock, $this->customerService->createCustomer(['customer data'], $websiteId)
        );
    }

    /**
     * @return array
     */
    public function createCustomerDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
            ],
            'Without website Id' => [
                'websiteId' => null,
                'expectedWebsiteId' => null,
            ]
        ];
    }

    public function testLoadCustomer()
    {
        $customerId = 111;
        $websiteId = 12;
        $platformCustomerMock = $this->createPlatformCustomerMock();
        $this->initService($this->customerPlatformService, $websiteId);

        $this->customerPlatformService->expects($this->once())
            ->method('loadCustomer')
            ->with($customerId)
            ->willReturn($platformCustomerMock);

        $this->assertSame(
            $platformCustomerMock, $this->customerService->loadCustomer($customerId, $websiteId)
        );
    }

    public function testSaveCustomer()
    {
        $websiteId = 12;
        $platformCustomerMock = $this->createPlatformCustomerMock();
        $this->initService($this->customerPlatformService, $websiteId);

        $this->customerPlatformService->expects($this->once())
            ->method('saveCustomer')
            ->with($platformCustomerMock)
            ->willReturn($platformCustomerMock);

        $this->assertSame(
            $platformCustomerMock, $this->customerService->saveCustomer($platformCustomerMock, $websiteId)
        );
    }

    public function testLoadCustomers()
    {
        $websiteId = 12;
        $filters = ['filters'];
        $platformCustomersMock = [$this->createPlatformCustomerMock(), $this->createPlatformCustomerMock()];
        $this->initService($this->customerPlatformService, $websiteId);

        $this->customerPlatformService->expects($this->once())
            ->method('loadCustomers')
            ->with($filters)
            ->willReturn($platformCustomersMock);

        $this->assertEquals(
            $platformCustomersMock, $this->customerService->loadCustomers($filters, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerInterface
     */
    private function createPlatformCustomerMock()
    {
        return $this->getMockBuilder(CustomerInterface::class)->getMock();
    }
}
