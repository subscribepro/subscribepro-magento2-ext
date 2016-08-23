<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Helper;

use Swarming\SubscribePro\Gateway\Helper\SubjectReader;

class SubjectReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    protected function setUp()
    {
        $this->subjectReader = new SubjectReader();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Response object does not exist
     * @param array $subject
     * @dataProvider failToReadNotObjectTransactionDataProvider
     */
    public function testFailToReadNotObjectTransaction($subject) {
        $this->subjectReader->readTransaction($subject);
    }

    /**
     * @return array
     */
    public function failToReadNotObjectTransactionDataProvider()
    {
        return [
            'Transaction is not set' => [
                'subject' => [],
            ],
            'Transaction is not an object' => [
                'subject' => ['transaction' => 'string'],
            ],
        ];
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The object is not a class \SubscribePro\Service\Transaction\TransactionInterface.
     */
    public function testFailToReadNotTransactionInterfaceInstance() {
        $subject = ['transaction' => new \ArrayObject()];
        $this->subjectReader->readTransaction($subject);
    }
    
    public function testReadTransaction() {
        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $subject = ['transaction' => $transactionMock];
        
        $this->assertSame($transactionMock, $this->subjectReader->readTransaction($subject));
    }
}
