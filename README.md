# RuckPay PrestaShop module

This official module allows you to accept credit card payments via the RuckPay platform directly on PrestaShop.

## Requirements

### Minimal versions

| PrestaShop version | PHP Version    |
|--------------------|----------------|
| 1.7.8 - 8.1          | 7.1 or greater |

### Getting started
In order to configure the PrestaShop module, you need RuckPay API keys.
You can find your keys in the <q>Developers</q> tab on your [RuckPay account](https://dashboard.ruckpay.com).

When creating your account, a private and public key is automatically generated for test mode.
Live mode keys will be available after account validation.

## Supported payment method
The module allows you to make payments by credit card.

Payments are 3D Secure compatible.

## Resources
- PrestaShop module : (https://github.com/RuckPay/SDK-Prestashop/wiki) 
- RuckPay API : (https://documenter.getpostman.com/view/21166936/2s9Xy6rVu1)
- RuckPay Resource (FAQ) : (https://www.ruckpay.com/support-en/)

## License
The module for PrestaShop is available under the Apache 2.0 License. Check out the LICENSE file for more information.

## Before you begin
To test your integration:

* Use your test mode API Key. You can find them in [RUCKPAY Dashboard → Developpers → API Keys](https://dashboard.ruckpay.com/developers/apikeys)
* You can check the status of a test payment in your [RUCKPAY Dashboard → Transactions](https://dashboard.ruckpay.com/transactions/) (in test mode).

## Install
* Download the latest version of the [RUCKPAY PrestaShop module](https://github.com/RuckPay/SDK-Prestashop/releases/tag/v1.0.0)
* Go to your PrestaShop admin panel
* Navigate to **Modules → Module Manager**
* Click on **Upload a module**
* Select the downloaded `ruckpay.zip` file

![image](https://github.com/RuckPay/SDK-Prestashop/assets/104771160/b55488fe-e8d2-4a73-a395-dfa6df64c6b1)

## Configure
Go to PrestaShop → Payment → Payment Methods → RUCKPAY → Configure
1. Activate the module !
1. Enter your RUCKPAY's APIKeys for each environment
1. Select the Payment Methods to be use
1. **Save**

![image](https://github.com/RuckPay/SDK-Prestashop/assets/104771160/095795c9-a420-428b-a6fd-278ec6b2cb40)

## Before you go live
* Make sure that your account has been approved (LIVE enabled)
