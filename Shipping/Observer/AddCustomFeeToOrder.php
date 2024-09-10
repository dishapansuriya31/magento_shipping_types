<?php
namespace Kitchen\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AddCustomFeeToOrder implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $customFee = $quote->getCustomFee();
        if ($customFee) {
            $order->setCustomFee($customFee);
            $order->setGrandTotal($order->getGrandTotal() + $customFee);
            $order->setBaseGrandTotal($order->getBaseGrandTotal() + $customFee);
        }
        return $this;
    }
}
