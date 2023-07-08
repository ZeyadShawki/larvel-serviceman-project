<?php

namespace Modules\BidModule\Http\Controllers\Web\APi\V1\Customer;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BidModule\Entities\Post;
use Modules\BidModule\Entities\PostAdditionalInstruction;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;
use Ramsey\Uuid\Uuid;
use function response;
use function response_formatter;

class PostController extends Controller
{
    public function __construct(
        private Post $post,
        private PostAdditionalInstruction $post_additional_instruction,
    )
    {
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'is_booked' => 'in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bidding_post_validity = (int) (business_config('bidding_post_validity', 'bidding_system'))->live_values;
        $posts = $this->post
            ->with(['addition_instructions', 'service', 'category', 'sub_category', 'booking'])
            ->withCount(['bids' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->where('customer_user_id', $request->user()->id)
            ->whereBetween('created_at', [Carbon::now()->subDays($bidding_post_validity), Carbon::now()])
            ->when(!is_null($request['is_booked']), function ($query) use ($request) {
                $query->where('is_booked',$request['is_booked']);
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        if ($posts->count() < 1) {
            return response()->json(response_formatter(DEFAULT_404, null), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $posts), 200);
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function show($post_id, Request $request): JsonResponse
    {
        $post = $this->post
            ->with(['addition_instructions', 'service', 'category', 'sub_category', 'booking', 'service_address'])
            ->withCount(['bids'])
            ->where('id', $post_id)
            ->where('customer_user_id', $request->user()->id)
            ->first();

        if (!isset($post)) {
            return response()->json(response_formatter(DEFAULT_404, null), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $post), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_description' => 'required',
            'booking_schedule' => 'required|date',
            'service_id' => 'required|uuid',
            'category_id' => 'required|uuid',
            'sub_category_id' => 'required|uuid',
            'service_address_id' => 'required',
            'additional_instructions' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $post = $this->post;
        $post->service_description = $request['service_description'];
        $post->booking_schedule = $request['booking_schedule'];
        $post->customer_user_id = $request->user()->id;
        $post->service_id = $request['service_id'];
        $post->category_id = $request['category_id'];
        $post->sub_category_id = $request['sub_category_id'];
        $post->service_address_id = $request['service_address_id'];
        $post->zone_id = $request['zone_id'] ?? config('zone_id');
        $post->save();

        //notification to customer
        device_notification_for_bidding($request->user()->fcm_token, translate('Successfully requested for new service'), null, null, 'bidding', null, $post->id, null);

        if (count($request['additional_instructions']) > 0) {
            $data = [];
            foreach ($request['additional_instructions'] as $key=>$item) {
                $data[$key]['id'] = Uuid::uuid4();
                $data[$key]['details'] = $item;
                $data[$key]['post_id'] = $post->id;
                $data[$key]['created_at'] = now();
                $data[$key]['updated_at'] = now();
            }
            $this->post_additional_instruction->insert($data);
        }

        //notification to provider
        $provider_ids = SubscribedService::where('sub_category_id', $request['sub_category_id'])->ofSubscription(1)->pluck('provider_id')->toArray();
        $providers = Provider::with('owner')->whereIn('id', $provider_ids)->where('zone_id', $post->zone_id)->get();
        foreach ($providers as $provider) {
            $fcm_token = $provider->owner->fcm_token ?? null;
            if(!is_null($fcm_token)) device_notification_for_bidding($fcm_token, translate('New service request has been arrived'), null, null, 'bidding', null, $post->id, null);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, null), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update_info(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'booking_schedule' => 'date',
            'service_address_id' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $post = $this->post->find($request['post_id']);
        if (!isset($post)) return response()->json(response_formatter(DEFAULT_404, null), 404);

        $post->service_address_id = !is_null($request['service_address_id']) ? $request['service_address_id'] : $post->service_address_id;
        $post->booking_schedule = $request['booking_schedule'] ? $request['booking_schedule'] : $post->booking_schedule;
        $post->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
    }

}
