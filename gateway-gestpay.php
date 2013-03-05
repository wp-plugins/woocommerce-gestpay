<?php
/*
  Plugin Name: WooCommerce GestPay Redirect Gateway (Basic version)
  Plugin URI: http://www.mauromascia.com/portfolio/wordpress-woocommerce-gestpay
  Description: Extends WooCommerce providing the Basic version of the GestPay (Banca Sella) redirect gateway for WooCommerce.
  Version: 2.0
  Author: Mauro Mascia (baba_mmx)
  Author URI: http://www.mauromascia.com
  License: GPLv2
  Support: info@mauromascia.com

  Copyright 2012  Mauro Mascia (email: info@mauromascia.com)

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

/*
 * Useful doc:
 * - http://wcdocs.woothemes.com/codex/extending/payment-gateway-api/
 *
 * Banca sella / Gestpay related useful doc:
 * - http://service.easynolo.it/download/GestPaySpecifichetecnichecrittografia2.1.pdf
 * - http://faustinelli.wordpress.com/2011/12/11/banca-sella-ws-for-dummies-i-web-services-di-banca-sella/
 * - http://www.mariaserenapiccioni.com/2010/10/come-criptare-i-dati-da-inviare-a-banca-sella-usando-il-webservice-wscryptdecrypt/
 * - http://www.openbrain.it/pagamento-con-banca-sella/
 */

add_action('plugins_loaded', 'init_gestpay_gateway', 0);

function init_gestpay_gateway() {

  if (!class_exists('WC_Payment_Gateways'))
    return;

  if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
    echo "<br><strong>Please update WooCommerce to the last version or restore the previous GestPay version.</strong><br>";
    echo "<br><strong>Perfavore, aggiorna Woocommerce all'ultima versione oppure ripristina la precedente versione di GestPay</strong><br><br>";
    return;
  }

  /**
   * Add the gateway to WooCommerce.
   *
   * @access public
   * @param array $methods
   * @return array
   */
  add_filter( 'woocommerce_payment_gateways', 'woocommerce_gestpay_add_gateway' );

  function woocommerce_gestpay_add_gateway( $methods ) {
      $methods[] = 'WC_Gateway_Gestpay';
      return $methods;
  }

  /**
   * Gateway's Constructor.
   *
   * @return void
   */
  class WC_Gateway_Gestpay extends WC_Payment_Gateway {

    public function __construct() {
      global $woocommerce;

      $this->id = 'gestpay';
      $this->method_title = __('Gestpay', 'woocommerce_gestpay');
      $this->logo = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/gestpay.jpg';
      $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/gestpay-cards.jpg';

      // Set up localisation
      $this->load_plugin_textdomain();

      // Load the form fields.
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();

      // Define user set variables
      $this->title = $this->settings['title'];
      $this->description = $this->settings['description'];
      $this->shopLogin = $this->settings['shopLogin'];
      $this->gestpay_processUrl = ($this->settings['gestpay_processUrl'] == "yes" ? true : false);
      $this->transactionDate = date('Y-m-d H:i:s O');
      //$this->gestpay_account_type = $this->settings['gestpay_account_type']; // Advanced/Basic

      $this->has_fields = false; // doesn't output a payment_box containing direct payment form

      /*
       * ==========   See ADVANCED-VERSION-NOTES in the readme file   ==========
       *
       *  if ($this->gestpay_account_type == 'Advanced') {
       *      $this->has_fields = true;
       *  }
       *  else {
       *      $this->has_fields = false;
       *  }
       *
       * is needed.
       */

      // Add style
      wp_register_style('gestpay-css', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/gestpay.css');
      wp_enqueue_style('gestpay-css');

      // Actions
      add_action('init', array(&$this, 'check_gestpay_response'));
      add_action('valid-gestpay-request', array(&$this, 'successful_request'));
      add_action('woocommerce_receipt_gestpay', array(&$this, 'receipt_page'));

      // Add the ID in WC 2.0
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));

      add_action('woocommerce_thankyou_gestpay', array(&$this, 'thankyou_page'));
    }

    /**
     * Localisation.
     *
     * @access public
     * @return void
     */
    function load_plugin_textdomain() {
      $locale = apply_filters('plugin_locale', get_locale(), 'woocommerce');
      $this->locale = $locale;
      load_textdomain('woocommerce_gestpay', dirname(plugin_basename(__FILE__)) . '/languages/woocommerce_gestpay-' . $locale . '.mo');
      load_plugin_textdomain('woocommerce_gestpay', false, dirname(plugin_basename(__FILE__)) . "/languages");
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

      $this->form_fields = array(
          'enabled' => array(
              'title' => __('Enable/Disable:', 'woothemes'),
              'type' => 'checkbox',
              'label' => __('Enable GestPay Redirect Payment Module.', 'woocommerce_gestpay'),
              'default' => 'yes'
          ),
          'title' => array(
              'title' => __('Title:', 'woothemes'),
              'type' => 'text',
              'description' => __('The title which the user sees during checkout.', 'woothemes'),
              'default' => __('Credit/Debit Card', 'woocommerce_gestpay')
          ),
          'description' => array(
              'title' => __('Description:', 'woothemes'),
              'type' => 'textarea',
              'description' => __('This controls the description which the user sees during checkout.', 'woothemes'),
              'default' => __("Pay securely by Credit or Debit card through GestPay's Secure Servers.", 'woocommerce_gestpay')
          ),
          'shopLogin' => array(
              'title' => __('GestPay Shop Login:', 'woothemes'),
              'type' => 'text',
              'description' => __('Please enter your shopLogin as provided by GestPay.', 'woocommerce_gestpay'),
              'default' => ''
          ),
          'gestpay_processUrl' => array(
              'title' => __('Process URL:', 'woothemes'),
              'type' => 'checkbox',
              'label' => __(' ', 'woothemes'),
              'description' => __('If checked (default), the checkout will be processed with the test URL, else with the real one.', 'woocommerce_gestpay'),
              'default' => 'yes'
          ),
              /* ==========   See ADVANCED-VERSION-NOTES in the readme file   ==========
               *
               * If will be added the ADVANCED version code, also the choice of the
               * basic or the advanced version must be enable.
               *
                'gestpay_account_type' => array(
                'title' => __( 'GestPay Account Type:', 'woocommerce_gestpay' ),
                'type' => 'select',
                'options' => array('Advanced', 'Basic'),
                'description' => __( 'Please select your GestPay account type.', 'woocommerce_gestpay' ),
                'default' => 'Basic'
                ),
               */
      );
    }

