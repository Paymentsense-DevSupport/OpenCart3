<?php
/*
 * Copyright (C) 2018 Paymentsense Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      Paymentsense
 * @copyright   2018 Paymentsense Ltd.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

require_once DIR_APPLICATION . "controller/extension/payment/paymentsense_base.php";

/**
 * Front Controller for Paymentsense Hosted
 */
class ControllerExtensionPaymentPaymentsenseHosted extends ControllerExtensionPaymentPaymentsenseBase
{
	/**
	 * Response Status Codes
	 */
	const STATUS_CODE_OK        = '0';
	const STATUS_CODE_ERROR     = '30';

	/**
	 * Request Types
	 */
	const REQ_NOTIFICATION      = '0';
	const REQ_CUSTOMER_REDIRECT = '1';

	/**
	 * Response Messages
	 */
	const MSG_SUCCESS           = 'Request processed successfully.';
	const MSG_NOT_CONFIGURED    = 'The plugin is not configured.';
	const MSG_HASH_DIGEST_ERROR = 'Invalid or empty hash digest.';
	const MSG_EXCEPTION         = 'An exception with message "%s" has been thrown. Please contact support.';

	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName = 'paymentsense_hosted';

	/** @var array */
	protected $responseVars = array(
		'status_code' => '',
		'message'     => ''
	);

	/**
	 * Index Action
	 *
	 * @return string
	 */
	public function index() {
		$this->load->language('extension/payment/paymentsense_hosted');
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$fields = array(
			'Amount' => round($this->currency->format($order_info['total'], $order_info['currency_code'], false, false)*100),
			'CurrencyCode' => $this->getCurrencyIsoCode($order_info['currency_code']),
			'EchoAVSCheckResult' => 'true',
			'EchoCV2CheckResult' => 'true',
			'EchoThreeDSecureAuthenticationCheckResult' => 'true',
			'OrderID' => $this->session->data['order_id'],
			'TransactionType' => $this->getConfigValue('paymentsense_hosted_type') ? 'SALE' : 'PREAUTH',
			'TransactionDateTime' => (date("Y-m-d H:i:s P")),
			'CallbackURL' => $this->url->link('extension/payment/paymentsense_hosted/customerredirect', '', 'SSL'),
			'OrderDescription' => "Order ID: {$this->session->data['order_id']}",
			'CustomerName' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
			'Address1' => $order_info['payment_address_1'],
			'Address2' => $order_info['payment_address_2'],
			'Address3' => '',
			'Address4' => '',
			'City' => $order_info['payment_city'],
			'State' => $order_info['payment_zone'],
			'PostCode' => $order_info['payment_postcode'],
			'CountryCode' => $this->getCountryIsoCode($order_info['payment_country']),
			'EmailAddress' => $order_info['email'],
			'PhoneNumber' => $order_info['telephone'],
			'EmailAddressEditable' => 'true',
			'PhoneNumberEditable' => 'true',
			'CV2Mandatory' => $this->getConfigValue('paymentsense_hosted_cv2_mand'),
			'Address1Mandatory' => $this->getConfigValue('paymentsense_hosted_address1_mand'),
			'CityMandatory' => $this->getConfigValue('paymentsense_hosted_city_mand'),
			'PostCodeMandatory' => $this->getConfigValue('paymentsense_hosted_postcode_mand'),
			'StateMandatory' => $this->getConfigValue('paymentsense_hosted_state_mand'),
			'CountryMandatory' => $this->getConfigValue('paymentsense_hosted_country_mand'),
			'ResultDeliveryMethod' => 'SERVER',
			'ServerResultURL' => $this->url->link('extension/payment/paymentsense_hosted/notification', '', 'SSL'),
			'PaymentFormDisplaysResult' => 'FALSE'
		);

		$data  = 'MerchantID=' . $this->getConfigValue('paymentsense_hosted_mid');
		$data .= '&Password=' . $this->getConfigValue('paymentsense_hosted_pass');

		foreach ($fields as $key => $value) {
			$data .= '&' . $key . '=' . str_replace('&amp;', '&', $value);
		};

		$additional_fields = array(
			'HashDigest' => $this->calculateHashDigest($data, 'SHA1', $this->getConfigValue('paymentsense_hosted_key')),
			'MerchantID' => $this->getConfigValue('paymentsense_hosted_mid')
		);

		$fields = array_merge($additional_fields, $fields);

		$data = array(
			'action'         => 'https://mms.paymentsensegateway.com/Pages/PublicPages/PaymentForm.aspx',
			'button_confirm' => $this->language->get('button_confirm'),
			'text_wait'      => $this->language->get('text_wait'),
			'text_loading'   => $this->language->get('text_loading'),
			'fields'         => $fields
		);

		return $this->loadTemplate($data);
	}

