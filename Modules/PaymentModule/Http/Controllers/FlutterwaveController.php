<?php

namespace Modules\PaymentModule\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Modules\BidModule\Entities\PostBid;
use Modules\BidModule\Http\Controllers\Web\APi\V1\Customer\PostBidController;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\UserManagement\Entities\User;

class FlutterwaveController extends Controller
{
    use BookingTrait;

    public function __construct()
    {
        //configuration initialization
        $config = business_config('flutterwave', 'payment_config');

        $values = null;
        if (!is_null($config) && $config->mode == 'live') {
            $values = $config->live_values;
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = $config->test_values;
        }

        if ($values) {
            $config = array(
                'publicKey' => env('FLW_PUBLIC_KEY', $values['public_key']),
                'secretKey' => env('FLW_SECRET_KEY', $values['secret_key']),
                'secretHash' => env('FLW_SECRET_HASH', $values['hash']),
            );
            Config::set('flutterwave', $config);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|Redirector|RedirectResponse|Application
     */
    public function initialize(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {

        $token = 'access_token=' . $request['user']->id;
        $token .= $request->has('callback') ? '&&callback=' . $request['callback'] : '';
        $token .= '&&zone_id=' . $request['zone_id'] . '&&service_schedule=' . $request['service_schedule'] . '&&service_address_id=' . $request['service_address_id'];
        //For bidding
        $token .= $request->has('post_id') ? '&&post_id=' . $request['post_id'] : '';
        $token .= $request->has('provider_id') ? '&&provider_id=' . $request['provider_id'] : '';

        $token = base64_encode($token);
        $user_data = User::find($request['user']->id);

        if (is_null($request['post_id'])) {
            $booking_amount = cart_total($request->user->id);
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

            $booking_amount = $post_bid->offered_price;
        }

        //This generates a payment reference
        $reference = Flutterwave::generateReference();

        // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $booking_amount,
            'email' => $user_data['email'],
            'tx_ref' => $reference,
            'currency' => currency_code(),
            'redirect_url' => route('flutterwave.callback', ['token' => $token]),
            'customer' => [
                'email' => $user_data['email'],
                "phone_number" => $user_data['phone'],
                "name" => $user_data['first_name'] . ' ' . $user_data['last_name'],
            ],

            "customizations" => [
                "title" => (business_config('business_name', 'business_information'))->live_values ?? null,
                "description" => '',
            ]
        ];

        $payment = Flutterwave::initializePayment($data);

        if ($payment['status'] !== 'success') {
            if ($request->has('callback')) {
                return redirect($request['callback'] . '?payment_status=fail');
            } else {
                return response()->json(response_formatter(DEFAULT_204), 200);
            }
        }

        return redirect($payment['data']['link']);
    }

    public function callback(Request $request)
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

        $transaction_reference = $request['transaction_reference'];
        $status = $request['status'];

        //If payment is successful
        if ($status == 'successful') {
            $tran_id = $transaction_reference;
            $request['payment_method'] = 'flutterwave';
            $request['service_address_id'] = $service_address_id;
            $request['zone_id'] = $zone_id;
            $request['service_schedule'] = $service_schedule;

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

            //If place booking successful
            if ($response['flag'] == 'success') {
                if (isset($callback)) {
                    return redirect($callback . '?payment_status=success');
                } else {
                    return response()->json(response_formatter(DEFAULT_200), 200);
                }

            } else {
                if (isset($callback)) {
                    return redirect($callback . '?payment_status=failed');
                } else {
                    return response()->json(response_formatter(DEFAULT_204), 200);
                }
            }


        } elseif ($status == 'cancelled') {
            if (isset($callback)) {
                return redirect($callback . '?payment_status=cancelled');
            }
            return response()->json(response_formatter(DEFAULT_204), 200);

        } else {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

    }
}
