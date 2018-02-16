<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Config;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Validator\ResponseValidator;

class ResponseValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Validator\ResponseValidator
     */
    protected $responseValidator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Gateway\Validator\ResultInterfaceFactory
     */
    protected $resultInterfaceFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp()
    {
        $this->resultInterfaceFactoryMock = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();
        
        $this->responseValidator = new ResponseValidator(
            $this->resultInterfaceFactoryMock,
            $this->subjectReaderMock
        );
    }

    /**
     * @param array $validationSubject
     * @param string $transactionState
     * @param bool $isValid
     * @dataProvider validateDataProvider
     */
    public function testValidate($validationSubject, $transactionState, $isValid) {
        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->expects($this->once())->method('getState')->willReturn($transactionState);
        
        $resultMock = $this->getMockBuilder(ResultInterface::class)->getMock();
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readTransaction')
            ->with($validationSubject)
            ->willReturn($transactionMock);
        
        $this->resultInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->with(['isValid' => $isValid, 'failsDescription' => []])
            ->willReturn($resultMock);
        
        $this->assertSame($resultMock, $this->responseValidator->validate($validationSubject));
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'Not valid' => [
                'validationSubject' => ['subject'],
                'transactionState' => TransactionInterface::STATE_FAILED,
                'isValid' => false,
            ],
            'Valid' => [
                'validationSubject' => ['subject'],
                'transactionState' => TransactionInterface::STATE_SUCCEEDED,
                'isValid' => true,
            ],
        ];
    }
}
