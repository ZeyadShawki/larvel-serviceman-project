<?php

namespace Modules\BidModule\Http\Controllers\Web\APi\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BidModule\Entities\PostBid;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\UserAddress;
use function config;
use function response;
use function response_formatter;

class PostBidController extends Controller
{
    use BookingTrait;

    public function __construct(
        private PostBid $post_bid,
    )
    {
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'post_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $post_bids = $this->post_bid
            ->with(['provider.owner.reviews'])
            ->where('post_id', $request['post_id'])
            ->where('status', 'pending')
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        if ($post_bids->count() < 1) {
            return response()->json(response_formatter(DEFAULT_404, null), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $post_bids), 200);
    }

    /**
     * @param Request $request
     * @param $post_bid_id
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|uuid',
            'provider_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bid_offers_visibility_for_providers = (business_config('bid_offers_visibility_for_providers', 'bidding_system'))->live_values ?? 0;

        if (!$bid_offers_visibility_for_providers) {
            return response()->json(response_formatter(DEFAULT_403, null), 403);
        }

        $post_bid = $this->post_bid
            ->with(['provider.owner.reviews'])
            ->where('post_id', $request['post_id'])
            ->where('provider_id', $request['provider_id'])
            ->first();

        if (!isset($post_bid)) {
            return response()->json(response_formatter(DEFAULT_404, null), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $post_bid), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|uuid',
            'provider_id' => 'required|uuid',
            'status' => 'required|in:accept,deny',
            'booking_schedule' => 'date',
            'service_address_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $post_bid = $this->post_bid
            ->whereHas('post', function ($query) {
                $query->where('is_booked', '!=', 1);
            })
            ->where('post_id', $request['post_id'])
            ->where('provider_id', $request['provider_id'])
            ->where('status', 'pending')
            ->first();

        if (is_null($post_bid))
            return response()->json(response_formatter(DEFAULT_404, null), 200);

        /** if DENY */
        if ($request['status'] == 'deny') {
            $post_bid->status = 'denied';
            $post_bid->save();

            //notification to provider
            $provider = Provider::with('owner')->find($request['provider_id'])->first();
            if ($provider) {
                device_notification_for_bidding($provider->owner->fcm_token, translate('Your offer has been denied'), null, null, 'bidding', null, null, $request['provider_id']);
            }

            return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
        }

        /** if SUCCESS */

        //booking placement
        $data = [
            'payment_method' => 'cash_after_service',
            'zone_id' => config('zone_id'),
            'service_tax' => $post_bid?->post?->service?->tax,
            'provider_id' => $post_bid->provider_id,
            'price' => $post_bid->offered_price,
            'service_schedule' => !is_null($request['booking_schedule']) ? $request['booking_schedule'] : $post_bid->post->booking_schedule,
            'service_id' => $post_bid->post->service_id,
            'category_id' => $post_bid->post->category_id,
            'sub_category_id' => $post_bid->post->category_id,
            'service_address_id' => !is_null($request['service_address_id']) ? $request['service_address_id'] : $post_bid->post->service_address_id,
        ];

        $response = $this->place_booking_request_for_bidding($request->user()->id, $request, 'cash-payment', $data);    //cash after service

        //posts & post_bids table update
        if ($response['flag'] == 'success') {
            self::accept_post_bid_offer($post_bid->id, $response['booking_id']);

            //notification to provider
            $provider = Provider::with('owner')->find($request['provider_id'])->first();
            if ($provider) {
                device_notification_for_bidding($provider->owner->fcm_token, translate('Your offer has been accepted'), null, null, 'bidding', $response['booking_id'], $request['post_id'], $request['provider_id']);
            }

        } else {
            return response()->json(response_formatter(DEFAULT_FAIL_200, null), 200);
        }

        return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
    }

    public static function accept_post_bid_offer($post_bid_id, $booking_id)
    {
        DB::transaction(function () use ($post_bid_id, $booking_id) {
            $post_bid = PostBid::find($post_bid_id);
            $post_bid->post->is_booked = 1;
            $post_bid->post->booking_id = $booking_id;
            $post_bid->post->save();

            $post_bid->status = 'accepted';
            $post_bid->save();
        });
    }
}
