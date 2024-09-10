<?php
namespace Kitchen\Shipping\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Framework\Exception\LocalizedException;

class CustomShip extends Action
{
    protected $resultJsonFactory;
    protected $quoteRepository;
    protected $checkoutSession;
    protected $cartManagement;
    protected $addressFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        CartManagementInterface $cartManagement,
        AddressFactory $addressFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->addressFactory = $addressFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $quoteId = $this->checkoutSession->getQuoteId();
            if (!$quoteId) {
                throw new LocalizedException(__('Quote not found.'));
            }

            $quote = $this->quoteRepository->getActive($quoteId);
            if (!$quote) {
                throw new LocalizedException(__('Active quote not found.'));
            }

            $postData = $this->getRequest()->getParams();
            $liftgate = isset($postData['liftgate']) ? (bool)$postData['liftgate'] : false;
            $delivery = isset($postData['delivery']) ? (bool)$postData['delivery'] : false;
            $selected_shipping_method = isset($postData['selected_shipping_method']) ? $postData['selected_shipping_method'] : null;

            if (!$selected_shipping_method) {
                throw new LocalizedException(__('No shipping method selected.'));
            }

            $shippingAddress = $quote->getShippingAddress();
            if (!$shippingAddress->getId()) {
                $shippingAddress = $this->addressFactory->create();
                $quote->setShippingAddress($shippingAddress);
            }

            $availableMethods = $shippingAddress->getAllShippingRates();
            $methodAvailable = true;
            foreach ($availableMethods as $method) {
                if ($method->getCode() == $selected_shipping_method) {
                    $methodAvailable = true;
                    break;
                }
            }
            
            if (!$methodAvailable) {
                throw new LocalizedException(__('Selected shipping method is not available.'));
            }
         
            $quote->setData('liftgate', $liftgate ? 1 : 0);
            $quote->setData('delivery_appointment', $delivery ? 1 : 0);

            $customFee = 0;
            if ($liftgate) {
                $customFee += 50; 
            }
            if ($delivery) {
                $customFee += 20; 
            }
            $quote->setData('custom_fee', $customFee);

            $shippingAddress->setShippingMethod($selected_shipping_method);
            $shippingAddress->setCollectShippingRates(true);

            $quote->collectTotals()->save();

            $this->quoteRepository->save($quote);

            $response = [
                'success' => true,
                'message' => __('Shipping details updated successfully.'),
                'new_shipping_price' => $quote->getData('custom_fee') 
            ];

            return $result->setData($response);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
