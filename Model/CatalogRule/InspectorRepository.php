<?php

namespace Swarming\SubscribePro\Model\CatalogRule;

use Magento\Framework\ObjectManagerInterface;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;

class InspectorRepository
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $defaultInspector;

    /**
     * @var array
     */
    protected $inspectors;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $defaultInspector
     * @param array $inspectors
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $defaultInspector,
        array $inspectors = []
    ) {
        $this->objectManager = $objectManager;
        $this->defaultInspector = $defaultInspector;
        $this->inspectors = $inspectors;
    }

    /**
     * @param string $productType
     * @return \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface
     */
    public function get($productType)
    {
        $inspector = isset($this->inspectors[$productType])
            ? $this->inspectors[$productType]
            : $this->defaultInspector;

        $inspector = $this->objectManager->get($inspector);

        if (!$inspector instanceof InspectorInterface) {
            throw new \InvalidArgumentException(
                'Catalog rule inspector must implement ' . InspectorInterface::class . ' interface'
            );
        }

        return $inspector;
    }
}
