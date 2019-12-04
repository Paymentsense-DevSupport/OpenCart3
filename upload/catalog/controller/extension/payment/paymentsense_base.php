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

/**
 * Base Front Controller for Paymentsense Hosted and Direct
 */
abstract class ControllerExtensionPaymentPaymentsenseBase extends Controller
{
	/**
	 * Module Name and Version
	 */
	const MODULE_NAME    = 'Paymentsense Module for OpenCart';
	const MODULE_VERSION = '3.0.3';

	/**
	 * Transaction Result Codes
	 */
	const TRX_RESULT_SUCCESS    = '0';
	const TRX_RESULT_INCOMPLETE = '3';
	const TRX_RESULT_REFERRED   = '4';
	const TRX_RESULT_DECLINED   = '5';
	const TRX_RESULT_DUPLICATE  = '20';
	const TRX_RESULT_FAILED     = '30';

	/**
	 * OpenCart Order Status Constants
	 * Reference: database table oc_order_status
	 */
	const OC_ORD_STATUS_PENDING           =  1;
	const OC_ORD_STATUS_PROCESSING        =  2;
	const OC_ORD_STATUS_SHIPPED           =  3;
	const OC_ORD_STATUS_COMPLETE          =  5;
	const OC_ORD_STATUS_CANCELED          =  7;
	const OC_ORD_STATUS_DENIED            =  8;
	const OC_ORD_STATUS_CANCELED_REVERSAL =  9;
	const OC_ORD_STATUS_FAILED            = 10;
	const OC_ORD_STATUS_REFUNDED          = 11;
	const OC_ORD_STATUS_REVERSED          = 12;
	const OC_ORD_STATUS_CHARGEBACK        = 13;
	const OC_ORD_STATUS_EXPIRED           = 14;
	const OC_ORD_STATUS_PROCESSED         = 15;
	const OC_ORD_STATUS_VOIDED            = 16;

	/**
	 * Comment fields delimiter and colon sign
	 * Used top build a comment in the following form
	 */
	const COMMENT_FIELD_DELIMITER  = ' || ';
	const COMMENT_FIELD_EQUAL_SIGN = ': ';

	/**
	 * Comment fields delimiter and equal sign
	 * Used top build a comment in the following form
	 */
	const COMMENT_FIELD_AUTH_CODE       = 'AuthCode';
	const COMMENT_FIELD_MESSAGE         = 'Message';
	const COMMENT_FIELD_CROSS_REFERENCE = 'CrossReference';
	const COMMENT_FIELD_AVS_CHECK       = 'AVS Check';
	const COMMENT_FIELD_POSTCODE_CHECK  = 'Postcode Check';
	const COMMENT_FIELD_CV2_CHECK       = 'CV2 Check';
	const COMMENT_FIELD_3DS_CHECK       = '3D Secure Check';

	/**
	 * Content types of the output of the module information
	 */
	const TYPE_APPLICATION_JSON = 'application/json';
	const TYPE_TEXT_PLAIN       = 'text/plain';

	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName;

	/**
	 * Supported content types of the output of the module information
	 *
	 * @var array
	 */
	protected $content_types = array(
		'json' => self::TYPE_APPLICATION_JSON,
		'text' => self::TYPE_TEXT_PLAIN
	);

	/**
	 * Module Information Action
	 */
	public function info() {
		$info = array(
			'Module Name'              => $this->getModuleName(),
			'Module Installed Version' => $this->getModuleInstalledVersion(),
		);

		if ($this->getRequestParameter('extended_info', '') === 'true') {
			$extended_info = array(
				'Module Latest Version' => $this->getModuleLatestVersion(),
				'OpenCart Version'      => $this->getOpenCartVersion(),
				'PHP Version'           => $this->getPhpVersion()
			);
			$info = array_merge($info, $extended_info);
		}

		$this->outputInfo($info);
	}

	/**
	 * Checksums Action
	 */
	public function checksums() {
		$info = array(
			'Checksums' => $this->getFileChecksums(),
		);
		$this->outputInfo($info);
	}

