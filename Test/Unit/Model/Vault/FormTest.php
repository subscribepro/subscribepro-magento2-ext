<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Vault;

use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentToken;
use Magento\Vault\Model\CreditCardTokenFactory;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Model\Vault\Form;
use Swarming\SubscribePro\Helper\Vault as VaultHelper;
use Swarming\SubscribePro\Platform\Service\PaymentProfile as PaymentProfileService;
use Swarming\SubscribePro\Platform\Manager\Customer as CustomerManager;
use Swarming\SubscribePro\Model\Vault\Validator as VaultValidator;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;

class FormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Vault\Form
     */
    protected $vaultForm;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagementMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Model\CreditCardTokenFactory
     */
    protected $paymentTokenFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Vault\Validator
     */
    protected $validatorMock;

    protected function setUp()
    {
        $this->paymentTokenRepositoryMock = $this->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentTokenManagementMock = $this->getMockBuilder(PaymentTokenManagementInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentTokenFactoryMock = $this->getMockBuilder(CreditCardTokenFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->vaultHelperMock = $this->getMockBuilder(VaultHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformPaymentProfileServiceMock = $this->getMockBuilder(PaymentProfileService::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformCustomerManagerMock = $this->getMockBuilder(CustomerManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->validatorMock = $this->getMockBuilder(VaultValidator::class)
            ->disableOriginalConstructor()->getMock();

        $this->vaultForm = new Form(
            $this->paymentTokenRepositoryMock,
            $this->paymentTokenManagementMock,
            $this->paymentTokenFactoryMock,
            $this->vaultHelperMock,
            $this->platformPaymentProfileServiceMock,
            $this->platformCustomerManagerMock,
            $this->validatorMock
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The credit card can be not saved.
     * @param array $profileData
     * @param int $customerId
     * @dataProvider createProfileIfEmptyTokenDataProvider
     */
    public function testCreateProfileIfEmptyToken($profileData, $customerId)
    {
        $this->validatorMock->expects($this->never())->method('validate');
        $this->platformPaymentProfileServiceMock->expects($this->never())->method('saveToken');
        $this->paymentTokenRepositoryMock->expects($this->never())->method('save');

        $this->vaultForm->createProfile($profileData, $customerId);
    }

    /**
     * @return array
     */
    public function createProfileIfEmptyTokenDataProvider()
    {
        return [
            'Token not set' => [
                'profileData' => ['key' => 'value'],
                'customerId' => 123,
            ],
            'Token is empty' => [
                'profileData' => ['key' => 'value', 'token' => ''],
                'customerId' => 3453,
            ],
        ];
    }

    public function testCreateProfile()
    {
        $customerId = 123123;
        $platformCustomerId = 987;
        $token = 'token_token';
        $profileData = ['token' => $token];
        $updatedProfileData = ['token' => $token, 'data' => 'data_value'];

        $platformCustomerMock = $this->createPlatformCustomerMock();
        $platformCustomerMock->expects($this->once())->method('getId')->willReturn($platformCustomerId);
        $platformCustomerMock->expects($this->once())->method('getMagentoCustomerId')->willReturn($customerId);

        $paymentTokenMock = $this->createPaymentTokenMock();

        $profileMock = $this->createProfileMock();
        $profileMock->expects($this->once())->method('importData')->with($updatedProfileData);
        $profileMock->expects($this->once())->method('setCustomerId')->with($platformCustomerId);
        $profileMock->expects($this->once())->method('setMagentoCustomerId')->with($customerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId, true)
            ->willReturn($platformCustomerMock);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($profileData)
            ->willReturn($updatedProfileData);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->willReturn($profileMock);
        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('saveToken')
            ->with($token, $profileMock);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('initVault')
            ->with($paymentTokenMock, $profileMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $this->vaultForm->createProfile($profileData, $customerId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The credit card is not found.
     */
    public function testUpdateProfileIfNoPaymentToken()
    {
        $customerId = 123123;
        $profileData = ['data'];
        $publicHash = 'hash';

        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByPublicHash')
            ->with($publicHash, $customerId)
            ->willReturn(null);

        $this->validatorMock->expects($this->never())->method('validate');
        $this->paymentTokenRepositoryMock->expects($this->never())->method('save');

        $this->vaultForm->updateProfile($publicHash, $profileData, $customerId);
    }

    public function testUpdateProfile()
    {
        $customerId = 45645;
        $profileData = ['data'];
        $updatedProfileData = ['updated data'];
        $publicHash = '111hash111';
        $gatewayToken = 'gateway_token';

        $paymentTokenMock = $this->createPaymentTokenMock();
        $paymentTokenMock->expects($this->once())->method('getGatewayToken')->willReturn($gatewayToken);

        $profileMock = $this->createProfileMock();
        $profileMock->expects($this->once())->method('importData')->with($updatedProfileData);
        $profileMock->expects($this->once())->method('setId')->with($gatewayToken);

        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByPublicHash')
            ->with($publicHash, $customerId)
            ->willReturn($paymentTokenMock);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($profileData)
            ->willReturn($updatedProfileData);

        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('createProfile')
            ->willReturn($profileMock);
        $this->platformPaymentProfileServiceMock->expects($this->once())
            ->method('saveProfile')
            ->with($profileMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('updateVault')
            ->with($paymentTokenMock, $profileMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $this->vaultForm->updateProfile($publicHash, $profileData, $customerId);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerInterface
     */
    private function createPlatformCustomerMock()
    {
        return $this->getMockBuilder(PlatformCustomerInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    private function createProfileMock()
    {
        return $this->getMockBuilder(PaymentProfileInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Model\PaymentToken
     */
    private function createPaymentTokenMock()
    {
        return $this->getMockBuilder(PaymentToken::class)->disableOriginalConstructor()->getMock();
    }
}
