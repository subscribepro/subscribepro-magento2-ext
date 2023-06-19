<?php

namespace Swarming\SubscribePro\Ui\ConfigProvider;

use Swarming\SubscribePro\Model\Config\SubscriptionOptions;

class SubscriptionConfig
{
    public const BLOCK_NAME_CANCELLATION = 'subscribe_pro_cancel_popup';

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionConfig;

    /**
     * @var \Magento\Framework\View\Element\BlockFactory
     */
    protected $blockFactory;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionConfig
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionConfig,
        \Magento\Framework\View\Element\BlockFactory $blockFactory
    ) {
        $this->subscriptionOptionConfig = $subscriptionOptionConfig;
        $this->blockFactory = $blockFactory;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'isCancelAllowed' => $this->subscriptionOptionConfig->isAllowedCancel(),
            'minDaysToNextOrder' => SubscriptionOptions::QTY_MIN_DAYS_TO_NEXT_ORDER,
            'cancelContent' => $this->getCancellationContent()
        ];
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function getCancellationContent()
    {
        $cmsBlock = $this->blockFactory->createBlock(
            \Magento\Cms\Block\Block::class,
            ['data' => ['block_id' => self::BLOCK_NAME_CANCELLATION]]
        );
        $content = $cmsBlock->toHtml() ?: __('Are you sure you want to cancel the subscription?');
        return $content;
    }
}