	/**
	 * Switches the template engine to tpl
	 *
	 * @param string $route
	 */
	public function switchTemplateEngine(&$route) {
		if (strpos($route, $this->moduleName)) {
			if (!array_key_exists('configured_template_engine', $this->session->data) || !$this->session->data['configured_template_engine']) {
				$this->session->data['configured_template_engine'] = $this->config->get('template_engine');
			}

			$route = preg_replace('/tpl$/', '', $route);

			$theme = ($this->config->get('config_theme') == 'default')
				? 'theme_default_directory'
				: 'config_theme';

			$theme_name = $this->config->get($theme);

			if (!is_file(DIR_TEMPLATE . $theme_name . '/template/' . $route . '.tpl')) {
				$theme_name = 'default';
			}

			$this->config->set('template_directory', $theme_name . '/template/');
			$this->config->set('template_engine', 'template');
		}
	}

	/**
	 * Restores the template engine to the configured one
	 *
	 * @param string $route
	 */
	public function restoreTemplateEngine(&$route) {
		if (strpos($route, $this->moduleName)) {
			if ($this->session->data['configured_template_engine']) {
				$this->config->set('template_engine', $this->session->data['configured_template_engine']);
			}
		}
	}

	/**
	 * Loads the checkout template
	 *
	 * @param array $data Template data
	 * @return string
	 */
	public function loadTemplate($data) {
		return $this->load->view("extension/payment/{$this->moduleName}.tpl", $data);
	}

	/**
	 * Gets the message from the 'comment' field from the order histories
	 *
	 * @param string $order_id Order ID
	 * @return string
	 */
	protected function getOrderMessage($order_id) {
		$order_details = $this->getOrderDetails($order_id);
		return array_key_exists('Message', $order_details)
			? $order_details['Message']
			: '';
	}

	/**
	 * Gets order details from the 'comment' field from the order histories
	 *
	 * @param string $order_id Order ID
	 * @return array
	 */
	protected function getOrderDetails($order_id) {
		$result = array();
		$this->load->model('account/order');
		$histories        = $this->model_account_order->getOrderHistories($order_id);
		$history          = array_shift($histories);
		$comment_elements = explode(
			self::COMMENT_FIELD_DELIMITER,
			$history['comment']
		);
		if ($comment_elements) {
			foreach ($comment_elements as $comment_element) {
				$property_elements = explode(
					self::COMMENT_FIELD_EQUAL_SIGN,
					$comment_element,
					2
				);
				if ($property_elements && (count($property_elements) == 2)) {
					$result[trim($property_elements[0])] = trim($property_elements[1]);
				}
			}
		}
		return $result;
	}

	/**
	 * Adds a "Success" message to the order history
	 *
	 * @param string $order_id Order ID
	 * @param array $comments History Comments
	 */
	protected function addSuccessMessage($order_id, $comments) {
		$this->addMessageToHistory($order_id, $comments, true);
	}

	/**
	 * Adds a "Fail" message to the order history
	 *
	 * @param string $order_id Order ID
	 * @param array $comments History Comments
	 */
	protected function addFailMessage($order_id, $comments) {
		$this->addMessageToHistory($order_id, $comments, false);
	}

	/**
	 * Adds a message to the order history
	 *
	 * @param string $order_id Order ID
	 * @param array $comments History Comments
	 * @param bool $success True for a "Success" message, false for a "Fail" message
	 */
	protected function addMessageToHistory($order_id, $comments, $success) {
		$this->load->model('checkout/order');
		$order_status_id = $success
			? "{$this->moduleName}_order_status_id"
			: "{$this->moduleName}_failed_order_status_id";
		$this->model_checkout_order->addOrderHistory(
			$order_id,
			$this->getConfigValue($order_status_id),
			$this->buildCommentsMessage($comments),
			false
		);
	}

	/**
	 * Builds a comment message from comments array
	 *
	 * @param  array $comments
	 * @return string
	 */
	protected function buildCommentsMessage($comments) {
		$result = '';
		foreach ($comments as $key => $value) {
			if ($result != '') {
				$result .= self::COMMENT_FIELD_DELIMITER;
			}
			$result .= $key . self::COMMENT_FIELD_EQUAL_SIGN . $value;
		}
		return $result;
	}

	/**
	 * Gets the value of a configuration setting
	 *
	 * @param string $key Configuration key
	 * @param string|null $default Default value
	 *
	 * @return string|null
	 */
	protected function getConfigValue($key, $default = null) {
		if ($this->isOpenCartVersion3OrAbove()) {
			// As of OpenCart version 3 the key is 'payment_' prefixed
			$key = "payment_{$key}";
		}

		$value = $this->config->get($key);
		if (is_null($value) && !is_null($default)) {
			$value = $default;
		}
		return $value;
	}

