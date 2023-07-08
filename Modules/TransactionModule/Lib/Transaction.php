<?php

use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LoyaltyPointTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;


//*** place booking ***
if (!function_exists('place_booking_transaction_for_digital_payment')) {
    function place_booking_transaction_for_digital_payment($booking)
    {
        if ($booking['payment_method'] != 'cash_after_service') {
            $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
            DB::transaction(function () use ($booking, $admin_user_id) {
                //Admin account update
                $account = Account::where('user_id', $admin_user_id)->first();
                $account->balance_pending += $booking['total_booking_amount'];
                $account->save();

                //Admin transaction
                Transaction::create([
                    'ref_trx_id' => null,
                    'booking_id' => $booking['id'],
                    'trx_type' => TRX_TYPE['booking_amount'],
                    'debit' => 0,
                    'credit' => $booking['total_booking_amount'],
                    'balance' => $account->balance_pending,
                    'from_user_id' => $booking->customer_id,
                    'to_user_id' => $admin_user_id,
                    'from_user_account' => null,
                    'to_user_account' => ACCOUNT_STATES[0]['value']
                ]);
            });
        }
    }
}

//** Booking Edit5 ***
if (!function_exists('add_booking_service_transaction_for_digital_payment')) {
    function add_booking_service_transaction_for_digital_payment($booking)
    {
        $service_cost = $booking['additional_charge'] - $booking['additional_tax_amount'] - $booking['additional_discount_amount'] - $booking['additional_campaign_discount_amount'];

        //cost bearing (promotional)
        $booking_details_amounts = BookingDetailsAmount::where('booking_id', $booking->id)->get();
        $promotional_cost_by_admin = 0;
        $promotional_cost_by_provider = 0;
        foreach($booking_details_amounts as $booking_details_amount) {
            $promotional_cost_by_admin += $booking_details_amount['discount_by_admin'] + $booking_details_amount['coupon_discount_by_admin'] + $booking_details_amount['campaign_discount_by_admin'];
            $promotional_cost_by_provider += $booking_details_amount['discount_by_provider'] + $booking_details_amount['coupon_discount_by_provider'] + $booking_details_amount['campaign_discount_by_provider'];
        }

        //total booking amount (for provider)
        $provider_receivable_total_booking_amount = $service_cost - $promotional_cost_by_provider;

        //admin commission
        $provider = Provider::find($booking['provider_id']);
        $commission_percentage = $provider->commission_status == 1 ? $provider->commission_percentage : (business_config('default_commission', 'business_information'))->live_values;
        $admin_commission = ($provider_receivable_total_booking_amount*$commission_percentage)/100;

        //admin promotional cost will be deducted from admin commission
        $admin_commission -= $promotional_cost_by_admin;

        //total booking amount (without commission)
        $booking_amount_without_commission = $booking['additional_charge'] - $admin_commission;

        //user ids (from/to)
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        $provider_user_id = get_user_id($booking['provider_id'], PROVIDER_USER_TYPES[0]);

        DB::transaction(function () use ($booking, $admin_user_id, $provider_user_id, $admin_commission, $booking_amount_without_commission, $promotional_cost_by_admin, $promotional_cost_by_provider) {

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->received_balance += $booking_amount_without_commission;
            $account->save();

            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['received_amount'],
                'debit' => 0,
                'credit' => $booking_amount_without_commission,
                'balance' => $account->received_balance,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[1]['value']
            ]);

            //Provider transactions (for commission)
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->account_payable += $admin_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['payable_commission'],
                'debit' => 0,
                'credit' => $admin_commission,
                'balance' => $account->account_payable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[2]['value'],
                'to_user_account' => null
            ]);

            //Provider transactions (for commission)
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->account_receivable += $admin_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['receivable_commission'],
                'debit' => 0,
                'credit' => $admin_commission,
                'balance' => $account->account_receivable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[3]['value']
            ]);

            //expense (admin)
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->total_expense += $promotional_cost_by_admin;
            $account->save();

            //expense (provider)
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->total_expense += $promotional_cost_by_provider;
            $account->save();
        });
    }
}

