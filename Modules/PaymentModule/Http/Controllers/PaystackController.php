<?php

namespace Modules\PaymentModule\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Modules\BidModule\Entities\PostBid;
use Modules\BidModule\Http\Controllers\Web\APi\V1\Customer\PostBidController;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\UserManagement\Entities\User;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackController extends Controller
{
    use BookingTrait;

    public function __construct()
    {
        //configuration initialization
        $config = business_config('paystack', 'payment_config');

        if (!is_null($config) && $config->mode == 'live') {
            $values = $config->live_values;
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = $config->test_values;
        }

        if ($values) {
            $config = array(
                'publicKey' => env('PAYSTACK_PUBLIC_KEY', $values['public_key']),
                'secretKey' => env('PAYSTACK_SECRET_KEY', $values['secret_key']),
                'paymentUrl' => env('PAYSTACK_PAYMENT_URL', $values['callback_url']),
                'merchantEmail' => env('MERCHANT_EMAIL', $values['merchant_email']),
            );
            Config::set('paystack', $config);
        }
    }


    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|uuid',
            'service_schedule' => 'required|date',
            'service_address_id' => 'required',
            //For bidding
            'post_id' => 'uuid',
            'provider_id' => 'uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $token = 'access_token=' . $request['user']->id;
        $token .= $request->has('callback') ? '&&callback=' . $request['callback'] : '';
        $token .= '&&zone_id=' . $request['zone_id'] . '&&service_schedule=' . $request['service_schedule'] . '&&service_address_id=' . $request['service_address_id'];
        //For bidding
        $token .= $request->has('post_id') ? '&&post_id=' . $request['post_id'] : '';
        $token .= $request->has('provider_id') ? '&&provider_id=' . $request['provider_id'] : '';

        $token = base64_encode($token);

        if (is_null($request['post_id'])) {
            $order_amount = cart_total($request['user']->id);
        } else {
            //for bidding
            $post_bid = PostBid::whereHas('post', function ($query) {
                $query->where('is_booked', '!=', 1);
            })
                ->where('post_id', $request['post_id'])
                ->where('provider_id', $request['provider_id'])
                ->where('status', 'pending')
                ->first();
            if (!isset($post_bid)) return response()->json(response_formatter(DEFAULT_204), 200);

            $order_amount = $post_bid->offered_price;
        }

        $customer = User::find($request['user']->id);

        return view('paymentmodule::paystack', compact('token', 'order_amount', 'customer'));
    }

    public function redirectToGateway(Request $request)
    {
        $params = explode('&&', base64_decode($request['token']));
        $callback = null;
        foreach ($params as $param) {
            $data = explode('=', $param);
            if ($data[0] == 'access_token') {
                $access_token = $data[1];
            } elseif ($data[0] == 'callback') {
                $callback = $data[1];
            } elseif ($data[0] == 'zone_id') {
                $zone_id = $data[1];
            } elseif ($data[0] == 'service_schedule') {
                $service_schedule = $data[1];
            } elseif ($data[0] == 'service_address_id') {
                $service_address_id = $data[1];
            }
            //for bidding
            elseif ($data[0] == 'post_id') {
                $post_id = $data[1];
            } elseif ($data[0] == 'provider_id') {
                $provider_id = $data[1];
            }
        }

        \session()->put('callback', $callback);
        \session()->put('access_token', $access_token);
        \session()->put('post_id', $post_id);
        \session()->put('provider_id', $provider_id);

        try {
            return Paystack::getAuthorizationUrl()->redirectNow();

        } catch (\Exception $e) {
            //If payment fail
            if (isset($callback)) {
                return redirect($callback.'?payment_status=failed');
            } else {
                return response()->json(response_formatter(DEFAULT_204), 200);
            }
        }
    }

    public function handleGatewayCallback(Request $request)
    {
        $callback = session('callback');
        $access_token = session('access_token');
        $post_id = session('post_id');
        $provider_id = session('provider_id');

        $paymentDetails = Paystack::getPaymentData();

        //token string generate
        $transaction_reference = $paymentDetails['data']['reference'];

        if ($paymentDetails['status'] == true) {
            //If payment success
            $tran_id = $transaction_reference;
            $request['payment_method'] = 'paystack';

            if (!isset($post_id)) {
                $response = $this->place_booking_request($access_token, $request, $tran_id);
            } else {
                //for bidding
                $post_bid = PostBid::with(['post'])
                    ->where('post_id', $post_id)
                    ->where('provider_id', $provider_id)
                    ->first();

                $data = [
                    'payment_method' => $request['payment_method'],
                    'zone_id' => $request['zone_id'],
                    'service_tax' => $post_bid?->post?->service?->tax,
                    'provider_id' => $post_bid->provider_id,
                    'price' => $post_bid->offered_price,
                    'service_schedule' => !is_null($request['booking_schedule']) ? $request['booking_schedule'] : $post_bid->post->booking_schedule,
                    'service_id' => $post_bid->post->service_id,
                    'category_id' => $post_bid->post->category_id,
                    'sub_category_id' => $post_bid->post->category_id,
                    'service_address_id' => !is_null($request['service_address_id']) ? $request['service_address_id'] : $post_bid->post->service_address_id,
                ];

                $response = $this->place_booking_request_for_bidding($access_token, $request, $tran_id, $data);
                if ($response['flag'] == 'success') {
                    PostBidController::accept_post_bid_offer($post_bid->id, $response['booking_id']);
                }
            }

            //If booking place is successful
            if ($response['flag'] == 'success') {
                if (isset($callback)) {
                    return redirect($callback.'?payment_status=success');
                } else {
                    return response()->json(response_formatter(DEFAULT_200), 200);
                }
            }

        } else {
            //If payment fail
            if (isset($callback)) {
                return redirect($callback.'?payment_status=failed');
            } else {
                return response()->json(response_formatter(DEFAULT_204), 200);
            }
        }
    }

    public static function generate_transaction_Referance()
    {

        return Paystack::genTranxRef();
    }
}
