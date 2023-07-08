<?php

namespace Modules\PaymentModule\Http\Controllers;

use App\CentralLogics\Helpers;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PaypalPaymentController extends Controller
{

    public function __construct()
    {
        $config = business_config('paypal', 'payment_config');
        if (isset($config)){
            $values = null;
            $paypal_mode = null;
            if (!is_null($config) && $config->mode == 'live') {
                $values = $config->live_values;
                $paypal_mode = "live";
            } elseif (!is_null($config) && $config->mode == 'test') {
                $values = $config->test_values;
                $paypal_mode = "sandbox";
            }

            $this->_api_context = new ApiContext(new OAuthTokenCredential($values['client_id'], $values['client_secret']));
            $this->_api_context->setConfig([
                'mode' => env('PAYPAL_MODE', $paypal_mode),
                'http.ConnectionTimeOut' => 30,
                'log.LogEnabled' => true,
                'log.FileName' => storage_path() . '/logs/paypal.log',
                'log.LogLevel' => 'ERROR'
            ]);
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|uuid',
            'service_schedule' => 'required|date',
            'service_address_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $user = $request['user'];
        $order_amount = round(cart_total($user['id']), 2);

        $callback = $request['callback'];
        $tr_ref = Str::random(6) . '-' . rand(1, 1000);

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $items_array = [];
        $item = new Item();
        $item->setName($user['first_name'])
            ->setCurrency(currency_code())
            ->setQuantity(1)
            ->setPrice($order_amount);
        $items_array[] = $item;

        $item_list = new ItemList();
        $item_list->setItems($items_array);

        $amount = new Amount();
        $amount->setCurrency(currency_code())
            ->setTotal($order_amount);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription($tr_ref);

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(route('paypal.callback', ['callback' => $callback, 'transaction_reference' => $tr_ref]))
            ->setCancelUrl(route('paypal.failed', ['callback' => $callback, 'transaction_reference' => $tr_ref]));

        $payment = new Payment();
        $payment->setIntent('service')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));

        $payment->create($this->_api_context);

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                return Redirect::away($link->getHref());
            }
        }

        return 0;
    }

    public function getPaymentStatus(Request $request)
    {
        $callback = $request['callback'];
        $transaction_reference = $request['transaction_reference'];

        $payment_id = Session::get('paypal_payment_id');
        if (empty($request['PayerID']) || empty($request['token'])) {
            Session::put('error', 'Payment failed');
            return Redirect::back();
        }

        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($request['PayerID']);

        /**Execute the payment **/
        $result = $payment->execute($execution, $this->_api_context);

        //token string generate
        $transaction_reference = $payment_id;
        $token_string = 'payment_method=paypal&&transaction_reference=' . $transaction_reference;

        if ($result->getState() == 'approved') {
            //success
            if ($callback != null) {
                return redirect($callback . '/success' . '?token=' . base64_encode($token_string));
            } else {
                return \redirect()->route('payment-success', ['token' => base64_encode($token_string)]);
            }
        }

        //fail
        if ($callback != null) {
            return redirect($callback . '/fail' . '?token=' . base64_encode($token_string));
        } else {
            return \redirect()->route('payment-fail', ['token' => base64_encode($token_string)]);
        }
    }
}