	/**
	 * Gets country numeric ISO 3166-1 code by country name
	 *
	 * @param  string $country_name
	 * @return string
	 */
	protected function getCountryIsoCode($country_name) {
		$result        = '';
		$country_codes = array(
			'Afghanistan'=>'4',
			'Albania'=>'8',
			'Algeria'=>'12',
			'American Samoa'=>'16',
			'Andorra'=>'20',
			'Angola'=>'24',
			'Anguilla'=>'660',
			'Antarctica'=>'',
			'Antigua and Barbuda'=>'28',
			'Argentina'=>'32',
			'Armenia'=>'51',
			'Aruba'=>'533',
			'Australia'=>'36',
			'Austria'=>'40',
			'Azerbaijan'=>'31',
			'Bahamas'=>'44',
			'Bahrain'=>'48',
			'Bangladesh'=>'50',
			'Barbados'=>'52',
			'Belarus'=>'112',
			'Belgium'=>'56',
			'Belize'=>'84',
			'Benin'=>'204',
			'Bermuda'=>'60',
			'Bhutan'=>'64',
			'Bolivia'=>'68',
			'Bosnia and Herzegowina'=>'70',
			'Botswana'=>'72',
			'Brazil'=>'76',
			'Brunei Darussalam'=>'96',
			'Bulgaria'=>'100',
			'Burkina Faso'=>'854',
			'Burundi'=>'108',
			'Cambodia'=>'116',
			'Cameroon'=>'120',
			'Canada'=>'124',
			'Cape Verde'=>'132',
			'Cayman Islands'=>'136',
			'Central African Republic'=>'140',
			'Chad'=>'148',
			'Chile'=>'152',
			'China'=>'156',
			'Colombia'=>'170',
			'Comoros'=>'174',
			'Congo'=>'178',
			'Cook Islands'=>'180',
			'Costa Rica'=>'184',
			'Cote D\'Ivoire'=>'188',
			'Croatia'=>'384',
			'Cuba'=>'191',
			'Cyprus'=>'192',
			'Czech Republic'=>'196',
			'Democratic Republic of Congo'=>'203',
			'Denmark'=>'208',
			'Djibouti'=>'262',
			'Dominica'=>'212',
			'Dominican Republic'=>'214',
			'Ecuador'=>'218',
			'Egypt'=>'818',
			'El Salvador'=>'222',
			'Equatorial Guinea'=>'226',
			'Eritrea'=>'232',
			'Estonia'=>'233',
			'Ethiopia'=>'231',
			'Falkland Islands (Malvinas)'=>'238',
			'Faroe Islands'=>'234',
			'Fiji'=>'242',
			'Finland'=>'246',
			'France'=>'250',
			'French Guiana'=>'254',
			'French Polynesia'=>'258',
			'French Southern Territories'=>'',
			'Gabon'=>'266',
			'Gambia'=>'270',
			'Georgia'=>'268',
			'Germany'=>'276',
			'Ghana'=>'288',
			'Gibraltar'=>'292',
			'Greece'=>'300',
			'Greenland'=>'304',
			'Grenada'=>'308',
			'Guadeloupe'=>'312',
			'Guam'=>'316',
			'Guatemala'=>'320',
			'Guinea'=>'324',
			'Guinea-bissau'=>'624',
			'Guyana'=>'328',
			'Haiti'=>'332',
			'Honduras'=>'340',
			'Hong Kong'=>'344',
			'Hungary'=>'348',
			'Iceland'=>'352',
			'India'=>'356',
			'Indonesia'=>'360',
			'Iran (Islamic Republic of)'=>'364',
			'Iraq'=>'368',
			'Ireland'=>'372',
			'Israel'=>'376',
			'Italy'=>'380',
			'Jamaica'=>'388',
			'Japan'=>'392',
			'Jordan'=>'400',
			'Kazakhstan'=>'398',
			'Kenya'=>'404',
			'Kiribati'=>'296',
			'Korea, Republic of'=>'410',
			'Kuwait'=>'414',
			'Kyrgyzstan'=>'417',
			'Lao People\'s Democratic Republic'=>'418',
			'Latvia'=>'428',
			'Lebanon'=>'422',
			'Lesotho'=>'426',
			'Liberia'=>'430',
			'Libyan Arab Jamahiriya'=>'434',
			'Liechtenstein'=>'438',
			'Lithuania'=>'440',
			'Luxembourg'=>'442',
			'Macau'=>'446',
			'Macedonia'=>'807',
			'Madagascar'=>'450',
			'Malawi'=>'454',
			'Malaysia'=>'458',
			'Maldives'=>'462',
			'Mali'=>'466',
			'Malta'=>'470',
			'Marshall Islands'=>'584',
			'Martinique'=>'474',
			'Mauritania'=>'478',
			'Mauritius'=>'480',
			'Mexico'=>'484',
			'Micronesia, Federated States of'=>'583',
			'Moldova, Republic of'=>'498',
			'Monaco'=>'492',
			'Mongolia'=>'496',
			'Montserrat'=>'500',
			'Morocco'=>'504',
			'Mozambique'=>'508',
			'Myanmar'=>'104',
			'Namibia'=>'516',
			'Nauru'=>'520',
			'Nepal'=>'524',
			'Netherlands'=>'528',
			'Netherlands Antilles'=>'530',
			'New Caledonia'=>'540',
			'New Zealand'=>'554',
			'Nicaragua'=>'558',
			'Niger'=>'562',
			'Nigeria'=>'566',
			'Niue'=>'570',
			'Norfolk Island'=>'574',
			'Northern Mariana Islands'=>'580',
			'Norway'=>'578',
			'Oman'=>'512',
			'Pakistan'=>'586',
			'Palau'=>'585',
			'Panama'=>'591',
			'Papua New Guinea'=>'598',
			'Paraguay'=>'600',
			'Peru'=>'604',
			'Philippines'=>'608',
			'Pitcairn'=>'612',
			'Poland'=>'616',
			'Portugal'=>'620',
			'Puerto Rico'=>'630',
			'Qatar'=>'634',
			'Reunion'=>'638',
			'Romania'=>'642',
			'Russian Federation'=>'643',
			'Rwanda'=>'646',
			'Saint Kitts and Nevis'=>'659',
			'Saint Lucia'=>'662',
			'Saint Vincent and the Grenadines'=>'670',
			'Samoa'=>'882',
			'San Marino'=>'674',
			'Sao Tome and Principe'=>'678',
			'Saudi Arabia'=>'682',
			'Senegal'=>'686',
			'Seychelles'=>'690',
			'Sierra Leone'=>'694',
			'Singapore'=>'702',
			'Slovak Republic'=>'703',
			'Slovenia'=>'705',
			'Solomon Islands'=>'90',
			'Somalia'=>'706',
			'South Africa'=>'710',
			'Spain'=>'724',
			'Sri Lanka'=>'144',
			'Sudan'=>'736',
			'Suriname'=>'740',
			'Svalbard and Jan Mayen Islands'=>'744',
			'Swaziland'=>'748',
			'Sweden'=>'752',
			'Switzerland'=>'756',
			'Syrian Arab Republic'=>'760',
			'Taiwan'=>'158',
			'Tajikistan'=>'762',
			'Tanzania, United Republic of'=>'834',
			'Thailand'=>'764',
			'Togo'=>'768',
			'Tokelau'=>'772',
			'Tonga'=>'776',
			'Trinidad and Tobago'=>'780',
			'Tunisia'=>'788',
			'Turkey'=>'792',
			'Turkmenistan'=>'795',
			'Turks and Caicos Islands'=>'796',
			'Tuvalu'=>'798',
			'Uganda'=>'800',
			'Ukraine'=>'804',
			'United Arab Emirates'=>'784',
			'United Kingdom'=>'826',
			'United States'=>'840',
			'Uruguay'=>'858',
			'Uzbekistan'=>'860',
			'Vanuatu'=>'548',
			'Vatican City State (Holy See)'=>'336',
			'Venezuela'=>'862',
			'Viet Nam'=>'704',
			'Virgin Islands (British)'=>'92',
			'Virgin Islands (U.S.)'=>'850',
			'Wallis and Futuna Islands'=>'876',
			'Western Sahara'=>'732',
			'Yemen'=>'887',
			'Zambia'=>'894',
			'Zimbabwe'=>'716'
		);
		if (array_key_exists($country_name, $country_codes)) {
			$result = $country_codes[$country_name];
		}
		return $result;
	}

