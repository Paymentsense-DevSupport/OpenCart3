Paymentsense Extension for OpenCart
====================================

Payment extension for OpenCart, allowing you to take payments via Paymentsense.

Description
-----------

Payment extension for OpenCart 2.3 and 3.0 (tested up to 3.0.3.2), allowing you to take payments via Paymentsense.

Installation using Extension Installer
--------------------------------------

1. Download the Paymentsense extension file from the Paymentsense Developer Zone at http://developers.paymentsense.co.uk
2. Login to the OpenCart Admin Panel
3. Go to Extensions -> Extensions Installer -> Upload to upload the Paymentsense extension file downloaded in step 1
4. Go to Extensions -> Extensions -> Payments and click the Install button next to Paymentsense Hosted / Paymentsense Direct
5. Click the Edit button next to Paymentsense Hosted / Paymentsense Direct to configure the extension
6. Set the gateway credentials and pre-shared key where applicable
7. Optionally, set the rest of the settings as per your needs
8. Click the Save button

Manual installation
-------------------

1. Download the Paymentsense extension file from the Paymentsense Developer Zone at http://developers.paymentsense.co.uk
2. Unzip the extension file and upload the content of the upload folder to the root folder of your OpenCart
3. Login to the OpenCart Admin Panel
4. Go to Extensions -> Extensions -> Payments and click the Install button next to Paymentsense Hosted / Paymentsense Direct
5. Click the Edit button next to Paymentsense Hosted / Paymentsense Direct to configure the extension
6. Set the gateway credentials and pre-shared key where applicable
7. Optionally, set the rest of the settings as per your needs
8. Click the Save button

Changelog
---------

### 3.0.4
##### Added
- Detailed message to the transactions log when a transaction fails (Paymentsense Direct)

##### Removed
- Unneeded transaction retries when a transaction fails with an "Input variable errors" message (Paymentsense Direct)


### 3.0.3
##### Added
- Module information reporting feature

##### Changed
- Logo

##### Removed
- gw3 gateway entry point


### 3.0.2
##### Removed
- Check for the Merchant ID format


### 3.0.1
##### Fixed
- Path to the payment methods templates when using a custom theme


### 3.0.0
##### Added
- OpenCart 3.x support
- Warning on insecure OpenCart setup on the configuration page (Direct)
- Check for required card fields before sending the transaction to the gateway (Direct)
- Gateway message on failed transactions displayed on order checkout
- Default Successful Transaction Order Status (set as "Processing")
- Default Failed Transaction Order Status (set as "Failed")

##### Changed
- Hosted and Direct combined into one payment extension
- Status message on failed transactions changed to bootstrap alert (Direct)
- Logo (scaled down)

##### Fixed
- Broken links

##### Removed
- Database tables ("paymentsense" and "paymentsense_direct")
- Gateway password strict format

##### Security
- SSL/TLS required on checkout (Direct)
- Hash digest check on the customer redirect from the gateway (Hosted)

Support
-------

[devsupport@paymentsense.com](mailto:devsupport@paymentsense.com)
