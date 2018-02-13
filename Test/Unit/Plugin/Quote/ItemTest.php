<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Quote;

use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Plugin\Quote\Item;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOptionMock;

class ItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Quote\Item
     */
    protected $quoteItemPlugin;

    protected function setUp()
    {
        $this->quoteItemPlugin = new Item();
    }

    public function testAroundCompareOptionsIfSubjectResultIsFalse()
    {
        $subjectResult = false;
        $options1 = ['info_buyRequest' => $this->createQuoteItemOptionMock()];
        $options2 = ['info_buyRequest' => $this->createQuoteItemOptionMock()];
        $subjectMock = $this->createQuoteItemMock();

        $proceed = $this->createProceedCallback($subjectResult);

        $this->assertFalse($this->quoteItemPlugin->aroundCompareOptions($subjectMock, $proceed, $options1, $options2));
    }

    public function testAroundCompareOptionsIfNoBuyRequest()
    {
        $subjectResult = true;
        $options1 = ['options1'];
        $options2 = ['options2'];
        $subjectMock = $this->createQuoteItemMock();

        $proceed = $this->createProceedCallback($subjectResult);

        $this->assertTrue($this->quoteItemPlugin->aroundCompareOptions($subjectMock, $proceed, $options1, $options2));
    }

    /**
     * @param array $buyRequestParams1
     * @param array $buyRequestParams2
     * @param bool $result
     * @dataProvider aroundCompareOptionsDataProvider
     */
    public function testAroundCompareOptions($buyRequestParams1, $buyRequestParams2, $result)
    {
        $subjectResult = true;
        $subjectMock = $this->createQuoteItemMock();

        $buyRequestMock1 = $this->createQuoteItemOptionMock();
        $buyRequestMock1->expects($this->any())->method('getValue')->willReturn(json_encode($buyRequestParams1));

        $buyRequestMock2 = $this->createQuoteItemOptionMock();
        $buyRequestMock2->expects($this->any())->method('getValue')->willReturn(json_encode($buyRequestParams2));

        $options1 = ['info_buyRequest' => $buyRequestMock1];
        $options2 = ['info_buyRequest' => $buyRequestMock2];

        $proceed = $this->createProceedCallback($subjectResult);

        $this->assertEquals(
            $result,
            $this->quoteItemPlugin->aroundCompareOptions($subjectMock, $proceed, $options1, $options2)
        );
    }

    /**
     * @return array
     */
    public function aroundCompareOptionsDataProvider()
    {
        return [
            'Buy request params are empty' => [
                'buyRequestParams1' => [],
                'buyRequestParams2' => [OptionProcessor::KEY_SUBSCRIPTION_OPTION => []],
                'result' => true
            ],
            'One of subscription options not set' => [
                'buyRequestParams1' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe'
                    ]
                ],
                'buyRequestParams2' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => []
                ],
                'result' => false
            ],
            'Subscription options not equal' => [
                'buyRequestParams1' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe'
                    ]
                ],
                'buyRequestParams2' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'onetime_purchase'
                    ]
                ],
                'result' => false
            ],
            'Subscription intervals not set' => [
                'buyRequestParams1' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe'
                    ]
                ],
                'buyRequestParams2' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe'
                    ]
                ],
                'result' => true
            ],
            'Subscription intervals are different' => [
                'buyRequestParams1' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe',
                        SubscriptionOptionInterface::INTERVAL => '4 days'
                    ]
                ],
                'buyRequestParams2' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe',
                        SubscriptionOptionInterface::INTERVAL => '3 days'
                    ]
                ],
                'result' => false
            ],
            'Subscription intervals are equal' => [
                'buyRequestParams1' => [
                    'key' => 'value',
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'some_option',
                        SubscriptionOptionInterface::INTERVAL => '2 days'
                    ]
                ],
                'buyRequestParams2' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'some_option',
                        SubscriptionOptionInterface::INTERVAL => '2 days'
                    ]
                ],
                'result' => true
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item\Option
     */
    private function createQuoteItemOptionMock()
    {
        return $this->getMockBuilder(QuoteItemOptionMock::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param bool $result
     * @return callable
     */
    private function createProceedCallback($result)
    {
        return function ($item) use ($result) {
            return $result;
        };
    }
}