	/**
	 * Gets currency numeric ISO 4217 code by currency alphabetic code
	 *
	 * @param string $currency_code Currency 4217 code
	 * @param string $default_code Default currency code
	 * @return string
	 */
	protected function getCurrencyIsoCode($currency_code, $default_code = '826') {
		$result = $default_code;
		$iso_codes = array(
			'AFA' => 4,
			'ALK' => 8,
			'ALL' => 8,
			'DZD' => 12,
			'ADP' => 20,
			'AON' => 24,
			'AOK' => 24,
			'AZM' => 31,
			'ARA' => 32,
			'ARP' => 32,
			'ARS' => 32,
			'AUD' => 36,
			'ATS' => 40,
			'BSD' => 44,
			'BHD' => 48,
			'BDT' => 50,
			'AMD' => 51,
			'BBD' => 52,
			'BEF' => 56,
			'BMD' => 60,
			'BTN' => 64,
			'BOB' => 68,
			'BOP' => 68,
			'BAD' => 70,
			'BWP' => 72,
			'BRN' => 76,
			'BRE' => 76,
			'BRC' => 76,
			'BRB' => 76,
			'BZD' => 84,
			'SBD' => 90,
			'BND' => 96,
			'BGL' => 100,
			'MMK' => 104,
			'BUK' => 104,
			'BIF' => 108,
			'BYB' => 112,
			'KHR' => 116,
			'CAD' => 124,
			'CVE' => 132,
			'KYD' => 136,
			'LKR' => 144,
			'CLP' => 152,
			'CNY' => 156,
			'COP' => 170,
			'KMF' => 174,
			'ZRN' => 180,
			'ZRZ' => 180,
			'CRC' => 188,
			'HRK' => 191,
			'HRD' => 191,
			'CUP' => 192,
			'CYP' => 196,
			'CSK' => 200,
			'CZK' => 203,
			'DKK' => 208,
			'DOP' => 214,
			'ECS' => 218,
			'SVC' => 222,
			'GQE' => 226,
			'ETB' => 230,
			'ERN' => 232,
			'EEK' => 233,
			'FKP' => 238,
			'FJD' => 242,
			'FIM' => 246,
			'FRF' => 250,
			'DJF' => 262,
			'GEK' => 268,
			'GMD' => 270,
			'DEM' => 276,
			'DDM' => 278,
			'GHC' => 288,
			'GIP' => 292,
			'GRD' => 300,
			'GTQ' => 320,
			'GNF' => 324,
			'GNS' => 324,
			'GYD' => 328,
			'HTG' => 332,
			'HNL' => 340,
			'HKD' => 344,
			'HUF' => 348,
			'ISK' => 352,
			'ISJ' => 352,
			'INR' => 356,
			'IDR' => 360,
			'IRR' => 364,
			'IQD' => 368,
			'IEP' => 372,
			'ILS' => 376,
			'ILP' => 376,
			'ILR' => 376,
			'ITL' => 380,
			'JMD' => 388,
			'JPY' => 392,
			'KZT' => 398,
			'JOD' => 400,
			'KES' => 404,
			'KPW' => 408,
			'KRW' => 410,
			'KWD' => 414,
			'KGS' => 417,
			'LAK' => 418,
			'LBP' => 422,
			'LSL' => 426,
			'LVL' => 428,
			'LVR' => 428,
			'LRD' => 430,
			'LYD' => 434,
			'LTT' => 440,
			'LTL' => 440,
			'LUF' => 442,
			'MOP' => 446,
			'MGF' => 450,
			'MWK' => 454,
			'MYR' => 458,
			'MVR' => 462,
			'MLF' => 466,
			'MTP' => 470,
			'MTL' => 470,
			'MRO' => 478,
			'MUR' => 480,
			'MXN' => 484,
			'MXP' => 484,
			'MNT' => 496,
			'MDL' => 498,
			'MAD' => 504,
			'MZM' => 508,
			'MZE' => 508,
			'OMR' => 512,
			'NAD' => 516,
			'NPR' => 524,
			'NLG' => 528,
			'ANG' => 532,
			'AWG' => 533,
			'VUV' => 548,
			'NZD' => 554,
			'NIO' => 558,
			'NIC' => 558,
			'NGN' => 566,
			'NOK' => 578,
			'PKR' => 586,
			'PAB' => 590,
			'PGK' => 598,
			'PYG' => 600,
			'PEI' => 604,
			'PEN' => 604,
			'PES' => 604,
			'PHP' => 608,
			'PLZ' => 616,
			'PTE' => 620,
			'GWP' => 624,
			'GWE' => 624,
			'TPE' => 626,
			'QAR' => 634,
			'ROL' => 642,
			'RUB' => 643,
			'RWF' => 646,
			'SHP' => 654,
			'STD' => 678,
			'SAR' => 682,
			'SCR' => 690,
			'SLL' => 694,
			'SGD' => 702,
			'SKK' => 703,
			'VND' => 704,
			'SIT' => 705,
			'SOS' => 706,
			'ZAR' => 710,
			'RHD' => 716,
			'ZWD' => 716,
			'YDD' => 720,
			'ESP' => 724,
			'SSP' => 728,
			'SDP' => 736,
			'SDD' => 736,
			'SRG' => 740,
			'SZL' => 748,
			'SEK' => 752,
			'CHF' => 756,
			'SYP' => 760,
			'TJR' => 762,
			'THB' => 764,
			'TOP' => 776,
			'TTD' => 780,
			'AED' => 784,
			'TND' => 788,
			'TRL' => 792,
			'TMM' => 795,
			'UGS' => 800,
			'UGX' => 800,
			'UAK' => 804,
			'MKD' => 807,
			'RUR' => 810,
			'SUR' => 810,
			'EGP' => 818,
			'GBP' => 826,
			'TZS' => 834,
			'USD' => 840,
			'UYU' => 858,
			'UYP' => 858,
			'UZS' => 860,
			'VEB' => 862,
			'WST' => 882,
			'YER' => 886,
			'YUN' => 890,
			'CSD' => 891,
			'YUM' => 891,
			'YUD' => 891,
			'ZMK' => 894,
			'TWD' => 901,
			'CUC' => 931,
			'ZWL' => 932,
			'BYN' => 933,
			'TMT' => 934,
			'ZWR' => 935,
			'GHS' => 936,
			'VEF' => 937,
			'SDG' => 938,
			'UYI' => 940,
			'RSD' => 941,
			'MZN' => 943,
			'AZN' => 944,
			'RON' => 946,
			'CHE' => 947,
			'CHW' => 948,
			'TRY' => 949,
			'XAF' => 950,
			'XCD' => 951,
			'XOF' => 952,
			'XPF' => 953,
			'XEU' => 954,
			'ZMW' => 967,
			'SRD' => 968,
			'MGA' => 969,
			'COU' => 970,
			'AFN' => 971,
			'TJS' => 972,
			'AOA' => 973,
			'BYR' => 974,
			'BGN' => 975,
			'CDF' => 976,
			'BAM' => 977,
			'EUR' => 978,
			'MXV' => 979,
			'UAH' => 980,
			'GEL' => 981,
			'AOR' => 982,
			'ECV' => 983,
			'BOV' => 984,
			'PLN' => 985,
			'BRL' => 986,
			'BRR' => 987,
			'LUL' => 988,
			'LUC' => 989,
			'CLF' => 990,
			'ZAL' => 991,
			'BEL' => 992,
			'BEC' => 993,
			'ESB' => 995,
			'ESA' => 996,
			'USN' => 997,
			'USS' => 998,
		);
		if (array_key_exists($currency_code, $iso_codes)) {
			$result = $iso_codes[$currency_code];
		}
		return $result;
	}

