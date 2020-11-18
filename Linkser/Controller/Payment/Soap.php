<?php
namespace Vexsoluciones\Linkser\Controller\Payment;
// use \Magento\Framework\Webapi\Soap\ClientFactory;

class Soap extends \SoapClient
{
    private $request;

    // function __construct()
    // {
    //     // $this->request = $request;
    //     // parent::__construct($wsdl, $options);
    // }

    function __construct($wsdl, $options = null, $request = false)
    {
        // $this->request = $request;
        parent::__construct($wsdl, $options);
    }


    function __doRequest($request, $location, $action, $version, $one_way = NULL)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $config =  $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');

        // $user = VSWLINKSER_Gateway_Linkser::get_option_('mechand_id');
        // $password = VSWLINKSER_Gateway_Linkser::get_option_('api_key');

        $user = $config->getValue('payment/linkser_creditcard/merchan_cyber');
        $password = $config->getValue('payment/linkser_creditcard/key_cyber');

        $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$user</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$password</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";

        $requestDOM = new \DOMDocument('1.0');
        $soapHeaderDOM = new \DOMDocument('1.0');

        try {

            $requestDOM->loadXML($request);
            $soapHeaderDOM->loadXML($soapHeader);

            $node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
            $requestDOM->firstChild->insertBefore(
                $node,
                $requestDOM->firstChild->firstChild
            );

            $request = $requestDOM->saveXML();

            // printf("Modified Request:\n*$request*\n");
        } catch (DOMException $e) {
            die('Error adding UsernameToken: ' . $e->code);
        }

        return parent::__doRequest($request, $location, $action, $version);
    }
}
