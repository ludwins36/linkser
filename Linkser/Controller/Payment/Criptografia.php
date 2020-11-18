<?php
/**
 * Copyright � 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Vexsoluciones\Linkser\Controller\Payment;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vexsoluciones\Linkser\Controller\Payment\Soap;
class Criptografia extends \Magento\Framework\App\Action\Action
{
    public $javaDir;
    public $file;
    protected $_productRepository;


    public function __construct(
        \Magento\Framework\App\Action\Context $context


    ) {
        // $this->javaDir = $storeManager->getStore()->getBaseUrl();
        // $this->_productRepository = $productRepository;
        parent::__construct($context);
        

    }
    public function execute() 
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $soap = $objectManager->get('\Magento\Framework\Webapi\Soap\ClientFactory');
        $rest = $soap->create('https://lnksrvssaup2.linkser.com.bo:9483/wsComercioEcomme/ServiciosEcommeLNK?wsdl');
        // $_productRepository =  $objectManager->get('\Magento\Catalog\Model\ProductRepository');
        // $config =  $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $storeManager = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        // $media_dir = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
        // ->getStore()
        // ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $dir = $storeManager->getPath('app'). '/code/Vexsoluciones/Linkser/Java/Criptografia.jar';
        $key = $storeManager->getPath('media') . '/keys_linkser/public_store/publica.rsa';
        $key2 = $storeManager->getPath('media') . '/keys_linkser/public_linkser/publica.rsa';
        $dirKeyPrivate = $storeManager->getPath('media') . '/keys_linkser/private_store/privada.rsa';
        // $data = '411111111111111111';
        $shell = shell_exec("java -jar {$dir} Pu {$key}  2>&1");
        $code = shell_exec("java -jar {$dir} E {$key2} 1050  2>&1");
        $key3 = shell_exec("java -jar {$dir} E {$key2} VexEC2020  2>&1");
        $data = $rest->setRegistrar(
            array(
            'cod_institucion'=> $code, 
            'llave_publica'=> $shell, 
            'llave_registro'=> $key3
            )
        );
        $numeroTarjetaEncriptadoStr = shell_exec("java -jar {$dir} E {$key2} 4111111111111111  2>&1");
        $fechaExpiraEncriptadoStr = shell_exec("java -jar {$dir} E {$key2} 202012  2>&1");
        $cvv2EncriptadoStr = shell_exec("java -jar {$dir} E {$key2} 123  2>&1");
        $reto = $rest->getReto(array());
        $params = array(
            'cod_institucion'=>  $code, 
            'secuencia'=> '000374',
            'cod_comercio'=> "0375136", 
            'cod_terminal'=>"02179999", 
            'tarjeta'=> $numeroTarjetaEncriptadoStr, 
            'nombre_cliente'=> 'jose perez', 
            'fecha_expiracion'=> $fechaExpiraEncriptadoStr, 
            'cvv2'=> $cvv2EncriptadoStr, 
            'monto'=> '000000001000', 
            'moneda'=> '840', 
            'fecha_envio'=> date('Ymd'), 
            'hora_envio'=> date('His'), 
            'reto'=> $reto->return, 
            'validacionDigital'=> shell_exec("java -jar {$dir} F {$dirKeyPrivate} 1050 {$reto->return} 2>&1"), 
            'llave_registro'=>  $key3
        );
        
        $payme = $rest->me_set_Autho_Ecomm($params);

        print_r(\var_dump($payme));
        exit();
        // $shell = shell_exec("java -jar {$dir} E {$key} {$data}  2>&1");
       


        try{

            $fingerprint = strtotime('now');

            $location_sp_cre = 'https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.155.wsdl';
            $soap = new Soap($location_sp_cre, array());
            $request = new \stdClass();
            $request->merchantID = 'test_linkser';
	
	// Before using this example, replace the generic value with your own.
	$request->merchantReferenceCode = "789";

	// To help us troubleshoot any problems that you may encounter,
    // please include the following information about your PHP application.
	$request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();

	// This section contains a sample transaction request for the authorization 
    // service with complete billing, payment card, and purchase (two items) information.	
	$ccAuthService = new \stdClass();
	$ccAuthService->run = "true";
	$request->ccAuthService = $ccAuthService;

	$billTo = new \stdClass();
	$billTo->firstName = "John";
	$billTo->lastName = "Doe";
	$billTo->street1 = "1295 Charleston Road";
	$billTo->city = "Mountain View";
	$billTo->state = "CA";
	$billTo->postalCode = "94043";
	$billTo->country = "US";
	$billTo->email = "null@cybersource.com";
	$billTo->ipAddress = "10.7.111.111";
	$request->billTo = $billTo;

	$card = new \stdClass();
	$card->accountNumber = "4111111111111111";
	$card->expirationMonth = "08";
	$card->expirationYear = "2023";
	$card->cvNumber = "123";
	$request->card = $card;

	// $purchaseTotals = new \stdClass();
	// $purchaseTotals->currency = "USD";
	// $purchaseTotals->grandTotalAmount = 100.00;
	// $request->purchaseTotals = $purchaseTotals;

	$item0 = new \stdClass();
	$item0->unitPrice = "12.34";
	$item0->id = "0";	

	$item1 = new \stdClass();
	$item1->unitPrice = "56.78";
	$item1->id = "1";

	$request->item = array($item0, $item1);

	// $reply = $soapClient->runTransaction($request);
           
            $reply = $soap->runTransaction($request);

            print_r($reply);

            // print_r($soap->getTst());

        }catch(SoapFault $e){
            print_r($e->getMessage());
        }
        // $request = new \stdClass();
        // $checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
        // $address = $checkoutSession->getQuote()->getBillingAddress();
        // print_r($config->getValue('payment/linkser_creditcard/testmode',  \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        //     $allItems = $checkoutSession->getQuote()->getAllVisibleItems();
        //         $cart =  $objectManager->get('Magento\Checkout\Model\Cart');
        //         if(count($allItems) > 0){
        //             foreach ($allItems as $item) {
        //                 $itemId = $item->getProductId();
        //                 print_r(var_dump($item->getQty()));
        //                 // print_r(var_dump($item->getProductId()));
        //                 // $cart->removeItem($itemId)->save();
        //             }
        //         }

       exit('hola');
    }

    public function getProductById($id)
	{
		return $this->_productRepository->getById($id);
    }
    
    public function getConfigData($config_path)
    {
        return $this->scopeConfig->getValue(
            'payment/linkser_creditcard/'.$config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    } 
    // public function 
}