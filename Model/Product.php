<?php

namespace Swarming\SubscribePro\Model;

use Swarming\SubscribePro\Api\Data\ProductInterface;

class Product extends \SubscribePro\Service\Product\Product implements ProductInterface
{
    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->getData(self::URL);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @return string|null
     */
    public function getImageUrl()
    {
        return $this->getData(self::IMAGE_URL);
    }

    /**
     * @param string $imageUrl
     * @return $this
     */
    public function setImageUrl($imageUrl)
    {
        return $this->setData(self::IMAGE_URL, $imageUrl);
    }
}
