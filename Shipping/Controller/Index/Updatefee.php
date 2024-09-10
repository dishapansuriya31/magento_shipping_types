<?php

namespace Kitchen\Shipping\Controller\Shipping;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;

class UpdateFee extends Action
{
    protected $checkoutSession;
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $request = $this->getRequest();
        $response = ['success' => false];

        try {
            $params = $request->getParams();
            $liftgate = isset($params['liftgate']) ? (bool) $params['liftgate'] : false;
            $delivery = isset($params['delivery']) ? (bool) $params['delivery'] : false;

            $quote = $this->checkoutSession->getQuote();

            $liftgateFee = $liftgate ? 50.00 : 0.00;
            $deliveryFee = $delivery ? 20.00 : 0.00;
            $totalFee = $liftgateFee + $deliveryFee;

            $quote->setCustomShippingFee($totalFee);
            $quote->collectTotals()->save();

            $response['success'] = true;
            $response['fee'] = $totalFee;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $resultJson->setData($response);
    }
}
