@extends('paymentmodule::layouts.master')

@push('script')

@endpush

@section('content')
    <center><h1>Please do not refresh this page...</h1></center>


    <form action="{!!route('razor-pay.payment',['token'=>$token])!!}" id="form" method="POST">
    @csrf
    <!-- Note that the amount is in paise = 50 INR -->
        <!--amount need to be in paisa-->
        <script src="https://checkout.razorpay.com/v1/checkout.js"
                data-key="{{ config()->get('razor_config.api_key') }}"
                data-amount="{{round($order_amount, 2)*100}}"
                data-buttontext="Pay {{ round($order_amount, 2) . ' ' . currency_code() }}"
                data-name="lorem"
                data-description="{{$order_amount}}"
                data-image="def.png"
                data-prefill.name="{{$customer->first_name ?? ''}}"
                data-prefill.email="{{$customer->email ?? ''}}"
                data-theme.color="#ff7529">
        </script>
        <button class="btn btn-block" id="pay-button" type="submit" style="display:none"></button>
    </form>

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("pay-button").click();
        });
    </script>
@endsection
