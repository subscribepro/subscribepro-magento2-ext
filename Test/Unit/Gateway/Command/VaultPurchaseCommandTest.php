<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Command\VaultPurchaseCommand;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultPurchaseCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\VaultPurchaseCommand
     */
    protected $vaultPurchaseCommand;

    protected function setUp()
    {
        $this->initProperties();
        $this->vaultPurchaseCommand = new VaultPurchaseCommand(
            $this->requestBuilderMock,
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
        $exception = new \Exception('Payment profile is not passed');
        
        $this->processTransactionFail($requestData, $exception);
        $this->vaultPurchaseCommand->execute($this->commandSubject);
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
        
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('purchaseByProfile')
            ->with($profileId, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->vaultPurchaseCommand->execute($this->commandSubject);
    }
}
