<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Manager;

use Swarming\SubscribePro\Api\Data\AddressInterface;
use Swarming\SubscribePro\Platform\Manager\Address;
use Swarming\SubscribePro\Platform\Service\Address as AddressService;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class AddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Address
     */
    protected $addressManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Address
     */
    protected $platformAddressServiceMock;

    protected function setUp(): void
    {
        $this->platformAddressServiceMock = $this->getMockBuilder(AddressService::class)
            ->disableOriginalConstructor()->getMock();

        $this->addressManager = new Address($this->platformAddressServiceMock);
    }

    public function testFindOrSaveAddress()
    {
        $platformCustomerId = 451;
        $websiteId = 12;

        $addressMock = $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())->method('getCity')->willReturn('city');
        $addressMock->expects($this->once())->method('getCompany')->willReturn('company');
        $addressMock->expects($this->once())->method('getCountryId')->willReturn('UA');
        $addressMock->expects($this->once())->method('getRegionCode')->willReturn('region');
        $addressMock->expects($this->at(4))
            ->method('getStreetLine')
            ->with(1)
            ->willReturn('line 1');
        $addressMock->expects($this->at(5))
            ->method('getStreetLine')
            ->with(2)
            ->willReturn('line 2');
        $addressMock->expects($this->at(6))
            ->method('getStreetLine')
            ->with(3)
            ->willReturn('line 3');
        $addressMock->expects($this->once())->method('getPostcode')->willReturn('000');
        $addressMock->expects($this->once())->method('getTelephone')->willReturn('066');
        $addressMock->expects($this->once())->method('getFirstname')->willReturn('first');
        $addressMock->expects($this->once())->method('getLastname')->willReturn('last');
        $addressMock->expects($this->once())->method('getMiddlename')->willReturn('middle');

        $platformAddressMock = $this->getMockBuilder(AddressInterface::class)->getMock();
        $platformAddressMock->expects($this->once())->method('setCity')->with('city')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setCompany')->with('company')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setCountry')->with('UA')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setRegion')->with('region')->willReturnSelf();
        $platformAddressMock->expects($this->at(4))->method('setStreet1')->with('line 1')->willReturnSelf();
        $platformAddressMock->expects($this->at(5))->method('setStreet2')->with('line 2')->willReturnSelf();
        $platformAddressMock->expects($this->at(6))->method('setStreet3')->with('line 3')->willReturnSelf();
        $platformAddressMock->expects($this->once())
            ->method('setCustomerId')
            ->with($platformCustomerId)
            ->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setPostcode')->with('000')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setPhone')->with('066')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setFirstName')->with('first')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setLastName')->with('last')->willReturnSelf();
        $platformAddressMock->expects($this->once())->method('setMiddleName')->with('middle')->willReturnSelf();

        $this->platformAddressServiceMock->expects($this->once())
            ->method('createAddress')
            ->with([], $websiteId)
            ->willReturn($platformAddressMock);

        $this->platformAddressServiceMock->expects($this->once())
            ->method('findOrSave')
            ->with($platformAddressMock, $websiteId);

        $this->assertSame(
            $platformAddressMock,
            $this->addressManager->findOrSaveAddress($addressMock, $platformCustomerId, $websiteId)
        );
    }
}
