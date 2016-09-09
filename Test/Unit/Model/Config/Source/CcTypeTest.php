<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config\Source;

use Swarming\SubscribePro\Model\Config\Source\CcType;
use Magento\Payment\Model\Config as PaymentConfig;

class CcTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\CcType
     */
    protected $ccTypeSource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\Config
     */
    protected $paymentConfigMock;

    protected function setUp()
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
