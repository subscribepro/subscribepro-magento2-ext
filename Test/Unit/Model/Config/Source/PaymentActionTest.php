<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config\Source;

use Swarming\SubscribePro\Model\Config\Source\PaymentAction;

class PaymentActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\PaymentAction
     */
    protected $paymentActionSource;

    protected function setUp()
    {
        $this->paymentActionSource = new PaymentAction();
    }

    public function testToOptionArray()
    {
        $result = $this->paymentActionSource->toOptionArray();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        $randomEl = $result[array_rand($result)];
        $this->assertInternalType('array', $randomEl);
        $this->assertArrayHasKey('value', $randomEl);
        $this->assertArrayHasKey('label', $randomEl);
    }
}