if (!function_exists('remove_booking_service_transaction_for_digital_payment')) {
    function remove_booking_service_transaction_for_digital_payment($booking, $amount)
    {
        if ($booking['payment_method'] != 'cash_after_service') {
            $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
            $primary_transaction = Transaction::where('booking_id', $booking['id'])->whereNull('ref_trx_id')->first()?->id;

            DB::transaction(function () use ($booking, $admin_user_id, $amount, $primary_transaction) {
                //Admin transaction
                $account = Account::where('user_id', $admin_user_id)->first();
                $account->balance_pending -= $amount;
                $account->save();

                Transaction::create([
                    'ref_trx_id' => $primary_transaction ?? null,
                    'booking_id' => $booking['id'],
                    'trx_type' => TRX_TYPE['booking_amount'],
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $account->balance_pending,
                    'from_user_id' => $booking['customer_id'],
                    'to_user_id' => $admin_user_id,
                    'from_user_account' => null,
                    'to_user_account' => ACCOUNT_STATES[0]['value']
                ]);

                //Customer transaction
                $user = User::find($booking['customer_id']);
                $user->wallet_balance += $amount;
                $user->save();

                Transaction::create([
                    'ref_trx_id' => $primary_transaction ?? null,
                    'booking_id' => $booking['id'],
                    'trx_type' => WALLET_TRX_TYPE['wallet_refund'],
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $user->wallet_balance,
                    'from_user_id' => $booking['customer_id'],
                    'to_user_id' => $admin_user_id,
                    'from_user_account' => 'wallet_balance',
                    'to_user_account' => null
                ]);
            });
        }
    }
}

if (!function_exists('place_booking_transaction_for_wallet_payment')) {
    function place_booking_transaction_for_wallet_payment($booking)
    {
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        DB::transaction(function () use ($booking, $admin_user_id) {
            //Admin account update
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->balance_pending += $booking['total_booking_amount'];
            $account->save();

            //Admin transaction
            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['booking_amount'],
                'debit' => 0,
                'credit' => $booking['total_booking_amount'],
                'balance' => $account->balance_pending,
                'from_user_id' => $booking->customer_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[0]['value']
            ]);



            //Customer wallet update
            $user = User::find($booking['customer_id']);
            if ($user->wallet_balance >= $booking['total_booking_amount']) {
                $user->wallet_balance -= $booking['total_booking_amount'];
            }
            $user->save();

            //customer transaction (wallet)
            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => $booking['id'],
                'trx_type' => WALLET_TRX_TYPE['wallet_payment'],
                'debit' => $booking['total_booking_amount'],
                'credit' => 0,
                'balance' => $user->wallet_balance,
                'from_user_id' => $booking->customer_id,
                'to_user_id' => $booking->customer_id,
                'from_user_account' => null,
                'to_user_account' => 'user_wallet'
            ]);
        });
    }
}


//*** after complete booking ***
if (!function_exists('digital_payment_booking_transaction')) {
    function digital_payment_booking_transaction($booking)
    {
        $service_cost = $booking['total_booking_amount'] - $booking['total_tax_amount'] + $booking['total_discount_amount'] + $booking['total_campaign_discount_amount'] + $booking['total_coupon_discount_amount'];
        if($booking['additional_charge'] > 0) {
            $service_cost = $service_cost - $booking['additional_charge'] + $booking['additional_tax_amount'] + $booking['additional_discount_amount'] + $booking['additional_campaign_discount_amount'] -  $booking['total_coupon_discount_amount'];
        }

        //cost bearing (promotional)
        $booking_details_amounts = BookingDetailsAmount::where('booking_id', $booking->id)->get();
        $promotional_cost_by_admin = 0;
        $promotional_cost_by_provider = 0;
        foreach($booking_details_amounts as $booking_details_amount) {
            $promotional_cost_by_admin += $booking_details_amount['discount_by_admin'] + $booking_details_amount['coupon_discount_by_admin'] + $booking_details_amount['campaign_discount_by_admin'];
            $promotional_cost_by_provider += $booking_details_amount['discount_by_provider'] + $booking_details_amount['coupon_discount_by_provider'] + $booking_details_amount['campaign_discount_by_provider'];
        }

        //total booking amount (for provider)
        $provider_receivable_total_booking_amount = $service_cost - $promotional_cost_by_provider;

        //admin commission
        $provider = Provider::find($booking['provider_id']);
        $commission_percentage = $provider->commission_status == 1 ? $provider->commission_percentage : (business_config('default_commission', 'business_information'))->live_values;
        $admin_commission = ($provider_receivable_total_booking_amount*$commission_percentage)/100;

        //admin promotional cost will be deducted from admin commission
        $admin_commission -= $promotional_cost_by_admin;

        //total booking amount (without commission)
        $booking_amount_without_commission = $booking['total_booking_amount'] - $admin_commission - $booking['additional_charge'];

        //user ids (from/to)
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        $provider_user_id = get_user_id($booking['provider_id'], PROVIDER_USER_TYPES[0]);

        DB::transaction(function () use ($booking, $admin_user_id, $provider_user_id, $admin_commission, $booking_amount_without_commission, $promotional_cost_by_admin, $promotional_cost_by_provider) {

            $account = Account::where('user_id', $admin_user_id)->first();
            $account->balance_pending -= ($booking['total_booking_amount'] - $booking['additional_charge']);
            $account->save();

            //Admin transaction (-pending)
            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['pending_amount'],
                'debit' => ($booking['total_booking_amount'] - $booking['additional_charge']),
                'credit' => 0,
                'balance' => $account->balance_pending,
                'from_user_id' => $admin_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[0]['value'],
                'to_user_account' => null
            ]);

            //Provider transactions (+receivable)
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->account_receivable += $booking_amount_without_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['receivable_amount'],
                'debit' => 0,
                'credit' => $booking_amount_without_commission,
                'balance' => $account->account_receivable,
                'from_user_id' => $admin_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[3]['value']
            ]);

            //Admin transactions for commission (+received)
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->received_balance += $admin_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['received_commission'],
                'debit' => 0,
                'credit' => $admin_commission,
                'balance' => $account->received_balance,
                'from_user_id' => $admin_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => ACCOUNT_STATES[1]['value'],
                'to_user_account' => null
            ]);

            //Admin transactions (+payable)
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->account_payable += $booking_amount_without_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['payable_amount'],
                'debit' => 0,
                'credit' => $booking_amount_without_commission,
                'balance' => $account->account_payable,
                'from_user_id' => $admin_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => ACCOUNT_STATES[2]['value'],
                'to_user_account' => null
            ]);

            //expense
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->total_expense += $promotional_cost_by_admin;
            $account->save();

            $account = Account::where('user_id', $provider_user_id)->first();
            $account->total_expense += $promotional_cost_by_provider;
            $account->save();
        });
    }
}

