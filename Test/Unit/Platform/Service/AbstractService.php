<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use Swarming\SubscribePro\Platform\Platform;

class AbstractService extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Platform
     */
    protected $platformMock;

    protected $name = 'test_name';

    protected function initService($service, $websiteId = null)
    {
        $sdkMock = $this->getMockBuilder(\SubscribePro\Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sdkMock->expects($this->once())
            ->method('getService')
            ->with($this->name)
            ->willReturn($service);

        $this->platformMock->expects($this->once())
            ->method('getSdk')
            ->with($websiteId)
            ->willReturn($sdkMock);
    }

    protected function createPlatformMock()
    {
        return $this->getMockBuilder(Platform::class)->disableOriginalConstructor()->getMock();
    }
}