// End init_form_fields()

    /**
     * Admin Panel Options
     */
    public function admin_options() {
      ?>
      <p><a href="http://www.sella.it/" target="_blank"><img src="<?= $this->logo; ?>" /></a></p>
      <h3>GestPay Redirect Payments</h3>
      <p><?php _e('Accept payments from Credit/Debit cards through the GestPay Payment Gateway. The customer will be redirected to a secure GestPay hosted page to enter their card details.', 'woocommerce_gestpay'); ?></p>
      <p><?php _e('In the GestPay account, you have to set the response URLs to the one of the view order, for example: http://hostname/my-account/view-order/ to let the customer to know the transaction results.', 'woocommerce_gestpay'); ?></p>
      <table class="form-table">
        <?php
        // Generate the HTML for the fields on the "settings" screen.
        // This comes from class-wc-settings-api.php
        $this->generate_settings_html();
        ?>
      </table><!--/.form-table-->
      <?php
    }

// End admin_options()

    /**
     * payment_fields is shown on the checkout page and slides out to reveal the
     * content when the gateway is selected. This function is used by direct
     * gateways to show payment fields for things like credit card numbers.
     *
     * There are no payment fields for gestpay, but we want to show the description if set.
     */
    function payment_fields() {
      if ($this->description)
        echo wpautop(wptexturize($this->description));
    }

    /**
     * Generate the receipt page
     */
    function receipt_page($order_id) {
      /*
       * In the generate_gestpay_form there is an auto-redirect to the GestPay payment
       * website page, so each string shown here isn't shown really. The only
       * way this string is visible is if there are errors (there is not redirect)
       * but in this case, isn't useful to show the following string:
       *
       * echo '<p>'.__('Thank you for your order, please click the button below to pay with gestpay.', 'woocommerce_gestpay').'</p>';
       */

      $ret = $this->generate_gestpay_form($order_id);

      if ($ret == false) {
        $info = __("If you have come to this page it is likely that there has been an error in our system or in the GestPay payment system. In any case, an automated email has been sent to the system administrator, who will verify that error as soon as possible. To place your purchase you can try again later. We apologize for the inconvenience.", 'woocommerce_gestpay');
        $this->msg['class'] = 'woocommerce_info';
        $this->msg['message'] = $info;
        echo $this->show_message("");
      }
      else {
        echo $ret;
      }
    }

    /**
     * Generate the GestPay button link
     */
    public function generate_gestpay_form($order_id) {
      global $woocommerce;
      $order = new WC_Order( $order_id );

      // Set Web Service process url to test or real
      if ($this->gestpay_processUrl) {
        $gestpay_ws_crypt_url = "https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        $this->liveurl = "https://testecomm.sella.it/gestpay/pagam.asp";
      }
      else {
        //$gestpay_ws_crypt_url = "https://ecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        $gestpay_ws_crypt_url = "https://ecomms2s.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        $this->liveurl = "https://ecomm.sella.it/gestpay/pagam.asp";
      }

      // Set currency code
      $gestpay_allowed_currency_codes = array(
          'USD' => '1',
          'GBP' => '2',
          'CHF' => '3',
          'JPY' => '71',
          'HKD' => '103',
          'EUR' => '242'
      );

      if (in_array(get_option('woocommerce_currency'), array_keys($gestpay_allowed_currency_codes))) {
        $this->currency = $gestpay_allowed_currency_codes[get_option('woocommerce_currency')];
      }
      else {
        $this->currency = '242'; // Set EUR as default currency code
      }

      // Define GestPay parameters
      $params = new stdClass();

      // BASIC version parameters: must be used only this parameters
      $params->shopLogin = $this->shopLogin;
      $params->uicCode = '242'; //$this->currency;
      $params->amount = $order->order_total;
      $params->shopTransactionId = $order_id;

      /* ==========   See ADVANCED-VERSION-NOTES in the readme file   ==========
       *
       * ADVANCED version parameters: can be added also other informations
       *
        if ($this->gestpay_account_type == 'Advanced') {
        // Set country-language
        $gestpay_allowed_country_codes = array(
          'IT' => '1',
          'EN' => '2',
          'ES' => '3',
          'FR' => '4',
          'DE' => '5'
        );

        if (in_array($order->billing_country, array_keys($gestpay_allowed_country_codes))) {
        $params->languageId = $gestpay_allowed_country_codes[$order->billing_country];
        }
        else {
        // TODO: how to handle others county codes?
        $params->languageId = '1';
        }

        $params->buyerName = $order->billing_first_name . " " . $order->billing_last_name;
        $params->buyerEmail = $order->billing_email;

        // TODO: add other fields if necessary
        }
       *
       */

      // Create a SOAP client using the GestPay webservice
      try {
        $client = new SoapClient($gestpay_ws_crypt_url);
      }
      catch (Exception $e) {
        $err = __("Fatal Error: Soap Client Request Exception", 'woocommerce_gestpay');
        $this->msg['class'] = 'woocommerce_error';
        $this->msg['message'] = $err . " [{$e->getMessage()}]";
        echo $this->show_message("");

        $this->send_email_to_admin($this->msg['message']);

        return false;
      }

      // Encrypt values using the GestPay webservice
      try {
        $objectresult = $client->Encrypt($params);
      }
      catch (Exception $e) {
        $err = __("Fatal Error: Soap Client Encryption Exception", 'woocommerce_gestpay');
        $this->msg['class'] = 'woocommerce_error';
        $this->msg['message'] = $err . " [{$e->getMessage()}]";
        echo $this->show_message("");

        $this->send_email_to_admin($this->msg['message']);

        return false;
      }

      $xml = simplexml_load_string($objectresult->EncryptResult->any);

      // Check if the encryption call can be accepted
      if ($xml->TransactionResult == "KO") {
        $err = __("Fatal Error: Transaction Result Error", 'woocommerce_gestpay');
        $this->msg['class'] = 'woocommerce_error';
        $this->msg['message'] = $err . " [({$xml->ErrorCode}) {$xml->ErrorDescription}]";
        echo $this->show_message("");

        $this->send_email_to_admin($this->msg['message']);

        return false;
      }

      // Send the form to the GestPay server, using jQuery to auto-submit the form
      // If - for some reasons - javascript is disabled, show up two buttons to manually send the form
      return '<form action="' . $this->liveurl . '" method="post" id="gestpay_payment_form">
					<input name="a" type="hidden" value="' . $this->shopLogin . '">
          <input name="b" type="hidden" value="' . $xml->CryptDecryptString . '">

					<input type="submit" class="button-alt" id="submit_gestpay_payment_form" value="' . __('Pay via gestpay', 'woothemes') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'woothemes') . '</a>
					<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block({
									message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />' . __('Thank you for your order. We are now redirecting you to gestpay to make payment.', 'woothemes') . '",
									overlayCSS: {background: "#fff",opacity: 0.6},
									css: {padding:20,textAlign:"center",color:"#555",border:"3px solid #aaa",backgroundColor:"#fff",cursor:"wait",lineHeight:"32px"}
								});
							jQuery("#submit_gestpay_payment_form").click();
						});
					</script>
				</form>';
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    function process_payment( $order_id ) {
      $order = new WC_Order( $order_id );

      return array(
        'result' => 'success',
        'redirect' => add_query_arg(
          'order',
          $order->id,
          add_query_arg(
            'key',
            $order->order_key,
            get_permalink( woocommerce_get_page_id( 'pay' ) )
          )
        )
      );
    }

    /**
     * Check for valid gestpay server callback
     */
    function check_gestpay_response() {
      global $woocommerce;

      if (isset($_GET['a']) && isset($_GET['b'])) {
        $params = new stdClass();
        $params->shopLogin = $_GET['a'];
        $params->CryptedString = $_GET['b'];

        // Set Web Service process url to test or real
        if ($this->gestpay_processUrl) {
          $gestpay_ws_crypt_url = "https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        }
        else {
          //$gestpay_ws_crypt_url = "https://ecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
          $gestpay_ws_crypt_url = "https://ecomms2s.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
        }

        // Create a SOAP client using the GestPay webservice
        try {
          $client = new SoapClient($gestpay_ws_crypt_url);
        }
        catch (Exception $e) {
          $err = __("Fatal Error: Soap Client Response Exception", 'woocommerce_gestpay');
          $this->msg['class'] = 'woocommerce_error';
          $this->msg['message'] = $err . " [{$e->getMessage()}]";
          echo $this->show_message("");

          $this->send_email_to_admin($this->msg['message']);

          return false;
        }

        // Decrypt response using the GestPay webservice
        try {
          $objectresult = $client->Decrypt($params);
        }
        catch (Exception $e) {
          $err = __("Fatal Error: Soap Client Decryption Exception", 'woocommerce_gestpay');
          $this->msg['class'] = 'woocommerce_error';
          $this->msg['message'] = $err . " [{$e->getMessage()}]";
          echo $this->show_message( "" );

          $this->send_email_to_admin( $this->msg['message'] );

          return false;
        }

        $xml = simplexml_load_string( $objectresult->DecryptResult->any );

        $order_id = $xml->ShopTransactionID;
        $order = new WC_Order( (int) $order_id );

        if ($xml->TransactionResult == "OK") {
          $transaction = '<a href="' . esc_url(add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('view_order')))) .
                  '">' . $order->get_order_number() . '</a>';

          $str = __("Thank you for shopping with us. Your transaction %s has been processed correctly. We will be shipping your order to you soon.", 'woocommerce_gestpay');
          $this->msg['message'] = sprintf($str, $transaction);
          $this->msg['class'] = 'woocommerce_message';

          if ($order->status !== 'completed') {
            if ($order->status == 'processing') {
              // This is the second call - do nothing
            }
            else {
              // Update order status, add admin order note and empty the cart
              $order->payment_complete();
              $order->add_order_note('GestPay Payment: SUCCESSFUL<br>' . $order_id);
              $woocommerce->cart->empty_cart();
            }
          }
        }
        else {
          $this->msg['class'] = 'woocommerce_error';
          $this->msg['message'] = "ERROR [{$xml->ErrorCode}] : {$xml->ErrorDescription}";

          $this->send_email_to_admin($this->msg['message']);

          // Update order status and add admin order note
          $order->update_status('failed');
          $order->add_order_note("GestPay Payment Failed with error [{$xml->ErrorCode}]: {$xml->ErrorDescription} for $order_id");
        }

        add_action('the_content', array(&$this, 'show_message'));
      }
    }

    function show_message($content) {
      return <<<HTML
      <div class="gestpay-box {$this->msg['class']}">
        {$this->msg['message']}
      </div>
      $content
HTML;
    }

    /**
     * Send an email to the email specified in the wp settings
     *
     * @param string $err
     */
    function send_email_to_admin($err) {
      $object = "[WOOCOMMERCE GESTPAY ERROR NOTIFICATION]";
      $body = __("It seems that an error occours: ", 'woocommerce_gestpay');
      $body .= $err;

      $from = $to = get_settings('admin_email');

      $header = "MIME-Version: 1.0\r\n";
      $header.= "Content-type: text/html; charset=iso-8859-1\r\n";
      $header.= "From:<$from>\r\n";
      $header.= "Reply-To:<$from>\r\n";
      $header.= "X-Mailer: PHP/" . phpversion();

      @mail($to, $object, $body, $header);
    }

    /**
     * thankyou_page is hooked into the thanks page
     *
     * @global type $woocommerce
     */
    function thankyou_page() {
      global $woocommerce;

      // grab the order ID from the querystring
      $order_id = $_GET['OrderID'];

      // lookup the order details
      $order = new woocommerce_order((int) $order_id);

      //check the status of the order
      if ($order->status == 'processing') {
        //display additional success message
        echo "<p>Your payment for " . woocommerce_price($order->order_total) . " was successful. The authorisation code has been recorded, <a href=\"" . esc_url(add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_view_order_page_id')))) . "\">click here to view your order</a></p>";
      }
      else {
        //display additional failed message
        echo "<br>&nbsp;<p>For further information on why your order might have failed, <a href=\"" . esc_url(add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_view_order_page_id')))) . "\">click here to view your order</a>.</p>";
      }
    }

  }

}
