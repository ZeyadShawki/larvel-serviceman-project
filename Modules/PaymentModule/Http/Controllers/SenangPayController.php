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
use Modules\UserManagement\Entities\User;

class SenangPayController extends Controller
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


        if (!isset($request['post_id'])) {
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

        return view('paymentmodule::senang-pay', compact('token', 'order_amount', 'customer'));
    }

    /**
     * @param Request $request
     * @return JsonResponse|Redirector|RedirectResponse|Application
     */
    public function return_senang_pay(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        if ($request['status_id'] == 1) {
            //success
            $tran_id = Str::random(6) . '-' . rand(1, 1000);;
            $request['payment_method'] = 'senang_pay';

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

            //fail (in booking)
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        //fail (in payment)
        return response()->json(response_formatter(DEFAULT_204), 200);
    }
}
