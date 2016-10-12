<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Adminhtml\Config;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use SubscribePro\Sdk;
use Swarming\SubscribePro\Platform\SdkFactory;
use SubscribePro\Service\Webhook\WebhookService;
use Swarming\SubscribePro\Controller\Adminhtml\Config\TestConnection;
use Swarming\SubscribePro\Model\Config\Platform as PlatformConfig;
use Magento\Framework\Controller\Result\Json as ResultJson;

class TestConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Adminhtml\Config\TestConnection
     */
    protected $testConnectionController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\Platform
     */
    protected $platformConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\SdkFactory
     */
    protected $sdkFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\RequestInterface
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactoryMock;

    protected function setUp()
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactoryMock);

        $this->platformConfigMock = $this->getMockBuilder(PlatformConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->sdkFactoryMock = $this->getMockBuilder(SdkFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->testConnectionController = new TestConnection(
            $contextMock,
            $this->platformConfigMock,
            $this->sdkFactoryMock
        );
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $baseUrl
     * @param string $website
     * @param array $response
     * @dataProvider executeIfNotValidClientParamsDataProvider
     */
    public function testExecuteIfNotValidClientParams($clientId, $clientSecret, $baseUrl, $website, $response)
    {
        $resultMock = $this->createResultMock();
        $resultMock->expects($this->once())->method('setData')->with($response);

        $this->requestMock->expects($this->at(0))->method('getParam')->with('base_url')->willReturn($baseUrl);
        $this->requestMock->expects($this->at(1))->method('getParam')->with('client_id')->willReturn($clientId);
        $this->requestMock->expects($this->at(2))->method('getParam')->with('client_secret')->willReturn($clientSecret);
        $this->requestMock->expects($this->at(3))->method('getParam')->with('website')->willReturn($website);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->sdkFactoryMock->expects($this->never())->method('create');

        $this->assertSame($resultMock, $this->testConnectionController->execute());
    }

    /**
     * @return array
     */
    public function executeIfNotValidClientParamsDataProvider()
    {
        return [
            'No client ID:no client secret' => [
                'clientId' => null,
                'clientSecret' => null,
                'baseUrl' => 'baseUrl',
                'website' => 'website_code',
                'response' => [
                    'status' => 'fail',
                    'message' => __('Invalid values.')
                ],
            ],
            'No client ID:with client secret' => [
                'clientId' => null,
                'clientSecret' => 'dddf213213',
                'baseUrl' => 'base/url',
                'website' => 'code',
                'response' => [
                    'status' => 'fail',
                    'message' => __('Invalid values.')
                ],
            ],
            'With client ID:no client secret' => [
                'clientId' => '44fdsdf654',
                'clientSecret' => null,
                'baseUrl' => 'url',
                'website' => 'code_code',
                'response' => [
                    'status' => 'fail',
                    'message' => __('Invalid values.')
                ],
            ],
        ];
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $baseUrl
     * @param string $website
     * @param string $sdkConfig
     * @param bool $ping
     * @param array $response
     * @dataProvider executeIfNotEncryptedClientSecretDataProvider
     */
    public function testExecuteIfNotEncryptedClientSecret(
        $clientId,
        $clientSecret,
        $baseUrl,
        $website,
        $sdkConfig,
        $ping,
        $response
    ) {
        $webhookServiceMock = $this->createWebhookServiceMock();
        $webhookServiceMock->expects($this->once())->method('ping')->willReturn($ping);

        $sdkMock = $this->createSdkMock();
        $sdkMock->expects($this->once())->method('getWebhookService')->willReturn($webhookServiceMock);

        $resultMock = $this->createResultMock();
        $resultMock->expects($this->once())->method('setData')->with($response);

        $this->requestMock->expects($this->at(0))->method('getParam')->with('base_url')->willReturn($baseUrl);
        $this->requestMock->expects($this->at(1))->method('getParam')->with('client_id')->willReturn($clientId);
        $this->requestMock->expects($this->at(2))->method('getParam')->with('client_secret')->willReturn($clientSecret);
        $this->requestMock->expects($this->at(3))->method('getParam')->with('website')->willReturn($website);

        $this->platformConfigMock->expects($this->never())->method('getClientSecret');

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->sdkFactoryMock->expects($this->once())->method('create')->with($sdkConfig)->willReturn($sdkMock);

        $this->assertSame($resultMock, $this->testConnectionController->execute());
    }

    /**
     * @return array
     */
    public function executeIfNotEncryptedClientSecretDataProvider()
    {
        return [
            'Ping failed' => [
                'clientId' => '123123123',
                'clientSecret' => 'sdfdgw123123',
                'baseUrl' => 'url/base',
                'website' => 'web_site',
                'sdkConfig' => [
                    'config' => [
                        'base_url' => 'url/base',
                        'client_id' => '123123123',
                        'client_secret' => 'sdfdgw123123'
                    ]
                ],
                'ping' => false,
                'response' => [
                    'status' => 'fail',
                    'message' => __('Failed to connect to platform!')
                ],
            ],
            'Ping successful' => [
                'clientId' => '21312453',
                'clientSecret' => 'dfgdgf23234',
                'baseUrl' => 'url/base/url',
                'website' => 'web_site_code',
                'sdkConfig' => [
                    'config' => [
                        'base_url' => 'url/base/url',
                        'client_id' => '21312453',
                        'client_secret' => 'dfgdgf23234'
                    ]
                ],
                'ping' => true,
                'response' => [
                    'status' => 'success',
                    'message' => __('Connected Successfully.')
                ],
            ],
        ];
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $baseUrl
     * @param string $website
     * @param string $websiteForClientSecret
     * @param string $sdkConfig
     * @param bool $ping
     * @param array $response
     * @dataProvider executeIfEncryptedClientSecretDataProvider
     */
    public function testExecuteIfEncryptedClientSecret(
        $clientId,
        $clientSecret,
        $baseUrl,
        $website,
        $websiteForClientSecret,
        $sdkConfig,
        $ping,
        $response
    ) {
        $webhookServiceMock = $this->createWebhookServiceMock();
        $webhookServiceMock->expects($this->once())->method('ping')->willReturn($ping);

        $sdkMock = $this->createSdkMock();
        $sdkMock->expects($this->once())->method('getWebhookService')->willReturn($webhookServiceMock);

        $resultMock = $this->createResultMock();
        $resultMock->expects($this->once())->method('setData')->with($response);

        $this->requestMock->expects($this->at(0))->method('getParam')->with('base_url')->willReturn($baseUrl);
        $this->requestMock->expects($this->at(1))->method('getParam')->with('client_id')->willReturn($clientId);
        $this->requestMock->expects($this->at(2))->method('getParam')->with('client_secret')->willReturn('******');
        $this->requestMock->expects($this->at(3))->method('getParam')->with('website')->willReturn($website);

        $this->platformConfigMock->expects($this->once())
            ->method('getClientSecret')
            ->with($websiteForClientSecret)
            ->willReturn($clientSecret);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->sdkFactoryMock->expects($this->once())->method('create')->with($sdkConfig)->willReturn($sdkMock);

        $this->assertSame($resultMock, $this->testConnectionController->execute());
    }

    /**
     * @return array
     */
    public function executeIfEncryptedClientSecretDataProvider()
    {
        return [
            'Empty website code:ping successful' => [
                'clientId' => '2312grfdfgdfg',
                'clientSecret' => 'asd34234',
                'baseUrl' => 'url/base',
                'website' => '',
                'websiteForClientSecret' => false,
                'sdkConfig' => [
                    'config' => [
                        'base_url' => 'url/base',
                        'client_id' => '2312grfdfgdfg',
                        'client_secret' => 'asd34234'
                    ]
                ],
                'ping' => true,
                'response' => [
                    'status' => 'success',
                    'message' => __('Connected Successfully.')
                ],
            ],
            'With website ID: ping successful' => [
                'clientId' => '213fdsdf',
                'clientSecret' => 'dfgdfgfdg',
                'baseUrl' => 'url',
                'website' => 'website_code',
                'websiteForClientSecret' => 'website_code',
                'sdkConfig' => [
                    'config' => [
                        'base_url' => 'url',
                        'client_id' => '213fdsdf',
                        'client_secret' => 'dfgdfgfdg'
                    ]
                ],
                'ping' => true,
                'response' => [
                    'status' => 'success',
                    'message' => __('Connected Successfully.')
                ],
            ],
            'With website ID: ping failed' => [
                'clientId' => '6+654648',
                'clientSecret' => 'dfogwpeori',
                'baseUrl' => 'url/to/website',
                'website' => 'main_website',
                'websiteForClientSecret' => 'main_website',
                'sdkConfig' => [
                    'config' => [
                        'base_url' => 'url/to/website',
                        'client_id' => '6+654648',
                        'client_secret' => 'dfogwpeori'
                    ]
                ],
                'ping' => false,
                'response' => [
                    'status' => 'fail',
                    'message' => __('Failed to connect to platform!')
                ],
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\Result\Json
     */
    private function createResultMock()
    {
        return $this->getMockBuilder(ResultJson::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Sdk
     */
    private function createSdkMock()
    {
        return $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebhookService'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\WebhookService
     */
    private function createWebhookServiceMock()
    {
        return $this->getMockBuilder(WebhookService::class)->disableOriginalConstructor()->getMock();
    }
}
