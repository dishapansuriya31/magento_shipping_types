<?php
namespace Kitchen\Shipping\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;

class AddCustomFeeAttribute implements DataPatchInterface
{
    protected $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            \Magento\Sales\Model\Order::ENTITY,
            'custom_fee',
            [
                'type' => 'decimal',
                'label' => 'Custom Fee',
                'input' => 'text',
                'required' => false,
                'visible_on_front' => false,
                'sort_order' => 100,
                'position' => 100,
                'system' => false,
            ]
        );

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