if (!function_exists('cash_after_service_booking_transaction')) {
    function cash_after_service_booking_transaction($booking)
    {
        $service_cost = $booking['total_booking_amount'] - $booking['total_tax_amount'] + $booking['total_discount_amount'] + $booking['total_campaign_discount_amount'] + $booking['total_coupon_discount_amount'];

        //cost bearing (promotional)
        $booking_details_amounts = BookingDetailsAmount::where('booking_id', $booking->id)->get();
        $promotional_cost_by_admin = 0;
        $promotional_cost_by_provider = 0;
        foreach($booking_details_amounts as $booking_details_amount) {
            $promotional_cost_by_admin += $booking_details_amount['discount_by_admin'] + $booking_details_amount['coupon_discount_by_admin'] + $booking_details_amount['campaign_discount_by_admin'];
            $promotional_cost_by_provider += $booking_details_amount['discount_by_provider'] + $booking_details_amount['coupon_discount_by_provider'] + $booking_details_amount['campaign_discount_by_provider'];
        }

        //total booking amount (for provider)
        $provider_receivable_total_booking_amount = $service_cost - $promotional_cost_by_provider;

        //admin commission
        $provider = Provider::find($booking['provider_id']);
        $commission_percentage = $provider->commission_status == 1 ? $provider->commission_percentage : (business_config('default_commission', 'business_information'))->live_values;
        $admin_commission = ($provider_receivable_total_booking_amount*$commission_percentage)/100;

        //admin promotional cost will be deducted from admin commission
        $admin_commission -= $promotional_cost_by_admin;

        //total booking amount (without commission)
        $booking_amount_without_commission = $booking['total_booking_amount'] - $admin_commission;

        //user ids (from/to)
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        $provider_user_id = get_user_id($booking['provider_id'], PROVIDER_USER_TYPES[0]);

        DB::transaction(function () use ($booking, $admin_user_id, $provider_user_id, $admin_commission, $booking_amount_without_commission, $promotional_cost_by_admin, $promotional_cost_by_provider) {

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->received_balance += $booking_amount_without_commission;
            $account->save();

            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['received_amount'],
                'debit' => 0,
                'credit' => $booking_amount_without_commission,
                'balance' => $account->received_balance,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[1]['value']
            ]);

            //Provider transactions (for commission)
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->account_payable += $admin_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['payable_commission'],
                'debit' => 0,
                'credit' => $admin_commission,
                'balance' => $account->account_payable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[2]['value'],
                'to_user_account' => null
            ]);

            //Provider transactions (for commission)
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->account_receivable += $admin_commission;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => $booking['id'],
                'trx_type' => TRX_TYPE['receivable_commission'],
                'debit' => 0,
                'credit' => $admin_commission,
                'balance' => $account->account_receivable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => ACCOUNT_STATES[3]['value'],
                'to_user_account' => null
            ]);

            //expense (admin)
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->total_expense += $promotional_cost_by_admin;
            $account->save();

            //expense (provider)
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->total_expense += $promotional_cost_by_provider;
            $account->save();
        });
    }
}


