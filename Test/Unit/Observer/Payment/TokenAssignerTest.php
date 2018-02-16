<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Payment;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Method\Vault;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;
use SubscribePro\Service\Transaction\TransactionInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Swarming\SubscribePro\Observer\Payment\TokenAssigner as PaymentTokenAssigner;
use Magento\Payment\Model\InfoInterface as PaymentInfoInterface;

class TokenAssignerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Payment\TokenAssigner
     */
    protected $paymentTokenAssigner;
    
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagementMock;

    protected function setUp()
    {
        $this->paymentTokenManagementMock = $this->getMockBuilder(PaymentTokenManagementInterface::class)->getMock();
        $this->paymentTokenAssigner = new PaymentTokenAssigner($this->paymentTokenManagementMock);
    }

    /**
     * @param mixed $additionalData
     * @dataProvider executeIfInvalidAdditionalDataDataProvider
     */
    public function testExecuteIfInvalidAdditionalData($additionalData)
    {
        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalData);
        
        $eventMock = $this->createEventMock();
        $eventMock->expects($this->once())
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        
        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);
        
        $this->paymentTokenAssigner->execute($observerMock);
    }

    /**
     * @return array
     */
    public function executeIfInvalidAdditionalDataDataProvider()
    {
        return [
            'Not an array' => [
                'additionalData' => 'not array'
            ],
            'Without profile_id key' => [
                'additionalData' => ['key' => 'value']
            ],
            'Profile ID is null' => [
                'additionalData' => [VaultDataBuilder::PAYMENT_PROFILE_ID => null]
            ],
        ];
    }

    public function testExecuteIfPaymentModelIsNotQuote()
    {
        $additionalInfo = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => 141
        ];
        
        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalInfo);
        
        $paymentInfoMock = $this->getMockBuilder(PaymentInfoInterface::class)->getMock();
        $paymentInfoMock->expects($this->never())->method('setAdditionalInformation');
        
        $eventMock = $this->createEventMock();
        $eventMock->expects($this->at(0))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        $eventMock->expects($this->at(1))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::MODEL_CODE)
            ->willReturn($paymentInfoMock);
        
        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);
        
        $this->paymentTokenAssigner->execute($observerMock);
    }

    public function testExecuteWithoutCustomerId()
    {
        $additionalInfo = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => 141
        ];

        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalInfo);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn(null);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $paymentInfoMock = $this->createQuotePaymentMock();
        $paymentInfoMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $paymentInfoMock->expects($this->never())->method('setAdditionalInformation');

        $eventMock = $this->createEventMock();
        $eventMock->expects($this->at(0))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        $eventMock->expects($this->at(1))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::MODEL_CODE)
            ->willReturn($paymentInfoMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->paymentTokenAssigner->execute($observerMock);
    }

    public function testExecuteWithoutPaymentToken()
    {
        $profileId = 4441;
        $customerId = 334;
        $additionalInfo = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId
        ];

        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalInfo);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $paymentInfoMock = $this->createQuotePaymentMock();
        $paymentInfoMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $paymentInfoMock->expects($this->never())->method('setAdditionalInformation');

        $eventMock = $this->createEventMock();
        $eventMock->expects($this->at(0))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        $eventMock->expects($this->at(1))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::MODEL_CODE)
            ->willReturn($paymentInfoMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);
        
        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByGatewayToken')
            ->with($profileId, ConfigProvider::CODE, $customerId)
            ->willReturn(null);

        $this->paymentTokenAssigner->execute($observerMock);
    }

    public function testExecute()
    {
        $profileId = 4441;
        $uniqueId = 123456789;
        $customerId = 334;
        $subscribeProToken = 'testtesttoken';
        $additionalInfo = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId,
            TransactionInterface::UNIQUE_ID => $uniqueId,
            TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN => $subscribeProToken
        ];
        $tokenHash = 'hash';
        $tokenAdditionalInfo = [
            PaymentTokenInterface::CUSTOMER_ID => $customerId,
            PaymentTokenInterface::PUBLIC_HASH => $tokenHash
        ];
        
        $tokenMock = $this->getMockBuilder('Magento\Vault\Api\Data\PaymentTokenInterface')->getMock();
        $tokenMock->expects($this->once())->method('getPublicHash')->willReturn($tokenHash);

        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalInfo);

        $customerMock = $this->createCustomerMock();
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $paymentInfoMock = $this->createQuotePaymentMock();
        $paymentInfoMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $paymentInfoMock->expects($this->exactly(4))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [PaymentTokenInterface::CUSTOMER_ID, $customerId],
                [PaymentTokenInterface::PUBLIC_HASH, $tokenHash],
                [TransactionInterface::UNIQUE_ID, $uniqueId],
                [TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN, $subscribeProToken]
            );

        $eventMock = $this->createEventMock();
        $eventMock->expects($this->at(0))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        $eventMock->expects($this->at(1))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::MODEL_CODE)
            ->willReturn($paymentInfoMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);
        
        $this->paymentTokenManagementMock->expects($this->once())
            ->method('getByGatewayToken')
            ->with($profileId, ConfigProvider::CODE, $customerId)
            ->willReturn($tokenMock);

        $this->paymentTokenAssigner->execute($observerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Payment
     */
    private function createQuotePaymentMock()
    {
        return $this->getMockBuilder(QuotePayment::class)->disableOriginalConstructor()->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event
     */
    private function createEventMock()
    {
        return $this->getMockBuilder(Event::class)->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createDataMock()
    {
        return $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(CartInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Api\Data\CustomerInterface
     */
    private function createCustomerMock()
    {
        return $this->getMockBuilder(CustomerInterface::class)->getMock();
    }
}
