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
 * Admin Controller for Paymentsense Direct
 */
class ControllerExtensionPaymentPaymentsenseDirect extends ControllerPaymentPaymentsenseBase {
	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName = 'paymentsense_direct';

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
		return true;
	}

	/**
	 * Gets the entry phrases
	 * @see admin/language/en-gb/extension/payment/paymentsense_direct.php
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
			'entry_type',
		);
	}

	/**
	 * Gets the names and default values of the module configuration fields
	 *
	 * @return array
	 */
	public function getConfigFields() {
		return array(
			'paymentsense_direct_status'                 => null,
			'paymentsense_direct_geo_zone_id'            => null,
			'paymentsense_direct_order_status_id'        => parent::OC_ORD_STATUS_PROCESSING,
			'paymentsense_direct_failed_order_status_id' => parent::OC_ORD_STATUS_FAILED,
			'paymentsense_direct_mid'                    => null,
			'paymentsense_direct_pass'                   => null,
			'paymentsense_direct_type'                   => null,
			'paymentsense_direct_sort_order'             => null,
			'paymentsense_direct_cv2_policy_1'           => null,
			'paymentsense_direct_cv2_policy_2'           => null,
			'paymentsense_direct_avs_policy_1'           => null,
			'paymentsense_direct_avs_policy_2'           => null,
			'paymentsense_direct_avs_policy_3'           => null,
			'paymentsense_direct_avs_policy_4'           => null
		);
	}

	/**
	 * Checks whether the user is permitted to modify the module and validates the input
	 *
	 * @return bool
	 */
	public function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/paymentsense_direct')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['paymentsense_direct_mid']) {
			$this->error['mid'] = $this->language->get('error_mid');
		}

		if (!$this->request->post['paymentsense_direct_pass']) {
			$this->error['pass'] = $this->language->get('error_pass');
		}

		return !$this->error;
	}
}