//*** (admin) collect cash from provider ***
if (!function_exists('collect_cash_transaction')) {
    function collect_cash_transaction($provider_id, $collect_amount) {
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
        $provider_user_id = get_user_id($provider_id, PROVIDER_USER_TYPES[0]);

        DB::transaction(function () use ($collect_amount, $admin_user_id, $provider_user_id) {

            $account = Account::where('user_id', $provider_user_id)->first();
            $account->account_payable -= $collect_amount;
            $account->save();

            //Provider transactions
            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['paid_commission'],
                'debit' => $collect_amount,
                'credit' => 0,
                'balance' => $account->account_payable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => ACCOUNT_STATES[2]['value'],
                'to_user_account' => null
            ]);

            //Admin transactions
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->received_balance += $collect_amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => null,
                'trx_type' => TRX_TYPE['received_commission'],
                'debit' => 0,
                'credit' => $collect_amount,
                'balance' => $account->received_balance,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[1]['value']
            ]);

            //admin transactions
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->account_receivable -= $collect_amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => null,
                'trx_type' => TRX_TYPE['receivable_commission'],
                'debit' => $collect_amount,
                'credit' => 0,
                'balance' => $account->account_receivable,
                'from_user_id' => $admin_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[3]['value'],
                'to_user_account' => null
            ]);
        });
    }
}


//*** (provider) withdraw from admin ***
if (!function_exists('withdraw_request_transaction')) {
    function withdraw_request_transaction($provider_user_id, $withdrawal_amount) {

        DB::transaction(function () use ($withdrawal_amount, $provider_user_id) {

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->account_receivable -= $withdrawal_amount;
            $account->save();

            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['withdrawable_amount'],
                'debit' => $withdrawal_amount,
                'credit' => 0,
                'balance' => $account->account_receivable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[3]['value'],
                'to_user_account' => null
            ]);

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->balance_pending += $withdrawal_amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => null,
                'trx_type' => TRX_TYPE['pending_amount'],
                'debit' => 0,
                'credit' => $withdrawal_amount,
                'balance' => $account->balance_pending,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[0]['value']
            ]);
        });
    }
}

if (!function_exists('withdraw_request_accept_transaction')) {
    function withdraw_request_accept_transaction($provider_user_id, $withdrawal_amount) {
        $admin_user_id = User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;

        DB::transaction(function () use ($admin_user_id, $withdrawal_amount, $provider_user_id) {

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->balance_pending -= $withdrawal_amount;
            $account->save();

            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['pending_amount'],
                'debit' => $withdrawal_amount,
                'credit' => 0,
                'balance' => $account->balance_pending,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[0]['value'],
                'to_user_account' => null
            ]);

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->total_withdrawn += $withdrawal_amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => null,
                'trx_type' => TRX_TYPE['received_amount'],
                'debit' => 0,
                'credit' => $withdrawal_amount,
                'balance' => $account->total_withdrawn,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => ACCOUNT_STATES[4]['value'],
                'to_user_account' => null
            ]);

            //Admin transactions
            $account = Account::where('user_id', $admin_user_id)->first();
            $account->account_payable -= $withdrawal_amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => null,
                'trx_type' => TRX_TYPE['paid_amount'],
                'debit' => $withdrawal_amount,
                'credit' => 0,
                'balance' => $account->account_payable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $admin_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[2]['value']
            ]);
        });
    }
}

if (!function_exists('withdraw_request_deny_transaction')) {
    function withdraw_request_deny_transaction($provider_user_id, $withdrawal_amount) {

        DB::transaction(function () use ($withdrawal_amount, $provider_user_id) {

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->account_receivable += $withdrawal_amount;
            $account->save();

            $primary_transaction = Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['withdrawable_amount'],
                'debit' => 0,
                'credit' => $withdrawal_amount,
                'balance' => $account->account_receivable,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => ACCOUNT_STATES[3]['value'],
                'to_user_account' => null
            ]);

            //Provider transactions
            $account = Account::where('user_id', $provider_user_id)->first();
            $account->balance_pending -= $withdrawal_amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => $primary_transaction['id'],
                'booking_id' => null,
                'trx_type' => TRX_TYPE['pending_amount'],
                'debit' => $withdrawal_amount,
                'credit' => 0,
                'balance' => $account->balance_pending,
                'from_user_id' => $provider_user_id,
                'to_user_id' => $provider_user_id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[0]['value']
            ]);
        });
    }
}


