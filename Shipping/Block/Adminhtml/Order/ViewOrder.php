<?php
/**
* Copyright © Magento, Inc. All rights reserved.
* See COPYING.txt for license details.
*/
namespace Kitchen\Shipping\Block\Adminhtml\Order;
 
/**
* Adminhtml order abstract block
*
* @api
* @author      Magento Core Team <core@magentocommerce.com>
* @since 100.0.2
*/
class ViewOrder extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    
   
  
    public function isLiftgateRequired()
    {
        $order = $this->getOrder();
        if ($order && $order->getLiftgate() == 1) {
            return true;
        }
        return false;
    }

    public function isDeliveryAppointmentRequired()
    {
        $order = $this->getOrder();
        if ($order && $order->getDeliveryAppointment() == 1) {
            return true;
        }
        return false;
    }
}