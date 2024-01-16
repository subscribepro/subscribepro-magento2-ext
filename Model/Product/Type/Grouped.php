<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Product\Type;

use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

class Grouped extends \Magento\GroupedProduct\Model\Product\Type\Grouped
{
    /**
     * @param DataObject $buyRequest
     * @param $product
     * @param $processMode
     * @return Phrase|array|string
     */
    protected function _prepareProduct(DataObject $buyRequest, $product, $processMode): Phrase|array|string
    {
        $products = [];
        $associatedProductsInfo = [];
        $isStrictProcessMode = $this->_isStrictProcessMode($processMode);
        $subscriptionConfig = $buyRequest->getData('subscription_option');
        $productsInfo = $this->getProductInfo($buyRequest, $product, $isStrictProcessMode);
        if (is_string($productsInfo)) {
            return $productsInfo;
        }
        $associatedProducts = !$isStrictProcessMode || !empty($productsInfo)
            ? $this->getAssociatedProducts($product)
            : false;

        foreach ($associatedProducts as $subProduct) {
            $qty = $productsInfo[$subProduct->getId()];
            if (!is_numeric($qty) || empty($qty)) {
                continue;
            }

            $_result = $subProduct->getTypeInstance()->_prepareProduct($buyRequest, $subProduct, $processMode);

            if (is_string($_result)) {
                return $_result;
            } elseif (!isset($_result[0])) {
                return __('Cannot process the item.')->render();
            }

            if ($isStrictProcessMode) {
                $subscription = [];
                if (isset($subscriptionConfig[$subProduct->getId()])) {
                    $subscription = $subscriptionConfig[$subProduct->getId()];
                }
                $_result[0]->setCartQty($qty);
                $_result[0]->addCustomOption('product_type', self::TYPE_CODE, $product);
                $_result[0]->addCustomOption(
                    'info_buyRequest',
                    $this->serializer->serialize(
                        [
                            'super_product_config' => [
                                'product_type' => self::TYPE_CODE,
                                'product_id' => $product->getId(),
                            ],
                            'subscription_option' => $subscription
                        ]
                    )
                );
                $products[] = $_result[0];
            } else {
                $associatedProductsInfo[] = [$subProduct->getId() => $qty];
                $product->addCustomOption('associated_product_' . $subProduct->getId(), $qty);
            }
        }

        if (!$isStrictProcessMode || count($associatedProductsInfo)) {
            $product->addCustomOption('product_type', self::TYPE_CODE, $product);
            $product->addCustomOption('info_buyRequest', $this->serializer->serialize($buyRequest->getData()));

            $products[] = $product;
        }

        if (count($products)) {
            return $products;
        }

        return __('Please specify the quantity of product(s).')->render();
    }
}
