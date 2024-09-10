<?php

namespace Kitchen\Shipping\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class CustomFee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $invoice->setCustomFee($order->getCustomFee());

        if ($order->getCustomFee()) {
            $invoice->setGrandTotal($invoice->getGrandTotal() + $order->getCustomFee());
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $order->getCustomFee());
        }
        return $this;
    }
}