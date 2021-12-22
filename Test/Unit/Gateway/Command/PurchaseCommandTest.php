<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Command\PurchaseCommand;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class PurchaseCommandTest extends AbstractProfileCreatorCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\PurchaseCommand
     */
    protected $purchaseCommand;

    protected function setUp(): void
    {
        $this->initProperties();
        $this->purchaseCommand = new PurchaseCommand(
            $this->requestBuilderMock,
            $this->platformMock,
            $this->storeManagerMock,
            $this->subjectReaderMock,
            $this->handlerMock,
            $this->validatorMock,
            $this->platformPaymentProfileServiceMock,
            $this->platformTransactionServiceMock,
            $this->loggerMock,
            $this->platformCustomerManagerMock
        );
    }

    /**
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessage Transaction has been declined. Please try again later.
     * @dataProvider executeIfFailToProcessTransactionDataProvider
     * @param array $requestData
     */
    public function testExecuteIfFailToProcessTransaction(array $requestData)
    {
        $exception = new \InvalidArgumentException('Payment token is not passed');
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->processTransactionFail($requestData, $exception);
        $this->purchaseCommand->execute($this->commandSubject);
    }

    /**
     * @return array
     */
    public function executeIfFailToProcessTransactionDataProvider()
    {
        return [
            'Payment method token is not set' => [
                'requestData' => []
            ],
            'Payment method token is empty' => [
                'requestData' => [PaymentDataBuilder::PAYMENT_METHOD_TOKEN => '']
            ],
        ];
    }

    /**
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessage Transaction has been declined. Please try again later.
     */
    public function testFailToExecuteIfIsActiveCodeWithoutPaymentProfile()
    {
        $exception = new LocalizedException(__('Cannot create payment profile.'));
        $requestData = [
            VaultConfigProvider::IS_ACTIVE_CODE => true,
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => 'token'
        ];
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->processTransactionFail($requestData, $exception);
        $this->purchaseCommand->execute($this->commandSubject);
    }

    public function testExecuteIfIsActiveCode()
    {
        $requestData = [
            VaultConfigProvider::IS_ACTIVE_CODE => true,
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => 'token',
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => 123
        ];
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $transactionMock = $this->createTransactionMock();
        $profileId = 123;
        $authorizeData = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId
        ];
        $profileMock = $this->createPaymentProfile($requestData);
        $profileMock->expects($this->once())->method('getId')->willReturn($profileId);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('purchaseByProfile')
            ->with($authorizeData, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->purchaseCommand->execute($this->commandSubject);
    }

    public function testExecuteIfNotIsActiveCode()
    {
        $token = 'token';
        $requestData = [
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => $token,
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => 123
        ];
        $transactionMock = $this->createTransactionMock();
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('purchaseByToken')
            ->with($token, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->purchaseCommand->execute($this->commandSubject);
    }
}
