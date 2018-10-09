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
 * Base Admin Controller for Paymentsense Hosted and Direct
 */
abstract class ControllerPaymentPaymentsenseBase extends Controller
{
	/**
	 * Module Version and Data
	 */
	const MODULE_VERSION = '3.0.1';
	const MODULE_DATE    = '9 October 2018';

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
	 * Patterns for validating the input of the configuration fields
	 */
	const PATTERN_MID = '/^([a-zA-Z0-9]{6})([-])([0-9]{7})$/';

	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName;

	/**
	 * Error messages
	 *
	 * @var array
	 */
	protected $error;

	/**
	 * Base Install Method
	 *
	 * Adds events for views for switching and restoring the template engine (for OpenCart 3)
	 */
	public function install() {
		if ($this->isOpenCartVersion3OrAbove()) {
			$this->load->model('setting/event');
			$this->model_setting_event->addEvent(
				"payment_{$this->moduleName}", 'admin/view/*/before',
				"extension/payment/{$this->moduleName}/switchTemplateEngine",
				1
			);
			$this->model_setting_event->addEvent(
				"payment_{$this->moduleName}", 'admin/view/*/after',
				"extension/payment/{$this->moduleName}/restoreTemplateEngine",
				1
			);
			$this->model_setting_event->addEvent(
				"payment_{$this->moduleName}", 'catalog/view/*/before',
				"extension/payment/{$this->moduleName}/switchTemplateEngine",
				1
			);
			$this->model_setting_event->addEvent(
				"payment_{$this->moduleName}", 'catalog/view/*/after',
				"extension/payment/{$this->moduleName}/restoreTemplateEngine",
				1
			);
		}
	}

	/**
	 * Base Uninstall Method
	 *
	 * Deletes events (for OpenCart 3)
	 */
	public function uninstall() {
		if ($this->isOpenCartVersion3OrAbove()) {
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode("payment_{$this->moduleName}");
		}
	}

	/**
	 * Base Index Action
	 */
	public function index() {
		$this->load->language("extension/payment/{$this->moduleName}");
		$this->document->setTitle($this->language->get('heading_title'));
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			try {
				if ($this->validate()) {
					$this->updateSettings();
					$json = array(
						'result' => 0
					);
				} else {
					if (array_key_exists('warning', $this->error) && ($this->error['warning'] != '')) {
						$json = array(
							'result'   => 1
						);
					} else {
						unset($this->error['warning']);
						$json = array(
							'result'   => 2,
							'messages' => $this->error
						);
					}
				}
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
			}
			catch (\Exception $e) {
				$this->response->addHeader('HTTP/1.0 500 Internal Server Error');
			}
		} else {
			$template_data = $this->prepareConfigTemplateVars();
			$this->response->setOutput(
				$this->load->view("extension/payment/{$this->moduleName}.tpl", $template_data)
			);
		}
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
	 * Prepares the template variables for the module configuration page
	 *
	 * @return array
	 */
	protected function prepareConfigTemplateVars() {
		$this->load->model('localisation/order_status');
		$this->load->model('localisation/geo_zone');
		$result = array(
			'module_version' => self::MODULE_VERSION,
			'module_date'    => self::MODULE_DATE,
			'order_statuses' => $this->model_localisation_order_status->getOrderStatuses(),
			'geo_zones'      => $this->model_localisation_geo_zone->getGeoZones(),
			'breadcrumbs'    => $this->getBreadcrumbs(),
			'action'         => $this->getPaymentExtensionLink(),
			'cancel'         => $this->getPaymentExtensionsLink(),
			'header'         => $this->load->controller('common/header'),
			'column_left'    => $this->load->controller('common/column_left'),
			'footer'         => $this->load->controller('common/footer'),
			'warning_insecure_connection' => $this->isSecureConnectionRequired() && !$this->isConnectionSecure()
				? $this->language->get('warning_insecure_connection')
				: ''
		);

		$phrases = array(
			'heading_title',
			'text_success',
			'error_failed',
			'error_permission',
			'error_required',
			'text_enabled',
			'text_disabled',
			'text_all_zones',
			'text_none',
			'text_yes',
			'text_no',
			'text_sale',
			'text_preauth',
			'button_save',
			'button_cancel',
			'tab_general'
		);

		$phrases = array_merge($phrases, $this->getEntryPhrases());
		foreach ($phrases as $phrase) {
			$result[$phrase] = $this->language->get($phrase);
		}

		$fields = $this->getConfigFields();
		foreach ($fields as $key => $value) {
			if (isset($this->request->post[$key])) {
				$result[$key] = $this->request->post[$key];
			} else {
				$result[$key] = $this->getConfigValue($key, $value);
			}
		}

		return $result;
	}

	/**
	 * Gets token name
	 *
	 * @return string
	 */
	protected function getTokenName()
	{
		return $this->isOpenCartVersion3OrAbove() ? 'user_token' : 'token';
	}

	/**
	 * Gets token value
	 *
	 * @return string
	 */
	protected function getToken()
	{
		return $this->session->data[$this->getTokenName()];
	}

	/**
	 * Builds token arguments string
	 *
	 * @return string
	 */
	protected function buildTokenArgumentString()
	{
		return $this->getTokenName() . '=' . $this->getToken();
	}

	/**
	 * Gets extension route
	 *
	 * @return string
	 */
	protected function getPaymentExtensionRoute()
	{
		return $this->isOpenCartVersion3OrAbove() ? 'marketplace/extension' : 'extension/extension';
	}

	/**
	 * Gets a link to the dashboard
	 *
	 * @return string
	 */
	protected function getDashboardLink()
	{
		return $this->url->link('common/dashboard', $this->buildTokenArgumentString(), 'SSL');
	}

	/**
	 * Gets a link to the Payment modules list
	 *
	 * @return string
	 */
	protected function getPaymentExtensionsLink()
	{
		return $this->url->link($this->getPaymentExtensionRoute(), 'type=payment&' . $this->buildTokenArgumentString(), 'SSL');
	}

	/**
	 * Gets a link to the Payment module
	 *
	 * @return string
	 */
	protected function getPaymentExtensionLink()
	{
		return $this->url->link("extension/payment/{$this->moduleName}", $this->buildTokenArgumentString(), 'SSL');
	}

	/**
	 * Gets the breadcrumbs
	 *
	 * @return array
	 */
	protected function getBreadcrumbs()
	{
		return array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->getDashboardLink()
			),
			array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->getPaymentExtensionsLink()
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->getPaymentExtensionLink()
			)
		);
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
	 * Updates module configuration settings
	 */
	protected function updateSettings()
	{
		$this->load->model('setting/setting');

		$code = $this->moduleName;
		$data = $this->request->post;

		if ($this->isOpenCartVersion3OrAbove()) {
			// As of OpenCart version 3 the code and the keys of the settings data array are 'payment_' prefixed
			$code = "payment_{$code}";
			$data = array_combine(
				array_map(
					function($key) {
						return "payment_{$key}";
					},
					array_keys($data)
				),
				$data
			);
		}

		$this->model_setting_setting->editSetting($code, $data);
	}

	/**
	 * Checks whether the current connection is secure
	 *
	 * @return bool
	 */
	protected function isConnectionSecure()
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
