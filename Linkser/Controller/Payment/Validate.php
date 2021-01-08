<?php
/**
 * Copyright � 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Vexsoluciones\Linkser\Controller\Payment;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order;
use Vexsoluciones\Linkser\Controller\Payment\Bins;
use Vexsoluciones\Linkser\Controller\Payment\Soap;


class Validate extends \Magento\Framework\App\Action\Action
{
	protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    
    protected $_checkoutSession;  
    protected $order;
	protected $customerSession;
	private $orderRepository;
	private $log;
    protected $_storeManager;
	
    protected $_orderAmount;  
    protected $soapClientFactory;  

    protected $_productRepository;  
    protected $_soap;  
	
    public function __construct(
    \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
    \Magento\Sales\Model\Order $_orderAmount,
	\Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
	\Magento\Customer\Model\Session $customerSession,
    \Magento\Sales\Model\Order\Address $order,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Checkout\Model\Session $checkoutSession,
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\View\Result\PageFactory $pageFactory,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, 
    \Magento\Framework\Json\EncoderInterface $encoder
    // \Magento\Catalog\Model\ProductRepository $productRepository
    ) {

        $this->soapClientFactory = $soapClientFactory;
	    $this->_orderAmount = $_orderAmount;
	    $this->_storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
	    $this->customerSession = $customerSession;
	    $this->order = $order;
	    $this->scopeConfig = $scopeConfig;
	    $this->_checkoutSession = $checkoutSession;
        $this->_context = $context;
        $this->_pageFactory = $pageFactory;
	    $this->resultJsonFactory = $resultJsonFactory;
	    $this->_jsonEncoder = $encoder;  
	    // $this->_productRepository = $productRepository;  
        parent::__construct($context);
    }

    public function execute() 
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productRepository = $objectManager->get('Magento\Catalog\Model\ProductRepository');
        $data = json_decode($_POST["u_data"], true);
        // $order_id = $data['idorder'];
        $country = "Bolivia";
        $binCountry = '';
        $reference = $this->_checkoutSession->getLastRealOrder();
        $reference_code =  $reference->getIncrementId();
        $order_id = $reference->getEntityId(); 
        $order = $this->orderRepository->get($order_id);
        // $address = $order->getBillingAddress();
        // $items =  $order->getItemsCollection();
        // $items2 = $order->getAllItems();
        // $items3 = $order->getAllVisibleItems();
        $total = $data['totalsp'];
        $result = $this->resultJsonFactory->create();

        $bin  = new Bins();
        $quota = $this->_checkoutSession->getQuote();
        if(isset($dataBin->country->name)){
            $binCountry = (string)$dataBin->country->name;
        }
        
		// $currency_system = $this->_storeManager->getStore()->getBaseCurrencyCode();  // OBTENER SIGLAS DE LA MONEDA CONFIGURADA EN LA TIENDA
		$currency_system = 'BOB';  // OBTENER SIGLAS DE LA MONEDA CONFIGURADA EN LA TIENDA
		$numeric_code_currency = array(
		    'USD' => '840', //	Dolar estadounidense
		    'BOB' => '068' //	BOLIVIANO
        );

        if((bool)$this->getConfigData('testmode')){
            $wsdl = 'https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.155.wsdl';
            $url = "https://lnksrvssaup2.linkser.com.bo:9483/wsComercioEcomme/ServiciosEcommeLNK?wsdl";

        }else{
            $wsdl = 'https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.155.wsdl';
            $url = 'https://lnksrvws1.linkser.com.bo:9483/wsComercioEcomme/ServiciosEcommeLNK?wsdl';

        }

        $customer = $this->customerSession->getCustomer();

        
        if(!$bin->validate_bin_linkser($data['cc_bin'])){
            $fingerprint = strtotime('now');

            $request = new \stdClass();
            $request->merchantID = $this->getConfigData('merchan_cyber');
            $request->merchantReferenceCode = $order_id;
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();
            
            $ccAuthService = new \stdClass();
			$ccAuthService->run = "true";
            $request->ccAuthService = $ccAuthService;
            
            $item = array();
            $request->deviceFingerprintID = $fingerprint;
            $request->device_fingerprint_id = $fingerprint;
            $request->RequestMessage = new \stdClass();
            $request->RequestMessage->deviceFingerprintID = $fingerprint;
            $allItems = $order->getAllVisibleItems();
            $address = $order->getBillingAddress();
            $i = 0;

            if(count($allItems) > 0){
                foreach ($allItems as $item) {
                    $productId = $item->getProductId();
                    $produc = $productRepository->getById($productId);
                    $request->{"item_" . $i . "_name"}  	    = $produc->getName();
				    $request->{"item_" . $i . "_productSKU"}  	= $produc->getSku();
				    $request->{"item_" . $i . "_unitPrice"}  	= $produc->getFinalPrice();
				    // $request->{"item_" . $i . "_quantity"}  	= $item->getQty();
                    $request->{"item_" . $i . "_productCode"}  	= $productId;
                    $i++;
                    // print_r(var_dump($item->getProductId()));
                    // $cart->removeItem($itemId)->save();
                }
            }

            $billTo = new \stdClass();
			$billTo->firstName = $customer->getFirstname();
			$billTo->lastName = $customer->getLastname();
			$billTo->street1 = $address->getData('street');
			$billTo->city = $address->getData('city');
			$billTo->state = $address->getData('region');
            
			$billTo->postalCode =  '0000';
			$billTo->country = 'BOL';
			$billTo->email = $customer->getEmail();
			$billTo->ipAddress = $_SERVER['REMOTE_ADDR'];
            $request->billTo = $billTo;
            
            $card = new \stdClass();
			$card->accountNumber = $data['cc_card'];
			$card->expirationMonth = strlen($data['cc_month']) == 1? '0'.$data['cc_month'] : $data['cc_month'];
			$card->expirationYear = $data['cc_year'];
			$card->cvNumber = $data['cc_vv'];
            $request->card = $card;

            $purchaseTotals = new \stdClass();
			$purchaseTotals->currency = strtoupper($currency_system);
			$purchaseTotals->grandTotalAmount = $total;
            $request->purchaseTotals = $purchaseTotals;
            $SoapClient = new Soap($wsdl, array());
        
            $request->offer0 = $purchaseTotals;					
            $ccCaptureService = new \stdClass();
            $ccCaptureService->run = "true";
            $request->ccCaptureService = $ccCaptureService;
            $capture_reply = $SoapClient->runTransaction($request);
            if ($capture_reply->reasonCode == 100) {
                $Orstatus = $this->getConfigData('order_status_aceptado');
			    $this->UpdateStatus($order_id, $Orstatus);
                $tum = array("code" => 0);
            }else{
                $Orstatus = $this->getConfigData('order_status_cancelado');
                $this->UpdateStatus($order_id, $Orstatus);
                $tum = array("code" => 1, "message" => "Error al procesar el pago.");
            }
               

            

            
            return $result->setData($tum); 

            
        }else{
            if (array_key_exists($currency_system, $numeric_code_currency)) {
				//Verdadero si existe la moneda en el array
		        $currency = $numeric_code_currency[$currency_system];
            } else{
                $currency = '840';
            }

            $_directoryList = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
            $dirJAva = $_directoryList->getPath('app') . '/code/Vexsoluciones/Linkser/Java/Criptografia.jar';
            $dirKeyLinkser = $_directoryList->getPath('media') . '/keys_linkser/public_linkser/publica.rsa';
            $dirKeyPublic = $_directoryList->getPath('media') . '/keys_linkser/public_store/publica.rsa';
            $dirKeyPrivate = $_directoryList->getPath('media') . '/keys_linkser/private_store/privada.rsa';

            $code = $this->getConfigData('code_id');
            $secret = $this->getConfigData('secret_key');
            $termial = $this->getConfigData('terminal_id');
            $key = $this->getConfigData('key');

            $codInstitucionEncriptadoStr = $this->encriptarData($dirJAva, $dirKeyLinkser, $code);
            $llavePublicaStr = shell_exec("java -jar {$dirJAva} Pu {$dirKeyPublic}  2>&1");

            $llaveRegisroEncriptadoStr = $this->encriptarData($dirJAva, $dirKeyLinkser, $key);
            $SoapResult = $this->soapClientFactory->create($url);
            $reto = $SoapResult->getReto(array());
            
            $setKey = $SoapResult->setRegistrar(
                array(
                'cod_institucion'=> $codInstitucionEncriptadoStr, 
                'llave_publica'=> $llavePublicaStr, 
                'llave_registro'=> $llaveRegisroEncriptadoStr
                )
            );
           
            if($setKey instanceof SoapFault){
                $tum = array("code" => '1', "message" => "Error del Sistema.");
                return $result->setData($tum); 
            }

            $numeroTarjetaEncriptadoStr = $this->encriptarData($dirJAva, $dirKeyLinkser, $data['cc_card']);
            $cvv2EncriptadoStr = $this->encriptarData($dirJAva, $dirKeyLinkser, $data['cc_vv']);

            $punto =  number_format((float)$total, 2, '.', ''); 
            $amount_format = str_pad($punto, 13, "0", STR_PAD_LEFT);
            $amount = str_replace ( ".", "", $amount_format);

           
            $dataExpiry = trim($data['cc_year']) . trim(strlen($data['cc_month']) == 1? '0'.$data['cc_month'] : $data['cc_month']);
            $fechaExpiraEncriptadoStr = $this->encriptarData($dirJAva, $dirKeyLinkser, $dataExpiry);

            $order_id = str_pad($order_id, 6, "0", STR_PAD_LEFT);
           
            

           
            $secuencia = substr($order_id, 0, 6);
            $fecha = date('Ymd');
            $hora = date('His');
            $validation = $this->getValidateFirma($dirJAva, $dirKeyPrivate, $code, $reto->return);
            
            $params = array(
                'cod_institucion'=> $codInstitucionEncriptadoStr, 
                'secuencia'=> $secuencia,
                'cod_comercio'=> $secret, 
                'cod_terminal'=>$termial, 
                'tarjeta'=> $numeroTarjetaEncriptadoStr, 
                'nombre_cliente'=> $customer->getFirstname() . ' ' . $customer->getLastname(), 
                'fecha_expiracion'=> $fechaExpiraEncriptadoStr, 
                'cvv2'=> $cvv2EncriptadoStr, 
                'monto'=> $amount, 
                'moneda'=> $currency, 
                'fecha_envio'=> $fecha, 
                'hora_envio'=> $hora, 
                'reto'=> $reto->return, 
                'validacionDigital'=> $validation, 
                'llave_registro'=> $llaveRegisroEncriptadoStr
            );
            // $tum = array("code" => 1, "message" => json_encode($params));
            // return $result->setData($tum); 
            $payme = $SoapResult->me_set_Autho_Ecomm($params);
            // return $result->setData($params);
            if($payme instanceof SoapFault){
                $tum = array("code" => 1, "message" => "Error de sistema.");
                return $result->setData($tum); 

            }


            if(count($payme->return) < 6){
                $Orstatus = $this->getConfigData('order_status_cancelado');
                $this->UpdateStatus($order_id, $Orstatus);
                $dat = array("code" => 1, "message" => $payme->return[1]);
                // return $result->setData($dat); 
            } else if($payme->return[2] == 0){
                // Success operation
                $Orstatus = $this->getConfigData('order_status_aceptado');
                $this->UpdateStatus($order_id, $Orstatus);
                $dat = array("code" => 0);
               
            }else if($payme->return[2] == 8 || $payme->return[2] == 91 || $payme->return[2] == 92){
                $params = array(
                    'cod_institucion'=> $code, 
                    'secuencia'=> $secuencia, 
                    'fecha_transaccion'=>$fecha, 
                    'fecha_envio'=>$fecha, 
                    'hora_envio'=>$hora, 
                    'reto'=> $codReto->return, 
                    'validacionDigital'=>$validation, 
                    'llave_registro'=>$llaveRegisroEncriptadoStr
                ); 

                $SoapResult->me_set_Rever_Ecomm($params);
                $Orstatus = $this->getConfigData('order_status_cancelado');
                $this->UpdateStatus($order_id, $Orstatus);
                $dat = array("code" => 1, "message" => $payme->return[1]);


            }
            else{
                $Orstatus = $this->getConfigData('order_status_cancelado');
                $this->UpdateStatus($order_id, $Orstatus);
                $dat = array("code" => 1, "message" => $payme->return[3]);
                
            }

            return $result->setData($dat); 
        }

		

        $tum = array("code" => $binCountry, "htmlRedirect" => $data['cc_bin'], "noAuth" => $this->getConfigData('merchan_cyber'));
    }

    function proccessPayment($params)
    {
        try {

            $resultado = $this->_soap->me_set_Autho_Ecomm($params);
            
            return $resultado;
        } catch ( SoapFault $e ) {
            // return $e->getMessage();
            return $e;
        }
		
    }

    function setPublicKeyStoreInLinkser($cod, $publicKey, $keystore)
    {
        try {

            $resultado = $this->_soap->setRegistrar(
                array(
                'cod_institucion'=> $cod, 
                'llave_publica'=> $publicKey, 
                'llave_registro'=> $keystore
                )
            );
            
            return $resultado;
        
            
        } catch ( SoapFault $e ) {
            // return $e->getMessage();
            return $e;
        }
		
    }

    function getValidateFirma($dirJAva, $dirKeyPrivate, $codI, $codR)
    {
        return shell_exec("java -jar {$dirJAva} F {$dirKeyPrivate} {$codI} {$codR} 2>&1");

    }

    function encriptarData($dirJAva, $key, $data)
    {
        return shell_exec("java -jar {$dirJAva} E {$key} {$data}  2>&1");
		
    }

    function getPublicKey($dirJAva, $dirKeyStore)
    {
        return shell_exec("java -jar {$dirJava} Pu {$dirKeyStore} 2>&1");
    }


    function UpdateStatus($idorder, $orstatus){
		$orderId = $idorder;
		//@var \Magento\Sales\Api\Data\OrderInterface $order 
		$order = $this->orderRepository->get($orderId);
		// $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
		$order->setState($orstatus)->setStatus($orstatus);
		$this->orderRepository->save($order);
	}


    public function get_bin_card($bin)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://lookup.binlist.net/{$bin}" ,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
        ));     

        $response = curl_exec($curl);       

        curl_close($curl);
        return $response;
    }

    public function getConfigData($config_path)
    {
        return $this->scopeConfig->getValue(
            'payment/linkser_creditcard/'.$config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    } 


}