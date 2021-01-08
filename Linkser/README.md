# README #
Magento linkser Payment method integrated seamlessly to linkser Payment Gateway.

### What is this repository for? ###

* Module to add payment method linkser

Installation
    Please use composer to install the extension.
    
    conposer require vexsoluciones/linkser-credit-card

### Menu and Needed Configurations ###

* Menu: Stores > Sales > Payment Methods > linkser Credit Card Payment

* linkser side:
    Acount → Options → Payment Result URL
    
    Insert information as below:
    
    Redirect API - Frontend return URL: https://{domain}/checkout/onepage/success/
    
    Server-to-server API - Frontend return URL: https://{domain}/linkser/process/response

### Changelog ###
    * 1.0.0: Initial module
    



