<?php
namespace Kitchen\Shipping\Block\Order;

use Magento\Sales\Model\Order\Address;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\OrderFactory;

class Info extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'Kitchen_Shipping::order/info.phtml';
    protected $coreRegistry = null;
    protected $paymentHelper;
    protected $addressRenderer;
    protected $orderFactory;

    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        OrderFactory $orderFactory,
        AddressRenderer $addressRenderer,
        array $data = []
    ) {
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->coreRegistry = $registry;
        $this->orderFactory = $orderFactory;
        $this->_isScopePrivate = true;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getRealOrderId()));
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }

    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getFormattedAddress(Address $address)
    {
        return $this->addressRenderer->format($address, 'html');
    }

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
