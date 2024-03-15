<?php

function gls_shipping_method_parcel_locker_init()
{
	if (!class_exists('GLS_Shipping_Method_Parcel_Locker')) {
		class GLS_Shipping_Method_Parcel_Locker extends WC_Shipping_Method
		{

			/**
			 * Constructor for shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct()
			{
				$this->id                 = GLS_SHIPPING_METHOD_PARCEL_LOCKER_ID;
				$this->method_title       = __('GLS Parcel Locker', 'gls_croatia');
				$this->method_description = __('Parcel Shop Delivery (PSD) service that ships parcels to the GLS Locker. GLS Parcel Locker can be selected from the interactive GLS Parcel Shop and GLS Locker finder map.', 'gls_croatia');

				$this->init();

				$this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
				$this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Delivery to GLC Parcel Locker', 'gls_croatia');
			}

			/**
			 * Init settings
			 *
			 * @access public
			 * @return void
			 */
			function init()
			{
				// Load the settings API
				$this->init_form_fields();
				$this->init_settings();

				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}

			public function init_form_fields()
			{
				$this->form_fields = array(
					'enabled' => array(
						'title' => __('Enable', 'gls_croatia'),
						'type' => 'checkbox',
						'description' => __('Enable this shipping.', 'gls_croatia'),
						'default' => 'yes'
					),
					'title' => array(
						'title' => __('Title', 'gls_croatia'),
						'type' => 'text',
						'description' => __('Title to be display on site', 'gls_croatia'),
						'default' => __('Delivery to GLS Parcel Locker', 'gls_croatia')
					),
					'shipping_price' => array(
						'title'       => __('Shipping Price', 'gls_croatia'),
						'type'        => 'text',
						'description' => __('Enter the shipping price for this method.', 'gls_croatia'),
						'default'     => 0,
						'desc_tip'    => true,
					),
					'supported_countries' => array(
						'title'   => __('Supported Countries', 'gls_croatia'),
						'type'    => 'multiselect',
						'class'   => 'wc-enhanced-select',
						'css'     => 'width: 400px;',
						'options' => array(
							'HR' => __('Croatia', 'gls_croatia'),
							'CZ' => __('Czech Republic', 'gls_croatia'),
							'HU' => __('Hungary', 'gls_croatia'),
							'RO' => __('Romania', 'gls_croatia'),
							'SI' => __('Slovenia', 'gls_croatia'),
							'SK' => __('Slovakia', 'gls_croatia'),
							'RS' => __('Serbia', 'gls_croatia'),
						),
						'default' => array('HR', 'CZ', 'HU', 'RO', 'SI', 'SK'),
						'desc_tip' => true,
						'description' => __('Select countries to support for this shipping method.', 'gls_croatia'),
					),
				);
			}

			/**
			 * Calculate Shipping Rate
			 *
			 * @access public
			 * @param array $package
			 * @return void
			 */
			public function calculate_shipping($package = array())
			{
				$supported_countries = $this->get_option('supported_countries');
				$price = $this->get_option('shipping_price', '0');

				if (in_array($package['destination']['country'], $supported_countries)) {
					$rate = array(
						'id'       => $this->id,
						'label'    => $this->title,
						'cost'     => $price,
						'calc_tax' => 'per_item'
					);

					// Register the rate
					$this->add_rate($rate);
				}
			}
		}
	}
}

add_action('woocommerce_shipping_init', 'gls_shipping_method_parcel_locker_init');