	/**
	 * Determines whether the OpenCart Version is 3 or above
	 *
	 * @return bool
	 */
	protected function isOpenCartVersion3OrAbove() {
		return defined('VERSION') && version_compare(VERSION, '3.0', '>=');
	}

	/**
	 * Checks whether the payment method is configured
	 *
	 * @return bool True if gateway merchant ID, password and pre-shared key (for Hosted only) are non-empty, otherwise false
	 */
	protected function isConfigured() {
		return ((trim($this->getConfigValue("{$this->moduleName}_mid")) != '') &&
			(trim($this->getConfigValue("{$this->moduleName}_pass")) != '') &&
			(
				($this->moduleName != 'paymentsense_hosted') ||
				(trim($this->getConfigValue("{$this->moduleName}_key")) != '')
			)
		);
	}

	/**
	 * Gets the value of an HTTP variable based on the requested method or empty string if doesn't exist
	 *
	 * @param string $field
	 * @return mixed
	 */
	protected function getHttpVar($field) {
		switch ($this->request->server['REQUEST_METHOD']) {
			case 'GET':
				return array_key_exists($field, $this->request->get)
					? $this->request->get[$field]
					: '';
			case 'POST':
				return array_key_exists($field, $this->request->post)
					? $this->request->post[$field]
					: '';
			default:
				return '';
		}
	}

