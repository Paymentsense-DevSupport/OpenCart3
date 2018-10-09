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

/**
 * Base Front Model for Paymentsense Hosted and Direct
 */
abstract class ModelExtensionPaymentPaymentsenseBase extends Model
{
	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName;

	/**
	 * Main method
	 *
	 * @param $address Order Address
	 * @param $total   Order Total
	 *
	 * @return array
	 */
	public function getMethod($address, $total)
	{
		$method_data = array();
		if ($this->isAvailable($address, $total)) {
			$this->load->language("extension/payment/{$this->moduleName}");
			$method_data = array(
				'code'       => $this->moduleName,
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->getConfigValue("{$this->moduleName}_sort_order")
			);
		}
		return $method_data;
	}

	/**
	 * Gets the value of a configuration setting
	 *
	 * @param string $key Configuration key
	 * @param string|null $default Default value
	 *
	 * @return string|null
	 */
	protected function getConfigValue($key, $default = null)
	{
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
	 * Checks whether the payment method is available for checkout
	 *
	 * @param $address Order Address
	 * @param $total   Order Total
	 *
	 * @return bool
	 */
	protected function isAvailable($address, $total)
	{
		if ($this->isSecureConnectionRequired() && !$this->isConnectionSecure()) {
			return false;
		}

		if ($total == 0) {
			return false;
		}

		if ($this->getConfigValue("{$this->moduleName}_geo_zone_id")) {
			$query = $this->db->query(
				"SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone " .
				"WHERE geo_zone_id = '" . (int)$this->getConfigValue("{$this->moduleName}_geo_zone_id") .
				"' AND country_id = '" . (int)$address['country_id'] .
				"' AND (zone_id = '" . (int)$address['zone_id'] .
				"' OR zone_id = '0')"
			);

			if (!$query->num_rows) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks whether the current connection is secure
	 *
	 * @return bool
	 */
	public function isConnectionSecure()
	{
		$https = array_key_exists('HTTPS',$this->request->server)
			? $this->request->server['HTTPS']
			: '';
		$forwarded_proto = array_key_exists('HTTP_X_FORWARDED_PROTO',$this->request->server)
			? $this->request->server['HTTP_X_FORWARDED_PROTO']
			: '';
		switch (true) {
			case !empty($https) && strtolower($https) != 'off':
				$result = true;
				break;
			case !empty($forwarded_proto) && $forwarded_proto == 'https':
				$result = true;
				break;
			default:
				$result = false;
		}
		return $result;
	}

	/**
	 * Determines whether the OpenCart Version is 3 or above
	 *
	 * @return bool
	 */
	protected function isOpenCartVersion3OrAbove()
	{
		return defined('VERSION') && version_compare(VERSION, '3.0', '>=');
	}
}
