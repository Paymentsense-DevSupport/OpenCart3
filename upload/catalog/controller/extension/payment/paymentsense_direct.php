<?php
/*
 * Copyright (C) 2019 Paymentsense Ltd.
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
 * @copyright   2019 Paymentsense Ltd.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

require_once DIR_APPLICATION . "controller/extension/payment/paymentsense_base.php";

/**
 * Front Controller for Paymentsense Hosted
 */
class ControllerExtensionPaymentPaymentsenseDirect extends ControllerExtensionPaymentPaymentsenseBase
{
	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName = 'paymentsense_direct';

	/**
	 * Index Action
	 *
	 * @return string
	 */
	public function index() {
		$this->load->language('extension/payment/paymentsense_direct');
		$data = array(
			'text_credit_card'     => $this->language->get('text_credit_card'),
			'text_issue'           => $this->language->get('text_issue'),
			'text_loading'         => $this->language->get('text_loading'),
			'entry_cc_owner'       => $this->language->get('entry_cc_owner'),
			'entry_cc_number'      => $this->language->get('entry_cc_number'),
			'entry_cc_expire_date' => $this->language->get('entry_cc_expire_date'),
			'entry_cc_cvv2'        => $this->language->get('entry_cc_cvv2'),
			'entry_cc_issue'       => $this->language->get('entry_cc_issue'),
			'button_confirm'       => $this->language->get('button_confirm'),
			'cc_exp_months'        => $this->buildCcExpMonths(),
			'cc_exp_years'         => $this->buildCcEXpYears()
		);
		return $this->loadTemplate($data);
	}

