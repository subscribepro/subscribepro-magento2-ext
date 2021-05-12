<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Psr\Log\LoggerInterface;
use SubscribePro\Exception\HttpException;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Plugin\Vault\TokenRepository;
use Swarming\SubscribePro\Platform\Service\PaymentProfile as PaymentProfileService;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class TokenRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Vault\TokenRepository
     */
    protected $tokenRepositoryPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $paymentProfileServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->paymentProfileServiceMock = $this->getMockBuilder(PaymentProfileService::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->tokenRepositoryPlugin = new TokenRepository(
            $this->paymentProfileServiceMock,
            $this->loggerMock
        );
    }

    /**
     * @param string $methodCode
     * @param string|null $paymentProfileId
     * @param bool $result
     * @dataProvider aroundDeleteIfPaymentMethodNotSubscribeProDataProvider
     */
    public function testAroundDeleteIfPaymentMethodNotSubscribePro($methodCode, $paymentProfileId, $result)
    {
        $subjectMock = $this->createPaymentTokenRepositoryMock();
        $proceed = $this->createProceedCallback($result);

        $paymentTokenMock = $this->createPaymentTokenMock();
        $paymentTokenMock->expects($this->once())->method('getPaymentMethodCode')->willReturn($methodCode);
        $paymentTokenMock->expects($this->any())->method('getGatewayToken')->willReturn($paymentProfileId);

        $this->paymentProfileServiceMock->expects($this->never())->method('redactProfile');

        $this->assertEquals(
            $result,
            $this->tokenRepositoryPlugin->aroundDelete($subjectMock, $proceed, $paymentTokenMock)
        );
    }

    /**
     * @return array
     */
    public function aroundDeleteIfPaymentMethodNotSubscribeProDataProvider()
    {
        return [
            'Method code is not subscribe pro' => [
                'methodCode' => 'braintree',
                'paymentProfileId' => '123123',
                'result' => false
            ],
            'Without payment profile ID' => [
                'methodCode' => ConfigProvider::CODE,
                'paymentProfileId' => null,
                'result' => true
            ],
            'Without payment profile ID:result false' => [
                'methodCode' => ConfigProvider::CODE,
                'paymentProfileId' => null,
                'result' => false
            ],
        ];
    }

    /**
     * @param string|null $paymentProfileId
     * @param bool $result
     * @dataProvider aroundDeleteIfFailToRedactProfileDataProvider
     */
    public function testAroundDeleteIfFailToRedactProfile($paymentProfileId, $result)
    {
        $exception = $this->getMockBuilder(HttpException::class)->disableOriginalConstructor()->getMock();
        $subjectMock = $this->createPaymentTokenRepositoryMock();
        $proceed = $this->createProceedCallback($result);

        $paymentTokenMock = $this->createPaymentTokenMock();
        $paymentTokenMock->expects($this->once())->method('getPaymentMethodCode')->willReturn(ConfigProvider::CODE);
        $paymentTokenMock->expects($this->any())->method('getGatewayToken')->willReturn($paymentProfileId);

        $paymentProfile = $this->createPaymentProfileMock();
        $paymentProfile->expects($this->once())
            ->method('getStatus')
            ->willReturn('NOT_REDACTED_STATUS');

        $this->paymentProfileServiceMock->expects($this->once())
            ->method('loadProfile')
            ->with($paymentProfileId)
            ->willReturn($paymentProfile);

        $this->paymentProfileServiceMock->expects($this->once())
            ->method('redactProfile')
            ->with($paymentProfileId)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->assertEquals(
            $result,
            $this->tokenRepositoryPlugin->aroundDelete($subjectMock, $proceed, $paymentTokenMock)
        );
    }

    /**
     * @return array
     */
    public function aroundDeleteIfFailToRedactProfileDataProvider()
    {
        return [
            'Result true' => [
                'paymentProfileId' => '45654',
                'result' => true
            ],
            'Result false' => [
                'paymentProfileId' => '4123789',
                'result' => false
            ],
        ];
    }

    /**
     * @param string|null $paymentProfileId
     * @param bool $result
     * @dataProvider aroundDeleteIfAlreadyRedactedDataProvider
     */
    public function testAroundDeleteIfAlreadyRedacted($paymentProfileId, $result)
    {
        $subjectMock = $this->createPaymentTokenRepositoryMock();

        $paymentTokenMock = $this->createPaymentTokenMock();
        $paymentTokenMock->expects($this->once())->method('getPaymentMethodCode')->willReturn(ConfigProvider::CODE);
        $paymentTokenMock->expects($this->any())->method('getGatewayToken')->willReturn($paymentProfileId);

        $proceed = $this->createProceedCallback($result);

        $paymentProfile = $this->createPaymentProfileMock();
        $paymentProfile->expects($this->once())
            ->method('getStatus')
            ->willReturn(PaymentProfileInterface::STATUS_REDACTED);

        $this->paymentProfileServiceMock->expects($this->once())
            ->method('loadProfile')
            ->with($paymentProfileId)
            ->willReturn($paymentProfile);

        $this->paymentProfileServiceMock->expects($this->never())
            ->method('redactProfile');

        $this->assertEquals(
            $result,
            $this->tokenRepositoryPlugin->aroundDelete($subjectMock, $proceed, $paymentTokenMock)
        );
    }

    /**
     * @return array
     */
    public function aroundDeleteIfAlreadyRedactedDataProvider()
    {
        return [
            'Result true' => [
                'paymentProfileId' => '123456',
                'result' => true
            ],
            'Result false' => [
                'paymentProfileId' => '654321',
                'result' => false
            ],
        ];
    }

    public function testAroundDelete()
    {
        $result = true;
        $paymentProfileId = '111222';
        $subjectMock = $this->createPaymentTokenRepositoryMock();

        $paymentTokenMock = $this->createPaymentTokenMock();
        $paymentTokenMock->expects($this->once())->method('getPaymentMethodCode')->willReturn(ConfigProvider::CODE);
        $paymentTokenMock->expects($this->any())->method('getGatewayToken')->willReturn($paymentProfileId);

        $proceed = $this->createProceedCallback($result);

        $paymentProfile = $this->createPaymentProfileMock();
        $paymentProfile->expects($this->once())
            ->method('getStatus')
            ->willReturn('NOT_REDACTED_STATUS');

        $this->paymentProfileServiceMock->expects($this->once())
            ->method('loadProfile')
            ->with($paymentProfileId)
            ->willReturn($paymentProfile);

        $this->paymentProfileServiceMock->expects($this->once())
            ->method('redactProfile')
            ->with($paymentProfileId);

        $this->assertEquals(
            $result,
            $this->tokenRepositoryPlugin->aroundDelete($subjectMock, $proceed, $paymentTokenMock)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    private function createPaymentProfileMock()
    {
        return $this->getMockBuilder(PaymentProfileInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function createPaymentTokenMock()
    {
        return $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private function createPaymentTokenRepositoryMock()
    {
        return $this->getMockBuilder(PaymentTokenRepositoryInterface::class)->getMock();
    }

    /**
     * @param bool $result
     * @return callable
     */
    private function createProceedCallback($result)
    {
        return function ($paymentToken) use ($result) {
            return $result;
        };
    }
}
