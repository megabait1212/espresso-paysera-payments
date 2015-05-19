<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}

/**
 * EEG_Paysera_Payment_Method
 *
 * Note: one important feature of the Paysera Standard Gateway is that it can allow
 * Paypal itself to calculate taxes and shipping on an order, and then when the IPN
 * for the payment is received from Paypal, this class will update the line items
 * accordingly (also bearing in mind that this could be a payment re-attempt, in
 * which case Paypal shouldn't add shipping or taxes twice).
 *
 * @package 			Event Espresso
 * @subpackage          core
 * @author 				Vasily Ogar
 * @since 				$VID:$
 *
 */
require_once(dirname(__FILE__) . '/lib/vendor/webtopay/libwebtopay/WebToPay.php');

class EEG_Paysera_Payment_Method extends EE_Offsite_Gateway {

// Define user set variables
    protected $_title = NULL;
    protected $_description = NULL;
    protected $_projectid = NULL;
    protected $_password = NULL;
    protected $_debug = NULL;
    protected $_currencies_supported = array(
        'USD',
        'EUR'
    );

    /**
     * @return EEG_Paypal_Standard
     */
    public function __construct() {
        $this->set_uses_separate_IPN_request(true);
        parent::__construct();
    }

    /**
     * @param EEI_Payment $payment      to process
     * @param array       $billing_info but should be empty for this gateway
     * @param string      $return_url   URL to send the user to after payment on the payment provider's website
     * @param string      $notify_url   URL to send the instant payment notification
     * @param string      $cancel_url   URL to send the user to after a cancelled payment attempt on teh payment provider's website
     * @return EEI_Payment
     */
    public function set_redirection_info($payment, $billing_info = array(), $return_url = NULL, $notify_url = NULL, $cancel_url = NULL) {
        $redirect_args = array();
        $transaction = $payment->transaction();
        $primary_registrant = $transaction->primary_registration();
        $primary_attendee = $primary_registrant->attendee();
        $language = explode('-', get_bloginfo('language', 'raw'));
        $lng = array('lt' => 'LIT', 'lv' => 'LAV', 'ee' => 'EST', 'ru' => 'RUS', 'de' => 'GER', 'pl' => 'POL', 'en' => 'ENG');
        $lang = get_locale();
        $lang = explode('_', $lang);

        $redirect_args = array(
            'projectid' => $this->_projectid,
            'sign_password' => $this->_password,
            'orderid' => $transaction->ID(),
            'amount' => intval(number_format($payment->amount(), 2, '', '')),
            'currency' => $payment->currency_code(),
            'accepturl' => $return_url,
            'cancelurl' => $cancel_url,
            'callbackurl' => $notify_url,
            'p_firstname' => $primary_attendee->fname(),
            'p_lastname' => $primary_attendee->lname(),
            'p_email' => $primary_attendee->email(),
            'lang' => $lng[$lang[0]] ? $lng[$lang[0]] : 'ENG',
            'test' => $this->_debug_mode ? $this->_debug_mode : 0,
            'locale' => get_locale(),
            'paytext' => sprintf(__('Payment of %1$s%2$s primary reg. code  %3$s', "event_espresso"), $payment->amount(), $payment->currency_code(), $primary_registrant->reg_code())
        );
//setup address?
        if ($primary_attendee->address() &&
                $primary_attendee->city() &&
                $primary_attendee->state_name() &&
                $primary_attendee->country_name() &&
                $primary_attendee->zip()) {
            $redirect_args['country'] = $primary_attendee->country_ID();
            $redirect_args['p_street'] = $primary_attendee->address();
            $redirect_args['p_city'] = $primary_attendee->city();
            $redirect_args['p_state'] = $primary_attendee->state_name();
            $redirect_args['p_zip'] = $primary_attendee->zip();
            $redirect_args['p_countrycode'] = $primary_attendee->country_ID();
        }

        $request = WebToPay::buildRequest($redirect_args);
//print_r($redirect_args);

        $url = http_build_query($request);
        $url = preg_replace('/[\r\n]+/is', '', $url);
// var_dump();
        $payment->set_redirect_url(WebToPay::PAY_URL . '?' . $url);
        $payment->set_txn_id_chq_nmbr($transaction->ID());
        $payment->set_redirect_args(array('0' => ''));
        return $payment;
    }

    /**
     * Often used for IPNs. But applies the info in $update_info to the payment.
     * What is $update_info? Often the contents of $_REQUEST, but not necessarily. Whatever
     * the payment method passes in.
     * @param array $update_info like $_POST
     * @param EEI_Transaction $transaction
     * @return EEI_Payment updated
     */
    public function handle_payment_update($update_info, $transaction) {
        $response = WebToPay::checkResponse($update_info, array(
                    'projectid' => $this->_projectid,
                    'sign_password' => $this->_password,
        ));

        $payment = $this->_pay_model->get_payment_by_txn_id_chq_nmbr($response['orderid']);
        if (!$payment) {
            $payment = $transaction->last_payment();
        }

        if ($response['status'] == 0) {
            $status = $this->_pay_model->declined_status();
            $gateway_response = __('Payment has no been executed', 'event_espresso');
        } elseif ($response['status'] == 1) {
            $status = $this->_pay_model->approved_status();
            $gateway_response = __('Your payment is approved.', 'event_espresso');
        } else {
            $gateway_response = __('Your payment has been declined.', 'event_espresso');
            $status = $this->_pay_model->declined_status();
            throw new EE_Error(sprintf(__('%s IPNs are not yet supported by Event Espresso Paysera integration', 'event_espresso'), $response['status']));
        }

        if (!empty($payment)) {
            //payment exists. if this has the exact same status and amount, don't bother updating. just return
            if ($payment->status() == $status && $payment->amount() == $response['payamount'] / 100) {
                //echo "duplicated ipn! dont bother updating transaction foo!";
                $this->log(array(
                    'url' => isset($_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"]) ? (is_ssl() ? 'https://' : 'http://' ) . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : 'unknown',
                    'message' => sprintf(__('It appears we have received a duplicate IPN from Paysera for payment %d', 'event_espresso'), $payment->ID()),
                    'payment' => $payment->model_field_array(),
                    'IPN data' => $response), $payment);
            } else {

                $payment->set_status($status);
                $payment->set_amount($response['payamount'] / 100);
                $payment->set_gateway_response($gateway_response);
                $payment->set_details($response);
                $payment->set_txn_id_chq_nmbr($response['orderid'] . '/' . $response['requestid']);
                $this->log(array(
                    'url' => isset($_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"]) ? ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : 'unknown',
                    'message' => sprintf(__('Updated payment either from IPN or as part of POST from PayPal', 'event_espresso')),
                    'payment' => $payment->model_field_array(),
                    'IPN_data' => $response), $payment);
            }
        }
//                if ($response['test'] !== '0') {
//                    throw new Exception('Testing, real payment was not made');
//                }


        if (isset($_REQUEST['ee_payment_method']) && $_REQUEST['ee_payment_method'] == 'paysera_payment_method') {
            //don't exit yet because EE_Payment_Processor still needs to process the payment data
            //but setup a filter so we will exit and send back the response Paysera needs
            add_filter('FHEE__EES_Espresso_Txn_Page__run__exit', array($this, 'send_ok'));
        }
        return $payment;
    }

    function send_ok() {
        echo "OK";
        return true;
    }

}

// End of file EEG_Paysera_Payment_Method.gateway.php