	/**
	 * Notification Action
	 */
	public function notification()
	{
		$this->processNotification();
		$this->outputResponse();
	}

	/**
	 * Customer Redirect Action
	 */
	public function customerredirect() {
		try {
			if (!$this->isConfigured()) {
				$this->session->data['error'] = self::MSG_NOT_CONFIGURED;
				$this->response->redirect($this->url->link('checkout/cart', '', 'SSL'));
			}
			if (!$this->isHashDigestValid(self::REQ_CUSTOMER_REDIRECT)) {
				$this->session->data['error'] = self::MSG_HASH_DIGEST_ERROR;
				$this->response->redirect($this->url->link('checkout/cart', '', 'SSL'));
			}

			$this->language->load('extension/payment/paymentsense_hosted');
			$this->load->model('checkout/order');

			$order_id   = $this->request->get['OrderID'];
			$order_info = $this->model_checkout_order->getOrder($order_id);

			// If there is no order info then fail.
			if (!$order_info) {
				$this->session->data['error'] = $this->language->get('error_no_order');
				$this->response->redirect(
					isset($this->session->data['guest'])
						? $this->url->link('checkout/guest_step_3', '', 'SSL')
						: $this->url->link('checkout/confirm', '', 'SSL')
				);
			}

			if ($order_info['order_status_id'] == $this->getConfigValue('paymentsense_hosted_order_status_id')) {
				$this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
			} else {
				$this->session->data['error'] = sprintf($this->language->get('text_payment_failed'), $this->getOrderMessage($order_id));
				$this->response->redirect($this->url->link('checkout/cart', '', 'SSL'));
			}

		} catch (Exception $exception) {
			$this->setError(sprintf(self::MSG_EXCEPTION, $exception->getMessage()));
		}
	}

	/**
	 * Processes the notification request from the Paymentsense gateway
	 */
	protected function processNotification()
	{
		try {
			if (!$this->isConfigured()) {
				$this->setError(self::MSG_NOT_CONFIGURED);
				return;
			}
			if (!$this->isHashDigestValid(self::REQ_NOTIFICATION)) {
				$this->setError(self::MSG_HASH_DIGEST_ERROR);
				return;
			}
			$this->updateOrder();
			$this->setSuccess();
		} catch (Exception $exception) {
			$this->setError(sprintf(self::MSG_EXCEPTION, $exception->getMessage()));
		}
	}

	/**
	 * Updates the order by adding the transaction details to the order history
	 */
	protected function updateOrder()
	{
		$order_id        = $this->getHttpVar("OrderID");
		$message_field   = ($this->getHttpVar("StatusCode") === self::TRX_RESULT_DUPLICATE) ? "PreviousMessage" : "Message";
		$message         = $this->getHttpVar($message_field);
		$cross_reference = $this->getHttpVar("CrossReference");
		$avs_check       = $this->getHttpVar("AddressNumericCheckResult");
		$post_code_check = $this->getHttpVar("PostCodeCheckResult");
		$cv2_check       = $this->getHttpVar("CV2CheckResult");
		$three3ds_check  = $this->getHttpVar("ThreeDSecureAuthenticationCheckResult");

		if ($this->isOrderPaid($this->getHttpVar("StatusCode"), $this->getHttpVar("PreviousStatusCode"))) {
			$comments = array(
				self::COMMENT_FIELD_AUTH_CODE       => $message,
				self::COMMENT_FIELD_CROSS_REFERENCE => $cross_reference,
				self::COMMENT_FIELD_AVS_CHECK       => $avs_check,
				self::COMMENT_FIELD_POSTCODE_CHECK  => $post_code_check,
				self::COMMENT_FIELD_CV2_CHECK       => $cv2_check,
				self::COMMENT_FIELD_3DS_CHECK       => $three3ds_check
			);
			$this->addSuccessMessage($order_id, $comments);
		} else {
			$comments = array(
				self::COMMENT_FIELD_MESSAGE         => $message,
				self::COMMENT_FIELD_CROSS_REFERENCE => $cross_reference,
				self::COMMENT_FIELD_AVS_CHECK       => $avs_check,
				self::COMMENT_FIELD_POSTCODE_CHECK  => $post_code_check,
				self::COMMENT_FIELD_CV2_CHECK       => $cv2_check,
				self::COMMENT_FIELD_3DS_CHECK       => $three3ds_check
			);
			$this->addFailMessage($order_id, $comments);
		}
	}

