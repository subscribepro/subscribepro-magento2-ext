<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config\Source;

use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;

class CartRuleCombineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\CartRuleCombine
     */
    protected $cartRuleCombineSource;

    protected function setUp()
    {
        $this->cartRuleCombineSource = new CartRuleCombine();
    }

    public function testToOptionArray()
    {
        $result = $this->cartRuleCombineSource->toOptionArray();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        $randomEl = $result[array_rand($result)];
        $this->assertInternalType('array', $randomEl);
        $this->assertArrayHasKey('value', $randomEl);
        $this->assertArrayHasKey('label', $randomEl);
    }
}
