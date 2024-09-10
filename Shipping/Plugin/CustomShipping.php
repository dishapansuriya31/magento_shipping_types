<?php
namespace Kitchen\Shipping\Plugin;
 
 
class CustomShipping
 
{
 
  protected $checkoutSession;
 
 
  public function __construct(
 
    \Magento\Checkout\Model\Session $checkoutSession
 
  ) {
 
    $this->checkoutSession   = $checkoutSession;
 
  }
 
 
  public function afterGetConfig(
 
    \Magento\Checkout\Model\DefaultConfigProvider $subject,
 
    $output
 
  ) {
 
    $quote = $this->checkoutSession->getQuote();
 
    
   
    $output['liftgate'] = (int)$quote->getLiftgate();
    $output['delivery_appointment'] = (int)$quote->getDeliveryAppointment();
    

    return $output;
 
  }
 
 
   
 
}