<?php

namespace Swarming\SubscribePro\Api\Data;

/**
 * Subscribe Pro Product interface.
 *
 * @api
 */
interface ProductInterface extends \SubscribePro\Service\Product\ProductInterface
{
    /**
     * Constants used as data array keys
     */
    const URL = 'url';

    const IMAGE_URL = 'image_url';

    /**
     * @return string|null
     */
    public function getUrl();

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url);

    /**
     * @return string|null
     */
    public function getImageUrl();

    /**
     * @param string $imageUrl
     * @return $this
     */
    public function setImageUrl($imageUrl);
}
