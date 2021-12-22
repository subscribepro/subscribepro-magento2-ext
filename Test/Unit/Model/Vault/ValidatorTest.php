<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Vault;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Api\Data\AddressInterface;
use Swarming\SubscribePro\Model\Vault\Validator;
use Magento\Directory\Helper\Data as DirectoryDataHelper;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Vault\Validator
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Directory\Model\RegionFactory
     */
    protected $regionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Directory\Helper\Data
     */
    protected $directoryDataMock;

    protected function setUp(): void
    {
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->directoryDataMock = $this->getMockBuilder(DirectoryDataHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->validator = new Validator(
            $this->regionFactoryMock,
            $this->directoryDataMock
        );
    }

    /**
     * @param array $profileData
     * @dataProvider validateIfProfileDataNotValidDataProvider
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Not all fields are filled.
     */
    public function testValidateIfProfileDataNotValid($profileData)
    {
        $this->directoryDataMock->expects($this->never())->method('isRegionRequired');
        $this->directoryDataMock->expects($this->never())->method('isZipCodeOptional');
        $this->regionFactoryMock->expects($this->never())->method('create');

        $this->validator->validate($profileData);
    }

    /**
     * @return array
     */
    public function validateIfProfileDataNotValidDataProvider()
    {
        return [
            'Without credit card month' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_YEAR => '2018',
                    PaymentProfileInterface::BILLING_ADDRESS => ['key' => 'value'],
                ],
            ],
            'Without credit card year' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '04',
                    PaymentProfileInterface::BILLING_ADDRESS => ['address'],
                ],
            ],
            'Without billing address' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '06',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2020',
                ],
            ],
            'Billing address is not array' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '01',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2022',
                    PaymentProfileInterface::BILLING_ADDRESS => 'billing address'
                ],
            ],
        ];
    }

    /**
     * @param array $profileData
     * @param string $country
     * @param bool $isRegionRequired
     * @param bool $isZipCodeOptional
     * @dataProvider validateIfNotValidAddressWithoutRegionUpdateDataProvider
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Not all billing address fields are filled.
     */
    public function testValidateIfNotValidAddressWithoutRegionUpdate(
        $profileData,
        $country,
        $isRegionRequired,
        $isZipCodeOptional
    ) {
        $this->directoryDataMock->expects($this->any())->method('isRegionRequired')
            ->with($country)
            ->willReturn($isRegionRequired);
        $this->directoryDataMock->expects($this->any())->method('isZipCodeOptional')
            ->with($country)
            ->willReturn($isZipCodeOptional);

        $this->regionFactoryMock->expects($this->never())->method('create');

        $this->validator->validate($profileData);
    }

    /**
     * @return array
     */
    public function validateIfNotValidAddressWithoutRegionUpdateDataProvider()
    {
        return [
            'Without billing address first name:with region_id:without country' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '01',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2022',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::LAST_NAME => 'Smith',
                        AddressInterface::STREET1 => 'some st.',
                        AddressInterface::CITY => 'Paris',
                        AddressInterface::REGION => 'some region',
                        AddressInterface::POSTCODE => 'post_code',
                        AddressInterface::PHONE => '099789',
                        'region_id' => 123
                    ],
                ],
                'country' => null,
                'isRegionRequired' => true,
                'isZipCodeOptional' => true
            ],
            'Without billing address last name:with country:without region_id' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '07',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2010',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Bob',
                        AddressInterface::STREET1 => 'Bob st.',
                        AddressInterface::CITY => 'Bob city',
                        AddressInterface::COUNTRY => 'Bob country',
                        AddressInterface::POSTCODE => 'post_code',
                        AddressInterface::PHONE => '050456',
                    ],
                ],
                'country' => 'Bob country',
                'isRegionRequired' => true,
                'isZipCodeOptional' => true,
            ],
            'Without billing address street 1' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '12',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2042',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Fake',
                        AddressInterface::LAST_NAME => 'Name',
                        AddressInterface::CITY => 'Fake city',
                        AddressInterface::COUNTRY => 'Fake country',
                        AddressInterface::POSTCODE => '333aaa',
                        AddressInterface::PHONE => '987789',
                    ],
                ],
                'country' => 'Fake country',
                'isRegionRequired' => false,
                'isZipCodeOptional' => true,
            ],
            'Without billing address city' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '08',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1942',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Lue',
                        AddressInterface::LAST_NAME => 'Kang',
                        AddressInterface::STREET1 => 'sight st.',
                        AddressInterface::REGION => 'new region',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::POSTCODE => '777222fff',
                        AddressInterface::PHONE => '5665456',
                    ],
                ],
                'country' => 'USA',
                'isRegionRequired' => false,
                'isZipCodeOptional' => true,
            ],
            'Without billing address country' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '02',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1972',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Johny',
                        AddressInterface::LAST_NAME => 'Bravo',
                        AddressInterface::STREET1 => 'old st.',
                        AddressInterface::REGION => 'old region',
                        AddressInterface::CITY => 'Oldwill',
                        AddressInterface::POSTCODE => '123123dd',
                        AddressInterface::PHONE => '7896421',
                    ],
                ],
                'country' => null,
                'isRegionRequired' => false,
                'isZipCodeOptional' => true,
            ],
            'Without billing address region:region required' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '03',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1973',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'John',
                        AddressInterface::LAST_NAME => 'John',
                        AddressInterface::STREET1 => 'John st.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'Johnwill',
                        AddressInterface::POSTCODE => '3255',
                        AddressInterface::PHONE => '12345',
                    ],
                ],
                'country' => 'USA',
                'isRegionRequired' => true,
                'isZipCodeOptional' => true,
            ],
            'Without billing address postcode:postcode is not optional' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '04',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1986',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Andy',
                        AddressInterface::LAST_NAME => 'Andy',
                        AddressInterface::STREET1 => 'Andy st.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'Andywill',
                        AddressInterface::PHONE => '945637',
                    ],
                ],
                'country' => 'USA',
                'isRegionRequired' => false,
                'isZipCodeOptional' => false,
            ],
            'Without billing address phone' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '05',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1991',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Mike',
                        AddressInterface::LAST_NAME => 'Hike',
                        AddressInterface::STREET1 => 'Famous st.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'Famouswill',
                    ],
                ],
                'country' => 'USA',
                'isRegionRequired' => false,
                'isZipCodeOptional' => true,
            ],
        ];
    }

    /**
     * @param array $profileData
     * @param string $country
     * @param bool $isRegionRequired
     * @param bool $isZipCodeOptional
     * @dataProvider validateIfValidAddressWithoutRegionUpdateDataProvider
     */
    public function testValidateIfValidAddressWithoutRegionUpdate(
        $profileData,
        $country,
        $isRegionRequired,
        $isZipCodeOptional
    ) {
        $this->directoryDataMock->expects($this->any())->method('isRegionRequired')
            ->with($country)
            ->willReturn($isRegionRequired);
        $this->directoryDataMock->expects($this->any())->method('isZipCodeOptional')
            ->with($country)
            ->willReturn($isZipCodeOptional);

        $this->regionFactoryMock->expects($this->never())->method('create');

        $this->assertEquals($profileData, $this->validator->validate($profileData));
    }

    /**
     * @return array
     */
    public function validateIfValidAddressWithoutRegionUpdateDataProvider()
    {
        return [
            'Without billing address region: region not required:valid' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '07',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1997',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'name',
                        AddressInterface::LAST_NAME => 'surname',
                        AddressInterface::STREET1 => 'street.',
                        AddressInterface::COUNTRY => 'country',
                        AddressInterface::CITY => 'country_city',
                        AddressInterface::PHONE => '75646',
                        AddressInterface::POSTCODE => 'post_code',
                    ],
                ],
                'country' => 'country',
                'isRegionRequired' => false,
                'isZipCodeOptional' => false,
            ],
            'Without billing address postcode: postcode is optional:valid' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '08',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2008',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Another',
                        AddressInterface::LAST_NAME => 'Name',
                        AddressInterface::STREET1 => 'Another st.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'city_city',
                        AddressInterface::PHONE => '646464',
                        AddressInterface::REGION => 'region',
                    ],
                ],
                'country' => 'USA',
                'isRegionRequired' => true,
                'isZipCodeOptional' => true,
            ],
            'With all data:valid' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '12',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2012',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Some',
                        AddressInterface::LAST_NAME => 'Name',
                        AddressInterface::STREET1 => 'Metalist st.',
                        AddressInterface::COUNTRY => 'Ukraine',
                        AddressInterface::CITY => 'Kiev',
                        AddressInterface::PHONE => '12331321',
                        AddressInterface::REGION => 'region_region',
                        AddressInterface::POSTCODE => 'postcode',
                    ],
                ],
                'country' => 'Ukraine',
                'isRegionRequired' => true,
                'isZipCodeOptional' => false,
            ],
        ];
    }

    /**
     * @param array $profileData
     * @param string $regionId
     * @param string|null $regionCode
     * @param string $regionCountryId
     * @param bool $isRegionRequired
     * @param bool $isZipCodeOptional
     * @dataProvider validateWithRegionUpdateIfAddressNotValidDataProvider
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Not all billing address fields are filled.
     */
    public function testValidateWithRegionUpdateIfAddressNotValid(
        $profileData,
        $regionId,
        $regionCode,
        $regionCountryId,
        $isRegionRequired,
        $isZipCodeOptional
    ) {
        $regionMock = $this->createRegionMock();
        $regionMock->expects($this->once())->method('load')->with($this->equalTo($regionId));
        $regionMock->expects($this->any())->method('getCode')->willReturn($regionCode);
        $regionMock->expects($this->any())->method('getCountryId')->willReturn($regionCountryId);

        $this->directoryDataMock->expects($this->any())->method('isRegionRequired')
            ->with($profileData[PaymentProfileInterface::BILLING_ADDRESS][AddressInterface::COUNTRY])
            ->willReturn($isRegionRequired);
        $this->directoryDataMock->expects($this->any())->method('isZipCodeOptional')
            ->with($profileData[PaymentProfileInterface::BILLING_ADDRESS][AddressInterface::COUNTRY])
            ->willReturn($isZipCodeOptional);

        $this->regionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($regionMock);

        $this->validator->validate($profileData);
    }

    /**
     * @return array
     */
    public function validateWithRegionUpdateIfAddressNotValidDataProvider()
    {
        return [
            'Region code is null: region is required' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '07',
                    PaymentProfileInterface::CREDITCARD_YEAR => '1997',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'name',
                        AddressInterface::LAST_NAME => 'surname',
                        AddressInterface::STREET1 => 'street.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'country_city',
                        AddressInterface::PHONE => '65489',
                        AddressInterface::POSTCODE => '111fff',
                        'region_id' => 'REG_ID',
                    ],
                ],
                'regionId' => 'REG_ID',
                'regionCode' => null,
                'regionCountryId' => 'USA',
                'isRegionRequired' => true,
                'isZipCodeOptional' => false,
            ],
            'Region country ID not match address country: region is required' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '02',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2022',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'John',
                        AddressInterface::LAST_NAME => 'Smith',
                        AddressInterface::STREET1 => 'Barber st.',
                        AddressInterface::COUNTRY => 'UAH',
                        AddressInterface::CITY => 'Lviv',
                        AddressInterface::PHONE => '789643',
                        AddressInterface::POSTCODE => '222',
                        'region_id' => 'id_22',
                    ],
                ],
                'regionId' => 'id_22',
                'regionCode' => 'reg_code',
                'regionCountryId' => 'FCH',
                'isRegionRequired' => true,
                'isZipCodeOptional' => false,
            ],
        ];
    }

    /**
     * @param array $profileData
     * @param string $regionId
     * @param string|null $regionCode
     * @param string $regionCountryId
     * @param bool $isRegionRequired
     * @param bool $isZipCodeOptional
     * @param array $result
     * @dataProvider validateWithRegionUpdateDataProvider
     */
    public function testValidateWithRegionUpdate(
        $profileData,
        $regionId,
        $regionCode,
        $regionCountryId,
        $isRegionRequired,
        $isZipCodeOptional,
        $result
    ) {
        $regionMock = $this->createRegionMock();
        $regionMock->expects($this->once())->method('load')->with($this->equalTo($regionId));
        $regionMock->expects($this->any())->method('getCode')->willReturn($regionCode);
        $regionMock->expects($this->any())->method('getCountryId')->willReturn($regionCountryId);

        $this->directoryDataMock->expects($this->any())->method('isRegionRequired')
            ->with($profileData[PaymentProfileInterface::BILLING_ADDRESS][AddressInterface::COUNTRY])
            ->willReturn($isRegionRequired);
        $this->directoryDataMock->expects($this->any())->method('isZipCodeOptional')
            ->with($profileData[PaymentProfileInterface::BILLING_ADDRESS][AddressInterface::COUNTRY])
            ->willReturn($isZipCodeOptional);

        $this->regionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($regionMock);

        $this->assertEquals($result, $this->validator->validate($profileData));
    }

    /**
     * @return array
     */
    public function validateWithRegionUpdateDataProvider()
    {
        return [
            'Region code is null: region not required:valid' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '12',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2012',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'New',
                        AddressInterface::LAST_NAME => 'Name',
                        AddressInterface::STREET1 => 'new street.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'LA',
                        AddressInterface::PHONE => '444666',
                        AddressInterface::POSTCODE => '222fff',
                        'region_id' => 'some_id',
                    ],
                ],
                'regionId' => 'some_id',
                'regionCode' => null,
                'regionCountryId' => 'USA',
                'isRegionRequired' => false,
                'isZipCodeOptional' => true,
                'result' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '12',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2012',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'New',
                        AddressInterface::LAST_NAME => 'Name',
                        AddressInterface::STREET1 => 'new street.',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'LA',
                        AddressInterface::PHONE => '444666',
                        AddressInterface::POSTCODE => '222fff',
                        'region_id' => 'some_id',
                    ],
                ]
            ],
            'Region country ID not match address country: region not required:valid' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '10',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2010',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Neo',
                        AddressInterface::LAST_NAME => 'Anderson',
                        AddressInterface::STREET1 => 'neo st.',
                        AddressInterface::COUNTRY => 'UAH',
                        AddressInterface::CITY => 'Kiev',
                        AddressInterface::PHONE => '1165489',
                        AddressInterface::POSTCODE => '333ddd',
                        'region_id' => '33dd',
                    ],
                ],
                'regionId' => '33dd',
                'regionCode' => 'some_code',
                'regionCountryId' => 'USA',
                'isRegionRequired' => false,
                'isZipCodeOptional' => false,
                'result' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '10',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2010',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Neo',
                        AddressInterface::LAST_NAME => 'Anderson',
                        AddressInterface::STREET1 => 'neo st.',
                        AddressInterface::COUNTRY => 'UAH',
                        AddressInterface::CITY => 'Kiev',
                        AddressInterface::PHONE => '1165489',
                        AddressInterface::POSTCODE => '333ddd',
                        'region_id' => '33dd',
                    ],
                ]
            ],
            'With all data:valid' => [
                'profileData' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '12',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2023',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Morpheus',
                        AddressInterface::LAST_NAME => 'XXX',
                        AddressInterface::STREET1 => '#123',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'New York',
                        AddressInterface::PHONE => '42233',
                        'region_id' => '5566',
                    ],
                ],
                'regionId' => '5566',
                'regionCode' => 'new_code',
                'regionCountryId' => 'USA',
                'isRegionRequired' => true,
                'isZipCodeOptional' => true,
                'result' => [
                    PaymentProfileInterface::CREDITCARD_MONTH => '12',
                    PaymentProfileInterface::CREDITCARD_YEAR => '2023',
                    PaymentProfileInterface::BILLING_ADDRESS => [
                        AddressInterface::FIRST_NAME => 'Morpheus',
                        AddressInterface::LAST_NAME => 'XXX',
                        AddressInterface::STREET1 => '#123',
                        AddressInterface::COUNTRY => 'USA',
                        AddressInterface::CITY => 'New York',
                        AddressInterface::PHONE => '42233',
                        AddressInterface::REGION => 'new_code',
                        'region_id' => '5566',
                    ],
                ]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Directory\Model\Region
     */
    private function createRegionMock()
    {
        return $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCountryId', 'getCode', 'load', '__wakeup'])
            ->getMock();
    }
}
