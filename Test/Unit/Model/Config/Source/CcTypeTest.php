<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config\Source;

use Magento\Payment\Model\Config as PaymentConfig;
use Swarming\SubscribePro\Model\Config\Source\CcType;

class CcTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\CcType
     */
    protected $ccTypeSource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\Config
     */
    protected $paymentConfigMock;

    protected function setUp(): void
    {
        $this->paymentConfigMock = $this->getMockBuilder(PaymentConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->ccTypeSource = new CcType($this->paymentConfigMock);
    }

    public function testGetAllowedTypes()
    {
        $result = $this->ccTypeSource->getAllowedTypes();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        $randomEl = $result[array_rand($result)];
        $this->assertInternalType('string', $randomEl);
    }
}
