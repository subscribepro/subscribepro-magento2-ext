<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Command\VaultAuthorizeCommand;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultAuthorizeCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\VaultAuthorizeCommand
     */
    protected $vaultAuthorizeCommand;

    protected function setUp(): void
    {
        $this->initProperties();
        $this->vaultAuthorizeCommand = new VaultAuthorizeCommand(
            $this->requestBuilderMock,
            $this->platformMock,
            $this->storeManagerMock,
            $this->subjectReaderMock,
            $this->handlerMock,
            $this->validatorMock,
            $this->platformPaymentProfileServiceMock,
            $this->platformTransactionServiceMock,
            $this->loggerMock
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
        $exception = new \InvalidArgumentException('Payment profile was not passed');
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->processTransactionFail($requestData, $exception);
        $this->vaultAuthorizeCommand->execute($this->commandSubject);
    }

    /**
     * @return array
     */
    public function executeIfFailToProcessTransactionDataProvider()
    {
        return [
            'Payment profile id is not set' => [
                'requestData' => []
            ],
            'Payment profile id is empty' => [
                'requestData' => [VaultDataBuilder::PAYMENT_PROFILE_ID => '']
            ],
        ];
    }

    public function testExecute()
    {
        $profileId = 123;
        $requestData = [VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId];
        $transactionMock = $this->createTransactionMock();
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('authorizeByProfile')
            ->with($requestData, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->vaultAuthorizeCommand->execute($this->commandSubject);
    }
}
