<?php
namespace Kitchen\Shipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class AddCustomVariables implements ObserverInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        // Fetch and log the custom attributes
        $liftgate = (bool)$order->getData('liftgate'); // Cast to boolean
        $deliveryAppointment = (bool)$order->getData('delivery_appointment'); // Cast to boolean

        $this->logger->debug('Liftgate: ' . ($liftgate ? 'true' : 'false'));
        $this->logger->debug('Delivery Appointment: ' . ($deliveryAppointment ? 'true' : 'false'));

        // Set the attributes on the order object
        $order->setLiftgate($liftgate);
        $order->setDeliveryAppointment($deliveryAppointment);
    }
}