<?php
/*
 * Copyright (C) 2020 Paymentsense Ltd.
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
 * @copyright   2020 Paymentsense Ltd.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

require_once DIR_APPLICATION . "controller/extension/payment/paymentsense_base.php";

/**
 * Admin Controller for Paymentsense Hosted
 */
class ControllerExtensionPaymentPaymentsenseHosted extends ControllerPaymentPaymentsenseBase {
	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName = 'paymentsense_hosted';

	/**
	 * Error messages
	 *
	 * @var array
	 */
	protected $error = array();

	/**
	 * Determines whether a secure connection is required for this module
	 *
	 * @return bool
	 */
	public function isSecureConnectionRequired() {
		return false;
	}

	/**
	 * Gets the entry phrases
	 * @see admin/language/en-gb/extension/payment/paymentsense_hosted.php
	 *
	 * @return array
	 */
	public function getEntryPhrases() {
		return array(
			'entry_status',
			'entry_geo_zone',
			'entry_order_status',
			'entry_failed_order_status',
			'entry_mid',
			'entry_pass',
			'entry_key',
			'entry_type',
			'entry_CV2Mandatory',
			'entry_Address1Mandatory',
			'entry_CityMandatory',
			'entry_PostCodeMandatory',
			'entry_StateMandatory',
			'entry_CountryMandatory',
		);
	}

	/**
	 * Gets the names and default values of the module configuration fields
	 *
	 * @return array
	 */
	public function getConfigFields() {
		return array(
			'paymentsense_hosted_status'                 => null,
			'paymentsense_hosted_geo_zone_id'            => null,
			'paymentsense_hosted_order_status_id'        => parent::OC_ORD_STATUS_PROCESSING,
			'paymentsense_hosted_failed_order_status_id' => parent::OC_ORD_STATUS_FAILED,
			'paymentsense_hosted_mid'                    => null,
			'paymentsense_hosted_pass'                   => null,
			'paymentsense_hosted_key'                    => null,
			'paymentsense_hosted_hash_method'            => 'SHA1',
			'paymentsense_hosted_type'                   => null,
			'paymentsense_hosted_sort_order'             => null,
			'paymentsense_hosted_cv2_mand'               => null,
			'paymentsense_hosted_address1_mand'          => null,
			'paymentsense_hosted_city_mand'              => null,
			'paymentsense_hosted_postcode_mand'          => null,
			'paymentsense_hosted_state_mand'             => null,
			'paymentsense_hosted_country_mand'           => null
		);
	}

	/**
	 * Checks whether the user is permitted to modify the module and validates the input
	 *
	 * @return bool
	 */
	public function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/paymentsense_hosted')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['paymentsense_hosted_mid']) {
			$this->error['mid'] = $this->language->get('error_mid');
		}

		if (!$this->request->post['paymentsense_hosted_pass']) {
			$this->error['pass'] = $this->language->get('error_pass');
		}

		if (!$this->request->post['paymentsense_hosted_key']) {
			$this->error['key'] = $this->language->get('error_key');
		}

		return !$this->error;
	}
}
