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
        $this->customerService->setWebsite($this->defaultWebsiteId);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createCustomerDataProvider
     */
    public function testCreateCustomer($websiteId, $expectedWebsiteId)
    {
        $customerMock = $this->createCustomerMock();
        
        $this->initService($this->customerPlatformService, $expectedWebsiteId);
        $this->customerPlatformService->expects($this->once())
            ->method('createCustomer')
            ->with(['customer data'])
            ->willReturn($customerMock);
        
        $this->assertSame(
            $customerMock, $this->customerService->createCustomer(['customer data'], $websiteId)
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
                'expectedWebsiteId' => $this->defaultWebsiteId,
            ]
        ];
    }

    public function testLoadCustomer()
    {
        $customerId = 111;
        $websiteId = 12;
        $customerMock = $this->createCustomerMock();
        $this->initService($this->customerPlatformService, $websiteId);

        $this->customerPlatformService->expects($this->once())
            ->method('loadCustomer')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->assertSame(
            $customerMock, $this->customerService->loadCustomer($customerId, $websiteId)
        );
    }

    public function testSaveCustomer()
    {
        $websiteId = 12;
        $customerMock = $this->createCustomerMock();
        $this->initService($this->customerPlatformService, $websiteId);

        $this->customerPlatformService->expects($this->once())
            ->method('saveCustomer')
            ->with($customerMock)
            ->willReturn($customerMock);

        $this->assertSame(
            $customerMock, $this->customerService->saveCustomer($customerMock, $websiteId)
        );
    }

    public function testLoadCustomers()
    {
        $websiteId = 12;
        $filters = ['filters'];
        $customersMock = [$this->createCustomerMock(), $this->createCustomerMock()];
        $this->initService($this->customerPlatformService, $websiteId);

        $this->customerPlatformService->expects($this->once())
            ->method('loadCustomers')
            ->with($filters)
            ->willReturn($customersMock);

        $this->assertEquals(
            $customersMock, $this->customerService->loadCustomers($filters, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerInterface
     */
    private function createCustomerMock()
    {
        return $this->getMockBuilder(CustomerInterface::class)->getMock();
    }
}
