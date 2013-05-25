<?php
/*
  Plugin Name: WooCommerce GestPay Starter
  Plugin URI: http://wordpress.org/plugins/woocommerce-gestpay/
  Description: Extends WooCommerce providing a payment gateway for the Starter (ex-Basic) version of the GestPay (Banca Sella) service.
  Version: 2.2.0
  Author: Mauro Mascia (baba_mmx)
  Author URI: http://www.mauromascia.com
  License: GPLv2
  Support: info@mauromascia.com

  Copyright 2013  Mauro Mascia (info@mauromascia.com)

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

  if ( !class_exists( 'WC_Payment_Gateways' ) )
    return;

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
      global $woocommerce;

      $this->id = 'gestpay-starter';
      $this->method_title = __( 'Gestpay Starter', 'woocommerce_gestpay_starter' );
      $this->logo = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/images/gestpay-starter.png';
      $this->icon = WP_PLUGIN_URL . "/" . plugin_basename( dirname (__FILE__ ) ) . '/images/gestpay-cards.jpg';
      $this->logfile = 'gestpay-starter';

      // 1) Set up localisation
      $this->load_plugin_textdomain();

      // 2) Load the strings used in this plugin
      $this->init_strings();

      // 3) Load the form fields.
      $this->init_form_fields();

      // 4) Load the settings.
      $this->init_settings();

      // 5) Define user set variables
      $this->title = $this->settings['title'];
      $this->description = $this->settings['description'];
      $this->shopLogin = $this->settings['shopLogin'];
      $this->gestpay_processUrl = $this->settings['gestpay_processUrl'] == "yes" ? true : false;
      $this->transactionDate = date( 'Y-m-d H:i:s' );
      $this->force_recrypt = $this->settings['force_recrypt'] == "yes" ? true : false;
      $this->send_email_on_error = $this->settings['send_email_on_error'] == "yes" ? true : false;
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
      if ( $this->debug ) {
        $this->log = $woocommerce->logger();
      }

      // Doesn't output a payment_box containing direct payment form
      $this->has_fields = false;

      // Add style
      wp_register_style( 'gestpay-starter-css', WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/gestpay-starter.css' );
      wp_enqueue_style( 'gestpay-starter-css' );

      // Actions
      add_action( 'woocommerce_receipt_' . $this->id, array( &$this, 'receipt_page' ) );
      add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );


      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      }
      else {
        add_action( 'init', array( &$this, 'check_wc_gestpay_starter_response' ) );
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
      }
    }

    /**
     * Localisation.
     */
    function load_plugin_textdomain() {
      $locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce' );
      $this->locale = $locale;
      load_textdomain( 'woocommerce_gestpay_starter', dirname( plugin_basename( __FILE__ ) ) . '/languages/woocommerce_gestpay_starter-' . $locale . '.mo' );
      load_plugin_textdomain( 'woocommerce_gestpay_starter', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => $this->strings['field_enabled']['t'],
          'type' => 'checkbox',
          'label' => $this->strings['field_enabled']['l'],
          'default' => 'yes'
        ),
        'title' => array(
          'title' => $this->strings['field_title']['t'],
          'type' => 'text',
          'description' => $this->strings['field_title']['d'],
          'default' => $this->strings['field_title']['def']
        ),
        'description' => array(
          'title' => $this->strings['field_description']['t'],
          'type' => 'textarea',
          'description' => $this->strings['field_description']['d'],
          'default' => $this->strings['field_description']['def']
        ),

        // -- SHOP LOGIN

        'shopLogin' => array(
          'title' => 'GestPay Shop Login:',
          'type' => 'text',
          'description' => $this->strings['field_shoplogin']['d'],
          'default' => ''
        ),

        // -- GESTPAY PRO PARAMETERS

        'parameters' => array(
          'title' => $this->strings['field_parameters']['t'],
          'type' => 'title',
          'description' => '',
          'class' => 'pro-disable-section',
        ),
        'param_buyer_email' => array(
          'title' => 'Buyer E-mail:',
          'type' => 'checkbox',
          'label' => $this->strings['field_buyer_email']['l'],
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'param_buyer_name' => array(
          'title' => 'Buyer Name:',
          'type' => 'checkbox',
          'label' => $this->strings['field_buyer_name']['l'],
          'default' => 'no',
          'class' => 'pro-disable-element'
        ),
        'param_language' => array(
          'title' => 'Language:',
          'type' => 'checkbox',
          'label' => $this->strings['field_language']['l'],
          'default' => 'no',
          'description' => $this->strings['field_language']['d'],
          'class' => 'pro-disable-element'
        ),
        'param_custominfo' => array(
          'title' => 'Custom Info:',
          'type' => 'textarea',
          'description' => $this->strings['field_custominfo']['d'],
          'class' => 'pro-disable-element',
          'default' => '',
        ),

        // -- ICONS

        'cards' => array(
          'title' => $this->strings['field_cards']['t'],
          'type' => 'title',
          'description' => $this->strings['field_cards']['d'],
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
          'title' => $this->strings['field_testing']['t'],
          'type' => 'title',
          'description' => '',
        ),
        'gestpay_processUrl' => array(
          'title' => $this->strings['field_processurl']['t'],
          'type' => 'checkbox',
          'label' => $this->strings['field_processurl']['l'],
          'description' => $this->strings['field_processurl']['d'],
          'default' => 'yes'
        ),
        'send_email_on_error' => array(
          'title' => 'Debug email:',
          'type' => 'checkbox',
          'label' => $this->strings['field_emaildebug']['l'],
          'default' => 'no',
          'description' => $this->strings['field_emaildebug']['d'],
        ),
        'debug' => array(
          'title' => 'Debug Log:',
          'type' => 'checkbox',
          'label' => $this->strings['field_debug']['l'],
          'default' => 'no',
          'description' => $this->strings['field_debug']['d'],
        ),

        // -- EXPERIMENTAL

        'experimental' => array(
          'title' => $this->strings['field_experimental']['t'],
          'type' => 'title',
          'description' => '',
        ),
        'force_recrypt' => array(
          'title' => $this->strings['field_force_recrypt']['t'],
          'type' => 'checkbox',
          'label' => $this->strings['field_force_recrypt']['l'],
          'default' => 'no',
          'description' => $this->strings['field_force_recrypt']['d'],
        ),

      );
    }

    /**
     * Initialise Gateway Strings
     */
    function init_strings() {
      include_once( 'gestpay-starter-strings.php' );
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
      global $woocommerce;
      $onlypro = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/images/onlypro.png';
      $woocommerce->add_inline_js( '

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
      if ( $this->debug )
        $this->log->add( $this->logfile, "[INFO]: " . "Processing payment..." );

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
      global $woocommerce;

      // Create a SOAP client using the GestPay webservice
      try {
        $client = new SoapClient( $this->gestpay_ws_crypt_url );
      }
      catch ( Exception $e ) {
        $err = sprintf( $this->strings['soap_req_error'], $e->getMessage() );
        $woocommerce->add_error( $err );

        if ( $this->debug )
          $this->log->add( $this->logfile, '[ERROR]: ' . $err );

        if ( $this->send_email_on_error )
          $this->wc_mail( $this->strings['transaction_error_subject'], $err );

        return false;
      }

      // Encrypt values using the GestPay webservice
      try {
        // https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?op=Encrypt
        $objectresult = $client->Encrypt( $params );
      }
      catch ( Exception $e ) {
        $err = sprintf( $this->strings['soap_enc_error'], $e->getMessage() );
        $woocommerce->add_error( $err );

        if ( $this->debug )
          $this->log->add( $this->logfile, '[ERROR]: ' . $err );

        if ( $this->send_email_on_error )
          $this->wc_mail( $this->strings['transaction_error_subject'], $err );

        return false;
      }

      $xml = simplexml_load_string( $objectresult->EncryptResult->any );

      // Check if the encryption call can be accepted
      if ( $xml->TransactionResult == "KO" ) {
        $err = sprintf( $this->strings['transaction_error'], $order_id, ' (' . $xml->ErrorCode . ') ' . $xml->ErrorDescription );

        $woocommerce->add_error( $err );

        if ( $this->debug )
          $this->log->add( $this->logfile, '[ERROR]: ' . $err );

        if ( $this->send_email_on_error )
          $this->wc_mail( $this->strings['transaction_error_subject'], $err );

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
      global $woocommerce;
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
      $woocommerce->add_inline_js( '
        jQuery("body").block({
            message: "Thank you for your order. We are now redirecting you to GestPay to make payment.",
            baseZ: 99999,
            overlayCSS:	{
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
              lineHeight:		  "24px",
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


    function check_wc_gestpay_starter_response() {
      if ( isset( $_GET['a'] ) && isset( $_GET['b'] ) ) {
        global $woocommerce;

        if ( $this->debug )
          $this->log->add( $this->logfile, "[INFO]: " . $this->strings['gestpay_response'] );

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

          if ( $this->send_email_on_error )
            $this->wc_mail( $this->strings['transaction_error_subject'], $err );

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

          if ( $this->send_email_on_error )
            $this->wc_mail( $this->strings['transaction_error_subject'], $err );

          return false;
        }

        $xml = simplexml_load_string( $objectresult->DecryptResult->any );

        $order_id = ( int ) $xml->ShopTransactionID;
        $order = new WC_Order( $order_id );

    //    if ( $this->debug ) {
    //      ob_start();
    //      var_dump($order);
    //      $result = ob_get_clean();
    //      $this->log->add( $this->logfile, "[VAR-DUMP]: " . $result );
    //    }

        $order_url = get_permalink( woocommerce_get_page_id( 'view_order' ) );

        $transaction = '<a href="' . esc_url( add_query_arg( 'order', $order_id, $order_url ) ) . '">' . $order_id . '</a>';

        if ( $xml->TransactionResult == "OK" ) {
          if ( $order->status !== 'completed' ) {
            if ( $order->status == 'processing' ) {
              // This is the second call - do nothing
              return true;
            }
            else {
              $this->msg['class'] = 'woocommerce_message';
              $this->msg['message'] = sprintf( $this->strings['transaction_thankyou'], $transaction );

              $this->show_message( "" );

              $msg = sprintf( $this->strings['transaction_ok'], $order_id );

              // Update order status, add admin order note and empty the cart
              $order->payment_complete();
              $order->add_order_note( $msg );
              $woocommerce->cart->empty_cart();

              if ( $this->debug )
                $this->log->add( $this->logfile, "[INFO]: " . $msg );
            }
          }
          else {
            if ( $this->debug )
              $this->log->add( $this->logfile, "[INFO]: " . sprintf( $this->strings['already_completed'], $order_id ) );
          }
        }
        else {
          $err_link = sprintf( $this->strings['transaction_error'], $transaction, ' (' . $xml->ErrorCode . ') ' . $xml->ErrorDescription );
          $err_str = sprintf( $this->strings['transaction_error'], $order_id, ' (' . $xml->ErrorCode . ') ' . $xml->ErrorDescription );

          // Set error message
          $this->msg['class'] = 'woocommerce_error';
          $this->msg['message'] = $err_link;

          $order->update_status( 'failed', $err_str );

          if ( $this->send_email_on_error )
            $this->wc_mail( $this->strings['transaction_error_subject'], $err_link );

          if ( $this->debug )
            $this->log->add( $this->logfile, "[ERROR]: " . $err_str );
        }

        add_action( 'the_content', array( &$this, 'show_message' ) );
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

    /**
     * Send an email to the admin
     */
    function wc_mail( $subject, $message ) {
      global $woocommerce;
      $to = get_settings( 'admin_email' );
      $mailer = $woocommerce->mailer();
      $mailer->wrap_message( $subject, $message );
      $mailer->send( $to, '[GESTPAY-STARTER] ' . $subject, $message );
    }

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

add_action( 'init', 'check_wc_gestpay_starter_response_new_wc' );
function check_wc_gestpay_starter_response_new_wc() {
  if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
    if ( isset( $_GET['a'] ) && isset( $_GET['b'] ) ) {
      $gestpay_starter = new WC_Gateway_Gestpay_Starter();
      $gestpay_starter->check_wc_gestpay_starter_response();
    }
  }
}
