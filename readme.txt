=== Plugin Name ===
Contributors: baba_mmx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4
Tags: woocommerce, credit card, gestpay, banca sella, sella.it
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WooCommerce providing the Basic version of the GestPay (Banca Sella) redirect gateway for WooCommerce.

== Description ==

Extends WooCommerce providing the Basic version of the GestPay (Banca Sella) redirect gateway for WooCommerce.

**This plugin only works with the BASIC version of the GestPay service.**

= ADVANCED VERSION NOTES =

*The ADVANCED version will also allow to directly insert the card informations (such as: number, verify code, date, email and so on) and for this reason, errors must be checked and handled here. ATM I can only make tests on the BASIC account version in which all the card checks are made in the GestPay page self: if an error occurs, there is no problem in this plugin because before return something, all values must be verified by the GestPay gateway it self. So, from the GestPay page to the view-order page, will be always a good response (a part of server/webservice error that are handled).*

If you need also the ADVANCED version send me an email at **info@mauromascia.com**

= Notes for italian users =

Visto che il plugin verrà utilizzato soltanto dagli utenti italiani, inserisco qui di seguito alcune note in lingua italiana.

Il plugin è compatibile con l'attuale versione 2.0 di WooCommerce ma se alcuni di voi usassero ancora la vecchia versione, la 1.6.1, potete scaricare la versione precedente di questo plugin seguendo al link: [WC GestPay 1.0.1](http://downloads.wordpress.org/plugin/woocommerce-gestpay.1.0.1.zip "WC GestPay 1.0.1")

= Gestione degli errori =

**Errore 1131**
Dipende quasi sicuramente dal fatto che l'indirizzo IP inserito nella pagina di amministrazione di GestPay non corrisponde a quello reale del server.
Per risolvere il problema dovreste contattare il vostro fornitore di hosting richiedendo il reale (o i reali) IP da utilizzare.
Per esempio nel caso di Aruba gli indirizzi IP da utilizzare dovrebbero essere i seguenti:
* 62.149.140.*
* 62.149.141.*
* 62.149.142.*
* 62.149.143.*

con l'asterisco finale.

**Errore 1142**
Dipende quasi sicuramente da uno seguenti fattori:
* lo shop login non è valorizzato correttamente
* si sta usando lo shop login di test nell'ambiente reale o viceversa: "Process URL" non deve essere selezionato nell'ambiente reale!

In entrambi i casi verificare che NON si sta usando la versione 1.0 di questo plugin perché conteneva un errore nell'indirizzo da utilizzare nell'ambiente reale.

== Installation ==

1. Unzip the archive of the plugin or download it from the [official Wordpress plugin repository](http://wordpress.org/extend/plugins/woocommerce-gestpay/ "Woocommerce Gestpay")
2. Upload the folder 'woocommerce-gestpay' to the Wordpress plugin directory (../wp-content/plugins/)
3. Activate the plugin through the 'Plugins' menu in WordPress (WooCommerce - of course - MUST be already enabled!)
4. Configure it under WooCommerce -> Settings -> Payment Gateways and click on the Gestpay link
5. Enter the following information into the boxes provided:
* Enable/Disable: Tick to Enable/Disable this payment gateway
* Title: The title which the user sees during checkout
* Description: This controls the description which the user sees during checkout.
* GestPay Shop Login: The **shopLogin** ID as provided by GestPay
* Process URL: Tick to use the test URL, else will be used the real one
6. Click the "Save changes" button when you have finished.

== Frequently Asked Questions ==

= Do I need to edit any files? =
No, once followed the installation instruction you have done. Just check for file permissions (chmod +x).
You may need to change `gestpay-cards.jpg` under `images` folder to add other cards (default is VISA and Mastercard).

= I need some plugin modifications. How can I tell you what I want? =
Send me an email at **info@mauromascia.com** and we'll talk about it.

== Screenshots ==

1. Pay with Gestpay Gateway (frontend)
2. Redirection to Banca Sella page
3. Configuration panel

== Changelog ==

= 2.0 =
* Updated code for the last WooCommerce version (2.0)

= 1.0.1 =
* Added: Better error handling

= 1.0 =
* First release
