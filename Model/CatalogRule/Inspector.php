<?php

namespace Swarming\SubscribePro\Model\CatalogRule;

use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;

class Inspector implements InspectorInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\InspectorRepository
     */
    protected $catalogRuleInspectorRepository;

    /**
     * @param \Swarming\SubscribePro\Model\CatalogRule\InspectorRepository $catalogRuleInspectorRepository
     */
    public function __construct(
        \Swarming\SubscribePro\Model\CatalogRule\InspectorRepository $catalogRuleInspectorRepository
    ) {
        $this->catalogRuleInspectorRepository = $catalogRuleInspectorRepository;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isApplied($product)
    {
        $catalogRuleInspector = $this->catalogRuleInspectorRepository->get($product->getTypeId());
        return $catalogRuleInspector->isApplied($product);
    }
}
