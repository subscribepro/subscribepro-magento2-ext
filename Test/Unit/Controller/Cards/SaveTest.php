<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Cards;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Controller\Cards\Save;
use Swarming\SubscribePro\Gateway\Config\VaultConfig;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Swarming\SubscribePro\Model\Vault\Form as VaultForm;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Swarming\SubscribePro\Model\Vault\Validator as VaultFormValidator;
use Swarming\SubscribePro\Gateway\Command\AuthorizeCommand as WalletAuthorizeCommand;
use \Magento\Store\Model\StoreManagerInterface as StoreManager;

class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Cards\Save
     */
    protected $saveController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Model\Session
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Vault\Form
     */
    protected $vaultFormMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $platformVaultConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Vault\Validator
     */
    protected $vaultFormValidator;

     /**
      * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Command\AuthorizeCommand
      */
    protected $walletAuthorizeCommand;

     /**
      * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
      */
    protected $storeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Message\ManagerInterface
     */
    protected $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\RequestInterface
     */
    protected $requestMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam', 'getParams', 'isPost'])
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->getMock();

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactoryMock);
        $contextMock->expects($this->once())->method('getMessageManager')->willReturn($this->messageManagerMock);

        $this->formKeyValidatorMock = $this->getMockBuilder(FormKeyValidator::class)
            ->disableOriginalConstructor()->getMock();
        $this->vaultFormMock = $this->getMockBuilder(VaultForm::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformVaultConfigMock = $this->getMockBuilder(VaultConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->gatewayConfig = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->vaultFormValidator = $this->getMockBuilder(VaultFormValidator::class)
            ->disableOriginalConstructor()->getMock();
        $this->walletAuthorizeCommand = $this->getMockBuilder(WalletAuthorizeCommand::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->saveController = new Save(
            $contextMock,
            $this->formKeyValidatorMock,
            $this->customerSessionMock,
            $this->vaultFormMock,
            $this->platformVaultConfigMock,
            $this->gatewayConfig,
            $this->vaultFormValidator,
            $this->walletAuthorizeCommand,
            $this->storeManager
        );
    }

    /**
     * @param bool $isFormValid
     * @param bool $isPost
     * @param bool $isActiveVaultConfig
     * @dataProvider executeIfBadRequestParamsDataProvider
     */
    public function testExecuteIfBadRequestParams($isFormValid, $isPost, $isActiveVaultConfig)
    {
        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('vault/cards/listaction')
            ->willReturnSelf();

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn($isFormValid);

        $this->requestMock->expects($this->any())
            ->method('isPost')
            ->willReturn($isPost);

        $this->platformVaultConfigMock->expects($this->any())
            ->method('isActive')
            ->willReturn($isActiveVaultConfig);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    /**
     * @return array
     */
    public function executeIfBadRequestParamsDataProvider()
    {
        return [
            'Form is not valid' => [
                'isFormValid' => false,
                'isPost' => true,
                'isActiveVaultConfig' => true,
            ],
            'Request is not post' => [
                'isFormValid' => true,
                'isPost' => false,
                'isActiveVaultConfig' => true,
            ],
            'Vault config is not active' => [
                'isFormValid' => true,
                'isPost' => true,
                'isActiveVaultConfig' => false,
            ]
        ];
    }

    public function testExecuteIfFailToCreateProfile()
    {
        $message = 'error';
        $exception = new LocalizedException(__($message));

        $params = ['request params'];
        $customerId = 123;

        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/edit', [])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(PaymentTokenInterface::PUBLIC_HASH)
            ->willReturn(null);
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with($params, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfFailToCreateProfileWithGeneralException()
    {
        $exception = new \InvalidArgumentException('error message');

        $params = ['request params'];
        $customerId = 53445;

        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('vault/cards/listaction')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(PaymentTokenInterface::PUBLIC_HASH)
            ->willReturn(null);
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with($params, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, __('An error occurred while saving the card.'));

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfCreateProfile()
    {
        $params = [
            'form_key' => 'key',
            'key' => 'value'
        ];
        $createData = ['key' => 'value'];
        $customerId = 53445;

        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('vault/cards/listaction')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(PaymentTokenInterface::PUBLIC_HASH)
            ->willReturn(null);
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with($createData, $customerId);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The credit card is saved.'));

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfFailToUpdateProfile()
    {
        $message = 'error';
        $exception = new LocalizedException(__($message));

        $publicHash = 'hash';
        $params = [
            PaymentTokenInterface::PUBLIC_HASH => $publicHash,
            'form_key' => 'key',
            'key' => 'value',
            'key2' => 'value2'
        ];
        $updateData = ['key' => 'value', 'key2' => 'value2'];
        $customerId = 53445;

        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/edit', ['_query' => [PaymentTokenInterface::PUBLIC_HASH => $publicHash]])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(PaymentTokenInterface::PUBLIC_HASH)
            ->willReturn($publicHash);
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('updateProfile')
            ->with($publicHash, $updateData, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfFailToUpdateProfileWithGeneralException()
    {
        $exception = new \InvalidArgumentException('error message');

        $publicHash = 'hash';
        $params = [
            PaymentTokenInterface::PUBLIC_HASH => $publicHash,
            'form_key' => 'key',
            'key' => 'value'
        ];
        $updateData = ['key' => 'value'];
        $customerId = 53445;

        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('vault/cards/listaction')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(PaymentTokenInterface::PUBLIC_HASH)
            ->willReturn($publicHash);
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('updateProfile')
            ->with($publicHash, $updateData, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, __('An error occurred while saving the card.'));

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfUpdateProfile()
    {
        $publicHash = 'hash';
        $params = [
            PaymentTokenInterface::PUBLIC_HASH => $publicHash,
            'form_key' => 'key',
            'key' => 'value'
        ];
        $updateData = ['key' => 'value'];
        $customerId = 53445;

        $resultMock = $this->createResultRedirectMock();
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('vault/cards/listaction')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(PaymentTokenInterface::PUBLIC_HASH)
            ->willReturn($publicHash);
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('updateProfile')
            ->with($publicHash, $updateData, $customerId);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The credit card is saved.'));

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\Result\Redirect
     */
    private function createResultRedirectMock()
    {
        return $this->getMockBuilder(ResultRedirect::class)->disableOriginalConstructor()->getMock();
    }
}
