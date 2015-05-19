<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}

/**
 *
 * Class EE_PMT_Paysera_Payment_Method
 *
 * @package 			Event Espresso
 * @subpackage          core
 * @author 				Vasily Ogar
 * @since 				$VID:$
 *
 */
class EE_PMT_Paysera_Payment_Method extends EE_PMT_Base {

    /**
     * @param null $pm_instance
     * @return \EEG_Paysera_Payment_Method
     */
    public function __construct($pm_instance = NULL) {
        require_once($this->file_folder() . 'EEG_Paysera_Payment_Method.gateway.php');
        $this->_gateway = new EEG_Paysera_Payment_Method();
        $this->_pretty_name = __("Paysera", 'event_espresso');
        $this->_default_description = sprintf(__('Upon submitting this form, you will be forwarded to Paysera to make your payment. %1$sMake sure you return to this site in order to properly finalize your registration.%2$s', 'event_espresso'), '<strong>', '</strong>');
        parent::__construct($pm_instance);
        $this->_default_button_url = $this->file_url() . 'lib' . DS . 'paysera_white.png';
    }

    /**
     * Creates the billing form for this payment method type
     * @param \EE_Transaction $transaction
     * @return NULL
     */
    public function generate_new_billing_form(EE_Transaction $transaction = NULL) {
        return NULL;
    }

    /**
     * Gets the form for all the settings related to this payment method type
     * @return EE_Payment_Method_Form
     */
    public function generate_new_settings_form() {
        require_once( $this->file_folder() . 'EE_Paysera_Payment_Method_Form.form.php' );
        $form = new EE_Paysera_Payment_Method_Form($this);
        $form->get_input('PMD_debug_mode')->set_html_label_text(sprintf(__("Use Paysera test mode %s", 'event_espresso'), $this->get_help_tab_link()));
        $form->get_input('PMD_debug_mode')->set_html_help_text("Be sure to turn this setting off when you are done testing.", 'event_espresso');
        return $form;
    }

    /**
     * Adds the help tab
     * @see EE_PMT_Base::help_tabs_config()
     * @return array
     */
    public function help_tabs_config() {
        return array(
            $this->get_help_tab_name() => array(
                'title' => __("Paysera Settings", 'event_espresso'),
                'filename' => 'payment_methods_overview_paysera'
            )
        );
    }

}
