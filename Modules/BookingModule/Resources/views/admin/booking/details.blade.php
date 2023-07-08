@extends('adminmodule::layouts.master')

@section('title',translate('Booking_Details'))

@push('css_or_js')

@endpush

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Booking_Details')}} </h2>
                    </div>

                    <ul class="nav nav--tabs nav--tabs__style2 mb-4">
                        <li class="nav-item">
                            <a class="nav-link {{$web_page=='details'?'active':''}}"
                               href="{{url()->current()}}?web_page=details">{{translate('details')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{$web_page=='status'?'active':''}}"
                               href="{{url()->current()}}?web_page=status">{{translate('status')}}</a>
                        </li>
                    </ul>

                    <div class="card mb-3">
                        <div class="card-body pb-5">
                            <div
                                class="border-bottom pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <div>
                                    <h3 class="c1 mb-2">{{translate('Booking')}}
                                        # {{$booking['readable_id']}}</h3>
                                    <p class="opacity-75 fz-12">{{translate('Booking_Placed')}}
                                        : {{date('d-M-Y h:ia',strtotime($booking->created_at))}}</p>
                                </div>
                                <div class="d-flex flex-wrap flex-xxl-nowrap gap-3">
                                    <select class="js-select theme-input-style max-w220 min-w180 selected-item-c1"
                                            id="serviceman_assign">
                                        <option value="no_serviceman">{{translate('--Assign_Serviceman--')}}</option>
                                        @foreach($servicemen as $serviceman)
                                            <option
                                                value="{{$serviceman->id}}" {{$booking->serviceman_id == $serviceman->id ? 'selected' : ''}} >
                                                {{$serviceman->user ? Str::limit($serviceman->user->first_name.' '.$serviceman->user->last_name, 30):''}}
                                            </option>
                                        @endforeach
                                    </select>
                                    <select class="js-select theme-input-style max-w220 min-w180 selected-item-c1"
                                            id="payment_status">
                                        <option value="0">{{translate('--Payment_Status--')}}</option>
                                        <option
                                            value="paid" {{$booking['is_paid'] ? 'selected' : ''}} >{{translate('Paid')}}</option>
                                        <option
                                            value="unpaid" {{!$booking['is_paid'] ? 'selected' : ''}} >{{translate('Unpaid')}}</option>
                                    </select>
                                    @if($booking->booking_status != 'pending')
                                        <select class="js-select theme-input-style max-w220 min-w180 selected-item-c1"
                                                id="booking_status">
                                            <option value="0">{{translate('--Booking_status--')}}</option>
                                            <option
                                                value="ongoing" {{$booking['booking_status'] == 'ongoing' ? 'selected' : ''}}>{{translate('Ongoing')}}</option>
                                            <option
                                                value="completed" {{$booking['booking_status'] == 'completed' ? 'selected' : ''}}>{{translate('Completed')}}</option>
                                            <option
                                                value="canceled" {{$booking['booking_status'] == 'canceled' ? 'selected' : ''}}>{{translate('Canceled')}}</option>
                                        </select>
                                    @endif

                                    @if(!in_array($booking->booking_status,['ongoing','completed']))
                                        <button type="button" class="btn btn--primary" data-bs-toggle="modal"
                                                id="change_schedule"
                                                data-bs-target="#changeScheduleModal">
                                            <span class="material-icons">schedule</span>
                                            {{translate('CHANGE_SCHEDULE')}}
                                        </button>
                                    @endif

                                    <a href="{{route('admin.booking.invoice',[$booking->id])}}"
                                       class="btn btn-primary" target="_blank">
                                        <span class="material-icons">description</span>
                                        {{translate('Invoice')}}
                                    </a>
                                </div>
                            </div>
                            <div
                                class="border-bottom py-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <div>
                                    <h4 class="mb-2">{{translate('Payment_Method')}}</h4>
                                    <h5 class="c1 mb-2">{{ translate($booking->payment_method) }}</h5>
                                    <p>
                                        <span>{{translate('Amount')}} : </span> {{with_currency_symbol($booking->total_booking_amount)}}
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2"><span>{{translate('Booking_Status')}} :</span> <span
                                            class="c1 text-capitalize"
                                            id="booking_status__span">{{$booking->booking_status}}</span></p>
                                    <p class="mb-2"><span>{{translate('Payment_Status')}} : </span> <span
                                            class="text-{{$booking->is_paid ? 'success' : 'danger'}}"
                                            id="payment_status__span">{{$booking->is_paid ? translate('Paid') : translate('Unpaid')}}</span>
                                    </p>
                                    <h5>{{translate('Service_Schedule_Date')}} : <span
                                            id="service_schedule__span">{{date('d/m/Y h:ia',strtotime($booking->service_schedule))}}</span>
                                    </h5>
                                </div>
                            </div>
                            <div class="py-3 d-flex gap-3 flex-wrap mb-2">
                                <!-- Customer Info -->
                                <div class="c1-light-bg radius-10 py-3 px-4 flex-grow-1">
                                    <div class="d-flex justify-content-start gap-2">
                                        <h4 class="mb-2">{{translate('Customer_Information')}}</h4>
                                        @if($booking['booking_status'] == 'pending' || $booking['booking_status'] == 'accepted' || $booking['booking_status'] == 'ongoing')
                                            <i class="material-icons" data-bs-toggle="modal"
                                               data-bs-target="#serviceAddressModal--{{$booking['id']}}"
                                               data-toggle="tooltip" data-placement="top"
                                               title="{{translate('Update service address')}}">edit</i>
                                        @endif
                                    </div>
                                    @if(isset($booking->customer))
                                        <h5 class="c1 mb-3">{{isset($booking->customer)?Str::limit($booking->customer->first_name. ' ' .$booking->customer->last_name, 30):''}}</h5>
                                        <ul class="list-info">
                                            <li>
                                                <span class="material-icons">phone_iphone</span>
                                                <a href="tel:{{isset($booking->customer)?$booking->customer->phone:''}}">{{isset($booking->customer)?$booking->customer->phone:''}}</a>
                                            </li>
                                            <li>
                                                <span class="material-icons">map</span>
                                                <p>{{Str::limit($booking->service_address->address??translate('not_available'), 100)}}</p>
                                            </li>
                                        </ul>
                                    @else
                                        <p class="text-muted text-center mt-30 fz-12">{{translate('No Customer Information')}}</p>
                                    @endif
                                </div>
                                <!-- End Customer Info -->

                                <!-- Provider Info -->
                                <div class="c1-light-bg radius-10 py-3 px-4 flex-grow-1">
                                    <h4 class="mb-2">{{translate('Provider Information')}}</h4>
                                    @if(isset($booking->provider))
                                        <h5 class="c1 mb-3">{{Str::limit($booking->provider->company_name??'', 30)}}</h5>
                                        <ul class="list-info">
                                            <li>
                                                <span class="material-icons">phone_iphone</span>
                                                <a href="tel:88013756987564">{{$booking->provider->contact_person_phone??''}}</a>
                                            </li>
                                            <li>
                                                <span class="material-icons">map</span>
                                                <p>{{Str::limit($booking->provider->company_address??'', 100)}}</p>
                                            </li>
                                        </ul>
                                    @else
                                        <p class="text-muted text-center mt-30 fz-12">{{translate('No Provider Information')}}</p>
                                    @endif
                                </div>
                                <!-- End Provider Info -->

                                <!-- Lead Service Info -->
                                <div class="c1-light-bg radius-10 py-3 px-4 flex-grow-1">
                                    <h4 class="mb-2">{{translate('Lead_Service_Information')}}</h4>
                                    @if(isset($booking->serviceman))
                                        <h5 class="c1 mb-3">{{Str::limit($booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->first_name.' '.$booking->serviceman->user->last_name:'', 30)}}</h5>
                                        <ul class="list-info">
                                            <li>
                                                <span class="material-icons">phone_iphone</span>
                                                <a href="tel:{{$booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone:''}}">
                                                    {{$booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone:''}}
                                                </a>
                                            </li>
                                        </ul>
                                    @else
                                        <p class="text-muted text-center mt-30 fz-12">{{translate('No Serviceman Information')}}</p>
                                    @endif
                                </div>
                                <!-- End Lead Service Info -->
                            </div>

                            <!-- Booking summary -->
                            <div class="d-flex justify-content-start gap-2">
                                <h3 class="mb-3">{{translate('Booking_Summary')}}</h3>
                                {{--@if($booking['booking_status'] == 'pending' || $booking['booking_status'] == 'accepted' || $booking['booking_status'] == 'ongoing')
                                    <i class="material-icons" data-bs-toggle="modal"
                                       data-bs-target="#serviceUpdateModal--{{$booking['id']}}"
                                       data-toggle="tooltip" data-placement="top"
                                       title="{{translate('Add or remove services')}}">edit</i>
                                @endif--}}
                            </div>

                            <div class="table-responsive border-bottom">
                                <table class="table text-nowrap align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th class="ps-lg-3">{{translate('Service')}}</th>
                                        <th>{{translate('Price')}}</th>
                                        <th>{{translate('Qty')}}</th>
                                        <th>{{translate('Discount')}}</th>
                                        <th class="text-end">{{translate('Total')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php($sub_total=0)
                                    @foreach($booking->detail as $detail)
                                        <tr>
                                            <td class="text-wrap ps-lg-3">
                                                @if(isset($detail->service))
                                                    <div class="d-flex flex-column">
                                                        <a href="{{route('admin.service.detail',[$detail->service->id])}}"
                                                           class="fw-bold">{{Str::limit($detail->service->name, 30)}}</a>
                                                        <div>{{Str::limit($detail ? $detail->variant_key : '', 50)}}</div>
                                                    </div>
                                                @else
                                                    <span
                                                        class="badge badge-pill badge-danger">{{translate('Service_unavailable')}}</span>
                                                @endif
                                            </td>
                                            <td>{{with_currency_symbol($detail->service_cost)}}</td>
                                            <td>{{$detail->quantity}}</td>
                                            <td>{{with_currency_symbol($detail->discount_amount)}}</td>
                                            <td class="text-end">{{with_currency_symbol($detail->total_cost)}}</td>
                                        </tr>
                                        @php($sub_total+=$detail->service_cost*$detail->quantity)
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="row justify-content-end mt-3">
                                <div class="col-sm-10 col-md-6 col-xl-5">
                                    <div class="table-responsive">
                                        <table class="table-sm title-color align-right w-100">
                                            <tbody>
                                            <tr>
                                                <td>{{translate('Sub_Total_(Vat _Excluded)')}}</td>
                                                <td>{{with_currency_symbol($sub_total)}}</td>
                                            </tr>
                                            <tr>
                                                <td>{{translate('Discount')}}</td>
                                                <td>{{with_currency_symbol($booking->total_discount_amount)}}</td>
                                            </tr>
                                            <tr>
                                                <td>{{translate('Coupon_Discount')}}</td>
                                                <td>{{with_currency_symbol($booking->total_coupon_discount_amount)}}</td>
                                            </tr>
                                            <tr>
                                                <td>{{translate('Campaign_Discount')}}</td>
                                                <td>{{with_currency_symbol($booking->total_campaign_discount_amount)}}</td>
                                            </tr>
                                            <tr>
                                                <td>{{translate('Vat')}}</td>
                                                <td>{{with_currency_symbol($booking->total_tax_amount)}}</td>
                                            </tr>
                                            <tr>
                                                <td>{{translate('Due')}}</td>
                                                <td>{{with_currency_symbol($booking->additional_charge)}}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{translate('Grand_Total')}}</strong></td>
                                                <td>
                                                    <strong>{{with_currency_symbol($booking->total_booking_amount)}}</strong>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Content -->

    <!-- Booking Schedule Modal -->
    <div class="modal fade" id="changeScheduleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
         aria-labelledby="changeScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeScheduleModalLabel">{{translate('Change_Booking_Schedule')}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="datetime-local" id="service_schedule" class="form-control" name="service_schedule"
                           value="{{$booking->service_schedule}}">
                </div>
                <div class="p-3 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn--secondary"
                            data-bs-dismiss="modal">{{translate('Close')}}</button>
                    <button type="button" class="btn btn--primary"
                            id="service_schedule__submit">{{translate('Submit')}}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Address Update Modal -->
    @include('bookingmodule::admin.booking.partials.details._service-address-modal')

    <!-- Service Update Modal -->
    @include('bookingmodule::admin.booking.partials.details._service-modal')
@endsection

@push('script')


    <script>
        @if($booking->booking_status == 'pending')
        $(document).ready(function () {
            selectElementVisibility('serviceman_assign', false);
            selectElementVisibility('payment_status', false);
            $("#change_schedule").addClass('d-none');
        });
        @endif


        //Service schedule update
        $("#service_schedule__submit").click(function () {
            var service_schedule = $("#service_schedule").val();
            var route = '{{route('admin.booking.schedule_update',[$booking->id])}}' + '?service_schedule=' + service_schedule;

            update_booking_details(route, '{{translate('want_to_update_status')}}', 'service_schedule__submit', service_schedule);
        });

        //Booking status update
        $("#booking_status").change(function () {
            var booking_status = $("#booking_status option:selected").val();
            if (parseInt(booking_status) !== 0) {
                var route = '{{route('admin.booking.status_update',[$booking->id])}}' + '?booking_status=' + booking_status;
                update_booking_details(route, '{{translate('want_to_update_status')}}', 'booking_status', booking_status);
            } else {
                toastr.error('{{translate('choose_proper_status')}}');
            }
        });

        //Serviceman assign/update
        $("#serviceman_assign").change(function () {
            var serviceman_id = $("#serviceman_assign option:selected").val();
            if (serviceman_id !== 'no_serviceman') {
                var route = '{{route('admin.booking.serviceman_update',[$booking->id])}}' + '?serviceman_id=' + serviceman_id;

                update_booking_details(route, '{{translate('want_to_assign_the_serviceman')}}?', 'serviceman_assign', serviceman_id);
            } else {
                toastr.error('{{translate('choose_proper_serviceman')}}');
            }
        });

        //Payment status update
        $("#payment_status").change(function () {
            var payment_status = $("#payment_status option:selected").val();
            if (parseInt(payment_status) !== 0) {
                var route = '{{route('admin.booking.payment_update',[$booking->id])}}' + '?payment_status=' + payment_status;

                update_booking_details(route, '{{translate('want_to_update_status')}}', 'payment_status', payment_status);
            } else {
                toastr.error('{{translate('choose_proper_payment_status')}}');
            }
        });


        //update ajax function
        function update_booking_details(route, message, componentId, updatedValue) {
            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--c2)',
                confirmButtonColor: 'var(--c1)',
                cancelButtonText: '{{translate('Cancel')}}',
                confirmButtonText: '{{translate('Yes')}}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        dataType: 'json',
                        data: {},
                        beforeSend: function () {
                            /*$('#loading').show();*/
                        },
                        success: function (data) {
                            // console.log('tt');return false;
                            update_component(componentId, updatedValue);
                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });

                            if (componentId === 'booking_status') {
                                location.reload();
                            }
                        },
                        complete: function () {
                            /*$('#loading').hide();*/
                        },
                    });
                }
            })
        }

        //component update
        function update_component(componentId, updatedValue) {

            if (componentId === 'booking_status') {
                $("#booking_status__span").html(updatedValue);

                selectElementVisibility('serviceman_assign', true);
                selectElementVisibility('payment_status', true);
                if ($("#change_schedule").hasClass('d-none')) {
                    $("#change_schedule").removeClass('d-none');
                }

            } else if (componentId === 'payment_status') {
                $("#payment_status__span").html(updatedValue);
                if (updatedValue === 'paid') {
                    $("#payment_status__span").addClass('text-success').removeClass('text-danger');
                } else if (updatedValue === 'unpaid') {
                    $("#payment_status__span").addClass('text-danger').removeClass('text-success');
                }

            } else if (componentId === 'service_schedule__submit') {
                $('#changeScheduleModal').modal('hide');
                let date = new Date(updatedValue);
                $('#service_schedule__span').html(date.getDate() + "-" + (date.getMonth() + 1) + "-" + date.getFullYear() + " " +
                    date.getHours() + ":" + date.getMinutes());

            }
        }

        //component update
        function selectElementVisibility(componentId, visibility) {
            if (visibility === true) {
                $('#' + componentId).next(".select2-container").show();
            } else if (visibility === false) {
                $('#' + componentId).next(".select2-container").hide();
            } else {
            }
        }
    </script>

    <!-- Service update -->
    <script>
        function removeService(bookingId, bookingDetailsId, serviceId, variantKey, zoneId) {
            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: '{{translate('want to remove the service from the booking')}}',
                type: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                cancelButtonColor: 'var(--c2)',
                confirmButtonColor: 'var(--c1)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: "{{route('admin.booking.service.remove')}}",
                        data: {
                            booking_id: bookingId,
                            booking_details_id: bookingDetailsId,
                            service_id: serviceId,
                            variant_key: variantKey,
                            zone_id: zoneId
                        },
                        type: 'put',
                        success: function (response) {
                            toastr.success('{{translate('successfully_updated')}}')
                            location.reload();
                        },
                        error: function () {
                            toastr.success('{{translate('Failed to update')}}')
                        }
                    });
                }
            })
        }

        $("#service_selector__select").on('change', function () {
            $("#service_variation_selector__select").html('<option value="" selected disabled>{{translate('Select Service Variation')}}</option>');
            const serviceId = this.value;

            $.get({
                url: '{{route('admin.booking.service.ajax-get-variant')}}' + '?service_id=' + serviceId + '&zone_id=' + "{{$booking->zone_id}}",
                data: {},
                processData: false,
                contentType: false,
                cache: false,
                timeout: 800000,
                success: function (response) {
                    var selectString = '<option value="" selected disabled>{{translate('Select Service Variation')}}</option>';
                    response.content.forEach((item) => {
                        selectString += `<option value="${item.id}">${item.variant}</option>`;
                    });
                    $("#service_variation_selector__select").html(selectString)
                },
                complete: function () {
                    //
                },
                error: function () {
                    //
                }
            });
        })

        $(document).ready(function () {
            $('#category_selector__select').select2({ dropdownParent: "#serviceUpdateModal--{{$booking['id']}}" })
        });
        $(document).ready(function () {
            $('#sub_category_selector__select').select2({ dropdownParent: "#serviceUpdateModal--{{$booking['id']}}" })
        });
        $(document).ready(function () {
            $('#service_selector__select').select2({ dropdownParent: "#serviceUpdateModal--{{$booking['id']}}" })
        });
        $(document).ready(function () {
            $('#service_variation_selector__select').select2({ dropdownParent: "#serviceUpdateModal--{{$booking['id']}}" })
        });
    </script>
    <!-- end -->

    <!-- Map scripts (customer address) -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{business_config('google_map', 'third_party')?->live_values['map_api_key_client']}}&libraries=places&v=3.45.8"></script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });


        $( document ).ready(function() {
            function initAutocomplete() {
                var myLatLng = {

                    lat: 23.811842872190343,
                    lng: 90.356331
                };
                const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                    center: {
                        lat: 23.811842872190343,
                        lng: 90.356331
                    },
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                var marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('latitude').value = coordinates['lat'];
                    document.getElementById('longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address').innerHtml = results[1].formatted_address;
                            }
                        }
                    });
                });
                // Create the search box and link it to the UI element.
                const input = document.getElementById("pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
                // Bias the SearchBox results towards current map's viewport.
                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];
                // Listen for the event fired when the user selects a prediction and retrieve
                // more details for that place.
                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }
                    // Clear out the old markers.
                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];
                    // For each place, get the icon, name and location.
                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('latitude').value = this.position.lat();
                            document.getElementById('longitude').value = this.position.lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            // Only geocodes have viewport.
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            initAutocomplete();
        });


        $('.__right-eye').on('click', function(){
            if($(this).hasClass('active')) {
                $(this).removeClass('active')
                $(this).find('i').removeClass('tio-invisible')
                $(this).find('i').addClass('tio-hidden-outlined')
                $(this).siblings('input').attr('type', 'password')
            }else {
                $(this).addClass('active')
                $(this).siblings('input').attr('type', 'text')


                $(this).find('i').addClass('tio-invisible')
                $(this).find('i').removeClass('tio-hidden-outlined')
            }
        })
    </script>
    <!-- End -->
@endpush
