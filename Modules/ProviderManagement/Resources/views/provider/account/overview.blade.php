@extends('providermanagement::layouts.master')

@section('title',translate('Account_Overview'))

@push('css_or_js')

@endpush

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{translate('Account_Information')}}</h2>
            </div>

            <!-- Nav Tabs -->
            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{$page_type=='overview'?'active':''}}"
                           href="{{url()->current()}}?page_type=overview">{{translate('Overview')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$page_type=='commission-info'?'active':''}}"
                           href="{{url()->current()}}?page_type=commission-info">{{translate('Commission_Info')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$page_type=='review'?'active':''}}"
                           href="{{url()->current()}}?page_type=review">{{translate('Reviews')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$page_type=='promotional_cost'?'active':''}}"
                           href="{{url()->current()}}?page_type=promotional_cost">{{translate('Promotional_Cost')}}</a>
                    </li>
                </ul>
            </div>
            <!-- End Nav Tabs -->

            <!-- Card -->
            <div class="card">
                <div class="card-body p-30">
                    <!-- Provider Details Overview -->
                    <div class="provider-details-overview mb-30">
                        <div class="provider-details-overview__collect-cash">
                            <!-- Statistics Card -->
                            <div class="statistics-card statistics-card__collect-cash h-100">
                                <h3>{{translate('Collect_Cash_From_admin')}}</h3>
                                <h2>{{with_currency_symbol($provider->owner->account->account_receivable)}}</h2>
                                <a href="{{route('provider.withdraw.list')}}"
                                    class="btn btn--primary text-capitalize w-100 btn--lg mw-75">{{translate('Collect_Cash')}}</a>
                            </div>
                            <!-- End Statistics Card -->
                        </div>
                        <div class="provider-details-overview__statistics">

                            <!-- Statistics Card -->
                            <div
                                class="statistics-card statistics-card__style2 statistics-card__pending-withdraw">
                                <h2>{{with_currency_symbol($provider->owner->account->balance_pending)}}</h2>
                                <h3>{{translate('Pending_Withdrawn')}}</h3>
                            </div>
                            <!-- End Statistics Card -->

                            <!-- Statistics Card -->
                            <div
                                class="statistics-card statistics-card__style2 statistics-card__already-withdraw">
                                <h2>{{with_currency_symbol($provider->owner->account->total_withdrawn)}}</h2>
                                <h3>{{translate('Already_Withdrawn')}}</h3>
                            </div>
                            <!-- End Statistics Card -->

                            <!-- Statistics Card -->
                            <div
                                class="statistics-card statistics-card__style2 statistics-card__withdrawable-amount">
                                <h2>{{with_currency_symbol($provider->owner->account->account_receivable)}}</h2>
                                <h3>{{translate('Withdrawable_Amount')}}</h3>
                            </div>
                            <!-- End Statistics Card -->

                            <!-- Statistics Card -->
                            <div
                                class="statistics-card statistics-card__style2 statistics-card__total-earning">
                                <h2>{{ with_currency_symbol($total_earning) }}</h2>
                                <h3>{{translate('Total_Earning')}}</h3>
                            </div>
                            <!-- End Statistics Card -->
                        </div>
                        <div class="provider-details-overview__order-overview">
                            <!-- Statistics Card -->
                            <div class="statistics-card statistics-card__order-overview h-100 pb-2">
                                <h3 class="mb-0">{{translate('Order_Overview')}}</h3>
                                <div id="apex-pie-chart" class="d-flex justify-content-center"></div>
                            </div>
                            <!-- End Statistics Card -->
                        </div>
                    </div>
                    <!-- End Provider Details Overview -->

                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <h2>{{translate('Information_Details')}}</h2>
                        <a href="{{route('provider.profile_update')}}" class="btn btn--primary">
                            <span class="material-icons">border_color</span>
                            {{translate('Edit')}}
                        </a>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <!-- Information Details Box -->
                            <div class="information-details-box media flex-column flex-sm-row gap-20 h-100">
                                <img class="avatar-img radius-5 max-w220"
                                        src="{{asset('storage/app/public/provider/logo')}}/{{$provider->logo}}"
                                        onerror="this.src='{{asset('public/assets/provider-module')}}/img/media/info-details.png'"
                                        alt="">
                                <div class="media-body ">
                                    <h2 class="information-details-box__title text-capitalize">{{Str::limit($provider->company_name, 30)}}</h2>

                                    <ul class="contact-list">
                                        <li>
                                            <span class="material-symbols-outlined">phone_iphone</span>
                                            <a href="tel:{{$provider->company_phone}}">{{$provider->company_phone}}</a>
                                        </li>
                                        <li>
                                            <span class="material-symbols-outlined">mail</span>
                                            <a href="mailto:{{$provider->company_email}}">{{$provider->company_email}}</a>
                                        </li>
                                        <li>
                                            <span class="material-symbols-outlined">map</span>
                                            {{Str::limit($provider->company_address, 100)}}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- End Information Details Box -->
                        </div>
                        <div class="col-lg-6">
                            <!-- Information Details Box -->
                            <div class="information-details-box h-100">
                                <h2 class="information-details-box__title c1">{{translate('Contact_Person_Information')}}
                                </h2>
                                <h3 class="information-details-box__subtitle text-capitalize">{{Str::limit($provider->contact_person_name, 30)}}</h3>

                                <ul class="contact-list">
                                    <li>
                                        <span class="material-symbols-outlined">phone_iphone</span>
                                        <a href="tel:{{$provider->contact_person_phone}}">{{$provider->contact_person_phone}}</a>
                                    </li>
                                    <li>
                                        <span class="material-symbols-outlined">mail</span>
                                        <a href="mailto:{{$provider->contact_person_email}}">{{$provider->contact_person_email}}</a>
                                    </li>
                                </ul>
                            </div>
                            <!-- End Information Details Box -->
                        </div>
                        <div class="col-12">
                            <!-- Information Details Box -->
                            <div class="information-details-box">
                                <div class="row g-4">
                                    <div class="col-lg-3">
                                        <h2 class="information-details-box__title c1 mb-3">{{translate('Business_Information')}}
                                        </h2>
                                        <p><strong
                                                class="text-capitalize">{{translate($provider->owner->identification_type)}}
                                                -</strong> {{$provider->owner->identification_number}}</p>
                                    </div>
                                    <div class="col-lg-9">
                                        <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                                            @if(isset($provider->owner->identification_image) && count($provider->owner->identification_image) > 0)
                                                @foreach($provider->owner->identification_image as $key=>$image)
                                                <div>
                                                    <img class="max-w320"
                                                    src="{{asset('storage/app/public/provider/identity')}}/{{$image}}"
                                                    onerror="this.src='{{asset('public/assets/provider-module')}}/img/media/provider-id.png'"
                                                    alt="">
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Information Details Box -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->
        </div>
    </div>
    <!-- End Main Content -->
@endsection

@push('script')
    <script src="{{asset('public/assets/provider-module')}}/plugins/apex/apexcharts.min.js"></script>

    <script>
        var options = {
            labels: ['accepted', 'ongoing', 'completed', 'canceled'],
            series: {{json_encode($total)}},
            chart: {
                width: 235,
                height: 160,
                type: 'donut',
            },
            dataLabels: {
                enabled: false
            },
            title: {
                text: "{{$provider->bookings_count}} Bookings",
                align: 'center',
                offsetX: 0,
                offsetY: 58,
                floating: true,
                style: {
                    fontSize:  '12px',
                },
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        show: true
                    }
                }
            }],
            legend: {
                position: 'bottom',
                offsetY: -5,
                height: 30,
            },
        };

        var chart = new ApexCharts(document.querySelector("#apex-pie-chart"), options);
        chart.render();
    </script>

@endpush
