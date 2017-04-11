<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileService;
use Swarming\SubscribePro\Platform\Service\PaymentProfile;

class PaymentProfileTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $paymentProfileService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\PaymentProfile\PaymentProfileService
     */
    protected $paymentProfilePlatformService;

    protected function setUp()
    {
        $this->platformMock = $this->createPlatformMock();
        $this->paymentProfilePlatformService = $this->getMockBuilder(PaymentProfileService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentProfileService = new PaymentProfile($this->platformMock, $this->name);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createProfileDataProvider
     */
    public function testCreateProfile($websiteId, $expectedWebsiteId)
    {
        $profileMock = $this->createProfileMock();
        
        $this->initService($this->paymentProfilePlatformService, $expectedWebsiteId);
        $this->paymentProfilePlatformService->expects($this->once())
            ->method('createProfile')
            ->with(['profile data'])
            ->willReturn($profileMock);
        
        $this->assertSame(
            $profileMock, 
            $this->paymentProfileService->createProfile(['profile data'], $websiteId)
        );
    }

    /**
     * @return array
     */
    public function createProfileDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
            ],
            'Without website Id' => [
                'websiteId' => null,
                'expectedWebsiteId' => null,
            ]
        ];
    }

    public function testLoadProfile()
    {
        $paymentProfileId = 111;
        $websiteId = 12;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('loadProfile')
            ->with($paymentProfileId)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, $this->paymentProfileService->loadProfile($paymentProfileId, $websiteId)
        );
    }

    public function testSaveProfile()
    {
        $websiteId = 12;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('saveProfile')
            ->with($profileMock)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, $this->paymentProfileService->saveProfile($profileMock, $websiteId)
        );
    }
    
    public function testRedactProfile()
    {
        $websiteId = 12;
        $paymentProfileId = 22;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('redactProfile')
            ->with($paymentProfileId)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, $this->paymentProfileService->redactProfile($paymentProfileId, $websiteId)
        );
    }

    public function testLoadProfiles()
    {
        $websiteId = 12;
        $filters = ['filters'];
        $paymentProfilesMock = [$this->createProfileMock(), $this->createProfileMock()];
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('loadProfiles')
            ->with($filters)
            ->willReturn($paymentProfilesMock);

        $this->assertEquals(
            $paymentProfilesMock, $this->paymentProfileService->loadProfiles($filters, $websiteId)
        );
    }

    public function testSaveThirdPartyToken()
    {
        $websiteId = 12;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('saveProfile')
            ->with($profileMock)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, $this->paymentProfileService->saveProfile($profileMock, $websiteId)
        );
    }

    public function testSaveToken()
    {
        $token = 'token';
        $websiteId = 12;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('saveToken')
            ->with($token, $profileMock)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, 
            $this->paymentProfileService->saveToken($token, $profileMock, $websiteId)
        );
    }

    public function testVerifyAndSaveToken()
    {
        $token = 'token';
        $websiteId = 12;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('verifyAndSaveToken')
            ->with($token, $profileMock)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, 
            $this->paymentProfileService->verifyAndSaveToken($token, $profileMock, $websiteId)
        );
    }

    public function testLoadProfileByToken()
    {
        $token = 'token';
        $websiteId = 12;
        $profileMock = $this->createProfileMock();
        $this->initService($this->paymentProfilePlatformService, $websiteId);

        $this->paymentProfilePlatformService->expects($this->once())
            ->method('loadProfileByToken')
            ->with($token)
            ->willReturn($profileMock);

        $this->assertSame(
            $profileMock, 
            $this->paymentProfileService->loadProfileByToken($token, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    private function createProfileMock()
    {
        return $this->getMockBuilder(PaymentProfileInterface::class)->getMock();
    }
}
