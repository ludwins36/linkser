<?php
namespace Vexsoluciones\Linkser\Controller\Payment;

/*
 *  Modulo Payment Credomatic
 * 	VexSoluciones
 * */
 

class GetAtt extends \Magento\Framework\App\Action\Action
{

    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_checkoutSession;  

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, 
        \Magento\Framework\Json\EncoderInterface $encoder
    ) {    
		$this->_checkoutSession = $checkoutSession;
        $this->_context = $context;
        $this->_pageFactory = $pageFactory;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->_jsonEncoder = $encoder;        
        parent::__construct($context);
    }
    
 
    
    public function execute() 
    {
        $totals = $this->_checkoutSession->getQuote()->getData('base_grand_total');
        $datos = array("id" => $this->_checkoutSession->getQuote()->getData('entity_id'),
        				"totalp" => $totals
        				);
                    
        $result = $this->resultJsonFactory->create();
        return $result->setData($datos); 
    }
}
