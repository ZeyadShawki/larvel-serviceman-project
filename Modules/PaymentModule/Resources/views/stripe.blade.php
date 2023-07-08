@extends('paymentmodule::layouts.master')

@push('script')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    {{--stripe--}}
    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
    <script src="https://js.stripe.com/v3/"></script>
    {{--stripe--}}
@endpush

@section('content')
    <center><h1>Do not refresh this page...</h1></center>

    <div class="d-flex justify-content-center">
        <button type="button" id="pay_button" class="btn btn-primary m-2 {{$auto_click ? 'd-none' : ''}}">{{translate('Pay')}}</button>
        <a href="{{route('stripe.cancel')}}" id="cancel_button" class="btn btn-danger m-2 {{$auto_click ? 'd-none' : ''}}">{{translate('Cancel')}}</a>
    </div>

    @php($config = business_config('stripe', 'payment_config'))
    <script type="text/javascript">
        // Create an instance of the Stripe object with your publishable API key
        var stripe = Stripe('{{$config->live_values['published_key']}}');

        document.getElementById('pay_button').addEventListener('click', function(e){
            fetch("{{ route('stripe.token',['token'=>$token]) }}", {
                method: "GET",
            }).then(function (response) {
                //console.log(response)
                return response.text();
            }).then(function (session) {
                //console.log(session)
                return stripe.redirectToCheckout({sessionId: JSON.parse(session).id});
            }).then(function (result) {
                if (result.error) {
                    alert(result.error.message);
                }
            }).catch(function (error) {
                //console.error("error:", error);
            });
        });

        @if($auto_click)
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById('pay_button').click();
            });
        @endif

    </script>
@endsection
