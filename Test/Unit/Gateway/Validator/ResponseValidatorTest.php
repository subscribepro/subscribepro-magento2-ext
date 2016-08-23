<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Config;

use SubscribePro\Service\Transaction\TransactionInterface;

class ResponseValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Validator\ResponseValidator
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
        $this->resultInterfaceFactoryMock = $this->getMockBuilder('Magento\Payment\Gateway\Validator\ResultInterfaceFactory')
            ->disableOriginalConstructor()->getMock();
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();
        
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->responseValidator = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Validator\ResponseValidator',
            [
                'subjectReader' => $this->subjectReaderMock,
                'resultInterfaceFactory' => $this->resultInterfaceFactoryMock,
            ]
        );
    }

    /**
     * @param array $validationSubject
     * @param string $transactionState
     * @param bool $isValid
     * @dataProvider validateDataProvider
     */
    public function testValidate($validationSubject, $transactionState, $isValid) {
        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $transactionMock->expects($this->once())->method('getState')->willReturn($transactionState);
        
        $resultMock = $this->getMockBuilder('Magento\Payment\Gateway\Validator\ResultInterface')->getMock();
        
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
