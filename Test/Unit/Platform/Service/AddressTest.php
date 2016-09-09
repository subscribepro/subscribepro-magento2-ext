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
        $platformAddressMock = $this->createPlatformAddressMock();
        
        $this->initService($this->addressPlatformService, $expectedWebsiteId);
        $this->addressPlatformService->expects($this->once())
            ->method('createAddress')
            ->with(['address data'])
            ->willReturn($platformAddressMock);
        
        $this->assertSame(
            $platformAddressMock, $this->addressService->createAddress(['address data'], $websiteId)
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
        $platformAddressMock = $this->createPlatformAddressMock();
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('loadAddress')
            ->with($addressId)
            ->willReturn($platformAddressMock);

        $this->assertSame(
            $platformAddressMock, $this->addressService->loadAddress($addressId, $websiteId)
        );
    }

    public function testSaveAddress()
    {
        $websiteId = 12;
        $platformAddressMock = $this->createPlatformAddressMock();
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('saveAddress')
            ->with($platformAddressMock)
            ->willReturn($platformAddressMock);

        $this->assertSame(
            $platformAddressMock, $this->addressService->saveAddress($platformAddressMock, $websiteId)
        );
    }

    public function testFindOrSaveAddress()
    {
        $websiteId = 12;
        $platformAddressMock = $this->createPlatformAddressMock();
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('findOrSave')
            ->with($platformAddressMock)
            ->willReturn($platformAddressMock);

        $this->assertSame(
            $platformAddressMock, $this->addressService->findOrSave($platformAddressMock, $websiteId)
        );
    }
    
    public function testLoadAddresses()
    {
        $websiteId = 12;
        $customerId = 33;
        $platformAddressesMock = [$this->createPlatformAddressMock(), $this->createPlatformAddressMock()];
        $this->initService($this->addressPlatformService, $websiteId);

        $this->addressPlatformService->expects($this->once())
            ->method('loadAddresses')
            ->with($customerId)
            ->willReturn($platformAddressesMock);

        $this->assertEquals(
            $platformAddressesMock, $this->addressService->loadAddresses($customerId, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\AddressInterface
     */
    private function createPlatformAddressMock()
    {
        return $this->getMockBuilder(AddressInterface::class)->getMock();
    }
}
