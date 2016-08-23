<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class AbstractProfileCreatorCommand extends AbstractCommand
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Customer
     */
    protected $platformCustomerServiceMock;

    protected function initProperties()
    {
        parent::initProperties();
        $this->platformCustomerServiceMock = $this->getMockBuilder('Swarming\SubscribePro\Platform\Service\Customer')
            ->disableOriginalConstructor()->getMock();
    }

    protected function createPaymentProfile($requestData)
    {
        $customerId = 12;
        $customerMock = $this->getMockBuilder('SubscribePro\Service\Customer\CustomerInterface')->getMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $profileMock = $this->getMockBuilder('SubscribePro\Service\PaymentProfile\PaymentProfileInterface')->getMock();
        $profileMock->expects($this->once())->method('setCustomerId')->with($customerId);

        $this->platformCustomerServiceMock->expects($this->once())
            ->method('getCustomer')
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
