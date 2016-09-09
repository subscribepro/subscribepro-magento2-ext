<?php

namespace Swarming\SubscribePro\Test\Unit\Ui\ConfigProvider;

use Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount as SubscriptionDiscountConfig;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Framework\Locale\FormatInterface as LocaleFormatInterface;

class PriceConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig
     */
    protected $uiPriceConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Tax\Model\Config
     */
    protected $taxConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormatMock;

    protected function setUp()
    {
        $this->subscriptionDiscountConfigMock = $this->getMockBuilder(SubscriptionDiscountConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->taxConfigMock = $this->getMockBuilder(TaxConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->localeFormatMock = $this->getMockBuilder(LocaleFormatInterface::class)->getMock();

        $this->uiPriceConfig = new PriceConfig(
            $this->subscriptionDiscountConfigMock,
            $this->taxConfigMock,
            $this->localeFormatMock
        );
    }

    /**
     * @param bool $discountTax
     * @param bool $applyTaxAfterDiscount
     * @param bool $priceIncludesTax
     * @param string $priceDisplayType
     * @param bool $isApplyDiscountToCatalogPrice
     * @param string $discountMessage
     * @param string $priceFormat
     * @param array $result
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(
        $discountTax,
        $applyTaxAfterDiscount,
        $priceIncludesTax,
        $priceDisplayType,
        $isApplyDiscountToCatalogPrice,
        $discountMessage,
        $priceFormat,
        $result
    ) {
        $this->taxConfigMock->expects($this->once())->method('discountTax')->willReturn($discountTax);
        $this->taxConfigMock->expects($this->once())
            ->method('applyTaxAfterDiscount')
            ->willReturn($applyTaxAfterDiscount);
        $this->taxConfigMock->expects($this->once())->method('priceIncludesTax')->willReturn($priceIncludesTax);
        $this->taxConfigMock->expects($this->atLeastOnce())
            ->method('getPriceDisplayType')
            ->willReturn($priceDisplayType);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isApplyDiscountToCatalogPrice')
            ->willReturn($isApplyDiscountToCatalogPrice);
        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('getDiscountMessage')
            ->willReturn($discountMessage);

        $this->localeFormatMock->expects($this->once())
            ->method('getPriceFormat')
            ->willReturn($priceFormat);

        $this->assertEquals($result, $this->uiPriceConfig->getConfig());
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            'Price display type including tax' => [
                'discountTax' => true,
                'applyTaxAfterDiscount' => false,
                'priceIncludesTax' => true,
                'priceDisplayType' => TaxConfig::DISPLAY_TYPE_INCLUDING_TAX,
                'isApplyDiscountToCatalogPrice' => false,
                'discountMessage' => 'discount message',
                'priceFormat' => 'price%currency',
                'result' => [
                    'discountTax' => true,
                    'applyTaxAfterDiscount' => false,
                    'priceIncludesTax' => true,
                    'displayPriceIncludingTax' => true,
                    'applyDiscountToCatalogPrice' => false,
                    'discountMessage' => 'discount message',
                    'priceFormat' => 'price%currency',
                ]
            ],
            'Price display type both' => [
                'discountTax' => false,
                'applyTaxAfterDiscount' => true,
                'priceIncludesTax' => false,
                'priceDisplayType' => TaxConfig::DISPLAY_TYPE_BOTH,
                'isApplyDiscountToCatalogPrice' => true,
                'discountMessage' => 'message',
                'priceFormat' => '%price',
                'result' => [
                    'discountTax' => false,
                    'applyTaxAfterDiscount' => true,
                    'priceIncludesTax' => false,
                    'displayPriceIncludingTax' => true,
                    'applyDiscountToCatalogPrice' => true,
                    'discountMessage' => 'message',
                    'priceFormat' => '%price',
                ]
            ],
            'Price display type excluding tax' => [
                'discountTax' => true,
                'applyTaxAfterDiscount' => true,
                'priceIncludesTax' => true,
                'priceDisplayType' => TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX,
                'isApplyDiscountToCatalogPrice' => true,
                'discountMessage' => 'message_discount',
                'priceFormat' => '%price%price',
                'result' => [
                    'discountTax' => true,
                    'applyTaxAfterDiscount' => true,
                    'priceIncludesTax' => true,
                    'displayPriceIncludingTax' => false,
                    'applyDiscountToCatalogPrice' => true,
                    'discountMessage' => 'message_discount',
                    'priceFormat' => '%price%price',
                ]
            ],
        ];
    }
}
