<?php
/*
  Plugin Name: WooCommerce GestPay Starter
  Plugin URI: http://wordpress.org/plugins/woocommerce-gestpay/
  Description: Estende WooCommerce fornendo il gateway di pagamento GestPay Starter di Banca Sella.
  Version: 20150418
  Author: Mauro Mascia (baba_mmx)
  Author URI: http://www.mauromascia.com
  License: GPLv2
  Support: info@mauromascia.com

  Copyright © 2013-2015 Mauro Mascia

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

add_action( 'plugins_loaded', 'init_gestpay_starter_gateway' );
function init_gestpay_starter_gateway() {

  if ( ! class_exists( 'WC_Payment_Gateways' ) ) { return; }

  if ( ! extension_loaded( 'soap' ) ) {
    echo '<div id="message" class="error"><p>ERRORE: Per poter utilizzare GESTPAY STARTER la libreria SOAP client di PHP deve essere abilitata!</p></div>';
    return;
  }

  /**
   * Add the gateway to WooCommerce.
   */
  add_filter( 'woocommerce_payment_gateways', 'woocommerce_gestpay_starter_add_gateway' );
  function woocommerce_gestpay_starter_add_gateway( $methods ) {
    $methods[] = 'WC_Gateway_Gestpay_Starter';
    return $methods;
  }

  /**
   * Gateway's Constructor.
   */
  class WC_Gateway_Gestpay_Starter extends WC_Payment_Gateway {

    public function __construct() {

      // Set up localisation
      load_plugin_textdomain( 'woocommerce_gestpay_starter', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );

      $this->id = 'gestpay-starter';
      $this->method_title = __( 'Gestpay Starter', 'woocommerce_gestpay_starter' );
      $this->logo = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/images/gestpay-starter.png';
      $this->icon = WP_PLUGIN_URL . "/" . plugin_basename( dirname (__FILE__ ) ) . '/images/gestpay-cards.jpg';
      $this->logfile = 'gestpay-starter';

      // Load some strings used in this plugin
      $this->init_strings();

      // Load form fields.
      $this->init_form_fields();

      // Load settings.
      $this->init_settings();

      // Define user set variables
      $this->title = $this->settings['title'];
      $this->description = $this->settings['description'];
      $this->shopLogin = $this->settings['shopLogin'];
      $this->show_message_on_end_page = $this->settings['show_message_on_end_page'] == "yes" ? true : false;
      $this->gestpay_processUrl = $this->settings['gestpay_processUrl'] == "yes" ? true : false;
      $this->transactionDate = date( 'Y-m-d H:i:s' );
      $this->force_recrypt = $this->settings['force_recrypt'] == "yes" ? true : false;
      $this->debug = $this->settings['debug'] == "yes" ? true : false;

      // Set Web Service process url to test or real
      if ( $this->gestpay_processUrl ) {
        $this->gestpay_ws_crypt_url = "https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        $this->liveurl = "https://testecomm.sella.it/gestpay/pagam.asp";
      }
      else {
        $this->gestpay_ws_crypt_url = "https://ecomms2s.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        $this->liveurl = "https://ecomm.sella.it/gestpay/pagam.asp";
      }

      // Logs
      if ( $this->debug && ( ! isset( $this->log ) || empty( $this->log ) ) ) {
        $this->log = $this->wc_logger();
      }


      // Doesn't output a payment_box containing direct payment form
      $this->has_fields = false;

      // Add style
      wp_register_style( 'gestpay-starter-css', WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/gestpay-starter.css' );
      wp_enqueue_style( 'gestpay-starter-css' );

      // Actions
      add_action( 'woocommerce_receipt_' . $this->id, array( &$this, 'receipt_page' ) );

      // Questa genera una doppia chiamata alla pagina di ordine ricevuto.
      add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

      if ( version_compare( wc_gestpay_starter_get_wc_version(), '2.0.0', '>=' ) ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      }
      else {
        if ( $this->debug ) {
          $this->log->add( $this->logfile, "[INFO]: check_wc_gestpay_starter_response on old WooCommerce..." );
        }

        add_action( 'init', array( &$this, 'check_wc_gestpay_starter_response' ) );
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
      }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable/Disable:', 'woocommerce_gestpay_starter' ),
          'type' => 'checkbox',
          'label' => __( 'Enable GestPay Starter.', 'woocommerce_gestpay_starter' ),
          'default' => 'yes'
        ),
        'title' => array(
          'title' => __( 'Title:', 'woocommerce_gestpay_starter' ),
          'type' => 'text',
          'description' => __( 'The title which the user sees during checkout.', 'woocommerce_gestpay_starter' ),
          'default' => "Banca Sella (GestPay Starter)"
        ),
        'description' => array(
          'title' => __( 'Description:', 'woocommerce_gestpay_starter' ),
          'type' => 'textarea',
          'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce_gestpay_starter' ) . '<br />' .
             sprintf( __( 'Tip: Use the quicktags (%s) with qTranslate.', 'woocommerce_gestpay_starter' ), "[:it]Italia[:en]English[:de]Deutsch" ),
          'default' => "Paga in tutta sicurezza con Banca Sella"
        ),

        // -- SHOP LOGIN

        'shopLogin' => array(
          'title' => 'GestPay Shop Login:',
          'type' => 'text',
          'description' => __( 'Please enter your shopLogin as provided by GestPay.', 'woocommerce_gestpay_starter' ),
          'default' => ''
        ),

        'show_message_on_end_page' => array(
          'title' => "Mostra messaggio esito transazione",
          'type' => 'checkbox',
          'description' => "Se selezionato mostra un messaggio positivo o negativo, a seconda dell'esito della transazione, nelle pagine di questo sito che sono state indicate nelle URL di risposta del backoffice di Gestpay",
          'default' => 'yes'
        ),

      // -- GESTPAY PRO PARAMETERS

        'parameters' => array(
          'title' => __( 'GestPay Pro Parameters', 'woocommerce_gestpay_starter' ),
          'type' => 'title',
          'description' => '',
          'class' => 'pro-disable-section',
        ),
        'param_buyer_email' => array(
          'title' => 'Buyer E-mail:',
          'type' => 'checkbox',
          'label' => __( 'Enable the buyer e-mail parameter', 'woocommerce_gestpay_starter' ),
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'param_buyer_name' => array(
          'title' => 'Buyer Name:',
          'type' => 'checkbox',
          'label' => __( 'Enable the buyer name parameter', 'woocommerce_gestpay_starter' ),
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'param_language' => array(
          'title' => 'Language:',
          'type' => 'checkbox',
          'label' => __( 'Enable the language parameter', 'woocommerce_gestpay_starter' ),
          'default' => 'no',
          'description' => __( 'Allows to set the language of the GestPay payment page automatically (with qtranslate or WPML)', 'woocommerce_gestpay_starter' ),
          'class' => 'pro-disable-element'
        ),
        'param_custominfo' => array(
          'title' => 'Custom Info:',
          'type' => 'textarea',
          'description' => __( 'Enter your custom information as parameter=value, one for each row. The space and the following characters are not allowed:', 'woocommerce_gestpay_starter' ) . " & § ( ) * < > , ; : *P1* / /* [ ] ? = %",
          'class' => 'pro-disable-element',
          'default' => '',
        ),

        // -- ICONS

        'cards' => array(
          'title' => __( 'Card Icons', 'woocommerce_gestpay_starter' ),
          'type' => 'title',
          'description' => __( 'Select the accepted cards to show them as icon', 'woocommerce_gestpay_starter' ),
          'class' => 'pro-disable-section'
        ),
        'card_visa' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'Visa Electron',
          'default' => 'yes',
          'class' => 'pro-disable-element'
        ),
        'card_mastercard' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'Mastercard',
          'default' => 'yes',
          'class' => 'pro-disable-element'
        ),
        'card_maestro' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'Maestro',
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'card_ae' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'American Express',
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'card_dci' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'Diners Club International',
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'card_paypal' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'PayPal',
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'card_jcb' => array(
          'title' => '',
          'type' => 'checkbox',
          'label' => 'JCB Cards',
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),


        // -- TESTING

        'testing' => array(
          'title' => __( 'Gateway Testing', 'woocommerce_gestpay_starter' ),
          'type' => 'title',
          'description' => '',
        ),
        'gestpay_processUrl' => array(
          'title' => __( 'Sandbox/test mode:', 'woocommerce_gestpay_starter' ),
          'type' => 'checkbox',
          'label' => __( 'Enable sandbox mode', 'woocommerce_gestpay_starter' ),
          'description' => __( 'If checked (default), the checkout will be processed with the test URL, else with the real one.', 'woocommerce_gestpay_starter' ),
          'default' => 'yes'
        ),
        'debug' => array(
          'title' => 'Debug Log:',
          'type' => 'checkbox',
          'label' => __( 'Enable logging events', 'woocommerce_gestpay_starter' ),
          'default' => 'no',
          'description' => sprintf( __( 'Log GestPay Starter events inside the woocommerce/logs/%s.txt file', 'woocommerce_gestpay_starter' ), version_compare( wc_gestpay_starter_get_wc_version(), '2.0.0', '>=' ) ? $this->logfile . "-" . sanitize_file_name( wp_hash( $this->logfile ) ) : $this->logfile ),
        ),

        // -- EXPERIMENTAL

        'experimental' => array(
          'title' => __( 'Experimental features', 'woocommerce_gestpay_starter' ),
          'type' => 'title',
          'description' => '',
        ),
        'force_recrypt' => array(
          'title' => __( 'Force Re-Encrypt:', 'woocommerce_gestpay_starter' ),
          'type' => 'checkbox',
          'label' => __( 'Forces the re-encryption process.', 'woocommerce_gestpay_starter' ),
          'default' => 'no',
          'description' => __( 'In certain cases can be useful to force the re-encryption of the string sent to the GestPay server. <strong>Warning: this is an experimental feature! Enable this feature only if you know what you are doing.</strong>', 'woocommerce_gestpay_starter' ),
        ),

      );
    }

    /**
     * Initialise Gateway Strings
     */
    function init_strings() {
      $this->strings = array(
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
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
      ?>
      <div class="gestpay-starter-admin-main">
        <p></p>
        <div class="gestpay-starter-message">
          <img src="<?= $this->logo; ?>" id="gestpay-starter-logo"/>
          <h3>GestPay STARTER |  <a href="http://www.sella.it/" target="_blank">Gruppo Banca Sella</a></h3>
          <br>
          <?php
            foreach ( $this->strings['admin_options'] as $string ) {
              echo "<p>" . $string . "</p>";
            }
          ?>
          <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4" target="_blank">Qui puoi effettura una donazione libera allo sviluppatore.</a> Grazie!
        </div>
        <br><br>
        <div class="gestpay-starter-message gestpay-starter-form">
          <table class="form-table">
            <?php
            // Generate the HTML for the fields on the "settings" screen.
            // This comes from class-wc-settings-api.php
            $this->generate_settings_html();
            ?>
          </table><!--/.form-table-->
        </div>
      </div>
      <?php

      $onlypro = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/images/onlypro.png';
      $this->gestpay_enqueue_js( '
        jQuery( ".pro-disable-element" ).closest( "table" ).each( function() {
          h = jQuery( this ).height();
          jQuery( this ).css({
            float: "left",
            position: "relative",
            width: "100%"
          }).append( "<img class=\"onlypro\" src=\"'.$onlypro.'\" height=\""+h+"\" />" );

        });
        jQuery( ".pro-disable-element" ).attr( "disabled", true );
    ' );
    }

    /**
     * Output a payment box containing your direct payment form
     */
    function payment_fields() {
      if ( $this->description ) :
        echo wpautop( wptexturize( wp_kses_post( __( $this->description ) ) ) );
      endif;
    }

    /**
     * Process the payment and return the result.
     */
    function process_payment( $order_id ) {
      if ( $this->debug ) {
        $this->log->add( $this->logfile, "[INFO]: Processing payment..." );
      }

      $order = new WC_Order( $order_id );

      if ( !$this->form_submission_method ) {
        $b_param = $this->get_gestpay_args( $order );
        if ( $b_param == false ) {
          return array(
            'result' => 'failed',
            'redirect' => add_query_arg( 'order', $order->id, add_query_arg( 'key', 'FAILED', get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
          );
        }
        else {
          return array(
            'result' => 'success',
            'redirect' => $this->liveurl . '?a=' . $this->shopLogin . '&b=' . $b_param,
          );
        }
      }
      else {
        return array(
          'result' => 'success',
          'redirect' => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
        );
      }
    }

    /**
     * Generate the receipt page
     */
    function receipt_page( $order ) {
      $ret = $this->generate_gestpay_form( $order );

      if ( $ret == false ) {
        $this->msg['class'] = 'woocommerce_info';
        $this->msg['message'] = $this->strings['receipt_page'];
        echo $this->show_message( "" );

        if ( $this->debug )
          $this->log->add( $this->logfile, "[ERROR]: " . $this->strings['receipt_error'] );
      }
      else {
        echo $ret;
      }
    }

    /**
     * Get args
     */
    function get_gestpay_args( $order ) {
      $order_id = $order->id;

      if ( $this->debug )
        $this->log->add( $this->logfile, "[INFO]: " . sprintf( $this->strings['retrieving_args'], $order_id ) );

      // Set currency code
      $gestpay_allowed_currency_codes = array(
        'USD' => '1',
        'GBP' => '2',
        'CHF' => '3',
        'JPY' => '71',
        'HKD' => '103',
        'EUR' => '242',
      );

      if ( in_array( get_option( 'woocommerce_currency' ), array_keys( $gestpay_allowed_currency_codes ) ) ) {
        $this->currency = $gestpay_allowed_currency_codes[get_option( 'woocommerce_currency' )];
      }
      else {
        $this->currency = '242'; // Set EUR as default currency code
      }

      // Define GestPay parameters
      $params = new stdClass();

      // BASIC version and required parameters
      $params->shopLogin = $this->shopLogin;
      $params->uicCode = $this->currency;
      $params->amount = $order->order_total;
      $params->shopTransactionId = $order_id;

      $crypted_string = $this->gestpay_encrypt( $params, $order_id );

      if ( $this->debug )
        $this->log->add( $this->logfile, '[INFO]: ' . sprintf( $this->strings['crypted_string'], '0', $crypted_string ) );

      if ( $this->force_recrypt ) {
        if ( $this->debug )
          $this->log->add( $this->logfile, "[WARNING]: " . $this->strings['crypted_string_info'] );

        // The web service is working?
        if ( $crypted_string ) {
          /***** NOTES *********************************************************
           *
           * Ok, check if the string contains asterisks: for some strange reasons,
           * sometimes, the webservice returns a string with asterisks that is
           * treated as invalid encrypted string from the web service itself.
           * After some retries it produces a valid string. This is not always
           * true: it seems that in some themes/web sites this causes an error
           * but in others not.
           * For that I use an option to force to retry the crypting process.
           */

          $i = 1;
          while ( strpos( $crypted_string, '*' ) !== false) {
            $crypted_string = $this->gestpay_encrypt( $params, $order_id );

            if ( $this->debug )
              $this->log->add( $this->logfile, '[INFO]: ' . sprintf( $this->strings['crypted_string'], $i, $crypted_string ) );
            $i++;
          }
        }
      }

      return $crypted_string;
    }

    /**
     * Encrypt parameters using the GestPay Web Service
     */
    function gestpay_encrypt( $params, $order_id ) {

      // Create a SOAP client using the GestPay webservice
      try {
        $client = new SoapClient( $this->gestpay_ws_crypt_url );
      }
      catch ( Exception $e ) {
        $err = sprintf( $this->strings['soap_req_error'], $e->getMessage() );
        $this->wc_add_error( $err );

        if ( $this->debug )
          $this->log->add( $this->logfile, '[ERROR]: ' . $err );

        return false;
      }

      // Encrypt values using the GestPay webservice
      try {
        // https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?op=Encrypt
        $objectresult = $client->Encrypt( $params );
      }
      catch ( Exception $e ) {
        $err = sprintf( $this->strings['soap_enc_error'], $e->getMessage() );
        $this->wc_add_error( $err );

        if ( $this->debug )
          $this->log->add( $this->logfile, '[ERROR]: ' . $err );

        return false;
      }

      $xml = simplexml_load_string( $objectresult->EncryptResult->any );

      // Check if the encryption call can be accepted
      if ( $xml->TransactionResult == "KO" ) {
        $err = sprintf( $this->strings['transaction_error'], $order_id, ' (' . $xml->ErrorCode . ') ' . $xml->ErrorDescription );

        $this->wc_add_error( $err );

        if ( $this->debug )
          $this->log->add( $this->logfile, '[ERROR]: ' . $err );

        return false;
      }
      else {
        return $xml->CryptDecryptString;
      }
    }

    /**
     * Generate the GestPay button link
     */
    public function generate_gestpay_form( $order_id ) {
      $order = new WC_Order( $order_id );

      if ( $this->debug )
        $this->log->add( $this->logfile, "[INFO]: " . $this->strings['generating_form_info'] );

      $b_param = $this->get_gestpay_args( $order );

      if ( $b_param == false ) {
        if ( $this->debug )
          $this->log->add( $this->logfile, "[ERROR]: " . $this->strings['generating_form_error'] );
        return false;
      }

      // Send the form to the GestPay server, using jQuery to auto-submit the form
      // If - for some reasons - javascript is disabled, show up two buttons to manually send the form
      $this->gestpay_enqueue_js( '
        jQuery("body").block({
            message: "Thank you for your order. We are now redirecting you to GestPay to make payment.",
            baseZ: 99999,
            overlayCSS: {
              background: "#fff",
              opacity: 0.6
            },
            css: {
              padding:        "20px",
              zindex:         "9999999",
              textAlign:      "center",
              color:          "#555",
              border:         "3px solid #aaa",
              backgroundColor:"#fff",
              cursor:         "wait",
              lineHeight:     "24px",
            }
          });
        jQuery("#submit_gestpay_starter_payment_form").click();
    ' );

      return '<form action="' . esc_url( $this->liveurl ) . '" method="post" id="gestpay_starter_payment_form" target="_top">
        <input name="a" type="hidden" value="' . $this->shopLogin . '">
        <input name="b" type="hidden" value="' . $b_param . '">
        <input type="submit" class="button alt" id="submit_gestpay_starter_payment_form" value="' . __( 'Pay via GestPay Starter', 'woocommerce' ) . '" />
        <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
        </form>';
    }


    /**
     * Retrocompatibility get URL
     */
    function get_wc_url( $path, $order ) {
      $url = '';

      switch ( $path ) {

        case 'view_order':

          if ( version_compare( wc_gestpay_starter_get_wc_version(), '2.1.0', '>=' ) ) {
            $url = $order->get_view_order_url();
          }
          else {
            $url = get_permalink( woocommerce_get_page_id( 'view_order' ) );
          }
          
          break;

        case 'order_received':

          // if ( version_compare( wc_gestpay_starter_get_wc_version(), '2.1.0', '>=' ) ) {
          //   $url = $order->get_checkout_order_received_url();
          // }
          // else {
          //   $url = get_permalink( woocommerce_get_page_id( 'order-received' ) );
          // }

          $url = $this->get_return_url( $order );

          break;

        default:

          $url = '';
      }

      return $url;
    }


    function check_wc_gestpay_starter_response() {
      if ( isset( $_GET['a'] ) && isset( $_GET['b'] ) ) {

        if ( $this->debug ) {
          $this->log->add( $this->logfile, "[INFO]: " . $this->strings['gestpay_response'] );
        }

        $params = new stdClass();
        $params->shopLogin = $_GET['a'];
        $params->CryptedString = $_GET['b'];

        // Create a SOAP client using the GestPay webservice
        try {
          $client = new SoapClient( $this->gestpay_ws_crypt_url );
        }
        catch ( Exception $e ) {
          $err = sprintf( $this->strings['soap_res_error'], $e->getMessage() );

          $this->msg['class'] = 'woocommerce_error';
          $this->msg['message'] = $err;

          if ( $this->debug )
            $this->log->add( $this->logfile, "[ERROR]: " . $err );

          return false;
        }

        // Decrypt response using the GestPay webservice
        try {
          $objectresult = $client->Decrypt( $params );
        }
        catch ( Exception $e ) {
          $err = sprintf( $this->strings['decrypt_error'], $e->getMessage() );

          $this->msg['class'] = 'woocommerce_error';
          $this->msg['message'] = $err;

          if ( $this->debug )
            $this->log->add( $this->logfile, "[ERROR]: " . $err );

          return false;
        }

        $xml = simplexml_load_string( $objectresult->DecryptResult->any );

        $order_id = ( int ) $xml->ShopTransactionID;
        $order = new WC_Order( $order_id );

        $order_link = '<a href="' . $this->get_wc_url( 'view_order', $order ) . '">' . $order_id . '</a>';

        if ( $order->status !== 'completed' ) {
          if ( $xml->TransactionResult == "OK" ) {
            if ( $order->status == 'processing' ) {
              // This is the second call - do nothing
            }
            else {
              $this->msg['class'] = 'woocommerce_message';
              $this->msg['message'] = sprintf( $this->strings['transaction_thankyou'], $order_link );

              $this->show_message( "" );

              $msg = sprintf( $this->strings['transaction_ok'], $order_id );

              // Update order status, add admin order note and empty the cart
              $order->payment_complete();
              $order->add_order_note( $msg );
              $this->wc_empty_cart();

              if ( $this->debug ) {
                $this->log->add( $this->logfile, "[INFO]: " . $msg );
              }
            }
          }
          else {
            $err_link = sprintf( $this->strings['transaction_error'], $order_link, ' (' . $xml->ErrorCode . ') ' . $xml->ErrorDescription );
            $err_str = sprintf( $this->strings['transaction_error'], $order_id, ' (' . $xml->ErrorCode . ') ' . $xml->ErrorDescription );

            // Set error message
            $this->msg['class'] = 'woocommerce_error';
            $this->msg['message'] = $err_link;

            $order->update_status( 'failed', $err_str );

            if ( $this->debug )
              $this->log->add( $this->logfile, "[ERROR]: " . $err_str );
          }

          if ( $this->show_message_on_end_page ) {
            add_action( 'the_content', array( &$this, 'show_message' ) );
          }

        }
        else {
          if ( $this->debug ) {
            $this->log->add( $this->logfile, "[INFO]: " . sprintf( $this->strings['already_completed'], $order_id ) );
          }
        }
      }

      // Se le URL di ritorno (OK/KO) sono impostate con wc-api=WC_Gateway_Gestpay_Starter
      // è possibile reindirizza l'utente verso la pagina di ordine ricevuto
      if ( isset( $_GET['wc-api'] ) && $_GET['wc-api'] == get_class( $this ) ) {
        $this->log->add( $this->logfile, "[INFO]: Redirect to order received" );
        wp_redirect( $this->get_return_url( $order ) );
        exit;
      }

    }

    /**
     * Output for the order received page.
     */
    function thankyou_page() {
      if ( $description = $this->get_description() )
        echo wpautop( wptexturize( wp_kses_post( $description ) ) );
    }

    function show_message( $content ) {
      return <<<HTML
      <div class="gestpay-box {$this->msg['class']}">
        {$this->msg['message']}
      </div>
      $content
HTML;
    }


    function gestpay_enqueue_js( $code ) {
      if ( ! $this->is_wc_gte_21() ) {
        global $woocommerce;
        return $woocommerce->add_inline_js( $code );
      }
      else {
        wc_enqueue_js( $code );
      }
    }


    function wc_empty_cart() {
      if ( $this->is_wc_gte_21() ) {
        WC()->cart->empty_cart();
      }
      else {
        global $woocommerce;
        $woocommerce->cart->empty_cart();
      }
    }

    /**
     * Backwards compatible add error
     */
    function wc_add_error( $error ) {
      if ( ! $this->is_wc_gte_21() ) {
        global $woocommerce;
        $woocommerce->add_error( $error );
      }
      else {
        if ( function_exists( 'wc_add_notice' ) ) {
          wc_add_notice( $error, 'error' );
        }
      }
    }

    /**
     * Backwards compatible woocommerce log.
     */
    function wc_logger() {
      if ( ! $this->is_wc_gte_21() ) {
        global $woocommerce;
        return $woocommerce->logger();
      }
      else {
        // See wp-admin/admin.php?page=wc-status&tab=logs
        return new WC_Logger();
      }
    }

    /* short checks */
    function is_wc_gte_20() { return version_compare( wc_gestpay_starter_get_wc_version(), '2.0.0', '>=' ); }
    function is_wc_gte_21() { return version_compare( wc_gestpay_starter_get_wc_version(), '2.1.0', '>=' ); }
    function is_wc_gte_22() { return version_compare( wc_gestpay_starter_get_wc_version(), '2.2.0', '>=' ); }

  }

}


/*
 * Registers any arbitrary API request handler.
 * We need to check the arguments when the user is redirected on the original
 * web site. To do that we need to listen for the "a" and the "b" parameters.
 * This differs from the WooCommerce 2.0-way in which some services (like IPN)
 * can be managed using the wc-api parameter.
 *
 * @see:
 * - http://docs.woothemes.com/document/payment-gateway-api/
 * - http://www.skyverge.com/blog/migrating-your-plugin-woocommerce-2-0/#payment_gateways
 * - http://wcdocs.woothemes.com/version-notes/woocommerce-1-6-6-2-0-plugin-and-theme-compatibility/
 * - http://www.mrova.com/lets-create-a-payment-gateway-plugin-payu-for-woocommerce/
 */

add_action( 'init', 'check_wc_gestpay_starter_response_new_wc', 999 );
function check_wc_gestpay_starter_response_new_wc() {
  if ( version_compare( wc_gestpay_starter_get_wc_version(), '2.0.0', '>=' ) ) {
    if ( isset( $_GET['a'] ) && isset( $_GET['b'] ) ) {
      $gestpay_starter = new WC_Gateway_Gestpay_Starter();
      $gestpay_starter->check_wc_gestpay_starter_response();
    }
  }
}

/**
 * Returns the WooCommerce version number, backwards compatible to WC 1.x
 * @return null|string
 */
function wc_gestpay_starter_get_wc_version() {
  if ( defined( 'WC_VERSION' ) && WC_VERSION ) return WC_VERSION;
  if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION ) return WOOCOMMERCE_VERSION;
  return null;
}