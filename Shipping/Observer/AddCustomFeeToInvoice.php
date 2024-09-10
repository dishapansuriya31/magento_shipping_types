<?php
namespace Kitchen\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AddCustomFeeToInvoice implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $customFee = $order->getCustomFee();
        if ($customFee) {
            $invoice->setCustomFee($customFee);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $customFee);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $customFee);
        }
        return $this;
    }
}
