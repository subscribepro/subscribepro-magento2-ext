<?php

namespace Swarming\SubscribePro\Platform\Cache\Type;

class Product extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{
    public const TYPE_IDENTIFIER = 'subscribe_pro_products';

    public const CACHE_TAG = 'SP_PRODUCTS';

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     * @codeCoverageIgnore
     */
    public function __construct(\Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
