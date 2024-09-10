<?php
namespace Kitchen\Shipping\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Psr\Log\LoggerInterface;

class UpgradeData implements UpgradeDataInterface
{
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            $connection = $setup->getConnection();
            $quoteTable = $setup->getTable('quote');
            $quoteAddressTable = $setup->getTable('quote_address');

            try {
                // Copy custom_fee from quote to quote_address
                $select = $connection->select()
                    ->from($quoteTable, ['custom_fee', 'quote_id'])
                    ->where('custom_fee IS NOT NULL');

                $data = [];
                foreach ($connection->fetchAll($select) as $row) {
                    $data[] = [
                        'quote_id' => $row['quote_id'],
                        'custom_fee' => $row['custom_fee'],
                    ];
                }

                if (!empty($data)) {
                    $connection->insertMultiple($quoteAddressTable, $data);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }

        $setup->endSetup();
    }
}