	/**
	 * Gets module name
	 *
	 * @return string
	 */
	protected function getModuleName() {
		return self::MODULE_NAME;
	}

	/**
	 * Gets module installed version
	 *
	 * @return string
	 */
	protected function getModuleInstalledVersion() {
		return self::MODULE_VERSION;
	}

	/**
	 * Gets module latest version
	 *
	 * @return string
	 */
	protected function getModuleLatestVersion() {
		$result = 'N/A';
		$headers = array(
			'User-Agent: ' . $this->getModuleName() . ' v.' . $this->getModuleInstalledVersion(),
			'Content-Type: text/plain; charset=utf-8',
			'Accept: text/plain, */*',
			'Accept-Encoding: identity',
			'Connection: close'
		);
		$data = array(
			'url'     => 'https://api.github.com/repos/'.
				'Paymentsense-DevSupport/OpenCart3/releases/latest',
			'headers' => $headers
		);
		if ($this->performCurl($data, $response) === 0) {
			$json_object = @json_decode($response);
			if (is_object($json_object) && property_exists($json_object, 'tag_name')) {
				$result = $json_object->tag_name;
			}
		}
		return $result;
	}

	/**
	 * Gets OpenCart version
	 *
	 * @return string
	 */
	protected function getOpenCartVersion() {
		return VERSION;
	}

