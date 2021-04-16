<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\Vault;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;

class VaultTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactoryMock;

    protected function setUp()
    {
        $this->gatewayConfigMock = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)->getMock();

        $this->vaultHelper = new Vault(
            $this->gatewayConfigMock,
            $this->encryptorMock,
            $this->dateTimeFactoryMock
        );
    }

    /**
     * @param int $profileId
     * @param string $type
     * @param string $gatewayToken
     * @param string $paymentMethodCode
     * @param int $customerId
     * @param string $cardType
     * @param string $ccType
     * @param int $lastDigits
     * @param string $year
     * @param string $month
     * @param string $expirationDate
     * @param string $formattedExpirationDate
     * @param array $tokenDetails
     * @param string $hashKey
     * @dataProvider initDataProvider
     */
    public function testInitVault(
        $profileId,
        $type,
        $gatewayToken,
        $paymentMethodCode,
        $customerId,
        $cardType,
        $ccType,
        $paymentToken,
        $lastDigits,
        $year,
        $month,
        $expirationDate,
        $formattedExpirationDate,
        $tokenDetails,
        $hashKey,
        $enctyptedHash
    ) {
        $profileMock = $this->createPaymentProfileMock();
        $profileMock->expects($this->once())->method('getId')->willReturn($profileId);
        $profileMock->expects($this->once())->method('getMagentoCustomerId')->willReturn($customerId);
        $profileMock->expects($this->once())->method('getCreditcardType')->willReturn($cardType);
        $profileMock->expects($this->once())->method('getCreditcardLastDigits')->willReturn($lastDigits);
        $profileMock->expects($this->any())->method('getCreditcardMonth')->willReturn($month);
        $profileMock->expects($this->any())->method('getCreditcardYear')->willReturn($year);
        $profileMock->expects($this->once())->method('getPaymentToken')->willReturn($paymentToken);

        $tokenMock = $this->createPaymentTokenMock();
        $tokenMock->expects($this->once())->method('setPaymentMethodCode')->with(ConfigProvider::CODE);
        $tokenMock->expects($this->once())->method('setGatewayToken')->with($profileId);
        $tokenMock->expects($this->once())->method('setIsActive')->with(true);
        $tokenMock->expects($this->once())->method('setIsVisible')->with(true);
        $tokenMock->expects($this->once())->method('setCustomerId')->with($customerId);
        $tokenMock->expects($this->once())->method('setTokenDetails')->with($tokenDetails);
        $tokenMock->expects($this->once())->method('setExpiresAt')->with($formattedExpirationDate);
        $tokenMock->expects($this->once())->method('setPublicHash')->with($enctyptedHash);
        $tokenMock->expects($this->once())->method('getGatewayToken')->willReturn($gatewayToken);
        $tokenMock->expects($this->any())->method('getCustomerId')->willReturn($customerId);
        $tokenMock->expects($this->any())->method('getPaymentMethodCode')->willReturn($paymentMethodCode);
        $tokenMock->expects($this->any())->method('getType')->willReturn($type);
        $tokenMock->expects($this->any())->method('getTokenDetails')->willReturn($tokenDetails);

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('add')->with(new \DateInterval('P1M'));
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d 00:00:00')
            ->willReturn($formattedExpirationDate);

        $this->gatewayConfigMock->expects($this->once())
            ->method('getMappedCcType')
            ->with($cardType)
            ->willReturn($ccType);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with($expirationDate, new \DateTimeZone('UTC'))
            ->willReturn($dateTimeMock);

        $this->encryptorMock->expects($this->once())
            ->method('getHash')
            ->with($hashKey)
            ->willReturn($enctyptedHash);

        $this->assertSame($tokenMock, $this->vaultHelper->initVault($tokenMock, $profileMock));
    }

    /**
     * @return array
     */
    public function initDataProvider()
    {
        return [
            'No customer ID: hash without customer ID' => [
                'profileId' => 123123,
                'type' => 'token_type',
                'gatewayToken' => 'gateway_token',
                'paymentMethodCode' => 'braintree',
                'customerId' => null,
                'cardType' => 'visa',
                'ccType' => 'cc_type',
                'paymentToken' => 'abc123xyz',
                'lastDigits' => 55412,
                'year' => '2020',
                'month' => '12',
                'expirationDate' => '2020-12-01 00:00:00',
                'formattedExpirationDate' => '2021-01-01 00:00:00',
                'tokenDetails' => json_encode([
                    'type' => 'cc_type',
                    'maskedCC' => 55412,
                    'expirationDate' => '12/2020',
                    'paymentToken' => 'abc123xyz',
                ]),
                'hashKey' => 'gateway_tokenbraintreetoken_type' . json_encode([
                    'type' => 'cc_type',
                    'maskedCC' => 55412,
                    'expirationDate' => '12/2020',
                    'paymentToken' => 'abc123xyz',
                ]),
                'enctyptedHash' => 'encr_hash'
            ],
            'With customer ID: hash includes customer ID' => [
                'profileId' => 899779,
                'type' => 'type_credit',
                'gatewayToken' => 'gateway',
                'paymentMethodCode' => 'some_method',
                'customerId' => 5551,
                'cardType' => 'mastercard',
                'ccType' => 'master_type',
                'paymentToken' => 'abc123xyz',
                'lastDigits' => 4444,
                'year' => '2025',
                'month' => '04',
                'expirationDate' => '2025-04-01 00:00:00',
                'formattedExpirationDate' => '2025-05-01 00:00:00',
                'tokenDetails' => json_encode([
                    'type' => 'master_type',
                    'maskedCC' => 4444,
                    'expirationDate' => '04/2025',
                    'paymentToken' => 'abc123xyz',
                ]),
                'hashKey' => '5551some_methodtype_credit' . json_encode([
                    'type' => 'master_type',
                    'maskedCC' => 4444,
                    'expirationDate' => '04/2025',
                    'paymentToken' => 'abc123xyz',
                ]),
                'enctyptedHash' => 'protected_hash'
            ]
        ];
    }

    public function testUpdateVaultIfNoTokenDetails()
    {
        $year = '2019';
        $month = '11';
        $updatedTokenDetails = ['expirationDate' => "{$month}/{$year}"];
        $expirationDate = '2025-05-01 00:00:00';

        $profileMock = $this->createPaymentProfileMock();
        $profileMock->expects($this->any())->method('getCreditcardMonth')->willReturn($month);
        $profileMock->expects($this->any())->method('getCreditcardYear')->willReturn($year);

        $tokenMock = $this->createPaymentTokenMock();
        $tokenMock->expects($this->once())->method('getTokenDetails')->willReturn(null);
        $tokenMock->expects($this->once())->method('setTokenDetails')->with(json_encode($updatedTokenDetails));
        $tokenMock->expects($this->once())->method('setExpiresAt')->with($expirationDate);

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('add')->with(new \DateInterval('P1M'));
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d 00:00:00')
            ->willReturn($expirationDate);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with($year . '-' . $month . '-01 00:00:00', new \DateTimeZone('UTC'))
            ->willReturn($dateTimeMock);

        $this->assertSame($tokenMock, $this->vaultHelper->updateVault($tokenMock, $profileMock));
    }

    public function testUpdateVault()
    {
        $year = '2019';
        $month = '11';
        $tokenDetails = [
            'type' => 'master_type',
            'maskedCC' => 4444,
            'expirationDate' => '04/2025',
            'paymentToken' => 'abc123xyz',
        ];
        $updatedTokenDetails = [
            'type' => 'master_type',
            'maskedCC' => 4444,
            'expirationDate' => "{$month}/{$year}",
            'paymentToken' => 'abc123xyz',
        ];
        $expirationDate = '2025-05-01 00:00:00';

        $profileMock = $this->createPaymentProfileMock();
        $profileMock->expects($this->any())->method('getCreditcardMonth')->willReturn($month);
        $profileMock->expects($this->any())->method('getCreditcardYear')->willReturn($year);

        $tokenMock = $this->createPaymentTokenMock();
        $tokenMock->expects($this->once())->method('getTokenDetails')->willReturn(json_encode($tokenDetails));
        $tokenMock->expects($this->once())->method('setTokenDetails')->with(json_encode($updatedTokenDetails));
        $tokenMock->expects($this->once())->method('setExpiresAt')->with($expirationDate);

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('add')->with(new \DateInterval('P1M'));
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d 00:00:00')
            ->willReturn($expirationDate);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with($year . '-' . $month . '-01 00:00:00', new \DateTimeZone('UTC'))
            ->willReturn($dateTimeMock);

        $this->assertSame($tokenMock, $this->vaultHelper->updateVault($tokenMock, $profileMock));
    }

    public function testGetTokenDetails()
    {
        $year = '2019';
        $month = '11';
        $cardType = 'card_type';
        $ccType = 'cc_type';
        $lastDigits = 1111;
        $paymentToken = 'abc123xyz';
        $tokenDetails = [
            'type' => $ccType,
            'maskedCC' => $lastDigits,
            'expirationDate' => "{$month}/{$year}",
            'paymentToken' => $paymentToken,
        ];

        $this->gatewayConfigMock->expects($this->once())
            ->method('getMappedCcType')
            ->with($cardType)
            ->willReturn($ccType);

        $this->assertEquals(
            json_encode($tokenDetails),
            $this->vaultHelper->getTokenDetails($cardType, $lastDigits, $month, $year, $paymentToken)
        );
    }

    public function testGetExpirationDate()
    {
        $year = '2019';
        $month = '11';
        $expirationDate = '2025-05-01 00:00:00';

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())->method('add')->with(new \DateInterval('P1M'));
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d 00:00:00')
            ->willReturn($expirationDate);

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with($year . '-' . $month . '-01 00:00:00', new \DateTimeZone('UTC'))
            ->willReturn($dateTimeMock);

        $this->assertEquals($expirationDate, $this->vaultHelper->getExpirationDate($year, $month));
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
     * @return \PHPUnit_Framework_MockObject_MockObject|\DateTime
     */
    private function createDateTimeMock()
    {
        return $this->getMockBuilder(\DateTime::class)->getMock();
    }
}
