<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook\Handler\PaymentProfile;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\CreateHandler;
use Swarming\SubscribePro\Platform\Service\PaymentProfile as PaymentProfileService;
use Swarming\SubscribePro\Helper\Vault as VaultHelper;
use Magento\Vault\Model\CreditCardTokenFactory;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;

class CreateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\CreateHandler
     */
    protected $createHandler;

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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Model\CreditCardTokenFactory
     */
    protected $paymentTokenFactoryMock;

    protected function setUp()
    {
        $this->paymentTokenManagementMock = $this->getMockBuilder(PaymentTokenManagementInterface::class)->getMock();
        $this->paymentTokenRepositoryMock = $this->getMockBuilder(PaymentTokenRepositoryInterface::class)->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();
        $this->platformPaymentProfileServiceMock = $this->getMockBuilder(PaymentProfileService::class)
            ->disableOriginalConstructor()->getMock();
        $this->vaultHelperMock = $this->getMockBuilder(VaultHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentTokenFactoryMock = $this->getMockBuilder(CreditCardTokenFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->createHandler = new CreateHandler(
            $this->paymentTokenManagementMock,
            $this->paymentTokenRepositoryMock,
            $this->customerRepositoryMock,
            $this->platformPaymentProfileServiceMock,
            $this->vaultHelperMock,
            $this->paymentTokenFactoryMock
        );
    }

    public function testExecuteIfCustomerIdIsInProfileData()
    {
        $customerId = 4341;
        $profileData = [
            'key' => 'value',
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => $customerId
        ];

        $profileMock = $this->createProfileMock();
        $profileMock->expects($this->once())->method('getMagentoCustomerId')->willReturn(null);
        $profileMock->expects($this->once())->method('setMagentoCustomerId')->with($customerId);

        $paymentTokenMock = $this->createPaymentTokenMock();

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->exactly(2))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->with($profileData)
            ->willReturn($profileMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('initVault')
            ->with($paymentTokenMock, $profileMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $this->createHandler->execute($webhookEventMock);
    }

    public function testExecuteIfCustomerIdIsInCustomerData()
    {
        $customerId = 89898;
        $profileData = ['key' => 'value'];
        $customerData = [PlatformCustomerInterface::MAGENTO_CUSTOMER_ID => $customerId];

        $profileMock = $this->createProfileMock();
        $profileMock->expects($this->once())->method('getMagentoCustomerId')->willReturn(null);
        $profileMock->expects($this->once())->method('setMagentoCustomerId')->with($customerId);

        $paymentTokenMock = $this->createPaymentTokenMock();

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->at(0))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(1))
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(2))
            ->method('getEventData')
            ->with('customer')
            ->willReturn($customerData);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->with($profileData)
            ->willReturn($profileMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('initVault')
            ->with($paymentTokenMock, $profileMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $this->createHandler->execute($webhookEventMock);
    }

    /**
     * @param array $profileData
     * @param array $customerData
     * @param string $email
     * @param int $customerId
     * @dataProvider executeWithCustomerEmailDataProvider
     */
    public function testExecuteWithCustomerEmail($profileData, $customerData, $email, $customerId)
    {
        $profileMock = $this->createProfileMock();
        $profileMock->expects($this->once())->method('getMagentoCustomerId')->willReturn(null);
        $profileMock->expects($this->once())->method('setMagentoCustomerId')->with($customerId);

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
            ->with('payment_profile')
            ->willReturn($profileData);
        $webhookEventMock->expects($this->at(2))
            ->method('getEventData')
            ->with('customer')
            ->willReturn($customerData);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->with($profileData)
            ->willReturn($profileMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('initVault')
            ->with($paymentTokenMock, $profileMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $this->customerRepositoryMock->expects($this->once())
            ->method('get')
            ->with($email)
            ->willReturn($customerMock);

        $this->createHandler->execute($webhookEventMock);
    }

    /**
     * @return array
     */
    public function executeWithCustomerEmailDataProvider()
    {
        return [
            'Email is in profile data' => [
                'profileData' => [
                    PaymentProfileInterface::CUSTOMER_EMAIL => 'email@mail.mail',
                    'some' => 'another field'
                ],
                'customerData' => ['name' => 'john'],
                'email' => 'email@mail.mail',
                'customerId' => 53242
            ],
            'Email is in customer data' => [
                'profileData' => ['profile data'],
                'customerData' => [
                    PlatformCustomerInterface::EMAIL => 'some@email',
                ],
                'email' => 'some@email',
                'customerId' => 4234
            ],
        ];
    }

    public function testExecuteIfProfileHasCustomerId()
    {
        $customerId = 4341;
        $profileData = ['profile data'];

        $profileMock = $this->createProfileMock();
        $profileMock->expects($this->once())->method('getMagentoCustomerId')->willReturn($customerId);
        $profileMock->expects($this->never())->method('setMagentoCustomerId');

        $paymentTokenMock = $this->createPaymentTokenMock();

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->once())
            ->method('getEventData')
            ->with('payment_profile')
            ->willReturn($profileData);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->with($profileData)
            ->willReturn($profileMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('initVault')
            ->with($paymentTokenMock, $profileMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $this->createHandler->execute($webhookEventMock);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    private function createProfileMock()
    {
        return $this->getMockBuilder(PaymentProfileInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Api\Data\CustomerInterface
     */
    private function createCustomerMock()
    {
        return $this->getMockBuilder(CustomerInterface::class)->getMock();
    }
}
