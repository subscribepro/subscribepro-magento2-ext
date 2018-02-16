<?php

namespace Swarming\SubscribePro\Test\Unit\Ui\ComponentProvider;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Ui\ComponentProvider\VaultToken;

class VaultTokenTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Ui\ComponentProvider\VaultToken
     */
    protected $uiVaultToken;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory
     */
    protected $componentFactoryMock;

    protected function setUp()
    {
        $this->componentFactoryMock = $this->getMockBuilder(TokenUiComponentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->uiVaultToken = new VaultToken($this->componentFactoryMock);
    }

    /**
     * @param string|null $tokenDetails
     * @param string $publicHash
     * @param array $componentConfig
     * @dataProvider getComponentForTokenDataProvider
     */
    public function testGetComponentForToken($tokenDetails, $publicHash, $componentConfig)
    {
        $componentMock = $this->createComponentMock();

        $paymentTokenMock = $this->createPaymentTokenMock();
        $paymentTokenMock->expects($this->once())->method('getTokenDetails')->willReturn($tokenDetails);
        $paymentTokenMock->expects($this->once())->method('getPublicHash')->willReturn($publicHash);

        $this->componentFactoryMock->expects($this->once())
            ->method('create')
            ->with($componentConfig)
            ->willReturn($componentMock);

        $this->assertSame($componentMock, $this->uiVaultToken->getComponentForToken($paymentTokenMock));
    }

    /**
     * @return array
     */
    public function getComponentForTokenDataProvider()
    {
        return [
            'Without token details' => [
                'tokenDetails' => null,
                'publicHash' => 'hash',
                'componentConfig' => [
                    'config' => [
                        'code' => ConfigProvider::VAULT_CODE,
                        TokenUiComponentProviderInterface::COMPONENT_DETAILS => [],
                        TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => 'hash'
                    ],
                    'name' => 'Swarming_SubscribePro/js/view/payment/method-renderer/vault'
                ]
            ],
            'With token details' => [
                'tokenDetails' => json_encode(['token', 'details']),
                'publicHash' => 'public__hash',
                'componentConfig' => [
                    'config' => [
                        'code' => ConfigProvider::VAULT_CODE,
                        TokenUiComponentProviderInterface::COMPONENT_DETAILS => ['token', 'details'],
                        TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => 'public__hash'
                    ],
                    'name' => 'Swarming_SubscribePro/js/view/payment/method-renderer/vault'
                ]
            ]
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
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Model\Ui\TokenUiComponentInterface
     */
    private function createComponentMock()
    {
        return $this->getMockBuilder(TokenUiComponentInterface::class)->getMock();
    }
}
