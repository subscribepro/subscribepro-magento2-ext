<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config\Source;

use Swarming\SubscribePro\Model\Config\Source\LogLevels;

class LogLevelsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\LogLevels
     */
    protected $logLevelsSource;

    protected function setUp()
    {
        $this->logLevelsSource = new LogLevels();
    }

    public function testToOptionArray()
    {
        $result = $this->logLevelsSource->toOptionArray();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        $randomEl = $result[array_rand($result)];
        $this->assertInternalType('array', $randomEl);
        $this->assertArrayHasKey('value', $randomEl);
        $this->assertArrayHasKey('label', $randomEl);
    }
}
