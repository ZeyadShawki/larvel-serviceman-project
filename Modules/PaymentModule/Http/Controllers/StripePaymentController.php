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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\BidModule\Entities\PostBid;
use Modules\BidModule\Http\Controllers\Web\APi\V1\Customer\PostBidController;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripePaymentController extends Controller
{
    use BookingTrait;

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
        $auto_click = true;

        return view('paymentmodule::stripe', compact('token', 'auto_click'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ApiErrorException
     */
    public function payment_process_3d(Request $request): JsonResponse
    {
        $params = explode('&&', base64_decode($request['token']));

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

        if (!isset($post_id)) {
            $booking_amount = cart_total($access_token);
        } else {
            //for bidding
            $post_bid = PostBid::whereHas('post', function ($query) {
                $query->where('is_booked', '!=', 1);
            })
                ->where('post_id', $post_id)
                ->where('provider_id', $provider_id)
                ->where('status', 'pending')
                ->first();
            if (!isset($post_bid)) return response()->json(response_formatter(DEFAULT_204), 200);

            $booking_amount = $post_bid->offered_price;
        }

        $config = business_config('stripe', 'payment_config');
        Stripe::setApiKey($config->live_values['api_key']);
        header('Content-Type: application/json');
        $currency_code = currency_code();

        $business_name = business_config('business_name', 'business_information');
        $business_logo = business_config('business_logo', 'business_information');

        $query_parameter = 'access_token=' . $access_token;
        $query_parameter .= isset($callback) ? '&&callback=' . $callback : '';
        $query_parameter .= '&&zone_id=' . $zone_id . '&&service_schedule=' . $service_schedule . '&&service_address_id=' . $service_address_id;
        //for bidding
        $query_parameter .= isset($post_id) ? '&&post_id=' . $post_id : '';
        $query_parameter .= isset($provider_id) ? '&&provider_id=' . $provider_id : '';

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency_code ?? 'usd',
                    'unit_amount' => round($booking_amount, 2) * 100,
                    'product_data' => [
                        'name' => $business_name->live_values,
                        'images' => [asset('storage/app/public/business') . '/' . $business_logo->live_values],
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/') . '/payment/stripe/success?' . $query_parameter,
            'cancel_url' => url('/') . '/payment/stripe/cancel?' . $query_parameter,
        ]);
        return response()->json(['id' => $checkout_session->id]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|Redirector|RedirectResponse|Application
     */
    public function success(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $tran_id = Str::random(6) . '-' . rand(1, 1000);;
        $request['payment_method'] = 'stripe';

        if (is_null($request['post_id'])) {
            $response = $this->place_booking_request($request['access_token'], $request, $tran_id);
        } else {
            //for bidding
            $post_bid = PostBid::with(['post'])
                ->where('post_id', $request['post_id'])
                ->where('provider_id', $request['provider_id'])
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

            $response = $this->place_booking_request_for_bidding($request['access_token'], $request, $tran_id, $data);
            if ($response['flag'] == 'success') {
                PostBidController::accept_post_bid_offer($post_bid->id, $response['booking_id']);
            }
        }

        if ($response['flag'] == 'success') {
            if ($request->has('callback')) {
                return redirect($request['callback'] . '?payment_status=success');
            } else {
                return response()->json(response_formatter(DEFAULT_200), 200);
            }
        }

        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public function cancel(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        if ($request->has('callback')) {
            return redirect($request['callback'] . '?payment_status=fail');
        } else {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }
    }
}
