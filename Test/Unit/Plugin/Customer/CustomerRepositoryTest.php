<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Checkout;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomerManager;
use Swarming\SubscribePro\Platform\Service\Customer as PlatformCustomerService;
use Swarming\SubscribePro\Plugin\Customer\CustomerRepository as CustomerRepositoryPlugin;

class CustomerRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $platformCustomerManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $platformCustomerService;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \Swarming\SubscribePro\Plugin\Customer\CustomerRepository
     */
    protected $customerRepositoryPlugin;

    protected function setUp(): void
    {
        $this->platformCustomerManager = $this->getMockBuilder(PlatformCustomerManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platformCustomerService = $this->getMockBuilder(PlatformCustomerService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->customerRepositoryPlugin = new CustomerRepositoryPlugin(
            $this->platformCustomerManager,
            $this->platformCustomerService,
            $this->logger
        );
    }

    public function testAroundSaveIfPlatformCustomerNotFound()
    {
        $customerId = 123;
        $websiteId = 12;
        $firstName = 'User';
        $lastName = 'Test';
        $email = 'user@email.test';

        $customer = $this->getCustomerMock($customerId, $websiteId);
        $savedCustomer = $this->getSavedCustomerMock($firstName, $lastName, $email, $websiteId);
        $proceed = $this->createProceedCallback($savedCustomer);

        $this->platformCustomerManager->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId, false, $websiteId)
            ->willThrowException(new NoSuchEntityException());

        $this->platformCustomerService->expects($this->never())->method('saveCustomer');
        $this->logger->expects($this->never())->method('critical');

        $subject = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();

        $this->assertSame(
            $savedCustomer,
            $this->customerRepositoryPlugin->aroundSave($subject, $proceed, $customer, null)
        );
    }

    public function testAroundSaveIfExceptionOnLoadCustomer()
    {
        $customerId = 321;
        $websiteId = 4;
        $firstName = 'User';
        $lastName = 'Test';
        $email = 'user@email.test';

        $customer = $this->getCustomerMock($customerId, $websiteId);
        $savedCustomer = $this->getSavedCustomerMock($firstName, $lastName, $email, $websiteId);

        $proceed = $this->createProceedCallback($savedCustomer);

        $exception = new \Exception();

        $this->platformCustomerManager->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId, false, $websiteId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->platformCustomerService->expects($this->never())->method('saveCustomer');

        $subject = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();

        $this->assertSame(
            $savedCustomer,
            $this->customerRepositoryPlugin->aroundSave($subject, $proceed, $customer, null)
        );
    }

    public function testAroundSaveIfExceptionOnSaveCustomer()
    {
        $customerId = 848;
        $websiteId = 1;
        $firstName = 'User';
        $lastName = 'Test';
        $email = 'user@email.test';

        $customer = $this->getCustomerMock($customerId, $websiteId);
        $savedCustomer = $this->getSavedCustomerMock($firstName, $lastName, $email, $websiteId);
        $platformCustomer = $this->getPlatformCustomerMock($firstName, $lastName, $email);

        $proceed = $this->createProceedCallback($savedCustomer);

        $exception = new \Exception();

        $this->platformCustomerManager->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId, false, $websiteId)
            ->willReturn($platformCustomer);

        $this->platformCustomerService->expects($this->once())
            ->method('saveCustomer')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $subject = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();

        $this->assertSame(
            $savedCustomer,
            $this->customerRepositoryPlugin->aroundSave($subject, $proceed, $customer, null)
        );
    }

    public function testAroundSave()
    {
        $customerId = 243;
        $websiteId = 2;
        $firstName = 'User';
        $lastName = 'Test';
        $email = 'user@email.test';

        $customer = $this->getCustomerMock($customerId, $websiteId);
        $savedCustomer = $this->getSavedCustomerMock($firstName, $lastName, $email, $websiteId);
        $platformCustomer = $this->getPlatformCustomerMock($firstName, $lastName, $email);

        $proceed = $this->createProceedCallback($savedCustomer);

        $this->platformCustomerManager->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId, false, $websiteId)
            ->willReturn($platformCustomer);

        $this->platformCustomerService->expects($this->once())
            ->method('saveCustomer')
            ->with($platformCustomer, $websiteId)
            ->willReturn($platformCustomer);

        $this->logger->expects($this->never())->method('critical');
        $subject = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();

        $this->assertSame(
            $savedCustomer,
            $this->customerRepositoryPlugin->aroundSave($subject, $proceed, $customer, null)
        );
    }

    /**
     * @param string $customerId
     * @param string $websiteId
     * @return \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCustomerMock($customerId, $websiteId)
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)->getMock();

        $customer->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($customerId);

        $customer->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        return $customer;
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $websiteId
     * @return \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSavedCustomerMock($firstName, $lastName, $email, $websiteId)
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)->getMock();

        $customer->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $customer->expects($this->any())
            ->method('getFirstname')
            ->willReturn($firstName);

        $customer->expects($this->any())
            ->method('getLastname')
            ->willReturn($lastName);

        $customer->expects($this->any())
            ->method('getEmail')
            ->willReturn($email);

        return $customer;
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @return \SubscribePro\Service\Customer\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPlatformCustomerMock($firstName, $lastName, $email)
    {
        $platformCustomer = $this->getMockBuilder(PlatformCustomerInterface::class)->getMock();

        $platformCustomer->expects($this->any())
            ->method('setFirstname')
            ->willReturn($firstName);

        $platformCustomer->expects($this->any())
            ->method('setLastname')
            ->willReturn($lastName);

        $platformCustomer->expects($this->any())
            ->method('setEmail')
            ->willReturn($email);

        return $platformCustomer;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $resultCustomer
     * @return callable
     */
    protected function createProceedCallback($resultCustomer)
    {
        return function ($customer, $passwordHash) use ($resultCustomer) {
            return $resultCustomer;
        };
    }
}
