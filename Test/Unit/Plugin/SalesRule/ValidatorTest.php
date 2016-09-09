<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\SalesRule;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount;
use Swarming\SubscribePro\Plugin\SalesRule\Validator;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount as SubscriptionDiscountConfig;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Magento\SalesRule\Model\Validator as SalesRuleValidator;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface as CatalogRuleInspectorInterface;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\SalesRule\Validator
     */
    protected $validatorPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount
     */
    protected $itemSubscriptionDiscountMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\CatalogRule\InspectorInterface
     */
    protected $catalogRuleInspectorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Vault\Validator
     */
    protected $validatorMock;

    protected function setUp()
    {
        $this->subscriptionDiscountConfigMock = $this->getMockBuilder(SubscriptionDiscountConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->itemSubscriptionDiscountMock = $this->getMockBuilder(ItemSubscriptionDiscount::class)
            ->disableOriginalConstructor()->getMock();
        $this->catalogRuleInspectorMock = $this->getMockBuilder(CatalogRuleInspectorInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->validatorPlugin = new Validator(
            $this->subscriptionDiscountConfigMock,
            $this->itemSubscriptionDiscountMock,
            $this->catalogRuleInspectorMock,
            $this->quoteItemHelperMock
        );
    }

    public function testAroundProcessIfSubscribeProNotEnabled()
    {
        $quoteItemAppliedRuleIds = [11, 23, 14];
        $quoteAppliedRuleIds = [121, 123, 154];
        $addressRules = [1, 23, 431];
        $discountDescriptions = ['descriptions'];
        $websiteId = 455;

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);

        $subjectMock = $this->createSalesRuleValidatorMock();
        $subjectMock->expects($this->never())->method('getItemBasePrice');

        $quoteAddressMock = $this->createQuoteAddressMock();
        $quoteAddressMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($addressRules);
        $quoteAddressMock->expects($this->once())
            ->method('getDiscountDescriptionArray')
            ->willReturn($discountDescriptions);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteAppliedRuleIds);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteItemAppliedRuleIds);
        $quoteItemMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->any())->method('getAddress')->willReturn($quoteAddressMock);

        $proceed = $this->createCallback($subjectMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->itemSubscriptionDiscountMock->expects($this->never())->method('processSubscriptionDiscount');
        $this->catalogRuleInspectorMock->expects($this->never())->method('isApplied');

        $this->assertSame($subjectMock, $this->validatorPlugin->aroundProcess($subjectMock, $proceed, $quoteItemMock));
    }

    public function testAroundProcessIfQuoteWithoutSubscription()
    {
        $quoteItemAppliedRuleIds = [11, 23, 14];
        $quoteAppliedRuleIds = [121, 123, 154];
        $addressRules = [1, 23, 431];
        $discountDescriptions = ['descriptions'];
        $websiteId = 455;

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);

        $subjectMock = $this->createSalesRuleValidatorMock();
        $subjectMock->expects($this->never())->method('getItemBasePrice');

        $quoteAddressMock = $this->createQuoteAddressMock();
        $quoteAddressMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($addressRules);
        $quoteAddressMock->expects($this->once())
            ->method('getDiscountDescriptionArray')
            ->willReturn($discountDescriptions);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteAppliedRuleIds);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteItemAppliedRuleIds);
        $quoteItemMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->any())->method('getAddress')->willReturn($quoteAddressMock);

        $proceed = $this->createCallback($subjectMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteId)
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('hasSubscription')
            ->with($quoteItemMock)
            ->willReturn(false);

        $this->itemSubscriptionDiscountMock->expects($this->never())->method('processSubscriptionDiscount');
        $this->catalogRuleInspectorMock->expects($this->never())->method('isApplied');

        $this->assertSame($subjectMock, $this->validatorPlugin->aroundProcess($subjectMock, $proceed, $quoteItemMock));
    }

    public function testAroundProcessIfCannotApplySubscriptionDiscount()
    {
        $quoteItemAppliedRuleIds = [321, 12323];
        $quoteAppliedRuleIds = [];
        $addressRules = [132, 564];
        $discountDescriptions = ['discount' => 'descriptions'];
        $websiteId = 12;
        $storeCode = 'main_site';

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $storeMock->expects($this->once())->method('getCode')->willReturn($storeCode);

        $subjectMock = $this->createSalesRuleValidatorMock();
        $subjectMock->expects($this->never())->method('getItemBasePrice');

        $productMock = $this->createProductMock();

        $quoteAddressMock = $this->createQuoteAddressMock();
        $quoteAddressMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($addressRules);
        $quoteAddressMock->expects($this->once())
            ->method('getDiscountDescriptionArray')
            ->willReturn($discountDescriptions);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteAppliedRuleIds);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteItemAppliedRuleIds);
        $quoteItemMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->any())->method('getAddress')->willReturn($quoteAddressMock);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $proceed = $this->createCallback($subjectMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteId)
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('hasSubscription')
            ->with($quoteItemMock)
            ->willReturn(true);

        $this->catalogRuleInspectorMock->expects($this->once())
            ->method('isApplied')
            ->with($productMock)
            ->willReturn(true);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isApplyDiscountToCatalogPrice')
            ->with($storeCode)
            ->willReturn(false);

        $this->itemSubscriptionDiscountMock->expects($this->never())->method('processSubscriptionDiscount');

        $this->assertSame($subjectMock, $this->validatorPlugin->aroundProcess($subjectMock, $proceed, $quoteItemMock));
    }

    /**
     * @param array $quoteItemAppliedRuleIds
     * @param array $quoteAppliedRuleIds
     * @param array $addressRules
     * @param array $discountDescriptions
     * @param int $itemBasePrice
     * @param bool $isCatalogRuleApplied
     * @param string $websiteId
     * @param string $storeCode
     * @param bool $isApplyDiscountToCatalogPrice
     * @dataProvider aroundProcessDataProvider
     */
    public function testAroundProcess(
        $quoteItemAppliedRuleIds,
        $quoteAppliedRuleIds,
        $addressRules,
        $discountDescriptions,
        $itemBasePrice,
        $isCatalogRuleApplied,
        $websiteId,
        $storeCode,
        $isApplyDiscountToCatalogPrice
    ) {
        $productMock = $this->createProductMock();

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $storeMock->expects($this->once())->method('getCode')->willReturn($storeCode);

        $subjectMock = $this->createSalesRuleValidatorMock();
        $subjectMock->expects($this->once())->method('getItemBasePrice')->willReturn($itemBasePrice);

        $quoteAddressMock = $this->createQuoteAddressMock();
        $quoteAddressMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($addressRules);
        $quoteAddressMock->expects($this->once())
            ->method('getDiscountDescriptionArray')
            ->willReturn($discountDescriptions);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteAppliedRuleIds);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($quoteItemAppliedRuleIds);
        $quoteItemMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->any())->method('getAddress')->willReturn($quoteAddressMock);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $proceed = $this->createCallback($subjectMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteId)
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('hasSubscription')
            ->with($quoteItemMock)
            ->willReturn(true);

        $this->catalogRuleInspectorMock->expects($this->once())
            ->method('isApplied')
            ->with($productMock)
            ->willReturn($isCatalogRuleApplied);

        $this->subscriptionDiscountConfigMock->expects($this->any())
            ->method('isApplyDiscountToCatalogPrice')
            ->with($storeCode)
            ->willReturn($isApplyDiscountToCatalogPrice);

        $this->itemSubscriptionDiscountMock->expects($this->once())
            ->method('processSubscriptionDiscount')
            ->with($quoteItemMock, $itemBasePrice, $this->isType('callable'))
            ->willReturn($isApplyDiscountToCatalogPrice);

        $this->assertSame($subjectMock, $this->validatorPlugin->aroundProcess($subjectMock, $proceed, $quoteItemMock));
    }

    /**
     * @return array
     */
    public function aroundProcessDataProvider()
    {
        return [
            'Catalog rule not applied' => [
                'quoteItemAppliedRuleIds' => [31, 43, 54],
                'quoteAppliedRuleIds' => [1, 54],
                'addressRules' => [],
                'discountDescriptions' => ['discount' => 'description'],
                'itemBasePrice' => 50,
                'isCatalogRuleApplied' => false,
                'websiteId' => '1232',
                'storeCode' => 'mainCode',
                'isApplyDiscountToCatalogPrice' => false
            ],
            'Catalog rule applied: apply discount to catalog price' => [
                'quoteItemAppliedRuleIds' => [456, 12],
                'quoteAppliedRuleIds' => [],
                'addressRules' => [5, 789],
                'discountDescriptions' => ['key' => 'value'],
                'itemBasePrice' => 70,
                'isCatalogRuleApplied' => true,
                'websiteId' => '456',
                'storeCode' => 'code',
                'isApplyDiscountToCatalogPrice' => true
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\StoreInterface
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(StoreInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    private function createQuoteAddressMock()
    {
        return $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDiscountDescriptionArray', 'getAppliedRuleIds', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds', 'getStore', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item\AbstractItem
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddress', 'getQuote', 'getAppliedRuleIds', 'getProduct', '__wakeup'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\SalesRule\Model\Validator
     */
    private function createSalesRuleValidatorMock()
    {
        return $this->getMockBuilder(SalesRuleValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|\Magento\SalesRule\Model\Validator $subject
     * @return callable
     */
    private function createCallback($subject)
    {
        return function ($item) use ($subject) {
            return $subject;
        };
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }
}
