<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;

class AddressController extends Controller
{
    private $address;

    public function __construct(UserAddress $address)
    {
        $this->address = $address;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (in_array($request->user()->user_type, CUSTOMER_USER_TYPES)) {
            $addresses = $this->address->where(['user_id' => auth('api')->user()->id])->get();
            return response()->json(response_formatter(DEFAULT_200, $addresses), 200);
        }
        return response()->json(response_formatter(DEFAULT_403), 401);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lon' => 'required',
            'city' => '',
            'street' => '',
            'zip_code' => '',
            'country' => '',
            'address' => 'required',
            'address_type' => 'required|in:service,billing',
            'contact_person_name' => 'required',
            'contact_person_number' => 'required',
            'address_label' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $point = new Point($request->lat, $request->lon);
        $zone = Zone::contains('coordinates', $point)->ofStatus(1)->latest()->first();
        if ($zone) {
            $zone_id = $zone->id;
        } else {
            $zone_id = null;
        }

        $address = $this->address;
        $address->user_id = $request->user()->id;
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = $request->city;
        $address->street = $request->street ?? '';
        $address->zip_code = $request->zip_code;
        $address->country = $request->country;
        $address->address = $request->address;
        $address->zone_id = $zone_id;
        $address->address_type = $request->address_type;
        $address->contact_person_name = $request->contact_person_name;
        $address->contact_person_number = $request->contact_person_number;
        $address->address_label = $request->address_label;
        $address->save();

        return response()->json(response_formatter(DEFAULT_STORE_200, $address), 200);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('categorymanagement::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return JsonResponse
     */
    public function edit(string $id): JsonResponse
    {
        $address = $this->address->where(['user_id' => auth('api')->user()->id])->where('id', $id)->first();
        if (isset($address)) {
            return response()->json(response_formatter(DEFAULT_200, $address), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lon' => 'required',
            'city' => 'required',
            'street' => '',
            'zip_code' => 'required',
            'country' => 'required',
            'address' => 'required',
            'address_type' => 'required|in:service,billing',
            'contact_person_name' => 'required',
            'contact_person_number' => 'required',
            'address_label' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $point = new Point($request->lat, $request->lon);
        $zone = Zone::contains('coordinates', $point)->ofStatus(1)->latest()->first();
        if ($zone) {
            $zone_id = $zone->id;
        } else {
            $zone_id = null;
        }

        $address = $this->address->where(['user_id' => auth('api')->user()->id])->where('id', $id)->first();
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = $request->city;
        $address->street = $request->has('street') ? $request->street : $address->street;
        $address->zip_code = $request->zip_code;
        $address->country = $request->country;
        $address->address = $request->address;
        $address->zone_id = $zone_id;
        $address->address_type = $request->address_type;
        $address->contact_person_name = $request->contact_person_name;
        $address->contact_person_number = $request->contact_person_number;
        $address->address_label = $request->address_label;
        $address->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_ids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->address
            ->whereIn('id', $request['address_ids'])
            ->update(['user_id' => null]);

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

}
