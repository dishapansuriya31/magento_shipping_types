<?php

namespace Kitchen\Shipping\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\Result\Method;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;

class QuoteShippingMethodPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        RequestInterface $request
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->request = $request;
    }

    /**
     * Add a custom shipping price based on the selected shipping method and additional options.
     *
     * @param Result $subject
     * @param Result $result
     * @return Result
     * @throws NoSuchEntityException
     */
    public function afterGetRates(Result $subject, Result $result): Result
    {
        $quoteId = $this->request->getParam('quote_id');
        if (!$quoteId) {
            return $result;
        }

        try {
            $quote = $this->quoteRepository->getActive($quoteId);
        } catch (NoSuchEntityException $e) {
            return $result;
        }

        // Get selected shipping method from request
        $selectedShippingMethod = $this->request->getParam('selected_shipping_method');

        // Get additional options (liftgate and delivery appointment)
        $liftgate = $this->request->getParam('liftgate', false);
        $delivery = $this->request->getParam('delivery_appointment', false);

        // Calculate custom shipping price
        $customShippingPrice = $this->calculateCustomShippingPrice(
            $quote,
            $selectedShippingMethod,
            $liftgate,
            $delivery
        );

        // Update the selected shipping method with the custom price
        foreach ($result->getAllRates() as $shippingMethod) {
            if ($shippingMethod->getCode() === $selectedShippingMethod) {
                $shippingMethod->setPrice($customShippingPrice);
            }
        }

        return $result;
    }

    /**
     * Calculate the custom shipping price based on the quote, shipping method, and additional options.
     *
     * @param Quote $quote
     * @param string $selectedShippingMethod
     * @param bool $liftgate
     * @param bool $delivery
     * @return float
     */
    private function calculateCustomShippingPrice(
        Quote $quote,
        string $selectedShippingMethod,
        bool $liftgate,
        bool $delivery
    ): float {
        $shippingAddress = $quote->getShippingAddress();
        $shippingMethod = $shippingAddress->getShippingRateByCode($selectedShippingMethod);
        if (!$shippingMethod) {
            return 0.00;
        }
        $shippingPrice = $shippingMethod->getPrice();
        $liftgatePrice = $liftgate ? 50.00 : 0.00;
        $deliveryPrice = $delivery ? 20.00 : 0.00;
        return $shippingPrice + $liftgatePrice + $deliveryPrice;
    }
}