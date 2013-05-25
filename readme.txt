=== Plugin Name ===
Contributors: baba_mmx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4
Tags: woocommerce, payment gateway, payment, credit card, gestpay, gestpay starter, gestpay pro, gestpay professional, banca sella, sella.it, easynolo
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 2.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WooCommerce providing a payment gateway for the Starter (ex-Basic) version of the GestPay (Banca Sella) service.

== Description ==

Extends WooCommerce providing a payment gateway for the Starter (ex-Basic) version of the GestPay (Banca Sella) service.

**This plugin only works with the START (ex-BASIC) version of the GestPay service.**

**This plugin is intended only for the italian audience and for that the following instructions are in italian.**

= Note sulle versioni =

La versione [Starter](https://www.gestpay.it/gestpay/offerta/starter.jsp "GestPay Starter") (chiamata precedentemente Basic) consente di effettuare i pagamenti con le più diffuse carte di credito e tramite PayPal.
A differenza della versione Professional non consente di:

* Gestire pagamenti in valuta diversa da euro
* Pianificare invii automatici dei report via mail
* Personalizzare i filtri per le estrazioni dei dati sui pagamenti
* Personalizzare automaticamente la lingua sulla pagina di pagamento

La versione Professional sarà acquistabile a breve sul mio sito [www.mauromascia.com](https://www.mauromascia.com "www.mauromascia.com") e consentirà di specificare alcuni parametri tra cui la lingua del back office di pagamento di GestPay.
Un'altra funzionalità disponibile solo nella versione Pro è quella di specificare le icone relative alle carte effettivamente abilitate.

Per maggiori informazioni ci possiamo sentire all'indirizzo: **info@mauromascia.com**


= Gestione degli errori =

**Errore 1131**

Dipende quasi sicuramente dal fatto che l'indirizzo IP inserito nella pagina di amministrazione di GestPay non corrisponde a quello reale del server.
Per risolvere il problema dovreste contattare il vostro fornitore di hosting richiedendo il reale (o i reali) IP da utilizzare.
Per esempio nel caso di Aruba gli indirizzi IP da utilizzare dovrebbero essere i seguenti:

* 62.149.140.*
* 62.149.141.*
* 62.149.142.*
* 62.149.143.*

dove, l'asterisco finale indica tutti gli indirizzi IP in quel gruppo di indirizzi.


**Errore 1142**

Dipende quasi sicuramente da uno seguenti fattori:

* lo shop login non è valorizzato correttamente
* si sta usando lo shop login di test nell'ambiente reale o viceversa: "Sandbox/test mode" non deve essere selezionato nell'ambiente reale!

In entrambi i casi verificare che NON si sta usando precedenti versioni di questo plugin.
La versione attuale è stata completamente rivista e adattata per funzionare sia con la versione 1.6 che la 2.x di WooCommerce.

== Installation ==

1. Unzip the archive of the plugin or download it from the [official Wordpress plugin repository](http://wordpress.org/extend/plugins/woocommerce-gestpay/ "Woocommerce Gestpay")
2. Upload the folder 'woocommerce-gestpay' to the Wordpress plugin directory (../wp-content/plugins/)
3. Activate the plugin through the 'Plugins' menu in WordPress (WooCommerce - of course - MUST be already enabled!)
4. Configure it under WooCommerce -> Settings -> Payment Gateways and click on the **Gestpay Starter** link

== Frequently Asked Questions ==

= Do I need to edit any files? =
No, once followed the installation instruction you have done. Just check for file permissions (chmod +x).

= I need some plugin modifications. How can I tell you what I want? =
Send me an email at **info@mauromascia.com** and we'll talk about it.

== Screenshots ==

1. Pay with Gestpay Gateway (frontend)
2. Redirection to Banca Sella page
3. Configuration panel

== Changelog ==

= 2.2 =
* Global refactoring of the code with better handling of options and translations
* Better back office presentation
* Solved compatibility issues between old and new WooCommerce versions
* Added informations about the Pro version
* Better error handling (log + email)
* Added experimental features
* Updated the name (it was "Basic", now it's called "Starter")

= 2.1 =
* Various changes

= 2.0 =
* Updated code for the last WooCommerce version (2.0)

= 1.0.1 =
* Added: Better error handling

= 1.0 =
* First release
