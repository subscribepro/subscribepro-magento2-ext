<?php

namespace Swarming\SubscribePro\Test\Unit\Service;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Address\Renderer\RendererInterface as AddressRendererInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Swarming\SubscribePro\Service\AddressManagement;

class AddressManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Service\AddressManagement
     */
    protected $addressManagement;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Model\Address\Config
     */
    protected $addressConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapperMock;

    protected function setUp(): void
    {
        $this->addressRepositoryMock = $this->getMockBuilder(AddressRepositoryInterface::class)->getMock();
        $this->addressConfigMock = $this->getMockBuilder(AddressConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->addressMapperMock = $this->getMockBuilder(AddressMapper::class)
            ->disableOriginalConstructor()->getMock();

        $this->addressManagement = new AddressManagement(
            $this->addressRepositoryMock,
            $this->addressMapperMock,
            $this->addressConfigMock
        );
    }

    /**
     * @expectedExceptionMessage An error occurred while saving address in the address book.
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testFailToSaveInAddressBook()
    {
        $exception = new LocalizedException(__('error'));

        $customerAddressMock = $this->getMockBuilder(AddressInterface::class)->getMock();
        $customerAddressMock->expects($this->once())->method('setCustomerId')->with(111);

        $quoteAddressMock = $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteAddressMock->expects($this->once())
            ->method('exportCustomerAddress')
            ->willReturn($customerAddressMock);

        $this->addressRepositoryMock->expects($this->once())
            ->method('save')
            ->with($customerAddressMock)
            ->willThrowException($exception);

        $this->addressManagement->saveInAddressBook(111, $quoteAddressMock);
    }

    public function testSaveInAddressBook()
    {
        $flatArray = ['flat'];
        $customerId = 111;

        $customerAddressMock = $this->getMockBuilder(AddressInterface::class)->getMock();
        $customerAddressMock->expects($this->once())->method('setCustomerId')->with($customerId);

        $renderMock = $this->getMockBuilder(AddressRendererInterface::class)->getMock();
        $renderMock->expects($this->once())->method('renderArray')->with($flatArray);

        $formatMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formatMock->expects($this->once())
            ->method('getData')
            ->with('renderer')
            ->willReturn($renderMock);

        $quoteAddressMock = $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteAddressMock->expects($this->once())
            ->method('exportCustomerAddress')
            ->willReturn($customerAddressMock);

        $this->addressRepositoryMock->expects($this->once())
            ->method('save')
            ->with($customerAddressMock);

        $this->addressMapperMock->expects($this->once())
            ->method('toFlatArray')
            ->with($customerAddressMock)
            ->willReturn($flatArray);

        $this->addressConfigMock->expects($this->once())
            ->method('getFormatByCode')
            ->with(AddressConfig::DEFAULT_ADDRESS_FORMAT)
            ->willReturn($formatMock);

        $this->addressManagement->saveInAddressBook($customerId, $quoteAddressMock);
    }
}
