<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Config;

use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Observer\Config\CustomerConfigSaveAfter;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Config\Model\Config\Factory as ConfigFactory;

class CustomerConfigSaveAfterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Config\CustomerConfigSaveAfter
     */
    protected $customerConfigSaveAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Config\Model\Config\Factory
     */
    protected $configFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->configFactoryMock = $this->getMockBuilder(ConfigFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->customerConfigSaveAfter = new CustomerConfigSaveAfter(
            $this->scopeConfigMock,
            $this->configFactoryMock,
            $this->loggerMock
        );
    }

    public function testExecuteIfCustomerAccountShareGlobal()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE, ScopeInterface::SCOPE_STORE)
            ->willReturn(Share::SHARE_GLOBAL);
        
        $this->configFactoryMock->expects($this->never())->method('create');

        $this->customerConfigSaveAfter->execute($this->createObserverMock());
    }

    public function testExecuteIfFailToSaveConfig()
    {
        $exception = new \Exception('error');

        $configData = [
            'section' => 'swarming_subscribepro',
            'website' => null,
            'store' => null,
            'groups' => [
                'platform' => [
                    'fields' => [
                        'client_id' => ['inherit' => true],
                        'client_secret' => ['inherit' => true]
                    ]
                ]
            ],
        ];

        $configModelMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configModelMock->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE, ScopeInterface::SCOPE_STORE)
            ->willReturn(Share::SHARE_WEBSITE);

        $this->configFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $configData])
            ->willReturn($configModelMock);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->customerConfigSaveAfter->execute($this->createObserverMock());
    }

    public function testExecute()
    {
        $configData = [
            'section' => 'swarming_subscribepro',
            'website' => null,
            'store' => null,
            'groups' => [
                'platform' => [
                    'fields' => [
                        'client_id' => ['inherit' => true],
                        'client_secret' => ['inherit' => true]
                    ]
                ]
            ],
        ];

        $configModel = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())->method('save');

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE, ScopeInterface::SCOPE_STORE)
            ->willReturn(Share::SHARE_WEBSITE);

        $this->configFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $configData])
            ->willReturn($configModel);

        $this->customerConfigSaveAfter->execute($this->createObserverMock());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }
}
