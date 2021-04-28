<?php

namespace Swarming\SubscribePro\Test\Unit\Ui\ConfigProvider;

use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\BlockInterface;
use Swarming\SubscribePro\Model\Config\SubscriptionOptions;
use Swarming\SubscribePro\Ui\ConfigProvider\SubscriptionConfig;
use Swarming\SubscribePro\Model\Config\SubscriptionOptions as SubscriptionOptionsConfig;

class SubscriptionConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Ui\ConfigProvider\SubscriptionConfig
     */
    protected $uiSubscriptionConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Element\BlockFactory
     */
    protected $blockFactoryMock;

    protected function setUp(): void
    {
        $this->subscriptionOptionConfigMock = $this->getMockBuilder(SubscriptionOptionsConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->blockFactoryMock = $this->getMockBuilder(BlockFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->uiSubscriptionConfig = new SubscriptionConfig(
            $this->subscriptionOptionConfigMock,
            $this->blockFactoryMock
        );
    }

    /**
     * @param string $blockHtml
     * @param bool $isAllowedCancel
     * @param array $result
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(
        $blockHtml,
        $isAllowedCancel,
        $result
    ) {
        $blockMock = $this->getMockBuilder(BlockInterface::class)->getMock();
        $blockMock->expects($this->once())->method('toHtml')->willReturn($blockHtml);

        $this->subscriptionOptionConfigMock->expects($this->once())
            ->method('isAllowedCancel')
            ->willReturn($isAllowedCancel);

        $this->blockFactoryMock->expects($this->once())
            ->method('createBlock')
            ->with(
                'Magento\Cms\Block\Block',
                ['data' => ['block_id' => SubscriptionConfig::BLOCK_NAME_CANCELLATION]]
            )
            ->willReturn($blockMock);

        $this->assertEquals($result, $this->uiSubscriptionConfig->getConfig());
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            'Block content is empty' => [
                'blockHtml' => '',
                'isAllowedCancel' => false,
                'result' => [
                    'isCancelAllowed' => false,
                    'minDaysToNextOrder' => SubscriptionOptions::QTY_MIN_DAYS_TO_NEXT_ORDER,
                    'cancelContent' => __('Are you sure you want to cancel the subscription?')
                ]
            ],
            'Block content is not empty' => [
                'blockHtml' => 'Some content for cancellation',
                'isAllowedCancel' => true,
                'result' => [
                    'isCancelAllowed' => true,
                    'minDaysToNextOrder' => SubscriptionOptions::QTY_MIN_DAYS_TO_NEXT_ORDER,
                    'cancelContent' => 'Some content for cancellation'
                ]
            ],
        ];
    }
}
