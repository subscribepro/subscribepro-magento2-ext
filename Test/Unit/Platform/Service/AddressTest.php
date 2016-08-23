<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\Address\AddressService;
use Swarming\SubscribePro\Api\Data\AddressInterface;
use Swarming\SubscribePro\Platform\Service\Address;

class AddressTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Address
     */
    protected $addressService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Address\AddressService
     */
    protected $addressPlatformService;

    protected function setUp()
    {
        $this->platformMock = $this->createPlatformMock();
        $this->addressPlatformService = $this->getMockBuilder(AddressService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressService = new Address($this->platformMock, $this->name);
        $this->addressService->setWebsite($this->defaultWebsiteId);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createAddressDataProvider
     */
    public function testCreateAddress($websiteId, $expectedWebsiteId)
    {
        $addressMock = $this->createAddressMock();
        
        $this->initService($this->addressPlatformService, $expectedWebsiteId);
        $this->addressPlatformService->expects($this->once())
            ->method('createAddress')
            ->with(['address data'])
            ->willReturn($addressMock);
        
        $this->assertSame(
            $addressMock, $this->addressService->createAddress(['address data'], $websiteId)
        );
    }

    /**
     * @return array
     */
    public function createAddressDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
            ],
            'Without website Id' => [
                'websiteId' => null,
                'expectedWebsiteId' => $this->defaultWebsiteId,
            ]
        ];
    }

    public function testLoadAddress()
    {
        $addressId = 111;
        $websiteId = 12;
        $addressMock = $this->createAddressMock();
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('loadAddress')
            ->with($addressId)
            ->willReturn($addressMock);

        $this->assertSame(
            $addressMock, $this->addressService->loadAddress($addressId, $websiteId)
        );
    }

    public function testSaveAddress()
    {
        $websiteId = 12;
        $addressMock = $this->createAddressMock();
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('saveAddress')
            ->with($addressMock)
            ->willReturn($addressMock);

        $this->assertSame(
            $addressMock, $this->addressService->saveAddress($addressMock, $websiteId)
        );
    }

    public function testFindOrSaveAddress()
    {
        $websiteId = 12;
        $addressMock = $this->createAddressMock();
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('findOrSave')
            ->with($addressMock)
            ->willReturn($addressMock);

        $this->assertSame(
            $addressMock, $this->addressService->findOrSave($addressMock, $websiteId)
        );
    }
    
    public function testLoadAddresses()
    {
        $websiteId = 12;
        $customerId = 33;
        $addressesMock = [$this->createAddressMock(), $this->createAddressMock()];
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('loadAddresses')
            ->with($customerId)
            ->willReturn($addressesMock);

        $this->assertEquals(
            $addressesMock, $this->addressService->loadAddresses($customerId, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\AddressInterface
     */
    private function createAddressMock()
    {
        return $this->getMockBuilder(AddressInterface::class)->getMock();
    }
}
