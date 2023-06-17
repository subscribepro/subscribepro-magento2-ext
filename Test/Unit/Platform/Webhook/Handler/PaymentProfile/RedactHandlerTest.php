<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook\Handler\PaymentProfile;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\RedactHandler;

class RedactHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\RedactHandler
     */
    protected $redactHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagementMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepositoryMock;

    protected function setUp(): void
    {
        $this->paymentTokenManagementMock = $this->getMockBuilder(PaymentTokenManagementInterface::class)->getMock();
        $this->paymentTokenRepositoryMock = $this->getMockBuilder(PaymentTokenRepositoryInterface::class)->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();

        $this->redactHandler = new RedactHandler(
            $this->paymentTokenManagementMock,
            $this->paymentTokenRepositoryMock,
            $this->customerRepositoryMock
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Saved card is not found for payment_id=4422 and customer_id=5566
     */
    public function testExecuteIfPaymentTokenNotFound()
    {
        $paymentProfileId = 4422;
        $customerId = 5566;
        $profileData = [
            'key' => 'value',
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => $customerId
        ];

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->at(0))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(1))
            ->method('getEventData')
            ->with('payment_profile_id')
            ->willReturn($paymentProfileId);
        $webhookEventMock->expects($this->at(2))
            ->method('getEventData')
            ->with('payment_profile_id')
            ->willReturn($paymentProfileId);

        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByGatewayToken')
            ->with($paymentProfileId, ConfigProvider::CODE, $customerId)
            ->willReturn(null);

        $this->paymentTokenRepositoryMock->expects($this->never())->method('delete');

        $this->redactHandler->execute($webhookEventMock);
    }

    public function testExecuteIfCustomerIdIsInProfileData()
    {
        $paymentProfileId = 424234;
        $customerId = 4341;
        $profileData = [
            'key' => 'value',
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => $customerId
        ];

        $paymentTokenMock = $this->createPaymentTokenMock();

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->at(0))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(1))
            ->method('getEventData')
            ->with('payment_profile_id')
            ->willReturn($paymentProfileId);

        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByGatewayToken')
            ->with($paymentProfileId, ConfigProvider::CODE, $customerId)
            ->willReturn($paymentTokenMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($paymentTokenMock);

        $this->redactHandler->execute($webhookEventMock);
    }

    public function testExecuteIfCustomerIdIsInCustomerData()
    {
        $paymentProfileId = 424234;
        $customerId = 4341;
        $profileData = ['profile data'];
        $customerData = [PlatformCustomerInterface::MAGENTO_CUSTOMER_ID => $customerId];

        $paymentTokenMock = $this->createPaymentTokenMock();

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->at(0))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(1))
            ->method('getEventData')
            ->with('customer')
            ->willReturn($customerData);
        $webhookEventMock->expects($this->at(2))
            ->method('getEventData')
            ->with('payment_profile_id')
            ->willReturn($paymentProfileId);

        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByGatewayToken')
            ->with($paymentProfileId, ConfigProvider::CODE, $customerId)
            ->willReturn($paymentTokenMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($paymentTokenMock);

        $this->redactHandler->execute($webhookEventMock);
    }

    /**
     * @param array $profileData
     * @param array $customerData
     * @param int $paymentProfileId
     * @param string $email
     * @param int $customerId
     * @dataProvider executeWithCustomerEmailDataProvider
     */
    public function testExecuteWithCustomerEmail($profileData, $customerData, $paymentProfileId, $email, $customerId)
    {
        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $paymentTokenMock = $this->createPaymentTokenMock();

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->at(0))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(1))
            ->method('getEventData')
            ->with('customer')
            ->willReturn($customerData);
        $webhookEventMock->expects($this->at(2))
            ->method('getEventData')
            ->with('payment_profile_id')
            ->willReturn($paymentProfileId);

        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByGatewayToken')
            ->with($paymentProfileId, ConfigProvider::CODE, $customerId)
            ->willReturn($paymentTokenMock);

        $this->customerRepositoryMock->expects($this->once())
            ->method('get')
            ->with($email)
            ->willReturn($customerMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($paymentTokenMock);

        $this->redactHandler->execute($webhookEventMock);
    }

    /**
     * @return array
     */
    public function executeWithCustomerEmailDataProvider()
    {
        return [
            'Email is in profile data' => [
                'profileData' => [
                    PaymentProfileInterface::CUSTOMER_EMAIL => 'some@mail.com',
                    'key' => 'field'
                ],
                'paymentProfileId' => 5123123,
                'customerData' => ['name' => 'Bob'],
                'email' => 'some@mail.com',
                'customerId' => 123123
            ],
            'Email is in customer data' => [
                'profileData' => ['profile', 'data'],
                'customerData' => [
                    PlatformCustomerInterface::EMAIL => 'some@mail.email',
                    'key' => 'val'
                ],
                'paymentProfileId' => 53453,
                'email' => 'some@mail.email',
                'customerId' => 123123
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function createPaymentTokenMock()
    {
        return $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\EventInterface
     */
    private function createWebhookEventMock()
    {
        return $this->getMockBuilder(EventInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Api\Data\CustomerInterface
     */
    private function createCustomerMock()
    {
        return $this->getMockBuilder(CustomerInterface::class)->getMock();
    }
}
