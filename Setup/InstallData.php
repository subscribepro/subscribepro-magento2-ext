<?php

namespace Swarming\SubscribePro\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        $eavSetup->addAttribute(
            $entityTypeId,
            'subscription_enabled',
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'group' => 'Subscribe Pro',
                'label' => 'Subscription Enabled',
                'input' => 'select',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '0',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => 'simple,configurable,bundle,virtual,downloadable'
            ]
        );
        
        $setup->endSetup();
    }
}
