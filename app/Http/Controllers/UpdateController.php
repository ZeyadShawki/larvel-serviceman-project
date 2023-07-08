<?php

namespace App\Http\Controllers;

use App\Traits\ActivationClass;
use App\Traits\UnloadedHelpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\UserManagement\Entities\User;

class UpdateController extends Controller
{
    use UnloadedHelpers;
    use ActivationClass;

    public function update_software_index()
    {
        Artisan::call('module:enable');
        return view('update.update-software');
    }

    public function update_software(Request $request)
    {
        $this->setEnvironmentValue('SOFTWARE_ID', 'NDAyMjQ3NzI=');
        $this->setEnvironmentValue('BUYER_USERNAME', $request['username']);
        $this->setEnvironmentValue('PURCHASE_CODE', $request['purchase_key']);
        $this->setEnvironmentValue('SOFTWARE_VERSION', '2.0');
        $this->setEnvironmentValue('APP_ENV', 'live');
        $this->setEnvironmentValue('APP_URL', url('/'));

        $data = $this->actch();
        try{
            if (!$data->getData()->active) {
                $remove = array("http://","https://","www.");
                $url= str_replace($remove,"",url('/'));

                $activation_url = base64_decode('aHR0cHM6Ly9hY3RpdmF0aW9uLjZhbXRlY2guY29t');
                $activation_url .= '?username=' . $request['username'];
                $activation_url .= '&purchase_code=' . $request['purchase_key'];
                $activation_url .= '&domain=' . $url .'&';

                return redirect($activation_url);
            }
        }catch (Exception $exception){
            Toastr::error('verification failed! try again');
            return back();
        }

        Artisan::call('migrate', ['--force' => true]);

        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:cache');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');

        //new keys for business settings
        //withdraw amount
        if (BusinessSettings::where(['key_name' => 'minimum_withdraw_amount', 'settings_type'=> 'business_information'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'minimum_withdraw_amount', 'settings_type'=> 'business_information'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'maximum_withdraw_amount', 'settings_type'=> 'business_information'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'maximum_withdraw_amount', 'settings_type'=> 'business_information'], [
                'live_values' => 0,
                'test_values' => 0,
            ]);
        }

        //promotional cost setup
        if (BusinessSettings::where(['key_name' => 'discount_cost_bearer', 'settings_type'=> 'promotional_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'discount_cost_bearer', 'settings_type'=> 'promotional_setup'], [
                'live_values' => ["bearer" => "provider", "admin_percentage" => 0 , "provider_percentage" => 100, "type" => "discount"],
                'test_values' => ["bearer" => "provider", "admin_percentage" => 0 , "provider_percentage" => 100, "type" => "coupon"]
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'coupon_cost_bearer', 'settings_type'=> 'promotional_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'coupon_cost_bearer', 'settings_type'=> 'promotional_setup'], [
                'live_values' => ["bearer" => "provider", "admin_percentage" => 0 , "provider_percentage" => 100, "type" => "coupon"],
                'test_values' => ["bearer" => "provider", "admin_percentage" => 0 , "provider_percentage" => 100, "type" => "coupon"]
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'campaign_cost_bearer', 'settings_type'=> 'promotional_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'campaign_cost_bearer', 'settings_type'=> 'promotional_setup'], [
                'live_values' => ["bearer" => "provider", "admin_percentage" => 0 , "provider_percentage" => 100, "type" => "campaign"],
                'test_values' => ["bearer" => "provider", "admin_percentage" => 0 , "provider_percentage" => 100, "type" => "campaign"]
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'phone_number_visibility_for_chatting', 'settings_type'=> 'business_information'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'phone_number_visibility_for_chatting', 'settings_type'=> 'business_information'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'cookies_text', 'settings_type'=> 'business_information'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'cookies_text', 'settings_type'=> 'business_information'], [
                'live_values' => "",
                'test_values' => ""
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'customer_referral_earning', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'customer_referral_earning', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'referral_value_per_currency_unit', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'referral_value_per_currency_unit', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'customer_wallet', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'customer_wallet', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'loyalty_point_value_per_currency_unit', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'loyalty_point_value_per_currency_unit', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'min_loyalty_point_to_transfer', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'min_loyalty_point_to_transfer', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'customer_loyalty_point', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'customer_loyalty_point', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'loyalty_point_percentage_per_booking', 'settings_type'=> 'customer_config'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'loyalty_point_percentage_per_booking', 'settings_type'=> 'customer_config'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'cash_after_service', 'settings_type'=> 'service_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'cash_after_service', 'settings_type'=> 'service_setup'], [
                'live_values' => 1,
                'test_values' => 1
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'digital_payment', 'settings_type'=> 'service_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'digital_payment', 'settings_type'=> 'service_setup'], [
                'live_values' => 1,
                'test_values' => 1
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'forget_password_verification_method', 'settings_type'=> 'business_information'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'forget_password_verification_method', 'settings_type'=> 'business_information'], [
                'live_values' => 'email',
                'test_values' => 'email'
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'email_verification', 'settings_type'=> 'service_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'email_verification', 'settings_type'=> 'service_setup'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }
        if (BusinessSettings::where(['key_name' => 'phone_verification', 'settings_type'=> 'service_setup'])->first() == false) {
            BusinessSettings::updateOrCreate(['key_name' => 'phone_verification', 'settings_type'=> 'service_setup'], [
                'live_values' => 0,
                'test_values' => 0
            ]);
        }

        //user referral code
        $users = User::whereNull('ref_code')->get();
        foreach ($users as $user) {
            $user->ref_code = generate_referer_code();
            $user->save();
        }

        //end

        return redirect(env('APP_URL'));
    }
}