	/**
	 * Process Action
	 */
	public function process() {
		if (!$this->validateCardData()) {
			$this->language->load('extension/payment/paymentsense_direct');
			$json['error'] = $this->language->get('error_required');
		} else {
			$json = $this->performCardDetailsTransaction();
		}
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 3D Secure Authentication Action
	 */
	public function threedsauth() {
		$json = $this->performThreeDSecureAuthTransaction();
		if ($json['error'] != '') {
			$this->language->load('extension/payment/paymentsense_direct');
			$this->session->data['error'] = sprintf($this->language->get('text_payment_failed'), $json['error']);
			$this->response->redirect($this->url->link('checkout/cart', '', 'SSL'));
		} else {
			$this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
		}
	}

	/**
	 * Builds an array containing card expiration months
	 *
	 * @return array
	 */
	protected function buildCcExpMonths() {
		$result = array();
		for ($i = 1; $i <= 12; $i++) {
			$result[] = array(
				'text'  => strftime('%m - %B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}
		return $result;
	}

	/**
	 * Builds an array containing card expiration years
	 *
	 * @return array
	 */
	protected function buildCcEXpYears() {
		$result = array();
		$today  = getdate();
		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$result[] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%y', mktime(0, 0, 0, 1, 1, $i))
			);
		}
		return $result;
	}

	/**
	 * Checks whether the required card fields are filled in
	 *
	 * @return string
	 */
	protected function validateCardData() {
		return $this->request->post['cc_owner'] != '' &&
			$this->request->post['cc_number'] !='' &&
			$this->request->post['cc_expire_date_month'] != '' &&
			$this->request->post['cc_expire_date_year'] != '' &&
			$this->request->post['cc_cvv2'] != '';
	}

	/**
	 * Performs Card Details Transaction
	 *
	 * @return array
	 */
	protected function performCardDetailsTransaction() {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$headers = array(
			'SOAPAction:https://www.thepaymentgateway.net/CardDetailsTransaction',
			'Content-Type: text/xml; charset = utf-8',
			'Connection: close'
		);

		$data = array(
			'OrderID' => $this->session->data['order_id'],
			'MerchantID' => $this->getConfigValue('paymentsense_direct_mid'),
			'Password' => $this->getConfigValue('paymentsense_direct_pass'),
			'Amount' => round($this->currency->format($order_info['total'], $order_info['currency_code'], false, false)*100),
			'CurrencyCode' => $this->getCurrencyIsoCode($order_info['currency_code']),
			'TransactionType' => $this->getConfigValue('paymentsense_direct_type'),
			'OrderDescription' => "Order ID: {$this->session->data['order_id']}",
			'CardName' => $this->request->post['cc_owner'],
			'CardNumber' => $this->request->post['cc_number'],
			'ExpMonth' => $this->request->post['cc_expire_date_month'],
			'ExpYear' => $this->request->post['cc_expire_date_year'],
			'IssueNumber' => $this->request->post['cc_issue'],
			'CV2' => $this->request->post['cc_cvv2'],
			'CV2Policy' => $this->getConfigValue('paymentsense_direct_cv2_policy_1') .
				$this->getConfigValue('paymentsense_direct_cv2_policy_2'),
			'AVSPolicy' => $this->getConfigValue('paymentsense_direct_avs_policy_1') .
				$this->getConfigValue('paymentsense_direct_avs_policy_2') .
				$this->getConfigValue('paymentsense_direct_avs_policy_3') .
				$this->getConfigValue('paymentsense_direct_avs_policy_4'),
			'Address1' => $order_info['payment_address_1'],
			'Address2' => $order_info['payment_address_2'],
			'Address3' => "",
			'Address4' => "",
			'City' => $order_info['payment_city'],
			'State' => "",
			'PostCode' => $order_info['payment_postcode'],
			'CountryCode' => $this->getCountryIsoCode($order_info['payment_country']),
			'PhoneNumber' => $order_info['telephone'],
			'EmailAddress' => $order_info['email'],
			'CustomerIPAddress' => $this->request->server['REMOTE_ADDR'],
		);

		$xml = $this->buildCardDetailsTransactionRequest($data);

		return $this->performTransaction($headers, $xml);
	}

	/**
	 * Performs 3D Secure Authentication Transaction
	 *
	 * @return array
	 */
	protected function performThreeDSecureAuthTransaction() {
		$headers = array(
			'SOAPAction:https://www.thepaymentgateway.net/ThreeDSecureAuthentication',
			'Content-Type: text/xml; charset = utf-8',
			'Connection: close'
		);

		$data = array(
			'MerchantID'     => $this->getConfigValue('paymentsense_direct_mid'),
			'Password'       => $this->getConfigValue('paymentsense_direct_pass'),
			'CrossReference' => $this->request->post['MD'],
			'PaRES'          => $this->request->post['PaRes'],
		);

		$xml = $this->buildThreeDSecureAuthenticationRequest($data);

		return $this->performTransaction($headers, $xml);
	}

	/**
	 * Performs a transaction (Card Details Transaction or 3D Secure Authentication)
	 *
	 * @param array $headers
	 * @param string $xml
	 * @return array
	 */
	protected function performTransaction($headers, $xml) {
		$result       = array();
		$soap_success = false;
		$gateway_id   = 1;
		$trx_attempt  = 1;

		while (!$soap_success && $gateway_id<=3 && $trx_attempt<=3) {

			$data = array(
				'url'     => $this->getPaymentGatewayUrl($gateway_id),
				'headers' => $headers,
				'xml'     => $xml
			);

			$response = '';
			if (0 === $this->sendTransaction($data, $response)) {
				$status_code     = $this->getXmlValue($response, 'StatusCode', '[0-9]+');
				$message         = $this->getXmlValue($response, 'Message', '.+');
				$auth_code       = $this->getXmlValue($response, 'AuthCode', '[a-zA-Z0-9]+');
				$cross_reference = $this->getXmlCrossReference($response);
				$avs_check       = $this->getXmlValue($response, 'AddressNumericCheckResult', '.+');
				$postcode_check  = $this->getXmlValue($response, 'PostCodeCheckResult', '.+');
				$cv2_check       = $this->getXmlValue($response, 'CV2CheckResult', '.+');
				$threeds_check   = $this->getXmlValue($response, 'ThreeDSecureAuthenticationCheckResult', '.+');
				$pareq           = $this->getXmlValue($response, 'PaREQ', '.+');
				$acs_url         = $this->getXmlValue($response, 'ACSURL', '.+');

				$status = 'failed';

				if (is_numeric($status_code)) {
					if (self::TRX_RESULT_FAILED !== $status_code) {
						$soap_success = true;
						switch ($status_code) {
							case self::TRX_RESULT_SUCCESS:
								$status = 'success';
								break;
							case self::TRX_RESULT_INCOMPLETE:
								$status = '3ds';
								break;
							case self::TRX_RESULT_DUPLICATE:
								$status          = 'failed';
								$prev_trx_result = $this->getXmlValue($response, 'PreviousTransactionResult', '.+');
								if ($prev_trx_result) {
									$message          = $this->getXmlValue($prev_trx_result, 'Message', '.+');
									$prev_status_code = $this->getXmlValue($prev_trx_result, 'StatusCode', '.+');
									if (self::TRX_RESULT_SUCCESS === $prev_status_code) {
										$status = 'success';
									}
								}
								break;
							case self::TRX_RESULT_REFERRED:
							case self::TRX_RESULT_DECLINED:
							default:
								$status = 'failed';
								break;
						}
					}

					switch ($status) {
						case 'success':
							$comments = array(
								self::COMMENT_FIELD_AUTH_CODE       => $auth_code,
								self::COMMENT_FIELD_CROSS_REFERENCE => $cross_reference,
								self::COMMENT_FIELD_AVS_CHECK       => $avs_check,
								self::COMMENT_FIELD_POSTCODE_CHECK  => $postcode_check,
								self::COMMENT_FIELD_CV2_CHECK       => $cv2_check,
								self::COMMENT_FIELD_3DS_CHECK       => $threeds_check
							);
							$this->addSuccessMessage($this->session->data['order_id'], $comments);
							$result['error'] = '';
							$result['success'] = $this->url->link('checkout/success', '', 'SSL');
							break;
						case '3ds':
							$result['ACSURL']  = $acs_url;
							$result['MD']      = $cross_reference;
							$result['PaReq']   = $pareq;
							$result['TermUrl'] = $this->url->link('extension/payment/paymentsense_direct/threedsauth', '', 'SSL');
							break;
						case 'failed':
						default:
							$comments = array(
								self::COMMENT_FIELD_MESSAGE         => $message,
								self::COMMENT_FIELD_CROSS_REFERENCE => $cross_reference,
								self::COMMENT_FIELD_AVS_CHECK       => $avs_check,
								self::COMMENT_FIELD_POSTCODE_CHECK  => $postcode_check,
								self::COMMENT_FIELD_CV2_CHECK       => $cv2_check,
								self::COMMENT_FIELD_3DS_CHECK       => $threeds_check
							);
							$this->addFailMessage($this->session->data['order_id'], $comments);
							$result['error'] = $message;
							break;
					}
				}
			}

			if ($trx_attempt<=2) {
				$trx_attempt++;
			} else {
				$trx_attempt = 1;
				$gateway_id++;
			}
		}
		return $result;
	}

	/**
	 * Builds the XML request for the CardDetailsTransaction
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	protected function buildCardDetailsTransactionRequest($data) {
		return '<?xml version="1.0" encoding="utf-8"?>
                        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                            <soap:Body>
                                <CardDetailsTransaction xmlns="https://www.thepaymentgateway.net/">
                                    <PaymentMessage>
                                        <MerchantAuthentication MerchantID="' . $data['MerchantID'] . '" Password="' . $data['Password'] . '" />
                                        <TransactionDetails Amount="' . $data['Amount'] . '" CurrencyCode="' . $data['CurrencyCode'] . '">
                                            <MessageDetails TransactionType="' . $data['TransactionType'] . '" />
                                            <OrderID>' . $data['OrderID'] . '</OrderID>
                                            <OrderDescription>' . $data['OrderDescription'] . '</OrderDescription>
                                            <TransactionControl>
                                                <EchoCardType>TRUE</EchoCardType>
                                                <EchoAVSCheckResult>TRUE</EchoAVSCheckResult>
                                                <EchoCV2CheckResult>TRUE</EchoCV2CheckResult>
                                                <EchoAmountReceived>FALSE</EchoAmountReceived>
                                                <DuplicateDelay>20</DuplicateDelay>
                                                <AVSOverridePolicy>'. $data['AVSPolicy'] .'</AVSOverridePolicy>
                                                <CV2OverridePolicy>'. $data['CV2Policy'] .'</CV2OverridePolicy>
                                            </TransactionControl>
                                        </TransactionDetails>
                                        <CardDetails>
                                            <CardName>' . $data['CardName'] . '</CardName>
                                            <CardNumber>' . $data['CardNumber'] . '</CardNumber>
                                            <ExpiryDate Month="' . $data['ExpMonth'] . '" Year="' . $data['ExpYear'] . '" />
                                            <CV2>' . $data['CV2'] . '</CV2>
                                            <IssueNumber>' . $data['IssueNumber'] . '</IssueNumber>
                                        </CardDetails>
                                        <CustomerDetails>
                                            <BillingAddress>
                                                <Address1>' . $data['Address1'] . '</Address1>
                                                <Address2>' . $data['Address2'] . '</Address2>
                                                <Address3>' . $data['Address3'] . '</Address3>
                                                <Address4>' . $data['Address4'] . '</Address4>
                                                <City>' . $data['City'] . '</City>
                                                <State>' . $data['State'] . '</State>
                                                <PostCode>' . $data['PostCode'] . '</PostCode>
                                                <CountryCode>' . $data['CountryCode'] . '</CountryCode>
                                            </BillingAddress>
                                            <EmailAddress>' . $data['EmailAddress'] . '</EmailAddress>
                                            <PhoneNumber>' . $data['PhoneNumber'] . '</PhoneNumber>
                                            <CustomerIPAddress>' . $data['CustomerIPAddress'] . '</CustomerIPAddress>
                                        </CustomerDetails>
                                    </PaymentMessage>
                                </CardDetailsTransaction>
                            </soap:Body>
                        </soap:Envelope>';
	}

	/**
	 * Builds the XML request for the ThreeDSecureAuthentication
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	protected function buildThreeDSecureAuthenticationRequest($data) {
		return '<?xml version="1.0" encoding="utf-8"?>
                    <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                        <soap:Body>
                            <ThreeDSecureAuthentication xmlns="https://www.thepaymentgateway.net/">
                                <ThreeDSecureMessage>
                                    <MerchantAuthentication MerchantID="' . $data['MerchantID'] . '" Password="' . $data['Password'] . '" />
                                    <ThreeDSecureInputData CrossReference="' . $data['CrossReference'] . '">
                                        <PaRES>' . $data['PaRES'] . '</PaRES>
                                    </ThreeDSecureInputData>
                                </ThreeDSecureMessage>
                            </ThreeDSecureAuthentication>
                        </soap:Body>
                    </soap:Envelope>';
	}

	/**
	 * Gets the value of a XML element from a XML document
	 *
	 * @param string $xml XML document.
	 * @param string $xml_element XML element.
	 * @param string $pattern Regular expression pattern.
	 * @param string $default Default value returned when the XML element is not found.
	 *
	 * @return string
	 */
	protected function getXmlValue($xml, $xml_element, $pattern, $default = '') {
		$result = $default;
		if (preg_match('#<' . $xml_element . '>(' . $pattern . ')</' . $xml_element . '>#iU', $xml, $matches)) {
			$result = $matches[1];
		}
		return $result;
	}

	/**
	 * Gets the value of the CrossReference element from a XML document
	 *
	 * @param string $xml XML document.
	 * @param string $default Default value returned when the XML element is not found.
	 *
	 * @return string
	 */
	protected function getXmlCrossReference($xml, $default = '') {
		$result = $default;
		if (preg_match('#<TransactionOutputData.*CrossReference="(.+)".*>#iU', $xml, $matches)) {
			$result = $matches[1];
		}
		return $result;
	}

	/**
	 * Gets Payment Gateway URL
	 *
	 * @param int $gatewayId Gateway ID.
	 * @return string|false
	 */
	protected function getPaymentGatewayUrl($gatewayId) {
		$payment_gateways = array(
			1 => 'https://gw1.paymentsensegateway.com:4430/',
			2 => 'https://gw2.paymentsensegateway.com:4430/'
		);
		$result = false;
		if (array_key_exists($gatewayId, $payment_gateways)) {
			$result = $payment_gateways[$gatewayId];
		}
		return $result;
	}

	/**
	 * Performs cURL requests to the Paymentsense gateway
	 *
	 * @param array $data cURL data.
	 * @param mixed $response the result or false on failure.
	 *
	 * @return int the error number or 0 if no error occurred
	 */
	protected function sendTransaction($data, &$response) {
		// @codingStandardsIgnoreStart
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_URL, $data['url']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data['xml']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$err_no   = curl_errno($ch);
		curl_close($ch);
		// @codingStandardsIgnoreEnd
		return $err_no;
	}
}