//*** FUND ***
if (!function_exists('add_fund_transaction')) {
    function add_fund_transaction($user_id, $amount, $reference) {

        DB::transaction(function () use ($user_id, $amount, $reference) {

            //Provider transactions
            $user = User::where('id', $user_id)->first();
            $user->wallet_balance += $amount;
            $user->save();

            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['fund_by_admin'],
                'debit' => 0,
                'credit' => $amount,
                'balance' => $user->wallet_balance,
                'from_user_id' => $user_id,
                'to_user_id' => $user_id,
                'from_user_account' => null,
                'to_user_account' => 'user_wallet',
                'reference_note' => $reference,
            ]);

        });
    }
}

//*** Referral Earn ***
if (!function_exists('referral_earning_transaction_during_registration')) {
    function referral_earning_transaction_during_registration($user, $amount) {

        DB::transaction(function () use ($user, $amount) {

            //Customer account
            $account = Account::where('user_id', $user->id)->first();
            $account->balance_pending += $amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['referral_earning'],
                'debit' => 0,
                'credit' => $amount,
                'balance' => $account->balance_pending,
                'from_user_id' => $user->id,
                'to_user_id' => $user->id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[0]['value'],
                'reference_note' => $user->ref_code,
            ]);

        });
    }
}

if (!function_exists('referral_earning_transaction_after_booking_complete')) {
    function referral_earning_transaction_after_booking_complete($user, $amount) {

        DB::transaction(function () use ($user, $amount) {

            //Customer account (removed from PENDING)
            $account = Account::where('user_id', $user->id)->first();
            $account->balance_pending -= $amount;
            $account->save();

            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['referral_earning'],
                'debit' => $amount,
                'credit' => 0,
                'balance' => $account->balance_pending,
                'from_user_id' => $user->id,
                'to_user_id' => $user->id,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[0]['value'],
                'reference_note' => $user->ref_code,
            ]);

            //Customer account (add in RECEIVABLE)
            $user = User::where('id', $user->id)->first();
            $user->wallet_balance += $amount;
            $user->save();

            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['referral_earning'],
                'debit' => 0,
                'credit' => $amount,
                'balance' => $user->wallet_balance,
                'from_user_id' => $user->id,
                'to_user_id' => $user->id,
                'from_user_account' => null,
                'to_user_account' => 'user_wallet',
                'reference_note' => $user->ref_code,
            ]);

        });
    }
}


//*** Loyalty point ***
if (!function_exists('loyalty_point_wallet_transfer_transaction')) {
    function loyalty_point_wallet_transfer_transaction($user_id, $point, $amount) {

        DB::transaction(function () use ($user_id, $point, $amount) {

            //Customer (loyalty_point update)
            $user = User::find($user_id);
            $user->loyalty_point -= $point;
            $user->wallet_balance += $amount;
            $user->save();

            //transaction
            Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => null,
                'trx_type' => TRX_TYPE['loyalty_point_earning'],
                'debit' => 0,
                'credit' => $amount,
                'balance' => $amount,
                'from_user_id' => $user_id,
                'to_user_id' => $user_id,
                'from_user_account' => null,
                'to_user_account' => 'user_wallet',
                'reference_note' => null,
            ]);

            //transaction
            LoyaltyPointTransaction::create([
                'user_id' => $user_id,
                'debit' => $point,
                'credit' => 0,
                'balance' => $user->loyalty_point,
                'reference' => null,
                'transaction_type' => null,
            ]);
        });
    }
}

if (!function_exists('loyalty_point_transaction')) {
    function loyalty_point_transaction($user_id, $point) {

        DB::transaction(function () use ($user_id, $point) {

            //point update
            $user = User::find($user_id);
            $user->loyalty_point += $point;
            $user->save();

            //transaction
            LoyaltyPointTransaction::create([
                'user_id' => $user_id,
                'debit' => 0,
                'credit' => $point,
                'balance' => $user->loyalty_point,
                'reference' => null,
                'transaction_type' => null,
            ]);
        });
    }
}
