=== WC GestPay Starter ===
Contributors: baba_mmx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4
Tags: woocommerce, payment gateway, payment, credit card, gestpay, gestpay starter, gestpay pro, gestpay professional, banca sella, sella.it, easynolo
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 20150314
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WooCommerce providing a payment gateway for the Starter (ex-Basic) version of the GestPay (Banca Sella) service.

== Description ==

Extends WooCommerce providing a payment gateway for the Starter (ex-Basic) version of the GestPay (Banca Sella) service.

**This plugin only works with the Starte (ex-Basic) version of the GestPay service.**

**This plugin is intended only for the italian audience and for that the following instructions are in italian.**

= Note sulle versioni =

La versione [Starter](https://www.gestpay.it/gestpay/offerta/starter.jsp "GestPay Starter") (chiamata precedentemente Basic) consente di effettuare i pagamenti con le più diffuse carte di credito e tramite PayPal.
A differenza della versione Professional **non consente di**:

* Gestire pagamenti in valuta diversa da euro
* Pianificare invii automatici dei report via mail (Opzione disponibile solo nel backoffice di Sella)
* Personalizzare i filtri per le estrazioni dei dati sui pagamenti (Opzione disponibile solo nel backoffice di Sella)
* Personalizzare automaticamente la lingua sulla pagina di pagamento

La versione Professional è [acquistabile sul mio sito](http://www.mauromascia.com/shop/product/woocommerce-gestpay-professional-banca-sella "GestPay Professional su mauromascia.com") e consentirà di specificare alcuni parametri tra cui la lingua del back office di pagamento di GestPay.
Un'altra funzionalità disponibile solo nella versione Pro è quella di specificare le icone relative alle carte effettivamente abilitate.

Per maggiori informazioni contattemi all'indirizzo: **info@mauromascia.com**


= Gestione degli errori =


**Non viene raggiunta la pagina di pagamento di Sella**

Il problema è probabilmente imputabile all'assenza della librearia SOAP Client di PHP sul server web.
Se su www.tuosito.it/wp-admin/admin.php?page=woocommerce_status è presente il messaggio d'errore:
`SOAP Client: Il server non ha la classe SOAP Client abilitata - alcuni plugin di gateway che utilizzano SOAP potrebbero non funzionare come previsto.`
è necessario installare/abilitare la librera SOAP Client di PHP o contattare il proprio hosting provider e richiedere l'abilitazione.


**Errori 1131 o 1142**

Questo tipo di errori può scaturire da uno dei seguenti casi:

* non si sta usando l'ultima versione di questo plugin

* lo shop login non è valorizzato correttamente

* si sta usando lo shop login di test nell'ambiente reale o viceversa: "Sandbox/test mode" non deve essere selezionato nell'ambiente reale!

* l'indirizzo IP inserito nella pagina di amministrazione di GestPay non corrisponde a quello ricevuto dal sistema di Banca Sella: questo accade perché il vostro provider di hosting assegna un particolare indirizzo IP al vostro sito web ma il server su cui viene ospitato ha un differente indirizzo IP, ed è questo l'indirizzo IP che interessa a Sella.

In questi casi non potrete avere la sicurezza che il sito venga ospitato sempre sullo stesso server, perché lo potrebbero spostare su un server diverso a seconda delle necessità (carico, guasto, ecc): per questo motivo non è detto che vi basti utilizzare un solo indirizzo IP del server ma potrebbe servirvi uno o più range di IP.

In linea generale, per risolvere il problema dovreste contattare il vostro fornitore di hosting richiedendo il reale (o i reali) IP da utilizzare. Questa operazione potrebbe essere difficile perché non è detto che siano tenuti a darvi tali informazioni. 

Nel caso di Aruba, per esempio, gli indirizzi IP da utilizzare dovrebbero essere i seguenti:

* 62.149.140.*
* 62.149.141.*
* 62.149.142.*
* 62.149.143.*

dove, l'asterisco finale indica tutti gli indirizzi IP in quel gruppo di indirizzi.

Ultimamente ad un utente (Angelo, che ringrazio) è capitato di dover utilizzare anche i seguenti indirizzi IP di Aruba:

* 62.149.144.*
* 62.149.145.*
* 62.149.146.*
* 62.149.147.*
* 62.149.148.*
* 62.149.149.*

ma non è detto che rimangano sempre e solo questi.

Purtroppo da parte mia non c'è molto da fare, quindi vi consiglio di prendere contatti sia con gli operatori di Banca Sella, sia con il vostro provider di hosting.


== Installation ==

1. Unzip the archive of the plugin or download it from the [official Wordpress plugin repository](http://wordpress.org/extend/plugins/woocommerce-gestpay/ "Woocommerce Gestpay")
2. Upload the folder 'woocommerce-gestpay' to the Wordpress plugin directory (../wp-content/plugins/)
3. Activate the plugin through the 'Plugins' menu in WordPress (WooCommerce - of course - MUST be already enabled!)
4. Configure it under WooCommerce -> Settings -> Payment Gateways and click on the **Gestpay Starter** link


== Screenshots ==

1. Pay with Gestpay Gateway (frontend)
2. Redirection to Banca Sella page
3. Configuration panel

== Changelog ==

= 20150314 =
* Fix altre compatibilità Woocommerce 2.3.x

= 20150303 =
* Fix compatibilità email Woocommerce 2.3.x

= 20150217 =
* Fix compatibilità con WooCommerce 2.3.3

= 20140710 =
* Aggiunta gestione della pagina view_order e order_received di WooCommerce.
* Aggiunta opzione per mostrare/nascondere il messaggio addizionale di ordine ricevuto.
* Corretto link all'ordine nel messaggio di ordine ricevuto.