<?php
/*
  Plugin Name: Event Espresso - Paysera Payment Method
  Description: The Event Espresso Paysera Payment Method adds new offsite payment method
  Version: 0.0.1
  Author: Vasily Ogar
  Copyright 2015 Vasily Ogar (email : ogar.vasily@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define( 'EE_PAYSERA_PAYMENT_METHOD_VERSION', '0.0.1' );
define( 'EE_PAYSERA_PAYMENT_METHOD_PLUGIN_FILE',  __FILE__ );
function load_espresso_paysera_payment_method() {
if ( class_exists( 'EE_Addon' )) {
	// new_payment_method version
	require_once ( plugin_dir_path( __FILE__ ) . 'EE_Paysera_Payment_Method.class.php' );
	EE_Paysera_Payment_Method::register_addon();
}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_paysera_payment_method' );

// End of file espresso_paysera_payment_method.php
// Location: wp-content/plugins/espresso-paysera-payment/espresso_paysera_payment_method.php