<?php

namespace Modules\PaymentModule\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\BidModule\Entities\PostBid;
use Modules\BidModule\Http\Controllers\Web\APi\V1\Customer\PostBidController;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\UserManagement\Entities\User;
use Razorpay\Api\Api;

class RazorPayController extends Controller
{
    use BookingTrait;

    public function __construct()
    {
        $config = business_config('razor_pay', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $razor = $config->live_values;
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = $config->test_values;
        }

        if ($razor) {
            $config = array(
                'api_key' => $razor['api_key'],
                'api_secret' => $razor['api_secret']
            );
            Config::set('razor_config', $config);
        }
    }

    /**
     * @param Request $request
     * @return View|Factory|JsonResponse|Application
     */
    public function index(Request $request): View|Factory|JsonResponse|Application
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

        return view('paymentmodule::razor-pay', compact('token', 'order_amount', 'customer'));
    }

    /**
     * @param Request $request
     * @return JsonResponse|Redirector|RedirectResponse|Application
     */
    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
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
            } //for bidding
            elseif ($data[0] == 'post_id') {
                $post_id = $data[1];
            } elseif ($data[0] == 'provider_id') {
                $provider_id = $data[1];
            }
        }

        $tran_id = Str::random(6) . '-' . rand(1, 1000);;
        $request['payment_method'] = 'razor_pay';
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

        if ($response['flag'] == 'failed') {
            if ($callback) {
                return redirect($callback . '?payment_status=failed');
            } else {
                return response()->json(response_formatter(DEFAULT_204), 200);
            }
        }

        $input = $request->all();
        $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);

        if (count($input) && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount' => $payment['amount']));

            } catch (\Exception $e) {
                //error
            }
        }

        if ($callback) {
            return redirect($callback . '?payment_status=success');
        } else {
            return response()->json(response_formatter(DEFAULT_200), 200);
        }
    }
}
