# A DeVault Payment plugin for woocommerce by Pest


PLEASE NOTE: I found a problem when using a gift card plugin. seems to work fine
w manually created coupons. ill fix it asap today if i have time


**tl;dr**
Accept DeVault in your woocommerce store.


**How it works etc.**  
1. Download plugin as zip from this github and upload in your wordpress site under install pluings.
2. Activate.
3. Click settings which takes you to the woocommerce->settings->payments->devault-payments settings page.
4. Fill out settings such as store address.
5. You can translate the checkout payment verification but changing the text in the description field. remember to keep the format separated by ```/``` .
The payment will be set as pending on your orders page  where you will also find the DVT amount and the TXID. This is to avoid problems untill I add a better verification method.
6. You have the follwing shortcodes availabla:  
[dvt-logo] adds a DeVault logo. you can set the size by width="" inside the brackets fx: [dvt-logo width="200"]
[dvt-icon-dark] adds a dark DeVault icon.  
[dvt-icon-light] adds a light DeVault icon.  
[dvt-price] shows current price of DeVault in the chosen woocommerce currency.
7. in woocommerce->settings->general you can set the woocommerce store currency to DVT.

**TODO/UPCOMING**
1. Add support for list of disposable addresses for security and to help make payments more unique.
2. Add support for DeLight wallet verification.




**Links**  
Website: http://www.devault.cc/  
Forum: https://devaultchat.cc/  
Github: https://github.com/devaultcrypto  
