<?php
namespace Kitchen\Shipping\Controller\Shipping;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class UpdateCost extends Action
{
    protected $resultJsonFactory;
    protected $cartRepository;
    protected $checkoutSession;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CartRepositoryInterface $cartRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $additionalCost = $this->getRequest()->getParam('additionalCost', 0);
        $quote = $this->checkoutSession->getQuote();

        // Add the additional cost to the quote
        $quote->setShippingAddressCustomAmount($additionalCost);
        $quote->collectTotals();
        $this->cartRepository->save($quote);

        return $result->setData(['success' => true, 'additionalCost' => $additionalCost]);
    }
}