	/**
	 * Gets PHP version
	 *
	 * @return string
	 */
	protected function getPhpVersion() {
		return phpversion();
	}

	/**
	 * Performs cURL requests to the Paymentsense gateway
	 *
	 * @param array $data cURL data.
	 * @param mixed $response the result or false on failure.
	 *
	 * @return int the error number or 0 if no error occurred
	 */
	protected function performCurl($data, &$response) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_URL, $data['url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$err_no   = curl_errno($ch);
		curl_close($ch);
		return $err_no;
	}

	/**
	 * Gets a parameter from the route
	 *
	 * @param string $parameter Parameter.
	 * @param string $default Default value.
	 * @return string
	 */
	protected function getRequestParameter($parameter, $default = '') {
		$result = $default;
		if (isset($this->request->get['route'])) {
			$route = $this->request->get['route'];
			if (preg_match('#\/' . $parameter .'\/([a-z]+)#i', $route, $matches)) {
				$result = $matches[1];
			}
		}
		return $result;
	}

	/**
	 * Converts an array to string
	 *
	 * @param array  $arr An associative array.
	 * @param string $ident Identation.
	 * @return string
	 */
	protected function convertArrayToString($arr, $ident = '') {
		$result        = '';
		$ident_pattern = '  ';
		foreach ($arr as $key => $value) {
			if ('' !== $result) {
				$result .= PHP_EOL;
			}
			if (is_array($value)) {
				$value = PHP_EOL . $this->convertArrayToString($value, $ident . $ident_pattern);
			}
			$result .= $ident . $key . ': ' . $value;
		}
		return $result;
	}

	/**
	 * Outputs plugin information
	 *
	 * @param array $info Module information.
	 */
	protected function outputInfo($info) {
		$output       = $this->getRequestParameter('output', 'text');
		$content_type = array_key_exists($output, $this->content_types)
			? $this->content_types[ $output ]
			: self::TYPE_TEXT_PLAIN;

		switch ($content_type) {
			case self::TYPE_APPLICATION_JSON:
				$body = json_encode($info);
				break;
			case self::TYPE_TEXT_PLAIN:
			default:
				$body = $this->convertArrayToString($info);
				break;
		}

		@header('Cache-Control: max-age=0, must-revalidate, no-cache, no-store', true);
		@header('Pragma: no-cache', true);
		@header('Content-Type: ' . $content_type, true);
		echo $body;
		exit;
	}

	/**
	 * Gets file checksums
	 *
	 * @return array
	 */
	protected function getFileChecksums() {
		$result = array();
		$root_path = realpath(__DIR__ . '/../../../..');
		$file_list = $this->getHttpVar('data');
		if (is_array($file_list)) {
			foreach ($file_list as $key => $file) {
				$filename = $root_path . '/' . $file;
				$result[$key] = is_file($filename)
					? sha1_file($filename)
					: null;
			}
		}
		return $result;
	}
}
