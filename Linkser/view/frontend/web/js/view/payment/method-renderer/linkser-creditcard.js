define([
        'jquery',

        'Magento_Checkout/js/model/quote',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function($, quote, Component, alert, fullScreenLoader) {
        'use strict';





        var city, telephone, postcode, base_currency, street, countryid, ids, totalshp;

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Vexsoluciones_Linkser/payment/linkser-creditcard'
            },


            initialize: function() {
                this._super();
                this.pm();
                this.supdata();
                this.licns();

            },


            licns: function() {

                if (!act_license) {
                    //alert({content: $.mage.__('There was an error validating the credomatic license')});

                }
            },


            pm: function() {
                $(document).ready(function() {
                    $.ajax({
                        url: Url_credomatic + 'linkser/payment/getatt',
                        type: 'POST',
                        async: false,
                        data: 1,
                        dataType: {

                            format: 'json'
                        },
                        dataType: 'json',
                        success: function(data) {
                            ids = data.id;
                            totalshp = data.totalp;

                        },
                        error: function(request, error) {
                            console.log(request);
                            console.log(error);
                        }
                    });
                });

            },




            supdata: function() {
                /*

                     city = quote.shippingAddress().city;
                     telephone = quote.shippingAddress().telephone;
                     postcode = quote.shippingAddress().postcode;
                     base_currency = quote.totals().base_currency_code;
                     street = quote.shippingAddress().street[0];
                     countryid = quote.shippingAddress().countryId;
                    */

            },

            afterPlaceOrder: function() {
                var dataArray = {
                    cc_card: document.getElementById("linkser_creditcard_cc_number").value,
                    cc_month: $("#linkser_creditcard_expiration :selected").val(),
                    cc_year: $("#linkser_creditcard_expiration_yr :selected").val(),
                    cc_vv: document.getElementById("linkser_creditcard_cc_cid").value,
                    cc_bin: document.getElementById("linkser_creditcard_cc_number").value.substring(0, 8),
                    idorder: ids,
                    totalsp: totalshp

                    /*
                    ciudad: city,
                    telefono: telephone,
                    postalcode: postcode,
                    calle:  street,
                    curren:  base_currency,
                    country: countryid
                    */
                };




                //   $('body').trigger('processStart');
                fullScreenLoader.startLoader();
                $(document).ready(function() {
                    $.ajax({
                        url: Url_credomatic + 'linkser/payment/validate',
                        type: 'POST',
                        data: { u_data: JSON.stringify(dataArray) },

                        dataType: 'json',
                        success: function(data) {
                            console.log(data);
                            if (data.code == '0') {
                                window.location.href = window.checkoutConfig.defaultSuccessPageUrl;
                                // window.location.href = Url_credomatic;



                                // window.location.href = window.checkoutConfig.defaultSuccessPageUrl;
                                // document.getElementById("f_form").innerHTML = data.htmlRedirect;

                            } else {
                                fullScreenLoader.stopLoader();
                                // console.log(data.code);
                                // console.log(data);
                                $('body').trigger('processStop');
                                alert({
                                    content: $.mage.__('No se ha podido completar la transacción, inténtelo más tarde.')
                                });
                                window.location.href = Url_credomatic;
                            }


                        },
                        error: function(request, error) {
                            console.log(request);
                            console.log(error);
                        }
                    });

                });

            },



            context: function() {


                return this;
            },

            getCode: function() {
                return 'linkser_creditcard';
            },

            isActive: function() {

                // if(!act_license){
                // //document.getElementById('vexsoluciones_linkser_cc_number').readOnly = true;
                // $('#vexsoluciones_linkser_cxxxxxxxxxxxxxxxxxc_number').attr("disabled", true);
                // $('#vexsoluciones_linkser_expiration').attr("disabled", true);
                // $('#vexsoluciones_linkser_expiration_yr').attr("disabled", true);
                // //$('#vexsoluciones_linkser_cc_cid').attr("disabled", true);
                // }	


                return true;
            }
        });



    }



);