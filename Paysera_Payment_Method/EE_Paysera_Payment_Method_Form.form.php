<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}

/**
 *
 * EE_Paysera_Payment_Method_Form
 * Override's normal EE_Payment_Method_Form to force shipping details to be set to require info
 * whenever the admin selects paypal to calculate shipping or taxes
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Vasily Ogar
 *
 */
class EE_Paysera_Payment_Method_Form extends EE_Payment_Method_Form {

    protected function _normalize($req_data) {
        parent::_normalize($req_data);
    }

    /**
     *
     * @param EE_PMT_Paysera $payment_method_type
     */
    public function __construct($payment_method_type) {
        $options_array = array(
            'payment_method_type' => $payment_method_type,
            'extra_meta_inputs' => array(
                'projectid' => new EE_Text_Input(array(
                    'html_label_text' => sprintf(__("Unique project number %s", 'event_espresso'), $payment_method_type->get_help_tab_link()),
                    'html_help_text' => __("Only activated projects can accept payments.", 'event_espresso'),
                    'required' => true
                        )),
                'password' => new EE_Password_Input(array(
                    'html_label_text' => sprintf(__("Sign", 'event_espresso'), $payment_method_type->get_help_tab_link()),
                    'html_help_text' => __("Paysera sign password", 'event_espresso'),
                    'required' => true
                        )),
            /* ,
              'image_url'=>new EE_Admin_File_Uploader_Input(array(
              'html_help_text'=>  __("Used for your business/personal logo on the Paysera page", 'event_espresso')
              )),*/
            ),
            //'before_form_content_template' => $payment_method_type->file_folder() . DS . 'templates' . DS . 'paysera_settings_before_form.template.php',
        );

        parent::__construct($options_array);
    }

}

// End of file EE_Paysera_Payment_Method_Form.php