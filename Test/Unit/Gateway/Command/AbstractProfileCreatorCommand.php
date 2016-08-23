<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use SubscribePro\Service\Customer\CustomerInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;
use Swarming\SubscribePro\Platform\Manager\Customer as CustomerManager;

class AbstractProfileCreatorCommand extends AbstractCommand
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManagerMock;

    protected function initProperties()
    {
        parent::initProperties();
        $this->platformCustomerManagerMock = $this->getMockBuilder(CustomerManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createPaymentProfile($requestData)
    {
        $customerId = 12;
        $customerMock = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $profileMock = $this->getMockBuilder(PaymentProfileInterface::class)->getMock();
        $profileMock->expects($this->once())->method('setCustomerId')->with($customerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($requestData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID], true)
            ->willReturn($customerMock);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->with($requestData)
            ->willReturn($profileMock);
        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('saveToken')
            ->with($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN], $profileMock);

        return $profileMock;
    }
}
