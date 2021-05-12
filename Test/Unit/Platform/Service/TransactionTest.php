<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\Transaction\TransactionInterface;
use SubscribePro\Service\Transaction\TransactionService;
use Swarming\SubscribePro\Api\Data\AddressInterface;
use Swarming\SubscribePro\Platform\Service\Transaction;

class TransactionTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Transaction
     */
    protected $transactionService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Transaction\TransactionService
     */
    protected $transactionPlatformService;

    protected function setUp(): void
    {
        $this->platformMock = $this->createPlatformMock();
        $this->transactionPlatformService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionService = new Transaction($this->platformMock, $this->name);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createTransactionDataProvider
     */
    public function testCreateTransaction($websiteId, $expectedWebsiteId)
    {
        $transactionMock = $this->createTransactionMock();

        $this->initService($this->transactionPlatformService, $expectedWebsiteId);
        $this->transactionPlatformService->expects($this->once())
            ->method('createTransaction')
            ->with(['transaction data'])
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->createTransaction(['transaction data'], $websiteId)
        );
    }

    /**
     * @return array
     */
    public function createTransactionDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
            ],
            'Without website Id' => [
                'websiteId' => null,
                'expectedWebsiteId' => null,
            ]
        ];
    }

    public function testLoadTransaction()
    {
        $transactionId = 111;
        $websiteId = 12;
        $transactionMock = $this->createTransactionMock();
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('loadTransaction')
            ->with($transactionId)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->loadTransaction($transactionId, $websiteId)
        );
    }

    public function testVerifyProfile()
    {
        $websiteId = 12;
        $paymentProfileId = 313;
        $transactionMock = $this->createTransactionMock();
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('verifyProfile')
            ->with($paymentProfileId, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->verifyProfile($paymentProfileId, $transactionMock, $websiteId)
        );
    }

    public function testAuthorizeByProfile()
    {
        $websiteId = 12;
        $paymentProfileId = 313;
        $transactionMock = $this->createTransactionMock();
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('authorizeByProfile')
            ->with($paymentProfileId, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->authorizeByProfile($paymentProfileId, $transactionMock, $websiteId)
        );
    }

    public function testPurchaseByProfile()
    {
        $websiteId = 12;
        $paymentProfileId = 313;
        $transactionMock = $this->createTransactionMock();
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('purchaseByProfile')
            ->with($paymentProfileId, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->purchaseByProfile($paymentProfileId, $transactionMock, $websiteId)
        );
    }

    /**
     * @param int $websiteId
     * @param string $token
     * @param \PHPUnit_Framework_MockObject_MockObject $transactionMock
     * @param \PHPUnit_Framework_MockObject_MockObject $platformAddressMock
     * @dataProvider authorizeByTokenDataProvider
     */
    public function testAuthorizeByToken($websiteId, $token, $transactionMock, $platformAddressMock)
    {
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('authorizeByToken')
            ->with($token, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->authorizeByToken($token, $transactionMock, $platformAddressMock, $websiteId)
        );
    }

    /**
     * @return array
     */
    public function authorizeByTokenDataProvider()
    {
        return [
            'Without address' => [
                'websiteId' => 122,
                'token' => 'token',
                'transactionMock' => $this->createTransactionMock(),
                'addressMock' => null,
            ],
            'With address' => [
                'websiteId' => 122,
                'token' => 'token',
                'transactionMock' => $this->createTransactionMock(),
                'addressMock' => $this->createPlatformAddressMock()
            ]
        ];
    }

    /**
     * @param int $websiteId
     * @param string $token
     * @param \PHPUnit_Framework_MockObject_MockObject $transactionMock
     * @param \PHPUnit_Framework_MockObject_MockObject $platformAddressMock
     * @dataProvider purchaseByTokenDataProvider
     */
    public function testPurchaseByToken($websiteId, $token, $transactionMock, $platformAddressMock)
    {
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('purchaseByToken')
            ->with($token, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->purchaseByToken($token, $transactionMock, $platformAddressMock, $websiteId)
        );
    }

    /**
     * @return array
     */
    public function purchaseByTokenDataProvider()
    {
        return [
            'Without address' => [
                'websiteId' => 122,
                'token' => 'token',
                'transactionMock' => $this->createTransactionMock(),
                'addressMock' => null,
            ],
            'With address' => [
                'websiteId' => 122,
                'token' => 'token',
                'transactionMock' => $this->createTransactionMock(),
                'addressMock' => $this->createPlatformAddressMock()
            ]
        ];
    }

    /**
     * @param int $websiteId
     * @param int $transactionId
     * @param \PHPUnit_Framework_MockObject_MockObject $transactionMock
     * @dataProvider captureDataProvider
     */
    public function testCapture($websiteId, $transactionId, $transactionMock)
    {
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('capture')
            ->with($transactionId, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->capture($transactionId, $transactionMock, $websiteId)
        );
    }

    /**
     * @return array
     */
    public function captureDataProvider()
    {
        return [
            'Without address' => [
                'websiteId' => 333,
                'transactionId' => 'token_1',
                'transactionMock' => null,
            ],
            'With address' => [
                'websiteId' => 444,
                'transactionId' => 'token_2',
                'transactionMock' => $this->createTransactionMock(),
            ]
        ];
    }

    /**
     * @param int $websiteId
     * @param int $transactionId
     * @param \PHPUnit_Framework_MockObject_MockObject $transactionMock
     * @dataProvider creditDataProvider
     */
    public function testCredit($websiteId, $transactionId, $transactionMock)
    {
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('credit')
            ->with($transactionId, $transactionMock)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->credit($transactionId, $transactionMock, $websiteId)
        );
    }

    /**
     * @return array
     */
    public function creditDataProvider()
    {
        return [
            'Without address' => [
                'websiteId' => 333,
                'transactionId' => 'token_1',
                'transactionMock' => null,
            ],
            'With address' => [
                'websiteId' => 444,
                'transactionId' => 'token_2',
                'transactionMock' => $this->createTransactionMock(),
            ]
        ];
    }

    public function testVoid()
    {
        $transactionId = 111;
        $websiteId = 12;
        $transactionMock = $this->createTransactionMock();
        $this->initService($this->transactionPlatformService, $websiteId);

        $this->transactionPlatformService->expects($this->once())
            ->method('void')
            ->with($transactionId)
            ->willReturn($transactionMock);

        $this->assertSame(
            $transactionMock,
            $this->transactionService->void($transactionId, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Transaction\TransactionInterface
     */
    private function createTransactionMock()
    {
        return $this->getMockBuilder(TransactionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\AddressInterface
     */
    private function createPlatformAddressMock()
    {
        return $this->getMockBuilder(AddressInterface::class)->getMock();
    }
}
