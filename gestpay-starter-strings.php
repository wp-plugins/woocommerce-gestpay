<?php

$this->strings = array(
  'field_enabled' => array(
    't' => __( 'Enable/Disable:', 'woocommerce_gestpay_starter' ),
    'l' => __( 'Enable GestPay Starter.', 'woocommerce_gestpay_starter' )
  ),
  'field_title' => array(
    't' => __( 'Title:', 'woocommerce_gestpay_starter' ),
    'd' => __( 'The title which the user sees during checkout.', 'woocommerce_gestpay_starter' ),
    'def' => "Banca Sella (GestPay Starter)"
  ),
  'field_description' => array(
    't' => __( 'Description:', 'woocommerce_gestpay_starter' ),
    'd' => __( 'This controls the description which the user sees during checkout.', 'woocommerce_gestpay_starter' ) . '<br />' .
       sprintf( __( 'Tip: Use the quicktags (%s) with qTranslate.', 'woocommerce_gestpay_starter' ), "[:it]Italia[:en]English[:de]Deutsch" ),
    'def' => "Pay securely by Credit or Debit card through GestPay's Secure Servers."
  ),
  'field_shoplogin' => array(
    'd' => __( 'Please enter your shopLogin as provided by GestPay.', 'woocommerce_gestpay_starter' ),
  ),
  'field_parameters' => array(
    't' => __( 'GestPay Pro Parameters', 'woocommerce_gestpay_starter' ),
  ),
  'field_buyer_email' => array(
    'l' => __( 'Enable the buyer e-mail parameter', 'woocommerce_gestpay_starter' ),
  ),
  'field_buyer_name' => array(
    'l' => __( 'Enable the buyer name parameter', 'woocommerce_gestpay_starter' ),
  ),
  'field_language' => array(
    'l' => __( 'Enable the language parameter', 'woocommerce_gestpay_starter' ),
    'd' => __( 'Allows to set the language of the GestPay payment page automatically (with qtranslate or WPML)', 'woocommerce_gestpay_starter' ),
  ),
  'field_custominfo' => array(
    'd' => __( 'Enter your custom information as parameter=value, one for each row. The space and the following characters are not allowed:', 'woocommerce_gestpay_starter' ) . " & § ( ) * < > , ; : *P1* / /* [ ] ? = %",
  ),
  'field_cards' => array(
    't' => __( 'Card Icons', 'woocommerce_gestpay_starter' ),
    'd' => __( 'Select the accepted cards to show them as icon', 'woocommerce_gestpay_starter' ),
  ),
  'field_testing' => array(
    't' => __( 'Gateway Testing', 'woocommerce_gestpay_starter' ),
  ),
  'field_processurl' => array(
    't' => __( 'Sandbox/test mode:', 'woocommerce_gestpay_starter' ),
    'l' => __( 'Enable sandbox mode', 'woocommerce_gestpay_starter' ),
    'd' => __( 'If checked (default), the checkout will be processed with the test URL, else with the real one.', 'woocommerce_gestpay_starter' ),
  ),
  'field_debug' => array(
    'l' => __( 'Enable logging events', 'woocommerce_gestpay_starter' ),
    'd' => sprintf( __( 'Log GestPay Starter events inside the woocommerce/logs/%s.txt file', 'woocommerce_gestpay_starter' ), version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ? $this->logfile . "-" . sanitize_file_name( wp_hash( $this->logfile ) ) : $this->logfile ),
  ),
  'field_emaildebug' => array(
    'l' => __( 'Enable emails for logging errors', 'woocommerce_gestpay_starter' ),
    'd' => __( 'Log GestPay Starter errors using the email', 'woocommerce_gestpay_starter' ),
  ),
  'field_experimental' => array(
    't' => __( 'Experimental features', 'woocommerce_gestpay_starter' ),
  ),
  'field_force_recrypt' => array(
    't' => __( 'Force Re-Encrypt:', 'woocommerce_gestpay_starter' ),
    'l' => __( 'Forces the re-encryption process.', 'woocommerce_gestpay_starter' ),
    'd' => __( 'In certain cases can be useful to force the re-encryption of the string sent to the GestPay server. <strong>Warning: this is an experimental feature! Enable this feature only if you know what you are doing.</strong>', 'woocommerce_gestpay_starter' ),
  ),
  'admin_options' => array(
    __( "Accept payments from Credit/Debit cards through the GestPay Payment Gateway. After the customer enters his credit card informations, will be redirected to a secure GestPay server's hosted page to finish the transaction.", 'woocommerce_gestpay_starter' ),
    __( 'In your GestPay account, you have to set the response URL to the one of the view order, for example on: http://yoursitetname/my-account/view-order/. In this way the customer can know the transaction results.', 'woocommerce_gestpay_starter' ),
    "<strong>" . __( "The credit card informations will not be stored anywhere in this system, but they will be inserted into the secure servers of GestPay.", 'woocommerce_gestpay_starter' ) . "</strong>",
    "<br>" . sprintf(__( "To get <strong>GestPay Professional</strong> please visit %s or send me an email at %s", 'woocommerce_gestpay_starter' ), '<a href="http://www.mauromascia.com" target="_blank">www.mauromascia.com</a>', '<a href="mailto:info@mauromascia.com">info@mauromascia.com</a>' )
  ),
  'crypted_string' => __( 'Crypted string [%s]: %s' ),
  'crypted_string_info' => __( 'You are forcing the re-encryption process: this may cause multiple calls to the GestPay webservice.' ),
  'receipt_page' => __( "If you have come to this page it is likely that there has been an error in our system or in the GestPay payment system. In any case, an automated email has been sent to the system administrator, who will verify that error as soon as possible. To place your purchase you can try again later. We apologize for the inconvenience.", 'woocommerce_gestpay_starter' ),
  'receipt_error' => __( 'Fatal Error: Check the GestPay configuration.' ),
  'transaction_lang' => __( 'Transaction language is %s' ),
  'retrieving_args' => __( 'Retrieving args for the order no: %s' ),
  'gestpay_response' => __( 'Checking GestPay response...', 'woocommerce_gestpay_starter' ),
  'second_call' => __( 'This is the second call: do nothing.', 'woocommerce_gestpay_starter' ),
  'generating_form_info' => __( 'Generating the gestpay redirect form...' ),
  'generating_form_error' => __( 'The b parameter is invalid.' ),
  'soap_req_error' => __( 'Fatal Error: Soap Client Request Exception with error %s', 'woocommerce_gestpay_starter' ),
  'soap_enc_error' => __( 'Fatal Error: Soap Client Encryption Exception with error %s', 'woocommerce_gestpay_starter' ),
  'soap_res_error' => __( 'Fatal Error: Soap Client Response Exception with error %s', 'woocommerce_gestpay_starter' ),
  'decrypt_error' => __( 'Fatal Error: Soap Client Decryption Exception with error %s', 'woocommerce_gestpay_starter' ),
  'transaction_error_subject' => __( 'Transaction failed with error', 'woocommerce_gestpay_starter' ),
  'transaction_error' => __( 'Transaction for order %s failed with error %s', 'woocommerce_gestpay_starter' ),
  'transaction_thankyou' => __( 'Thank you for shopping with us. Your transaction %s has been processed correctly. We will be shipping your order to you soon.', 'woocommerce_gestpay_starter' ),
  'transaction_ok' => __( 'Transaction for order %s has been completed successfully.', 'woocommerce_gestpay_starter' ),
  'already_completed' => __( 'Transaction for order %s has been already completed.', 'woocommerce_gestpay_starter' ),
);
?>
