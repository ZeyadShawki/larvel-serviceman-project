<?php

namespace Modules\CartModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\CartModule\Traits\AddedToCartTrait;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;

class CartController extends Controller
{
    private Cart $cart;
    private Service $service;
    private Variation $variation;
    private User $user;
    private Provider $provider;
    use AddedToCartTrait;

    public function __construct(Cart $cart, Service $service, Variation $variation, User $user, Provider $provider)
    {
        $this->cart = $cart;
        $this->service = $service;
        $this->variation = $variation;
        $this->user = $user;
        $this->provider = $provider;
    }

    public function add_to_cart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'uuid',
            'service_id' => 'required|uuid',
            'category_id' => 'required|uuid',
            'sub_category_id' => 'required|uuid',
            'variant_key' => 'required',
            'quantity' => 'required|numeric|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        //cart log (for service)
        $this->added_to_cart_update($request->user()->id, $request['service_id']);

        $variation = $this->variation->where(['zone_id' => Config::get('zone_id'), 'service_id' => $request['service_id']])
            ->where(['variant_key' => $request['variant_key']])
            ->first();

        if (isset($variation)) {
            $service = $this->service->find($request['service_id']);

            $check_cart = $this->cart->where([
                'service_id' => $request['service_id'],
                'variant_key' => $request['variant_key'],
                'customer_id' => $request->user()->id])->first();
            $cart = $check_cart ?? $this->cart;
            $quantity = $request['quantity'];

            //calculation
            $basic_discount = basic_discount_calculation($service, $variation->price * $quantity);
            $campaign_discount = campaign_discount_calculation($service, $variation->price * $quantity);
            $subtotal = round($variation->price * $quantity, 2);

            $applicable_discount = ($campaign_discount >= $basic_discount) ? $campaign_discount : $basic_discount;

            $tax = round( (($variation->price*$quantity-$applicable_discount)*$service['tax'])/100 , 2);

            //between normal discount & campaign discount, greater one will be calculated
            $basic_discount = $basic_discount > $campaign_discount ? $basic_discount : 0;
            $campaign_discount = $campaign_discount >= $basic_discount ? $campaign_discount : 0;

            //DB part
            $cart->provider_id = $request['provider_id'];
            $cart->customer_id = $request->user()->id;
            $cart->service_id = $request['service_id'];
            $cart->category_id = $request['category_id'];
            $cart->sub_category_id = $request['sub_category_id'];
            $cart->variant_key = $request['variant_key'];
            $cart->quantity = $quantity;
            $cart->service_cost = $variation->price;
            $cart->discount_amount = $basic_discount;
            $cart->campaign_discount = $campaign_discount;
            $cart->coupon_discount = 0;
            $cart->coupon_code = null;
            $cart->tax_amount = round($tax, 2);
            $cart->total_cost = round($subtotal - $basic_discount - $campaign_discount + $tax, 2);
            $cart->save();

            return response()->json(response_formatter(DEFAULT_STORE_200), 200);
        }

        return response()->json(response_formatter(DEFAULT_404), 200);
    }

    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $cart = $this->cart->with(['customer', 'provider.owner', 'category', 'sub_category', 'service'])->where(['customer_id' => $request->user()->id])
            ->latest()->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $wallet_balance = $this->user->find($request->user()->id)->wallet_balance??0;

        return response()->json(response_formatter(DEFAULT_200, ['cart'=>$cart, 'wallet_balance'=>with_decimal_point($wallet_balance)]), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update_qty(Request $request, string $id): JsonResponse
    {
        $cart = $this->cart->where(['id' => $id])->first();

        if (!isset($cart)) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $cart = $this->cart->find($id);
        $service = $this->service->find($cart['service_id']);

        $basic_discount = basic_discount_calculation($service, $cart->service_cost * $request['quantity']);
        $campaign_discount = campaign_discount_calculation($service, $cart->service_cost * $request['quantity']);
        $subtotal = round($cart->service_cost * $request['quantity'], 2);

        $applicable_discount = ($campaign_discount >= $basic_discount) ? $campaign_discount : $basic_discount;
        $tax = round(( (($cart->service_cost-$applicable_discount)*$service['tax'])/100 ) * $request['quantity'], 2);

        //between normal discount & campaign discount, greater one will be calculated
        $basic_discount = $basic_discount > $campaign_discount ? $basic_discount : 0;
        $campaign_discount = $campaign_discount >= $basic_discount ? $campaign_discount : 0;

        $cart->quantity = $request->quantity;
        $cart->discount_amount = $basic_discount;
        $cart->campaign_discount = $campaign_discount;
        $cart->coupon_discount = 0;
        $cart->coupon_code = null;
        $cart->tax_amount = round($tax, 2);
        $cart->total_cost = round($subtotal - $basic_discount - $campaign_discount + $tax, 2);
        $cart->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update_provider(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        //check if provider exists
        if (!$this->provider->where('id', $request['provider_id'])->exists()) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        //update provider
        $this->cart->where('customer_id', $request->user()->id)->update(['provider_id' => $request['provider_id']]);

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function remove(Request $request, string $id): JsonResponse
    {
        $cart = $this->cart->where(['id' => $id])->first();

        if (!isset($cart)) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $this->cart->where('id', $id)->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function empty_cart(Request $request): JsonResponse
    {
        $cart = $this->cart->where(['customer_id' => $request->user()->id]);

        if ($cart->count() == 0) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $cart->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }
}
