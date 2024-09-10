<?php

namespace Kitchen\Shipping\Block\Sales\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;

class Fee extends Template
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get order object
     *
     * @return Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getParentBlock()->getOrder();
        }
        return $this->_order;
    }

    /**
     * Initialize custom fee total
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $order = $this->getOrder();

        // Retrieve custom fee from quote (assuming it's stored in the order)
        $customFee = $order->getData('custom_fee');

        if ($customFee !== null) {
            // Create a new DataObject to hold the fee data
            $fee = new DataObject([
                'code' => 'custom_fee',
                'strong' => false,
                'value' => $customFee,
                'label' => __('Custom Fee'),
            ]);

            // Add the fee total to the parent block
            $parent->addTotal($fee, 'custom_fee');
        }

        return $this;
    }
}
