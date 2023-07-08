@extends('paymentmodule::layouts.master')

@push('script')

@endpush

@section('content')
    <center><h1>Please do not refresh this page...</h1></center>

    <?php
    $Paystack = new Modules\PaymentModule\Http\Controllers\PaystackController();
    $reference = $Paystack::generate_transaction_Referance();
    ?>

    <form method="POST" action="{!! route('paystack.payment',['token'=>$token]) !!}" accept-charset="UTF-8"
          class="form-horizontal"
          role="form">
        @csrf
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <input type="hidden" name="email"
                       value="{{$customer->email!=null?$customer->email:'required@email.com'}}"> {{-- required --}}
                <input type="hidden" name="orderID" value="">
                <input type="hidden" name="amount"
                       value="{{$order_amount*100}}"> {{-- required in kobo --}}
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="currency"
                       value="{{currency_code()}}">
                <input type="hidden" name="metadata"
                       value="{{ json_encode($array = ['key_name' => 'value',]) }}"> {{-- For other necessary things you want to add to your payload. it is optional though --}}
                <input type="hidden" name="reference"
                       value="{{ $reference }}"> {{-- required --}}

                <button class="btn btn-block" id="pay-button" type="submit" style="display:none"></button>
            </div>

        </div>
    </form>

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("pay-button").click();
        });
    </script>

@endsection
