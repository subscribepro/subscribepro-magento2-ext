<?php

namespace Swarming\SubscribePro\Test\Unit\Ui\ConfigProvider;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;
use Swarming\SubscribePro\Ui\ConfigProvider\Checkout;

class CheckoutTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Ui\ConfigProvider\Checkout
     */
    protected $uiCheckoutConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\ConfigProvider
     */
    protected $gatewayConfigProviderMock;

    protected function setUp(): void
    {
        $this->gatewayConfigProviderMock = $this->getMockBuilder(GatewayConfigProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->uiCheckoutConfig = new Checkout($this->gatewayConfigProviderMock);
    }

    public function testGetConfig()
    {
        $gatewayConfig = ['gateway', 'config'];
        $expectedConfig = [
            'payment' => [
                GatewayConfigProvider::CODE => $gatewayConfig
            ]
        ];

        $this->gatewayConfigProviderMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($gatewayConfig);

        $this->assertEquals($expectedConfig, $this->uiCheckoutConfig->getConfig());
    }
}