	/**
	 * Determines whether the order is paid
	 *
	 * @param string $statusCode Transaction status code
	 * @param string $prevStatusCode Previous transaction status code
	 *
	 * @return bool
	 */
	protected function isOrderPaid($statusCode, $prevStatusCode)
	{
		switch ($statusCode) {
			case self::TRX_RESULT_SUCCESS:
				$result = true;
				break;
			case self::TRX_RESULT_DUPLICATE:
				$result = $prevStatusCode === self::TRX_RESULT_SUCCESS;
				break;
			default:
				$result = false;
		}
		return $result;
	}

	/**
	 * Sets the success response message and status code
	 */
	protected function setSuccess()
	{
		$this->setResponse(self::STATUS_CODE_OK, self::MSG_SUCCESS);
	}

	/**
	 * Sets the error response message and status code
	 *
	 * @param string $message Response message
	 *
	 */
	protected function setError($message)
	{
		$this->setResponse(self::STATUS_CODE_ERROR, $message);
	}

	/**
	 * Sets the response variables
	 *
	 * @param string $statusCode Response status code
	 * @param string $message Response message
	 */
	protected function setResponse($statusCode, $message)
	{
		$this->responseVars['status_code'] = $statusCode;
		$this->responseVars['message']     = $message;
	}

	/**
	 * Outputs the response
	 */
	protected function outputResponse()
	{
		echo "StatusCode={$this->responseVars['status_code']}&Message={$this->responseVars['message']}";
	}

	/**
	 * Checks whether the hash digest received from the payment gateway is valid
	 *
	 * @param string $requestType Type of the request (notification or customer redirect)
	 *
	 * @return bool
	 */
	protected function isHashDigestValid($requestType)
	{
		$result = false;
		$data = $this->buildVariablesString($requestType);
		if ($data) {
			$hash_digest_received = $this->getHttpVar('HashDigest');
			$hash_digest_calculated = $this->calculateHashDigest(
				$data,
				'SHA1', // Hardcoded as per the current plugin implementation
				$this->getConfigValue('paymentsense_hosted_key')
			);
			$result = strToUpper($hash_digest_received) === strToUpper($hash_digest_calculated);
		}
		return $result;
	}

	/**
	 * Calculates the hash digest.
	 * Supported hash methods: MD5, SHA1, HMACMD5, HMACSHA1
	 *
	 * @param string $data Data to be hashed.
	 * @param string $hash_method Hash method.
	 * @param string $key Secret key to use for generating the hash.
	 * @return string
	 */
	protected function calculateHashDigest($data, $hash_method, $key)
	{
		$result     = '';
		$include_key = in_array($hash_method, ['MD5', 'SHA1'], true);
		if ($include_key) {
			$data = 'PreSharedKey=' . $key . '&' . $data;
		}
		switch ($hash_method) {
			case 'MD5':
				// @codingStandardsIgnoreLine
				$result = md5($data);
				break;
			case 'SHA1':
				$result = sha1($data);
				break;
			case 'HMACMD5':
				$result = hash_hmac('md5', $data, $key);
				break;
			case 'HMACSHA1':
				$result = hash_hmac('sha1', $data, $key);
				break;
		}
		return $result;
	}

	/**
	 * Builds a string containing the variables for calculating the hash digest
	 *
	 * @param string $request_type Type of the request (notification or customer redirect)
	 *
	 * @return bool
	 */
	protected function buildVariablesString($request_type)
	{
		$result = false;
		$fields = array(
			// Variables for hash digest calculation for notification requests (excluding configuration variables)
			self::REQ_NOTIFICATION      => array(
				'StatusCode',
				'Message',
				'PreviousStatusCode',
				'PreviousMessage',
				'CrossReference',
				'AddressNumericCheckResult',
				'PostCodeCheckResult',
				'CV2CheckResult',
				'ThreeDSecureAuthenticationCheckResult',
				'Amount',
				'CurrencyCode',
				'OrderID',
				'TransactionType',
				'TransactionDateTime',
				'OrderDescription',
				'CustomerName',
				'Address1',
				'Address2',
				'Address3',
				'Address4',
				'City',
				'State',
				'PostCode',
				'CountryCode',
				'EmailAddress',
				'PhoneNumber',
			),
			// Variables for hash digest calculation for customer redirects (excluding configuration variables)
			self::REQ_CUSTOMER_REDIRECT => array(
				'CrossReference',
				'OrderID',
			),
		);
		if (array_key_exists($request_type, $fields)) {
			$result = 'MerchantID=' . $this->getConfigValue('paymentsense_hosted_mid') .
				'&Password=' . $this->getConfigValue('paymentsense_hosted_pass');
			foreach ($fields[$request_type] as $field) {
				$result .= '&' . $field . '=' . $this->getHttpVar($field);
			}
		}
		return $result;
	}
}
