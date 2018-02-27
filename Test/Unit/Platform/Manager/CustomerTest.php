<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Manager;

use Magento\Customer\Api\CustomerRepositoryInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Swarming\SubscribePro\Platform\Manager\Customer;
use Swarming\SubscribePro\Platform\Service\Customer as CustomerService;

class CustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $customerManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Customer
     */
    protected $platformCustomerServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepositoryMock;

    protected function setUp()
    {
        $this->platformCustomerServiceMock = $this->getMockBuilder(CustomerService::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();

        $this->customerManager = new Customer(
            $this->platformCustomerServiceMock,
            $this->customerRepositoryMock
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Platform customer is not found.
     */
    public function testFailToGetCustomerIfNotFound()
    {
        $email = 'email';
        $websiteId = 12;

        $this->platformCustomerServiceMock->expects($this->once())
            ->method('loadCustomers')
            ->with([PlatformCustomerInterface::EMAIL => $email], $websiteId)
            ->willReturn([]);

        $this->customerManager->getCustomer($email, false, $websiteId);
    }

    public function testGetCustomerIfCreateIfNotExists()
    {
        $email = 'email';
        $websiteId = 12;
        
        $customerMock = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $customerMock->expects($this->once())->method('getId')->willReturn(11);
        $customerMock->expects($this->once())->method('getEmail')->willReturn($email);
        $customerMock->expects($this->once())->method('getFirstname')->willReturn('first');
        $customerMock->expects($this->once())->method('getLastname')->willReturn('last');
        $customerMock->expects($this->once())->method('getMiddlename')->willReturn('middle');
        $customerMock->expects($this->once())->method('getGroupId')->willReturn(324);
        $customerMock->expects($this->once())->method('getWebsiteId')->willReturn(21);
        
        $platformCustomerMock = $this->createPlatformCustomerMock();
        $platformCustomerMock->expects($this->once())->method('setMagentoCustomerId')->with(11);
        $platformCustomerMock->expects($this->once())->method('setEmail')->with($email);
        $platformCustomerMock->expects($this->once())->method('setFirstName')->with('first');
        $platformCustomerMock->expects($this->once())->method('setLastName')->with('last');
        $platformCustomerMock->expects($this->once())->method('setMiddleName')->with('middle');
        $platformCustomerMock->expects($this->once())->method('setMagentoCustomerGroupId')->with(324);
        $platformCustomerMock->expects($this->once())->method('setMagentoWebsiteId')->with(21);

        $this->platformCustomerServiceMock->expects($this->once())
            ->method('loadCustomers')
            ->with([PlatformCustomerInterface::EMAIL => $email], $websiteId)
            ->willReturn([]);
        $this->platformCustomerServiceMock->expects($this->once())
            ->method('createCustomer')
            ->with([], $websiteId)
            ->willReturn($platformCustomerMock);
        $this->platformCustomerServiceMock->expects($this->once())
            ->method('saveCustomer')
            ->with($platformCustomerMock, $websiteId);

        $this->customerRepositoryMock->expects($this->once())
            ->method('get')
            ->with($email, $websiteId)
            ->willReturn($customerMock);

        $this->assertSame(
            $platformCustomerMock,
            $this->customerManager->getCustomer($email, true, $websiteId)
        );
    }

    public function testGetCustomerIfPlatformCustomerExists()
    {
        $email = 'email';
        $websiteId = 12;
        $platformCustomerMock = $this->createPlatformCustomerMock();

        $this->platformCustomerServiceMock->expects($this->once())
            ->method('loadCustomers')
            ->with([PlatformCustomerInterface::EMAIL => $email], $websiteId)
            ->willReturn([$platformCustomerMock]);
        $this->platformCustomerServiceMock->expects($this->never())->method('createCustomer');
        $this->platformCustomerServiceMock->expects($this->never())->method('saveCustomer');

        $this->customerRepositoryMock->expects($this->never())->method('get');

        $this->assertSame(
            $platformCustomerMock,
            $this->customerManager->getCustomer($email, false, $websiteId)
        );
    }

    public function testGetCustomerById()
    {
        $email = 'email';
        $websiteId = 12;
        $magentoCustomerId = 112;
        $platformCustomerMock = $this->createPlatformCustomerMock();

        $customerMock = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $customerMock->expects($this->once())->method('getEmail')->willReturn($email);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($magentoCustomerId)
            ->willReturn($customerMock);

        $this->platformCustomerServiceMock->expects($this->once())
            ->method('loadCustomers')
            ->with([PlatformCustomerInterface::EMAIL => $email], $websiteId)
            ->willReturn([$platformCustomerMock]);
        $this->platformCustomerServiceMock->expects($this->never())->method('createCustomer');
        $this->platformCustomerServiceMock->expects($this->never())->method('saveCustomer');

        $this->customerRepositoryMock->expects($this->never())->method('get');

        $this->assertSame(
            $platformCustomerMock,
            $this->customerManager->getCustomerById($magentoCustomerId, false, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerInterface
     */
    private function createPlatformCustomerMock()
    {
        return $this->getMockBuilder(PlatformCustomerInterface::class)->getMock();
    }
}
