<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use Swarming\SubscribePro\Helper\SubscriptionProduct;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;
use Magento\Catalog\Model\Product\Url as ProductUrl;
use Magento\Catalog\Helper\Image as ImageHelper;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Block\Product\Subscription as SubscriptionBlock;
use Swarming\SubscribePro\Model\Subscription\OptionItemManager as SubscriptionOptionItemManager;
use Magento\Catalog\Helper\Product\ConfigurationPool as ProductConfigurationPool;
use Swarming\SubscribePro\Model\Subscription\OptionItem as SubscriptionOptionItem;
use Magento\Catalog\Helper\Product\Configuration\ConfigurationInterface as ProductConfigurationInterface;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface as CatalogRuleInspectorInterface;

class SubscriptionProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\SubscriptionProduct
     */
    protected $subscriptionProductHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Subscription\OptionItemManager
     */
    protected $subscriptionOptionItemManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Helper\Product\ConfigurationPool
     */
    protected $productConfigurationPoolMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Url
     */
    protected $productUrlModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Helper\Image
     */
    protected $imageHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculationMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\CatalogRule\InspectorInterface
     */
    protected $catalogRuleInspectorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrencyMock;

    protected function setUp(): void
    {
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->subscriptionOptionItemManagerMock = $this->getMockBuilder(SubscriptionOptionItemManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->productConfigurationPoolMock = $this->getMockBuilder(ProductConfigurationPool::class)
            ->disableOriginalConstructor()->getMock();
        $this->productUrlModelMock = $this->getMockBuilder(ProductUrl::class)
            ->disableOriginalConstructor()->getMock();
        $this->imageHelperMock = $this->getMockBuilder(ImageHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->taxCalculationMock = $this->getMockBuilder(TaxCalculationInterface::class)->getMock();
        $this->catalogRuleInspectorMock = $this->getMockBuilder(CatalogRuleInspectorInterface::class)->getMock();
        $this->priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)->getMock();

        $this->subscriptionProductHelper = new SubscriptionProduct(
            $this->platformProductManagerMock,
            $this->subscriptionOptionItemManagerMock,
            $this->productUrlModelMock,
            $this->imageHelperMock,
            $this->productConfigurationPoolMock,
            $this->taxCalculationMock,
            $this->catalogRuleInspectorMock,
            $this->priceCurrencyMock
        );
    }

    public function testLinkProductsIfFailToGetPlatformProduct()
    {
        $exception = new NoSuchEntityException(__('error'));

        $sku = 'product_sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->any())->method('getProductSku')->willReturn($sku);
        $subscriptionMock->expects($this->never())->method('setProduct');
        $subscriptions = [$subscriptionMock];

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willThrowException($exception);

        $this->assertEquals(
            $subscriptions,
            $this->subscriptionProductHelper->linkProducts($subscriptions)
        );
    }

    public function testLinkProductsIfNoMagentoProduct()
    {
        $defaultUrl = 'default/image';

        $sku = 'product_sku';
        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('setImageUrl')->with($defaultUrl)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with(0)->willReturnSelf();
        $platformProductMock->expects($this->never())->method('getIsDiscountPercentage');
        $platformProductMock->expects($this->never())->method('setUrl');
        $platformProductMock->expects($this->never())->method('setFinalPrice');
        $platformProductMock->expects($this->never())->method('setTaxRate');
        $platformProductMock->expects($this->never())->method('setOptionList');
        $platformProductMock->expects($this->never())->method('setIsCatalogRuleApplied');

        $subscriptionOptionItemMock = $this->createSubscriptionOptionItemMock();
        $subscriptionOptionItemMock->expects($this->once())->method('getProduct')->willReturn(null);

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->any())->method('getProductSku')->willReturn($sku);
        $subscriptionMock->expects($this->once())->method('setProduct')->with($platformProductMock);
        $subscriptions = [$subscriptionMock];

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionItemManagerMock->expects($this->once())
            ->method('getSubscriptionOptionItem')
            ->with($subscriptionMock)
            ->willReturn($subscriptionOptionItemMock);

        $this->imageHelperMock->expects($this->once())
            ->method('getDefaultPlaceholderUrl')
            ->with('thumbnail')
            ->willReturn($defaultUrl);

        $this->productUrlModelMock->expects($this->never())->method('getProductUrl');
        $this->taxCalculationMock->expects($this->never())->method('getCalculatedRate');
        $this->priceCurrencyMock->expects($this->never())->method('convertAndRound');
        $this->catalogRuleInspectorMock->expects($this->never())->method('isApplied');

        $this->assertEquals(
            $subscriptions,
            $this->subscriptionProductHelper->linkProducts($subscriptions)
        );
    }

    public function testLinkProductsIfProductNotVisibleInSite()
    {
        $imageUrl = 'url/image';
        $platformDiscount = 10;
        $finalPrice = 90;
        $convertedFinalPrice = 1200;
        $taxRate = 8;
        $taxClassId = 'taxable';
        $productTypeId = 'some_type';
        $isCatalogRuleApplied = true;
        $options = ['options'];

        $customAttributeMock = $this->createCustomAttributeMock();
        $customAttributeMock->expects($this->once())->method('getValue')->willReturn($taxClassId);

        $sku = 'product_sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);
        $productMock->expects($this->once())->method('getFinalPrice')->willReturn($finalPrice);
        $productMock->expects($this->any())->method('getTypeId')->willReturn($productTypeId);
        $productMock->expects($this->any())->method('isVisibleInSiteVisibility')->willReturn(false);
        $productMock->expects($this->once())
            ->method('getCustomAttribute')
            ->with(SubscriptionBlock::TAX_CLASS_ID)
            ->willReturn($customAttributeMock);

        $subscriptionOptionItemMock = $this->createSubscriptionOptionItemMock();
        $subscriptionOptionItemMock->expects($this->any())->method('getProduct')->willReturn($productMock);

        $configurationMock = $this->createProductConfigurationMock();
        $configurationMock->expects($this->once())
            ->method('getOptions')
            ->with($subscriptionOptionItemMock)
            ->willReturn($options);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($platformDiscount);
        $platformProductMock->expects($this->once())->method('setDiscount')->willReturn($platformDiscount);
        $platformProductMock->expects($this->once())->method('getIsDiscountPercentage')->willReturn(true);
        $platformProductMock->expects($this->once())->method('setUrl')->with(null)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setImageUrl')->with($imageUrl)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with($convertedFinalPrice)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setTaxRate')->with($taxRate)->willReturnSelf();
        $platformProductMock->expects($this->once())
            ->method('setIsCatalogRuleApplied')
            ->with($isCatalogRuleApplied)
            ->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setOptionList')->with($options)->willReturnSelf();

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->any())->method('getProductSku')->willReturn($sku);
        $subscriptionMock->expects($this->once())->method('setProduct')->with($platformProductMock);
        $subscriptions = [$subscriptionMock];

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionItemManagerMock->expects($this->once())
            ->method('getSubscriptionOptionItem')
            ->with($subscriptionMock)
            ->willReturn($subscriptionOptionItemMock);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($finalPrice)
            ->willReturn($convertedFinalPrice);

        $this->catalogRuleInspectorMock->expects($this->once())
            ->method('isApplied')
            ->with($productMock)
            ->willReturn($isCatalogRuleApplied);

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($productMock, 'product_thumbnail_image')
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())->method('getUrl')->willReturn($imageUrl);

        $this->productConfigurationPoolMock->expects($this->once())
            ->method('getByProductType')
            ->with($productTypeId)
            ->willReturn($configurationMock);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxClassId)
            ->willReturn($taxRate);

        $this->productUrlModelMock->expects($this->never())->method('getProductUrl');

        $this->assertEquals(
            $subscriptions,
            $this->subscriptionProductHelper->linkProducts($subscriptions)
        );
    }

    public function testLinkProducts()
    {
        $imageUrl = 'url/image';
        $platformDiscount = 10;
        $productUrl = 'product/url';
        $finalPrice = 90;
        $convertedFinalPrice = 1200;
        $taxRate = 8;
        $taxClassId = 'taxable';
        $productTypeId = 'some_type';
        $isCatalogRuleApplied = true;
        $options = ['options'];

        $customAttributeMock = $this->createCustomAttributeMock();
        $customAttributeMock->expects($this->once())->method('getValue')->willReturn($taxClassId);

        $sku = 'product_sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);
        $productMock->expects($this->once())->method('getFinalPrice')->willReturn($finalPrice);
        $productMock->expects($this->any())->method('getTypeId')->willReturn($productTypeId);
        $productMock->expects($this->any())->method('isVisibleInSiteVisibility')->willReturn(true);
        $productMock->expects($this->once())
            ->method('getCustomAttribute')
            ->with(SubscriptionBlock::TAX_CLASS_ID)
            ->willReturn($customAttributeMock);

        $subscriptionOptionItemMock = $this->createSubscriptionOptionItemMock();
        $subscriptionOptionItemMock->expects($this->any())->method('getProduct')->willReturn($productMock);

        $configurationMock = $this->createProductConfigurationMock();
        $configurationMock->expects($this->once())
            ->method('getOptions')
            ->with($subscriptionOptionItemMock)
            ->willReturn($options);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($platformDiscount);
        $platformProductMock->expects($this->once())->method('setDiscount')->willReturn($platformDiscount);
        $platformProductMock->expects($this->once())->method('getIsDiscountPercentage')->willReturn(true);
        $platformProductMock->expects($this->once())->method('setUrl')->with($productUrl)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setImageUrl')->with($imageUrl)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with($convertedFinalPrice)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setTaxRate')->with($taxRate)->willReturnSelf();
        $platformProductMock->expects($this->once())
            ->method('setIsCatalogRuleApplied')
            ->with($isCatalogRuleApplied)
            ->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setOptionList')->with($options)->willReturnSelf();

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->any())->method('getProductSku')->willReturn($sku);
        $subscriptionMock->expects($this->once())->method('setProduct')->with($platformProductMock);
        $subscriptions = [$subscriptionMock];

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionItemManagerMock->expects($this->once())
            ->method('getSubscriptionOptionItem')
            ->with($subscriptionMock)
            ->willReturn($subscriptionOptionItemMock);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($finalPrice)
            ->willReturn($convertedFinalPrice);

        $this->catalogRuleInspectorMock->expects($this->once())
            ->method('isApplied')
            ->with($productMock)
            ->willReturn($isCatalogRuleApplied);

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($productMock, 'product_thumbnail_image')
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())->method('getUrl')->willReturn($imageUrl);

        $this->productUrlModelMock->expects($this->once())
            ->method('getProductUrl')
            ->with($productMock)
            ->willReturn($productUrl);

        $this->productConfigurationPoolMock->expects($this->once())
            ->method('getByProductType')
            ->with($productTypeId)
            ->willReturn($configurationMock);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxClassId)
            ->willReturn($taxRate);

        $this->assertEquals(
            $subscriptions,
            $this->subscriptionProductHelper->linkProducts($subscriptions)
        );
    }

    public function testLinkProductsIfFixedDiscount()
    {
        $imageUrl = 'url/image';
        $discount = 100;
        $platformDiscount = 100.24;
        $productUrl = 'product/url';
        $finalPrice = 90;
        $convertedFinalPrice = 1200;
        $taxRate = 8;
        $taxClassId = 'taxable';
        $productTypeId = 'some_type';
        $isCatalogRuleApplied = false;
        $options = ['options'];

        $customAttributeMock = $this->createCustomAttributeMock();
        $customAttributeMock->expects($this->once())->method('getValue')->willReturn($taxClassId);

        $sku = 'product_sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);
        $productMock->expects($this->once())->method('getFinalPrice')->willReturn($finalPrice);
        $productMock->expects($this->any())->method('getTypeId')->willReturn($productTypeId);
        $productMock->expects($this->any())->method('isVisibleInSiteVisibility')->willReturn(true);
        $productMock->expects($this->once())
            ->method('getCustomAttribute')
            ->with(SubscriptionBlock::TAX_CLASS_ID)
            ->willReturn($customAttributeMock);

        $subscriptionOptionItemMock = $this->createSubscriptionOptionItemMock();
        $subscriptionOptionItemMock->expects($this->any())->method('getProduct')->willReturn($productMock);

        $configurationMock = $this->createProductConfigurationMock();
        $configurationMock->expects($this->once())
            ->method('getOptions')
            ->with($subscriptionOptionItemMock)
            ->willReturn($options);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($platformDiscount);
        $platformProductMock->expects($this->once())->method('setDiscount')->willReturn($discount);
        $platformProductMock->expects($this->once())->method('getIsDiscountPercentage')->willReturn(false);
        $platformProductMock->expects($this->once())->method('setUrl')->with($productUrl)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setImageUrl')->with($imageUrl)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with($convertedFinalPrice)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setTaxRate')->with($taxRate)->willReturnSelf();
        $platformProductMock->expects($this->once())
            ->method('setIsCatalogRuleApplied')
            ->with($isCatalogRuleApplied)
            ->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setOptionList')->with($options)->willReturnSelf();

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->any())->method('getProductSku')->willReturn($sku);
        $subscriptionMock->expects($this->once())->method('setProduct')->with($platformProductMock);
        $subscriptions = [$subscriptionMock];

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionItemManagerMock->expects($this->once())
            ->method('getSubscriptionOptionItem')
            ->with($subscriptionMock)
            ->willReturn($subscriptionOptionItemMock);

        $this->priceCurrencyMock->expects($this->at(0))
            ->method('convertAndRound')
            ->with($finalPrice)
            ->willReturn($convertedFinalPrice);
        $this->priceCurrencyMock->expects($this->at(1))
            ->method('convertAndRound')
            ->with($platformDiscount)
            ->willReturn($discount);

        $this->catalogRuleInspectorMock->expects($this->once())
            ->method('isApplied')
            ->with($productMock)
            ->willReturn($isCatalogRuleApplied);

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($productMock, 'product_thumbnail_image')
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())->method('getUrl')->willReturn($imageUrl);

        $this->productUrlModelMock->expects($this->once())
            ->method('getProductUrl')
            ->with($productMock)
            ->willReturn($productUrl);

        $this->productConfigurationPoolMock->expects($this->once())
            ->method('getByProductType')
            ->with($productTypeId)
            ->willReturn($configurationMock);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxClassId)
            ->willReturn($taxRate);

        $this->assertEquals(
            $subscriptions,
            $this->subscriptionProductHelper->linkProducts($subscriptions)
        );
    }

    public function testLinkProductsIfMultipleSubscriptions()
    {
        $defaultUrl = 'default/image';
        $imageUrl = 'url/image';
        $discount = 100;
        $platformDiscount = 100.24;
        $productUrl = 'product/url';
        $finalPrice = 80;
        $convertedFinalPrice = 200;
        $taxRate = 8;
        $taxClassId = 'taxable';
        $isCatalogRuleApplied = false;
        $productTypeId = 'simple';
        $options1 = ['options'];

        $customAttributeMock = $this->createCustomAttributeMock();
        $customAttributeMock->expects($this->once())->method('getValue')->willReturn($taxClassId);

        $product1Sku = 'product_sku';
        $product1Mock = $this->createProductMock();
        $product1Mock->expects($this->once())->method('getFinalPrice')->willReturn($finalPrice);
        $product1Mock->expects($this->any())->method('getTypeId')->willReturn($productTypeId);
        $product1Mock->expects($this->any())->method('isVisibleInSiteVisibility')->willReturn(true);
        $product1Mock->expects($this->once())
            ->method('getCustomAttribute')
            ->with(SubscriptionBlock::TAX_CLASS_ID)
            ->willReturn($customAttributeMock);

        $platformProduct1Mock = $this->createPlatformProductMock();
        $platformProduct1Mock->expects($this->once())->method('getDiscount')->willReturn($platformDiscount);
        $platformProduct1Mock->expects($this->once())->method('setDiscount')->willReturn($discount);
        $platformProduct1Mock->expects($this->once())->method('getIsDiscountPercentage')->willReturn(false);
        $platformProduct1Mock->expects($this->once())->method('setUrl')->with($productUrl)->willReturnSelf();
        $platformProduct1Mock->expects($this->once())->method('setImageUrl')->with($imageUrl)->willReturnSelf();
        $platformProduct1Mock->expects($this->once())->method('setPrice')->with($convertedFinalPrice)->willReturnSelf();
        $platformProduct1Mock->expects($this->once())->method('setTaxRate')->with($taxRate)->willReturnSelf();
        $platformProduct1Mock->expects($this->once())
            ->method('setIsCatalogRuleApplied')
            ->with($isCatalogRuleApplied)
            ->willReturnSelf();
        $platformProduct1Mock->expects($this->once())->method('setOptionList')->with($options1)->willReturnSelf();

        $product2Sku = 'product2_sku';
        $platformProduct2Mock = $this->createPlatformProductMock();
        $platformProduct2Mock->expects($this->once())->method('setImageUrl')->with($defaultUrl)->willReturnSelf();
        $platformProduct2Mock->expects($this->once())->method('setPrice')->with(0)->willReturnSelf();
        $platformProduct2Mock->expects($this->never())->method('setUrl');
        $platformProduct2Mock->expects($this->never())->method('setTaxRate');
        $platformProduct2Mock->expects($this->never())->method('setOptionList');
        $platformProduct2Mock->expects($this->never())->method('setIsCatalogRuleApplied');
        $platformProduct2Mock->expects($this->never())->method('getIsDiscountPercentage');

        $subscriptionOptionItem1Mock = $this->createSubscriptionOptionItemMock();
        $subscriptionOptionItem1Mock->expects($this->any())->method('getProduct')->willReturn($product1Mock);

        $subscriptionOptionItem2Mock = $this->createSubscriptionOptionItemMock();
        $subscriptionOptionItem2Mock->expects($this->once())->method('getProduct')->willReturn(null);

        $configuration1Mock = $this->createProductConfigurationMock();
        $configuration1Mock->expects($this->once())
            ->method('getOptions')
            ->with($subscriptionOptionItem1Mock)
            ->willReturn($options1);

        $subscription1Mock = $this->createSubscriptionMock();
        $subscription1Mock->expects($this->any())->method('getProductSku')->willReturn($product1Sku);
        $subscription1Mock->expects($this->once())->method('setProduct')->with($platformProduct1Mock);

        $subscription2Mock = $this->createSubscriptionMock();
        $subscription2Mock->expects($this->any())->method('getProductSku')->willReturn($product2Sku);
        $subscription2Mock->expects($this->once())->method('setProduct')->with($platformProduct2Mock);

        $subscriptions = [$subscription1Mock, $subscription2Mock];

        $this->platformProductManagerMock->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturnMap([[$product1Sku, null, $platformProduct1Mock], [$product2Sku, null, $platformProduct2Mock]]);

        $this->subscriptionOptionItemManagerMock->expects($this->exactly(count($subscriptions)))
            ->method('getSubscriptionOptionItem')
            ->willReturnMap([
                [$subscription1Mock, $subscriptionOptionItem1Mock],
                [$subscription2Mock, $subscriptionOptionItem2Mock]
            ]);

        $this->priceCurrencyMock->expects($this->at(0))
            ->method('convertAndRound')
            ->with($finalPrice)
            ->willReturn($convertedFinalPrice);
        $this->priceCurrencyMock->expects($this->at(1))
            ->method('convertAndRound')
            ->with($platformDiscount)
            ->willReturn($discount);

        $this->catalogRuleInspectorMock->expects($this->once())
            ->method('isApplied')
            ->with($product1Mock)
            ->willReturn($isCatalogRuleApplied);

        $this->productConfigurationPoolMock->expects($this->once())
            ->method('getByProductType')
            ->with($productTypeId)
            ->willReturn($configuration1Mock);

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($product1Mock, 'product_thumbnail_image')
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('getDefaultPlaceholderUrl')
            ->with('thumbnail')
            ->willReturn($defaultUrl);
        $this->imageHelperMock->expects($this->once())->method('getUrl')->willReturn($imageUrl);

        $this->imageHelperMock->expects($this->once())
            ->method('getDefaultPlaceholderUrl')
            ->with('thumbnail')
            ->willReturn($defaultUrl);

        $this->productUrlModelMock->expects($this->once())
            ->method('getProductUrl')
            ->with($product1Mock)
            ->willReturn($productUrl);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxClassId)
            ->willReturn($taxRate);

        $this->assertEquals(
            $subscriptions,
            $this->subscriptionProductHelper->linkProducts($subscriptions)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(PlatformProductInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Subscription\OptionItem
     */
    private function createSubscriptionOptionItemMock()
    {
        return $this->getMockBuilder(SubscriptionOptionItem::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionInterface
     */
    private function createSubscriptionMock()
    {
        return $this->getMockBuilder(SubscriptionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Helper\Product\Configuration\ConfigurationInterface
     */
    private function createProductConfigurationMock()
    {
        return $this->getMockBuilder(ProductConfigurationInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Api\AttributeInterface
     */
    private function createCustomAttributeMock()
    {
        return $this->getMockBuilder(AttributeInterface::class)->getMock();
    }
}
