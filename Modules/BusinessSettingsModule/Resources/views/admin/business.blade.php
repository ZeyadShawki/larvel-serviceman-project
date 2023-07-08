@extends('adminmodule::layouts.master')

@section('title',translate('business_setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('public/assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('public/assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('business_setup')}}</h2>
                    </div>

                    <!-- Nav Tabs -->
                    <div class="mb-3">
                        <ul class="nav nav--tabs nav--tabs__style2">
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=business_setup"
                                   class="nav-link {{$web_page=='business_setup'?'active':''}}">
                                    {{translate('business_Information_Setup')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=service_setup"
                                   class="nav-link {{$web_page=='service_setup'?'active':''}}">
                                    {{translate('System_Setup')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=bidding_system"
                                   class="nav-link {{$web_page=='bidding_system'?'active':''}}">
                                    {{translate('bidding_system')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=promotional_setup"
                                   class="nav-link {{$web_page=='promotional_setup'?'active':''}}">
                                    {{translate('promotional_Setup')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=otp_login_setup"
                                   class="nav-link {{$web_page=='otp_login_setup'?'active':''}}">
                                    {{translate('otp_&_login_setup')}}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <!-- End Nav Tabs -->

                    <!-- Tab Content -->
                    @if($web_page=='business_setup')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='business_setup'?'active show':''}}">
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form action="javascript:void(0)" method="POST" id="business-info-update-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="discount-type">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-30">
                                                            <div class="form-floating">
                                                                <input type="text" class="form-control"
                                                                       name="business_name"
                                                                       placeholder="{{translate('business_name')}} *"
                                                                       required=""
                                                                       value="{{$data_values->where('key_name','business_name')->first()->live_values}}">
                                                                <label>{{translate('business_name')}} *</label>
                                                            </div>
                                                        </div>

                                                        <div class="mb-30">
                                                            <div class="form-floating">
                                                                <input type="text" class="form-control"
                                                                       name="business_phone"
                                                                       placeholder="{{translate('business_phone')}} *"
                                                                       required=""
                                                                       oninput="this.value = this.value.replace(/[^+\d]+$/g, '').replace(/(\..*)\./g, '$1');"
                                                                       value="{{$data_values->where('key_name','business_phone')->first()->live_values}}">
                                                                <label>{{translate('business_phone')}} *</label>
                                                                <small class="d-block mt-1 text-danger">* ( {{translate('Country_Code_Required')}} )</small>
                                                            </div>
                                                        </div>
                                                        <div class="mb-30">
                                                            <div class="form-floating">
                                                                <input type="email" class="form-control"
                                                                       name="business_email"
                                                                       placeholder="{{translate('email')}} *"
                                                                       required=""
                                                                       value="{{$data_values->where('key_name','business_email')->first()->live_values}}">
                                                                <label>{{translate('email')}} *</label>
                                                            </div>
                                                        </div>
                                                        <div class="mb-30">
                                                            <div class="form-floating">
                                                            <textarea class="form-control" name="business_address"
                                                                      placeholder="{{translate('address')}} *"
                                                                      required="">{{$data_values->where('key_name','business_address')->first()->live_values}}</textarea>
                                                                <label>{{translate('address')}} *</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-30 d-flex flex-column align-items-center gap-2">
                                                                    <p class="title-color">{{translate('favicon')}}</p>
                                                                    <div class="upload-file mb-30">
                                                                        <input type="file" class="upload-file__input" name="business_favicon">
                                                                        <div class="upload-file__img">
                                                                            <img onerror="this.src='{{asset('public/assets/admin-module/img/media/upload-file.png')}}'" src="{{asset('storage/app/public/business')}}/{{$data_values->where('key_name','business_favicon')->first()->live_values}}"
                                                                                alt="">
                                                                        </div>
                                                                        <span class="upload-file__edit">
                                                                            <span class="material-icons">edit</span>
                                                                        </span>
                                                                    </div>
                                                                    <p class="opacity-75 max-w220">{{translate('Image format - jpg, png,
                                                                    jpeg, gif Image Size - maximum size 2 MB Image Ratio -
                                                                    1:1')}}</p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-30 d-flex flex-column align-items-center gap-2">
                                                                    <p class="title-color">{{translate('logo')}}</p>
                                                                    <div class="upload-file mb-30 max-w-100">
                                                                        <input type="file"
                                                                                class="upload-file__input"
                                                                                name="business_logo">
                                                                        <div class="upload-file__img upload-file__img_banner ratio-none">
                                                                            <img onerror="this.src='{{asset('public/assets/admin-module/img/media/banner-upload-file.png')}}'"
                                                                                src="{{asset('storage/app/public/business')}}/{{$data_values->where('key_name','business_logo')->first()->live_values}}"
                                                                                alt="">
                                                                        </div>
                                                                        <span class="upload-file__edit">
                                                                            <span class="material-icons">edit</span>
                                                                        </span>
                                                                    </div>
                                                                    <p class="opacity-75 max-w220">{{translate('Image format - jpg, png, jpeg, gif Image Size - maximum size 2 MB Image Ratio - 3:1')}}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 col-12 mb-30">
                                                        @php($country_code=$data_values->where('key_name','country_code')->first()->live_values)
                                                        <select class="js-select theme-input-style w-100"
                                                                name="country_code">
                                                            <option value="0" selected disabled>{{translate('---Select_Country---')}}</option>
                                                            @foreach(COUNTRIES as $country)
                                                                <option
                                                                    value="{{$country['code']}}" {{$country_code==$country['code']?'selected':''}}>
                                                                    {{$country['name']}}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        @php($currency_code=$data_values->where('key_name','currency_code')->first()->live_values)
                                                        <select class="js-select theme-input-style w-100"
                                                                name="currency_code">
                                                            <option value="0" selected disabled>{{translate('---Select_Currency---')}}</option>
                                                            @foreach(CURRENCIES as $currency)
                                                                <option
                                                                    value="{{$currency['code']}}" {{$currency_code==$currency['code']?'selected':''}}>
                                                                    {{$currency['name']}} ( {{$currency['symbol']}} )
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        @php($position=$data_values->where('key_name','currency_symbol_position')->first()->live_values)
                                                        <select class="js-select theme-input-style w-100"
                                                                name="currency_symbol_position">
                                                            <option value="0" selected disabled>{{translate('---Select_Corrency_Symbol_Position---')}}</option>
                                                            <option value="right" {{$position=='right'?'selected':''}}>
                                                                {{translate('right')}}
                                                            </option>
                                                            <option value="left" {{$position=='left'?'selected':''}}>
                                                                {{translate('left')}}
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        <div class="form-floating">
                                                            <input type="number" class="form-control"
                                                                   name="currency_decimal_point"
                                                                   min="0"
                                                                   max="10"
                                                                   placeholder="{{translate('ex: 2')}} *"
                                                                   required=""
                                                                   value="{{$data_values->where('key_name','currency_decimal_point')->first()->live_values}}">
                                                            <label>{{translate('decimal_point_after_currency')}}
                                                                *</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        <div class="form-floating">
                                                            <input type="number" class="form-control"
                                                                   name="default_commission"
                                                                   min="0"
                                                                   max="100"
                                                                   step="any"
                                                                   placeholder="{{translate('ex: 2')}} *"
                                                                   required=""
                                                                   value="{{$data_values->where('key_name','default_commission')->first()->live_values}}">
                                                            <label>{{translate('default_commission_for_provider')}} ( %
                                                                )
                                                                *</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        <div class="form-floating">
                                                            <input type="number" class="form-control"
                                                                   name="pagination_limit"
                                                                   placeholder="{{translate('ex: 2')}} *"
                                                                   min="1"
                                                                   required=""
                                                                   value="{{$data_values->where('key_name','pagination_limit')->first()->live_values}}">
                                                            <label>{{translate('pagination_limit')}} *</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        <div class="form-floating">
                                                            <input type="number" class="form-control"
                                                                   name="minimum_withdraw_amount"
                                                                   placeholder="{{translate('ex: 100')}} *"
                                                                   min="1"
                                                                   step="any"
                                                                   required
                                                                   value="{{$data_values->where('key_name','minimum_withdraw_amount')->first()->live_values??''}}">
                                                            <label>{{translate('minimum_withdraw_amount')}} *</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30">
                                                        <div class="form-floating">
                                                            <input type="number" class="form-control"
                                                                   name="maximum_withdraw_amount"
                                                                   placeholder="{{translate('ex: 2000')}} *"
                                                                   min="1"
                                                                   step="any"
                                                                   required
                                                                   value="{{$data_values->where('key_name','maximum_withdraw_amount')->first()->live_values??''}}">
                                                            <label>{{translate('maximum_withdraw_amount')}} *</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30 mt-4">
                                                        @php($time_zone=$data_values->where('key_name','time_zone')->first()->live_values)
                                                        <select class="js-select theme-input-style w-100"
                                                                name="time_zone">
                                                            <option value="0" selected disabled>{{translate('---Select_Time_Zone---')}}</option>
                                                            @foreach(TIME_ZONES as $time)
                                                                <option
                                                                    value="{{$time['tzCode']}}" {{$time_zone==$time['tzCode']?'selected':''}}>
                                                                    {{$time['tzCode']}} UTC {{$time['utc']}}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <!-- Forgot Password Verification Method -->
                                                    <div class="col-md-6 col-12 mb-30">
                                                        <div class="mb-2">{{translate('Forgot Password Verification Method')}}</div>
                                                        @php($method = $data_values->where('key_name','forget_password_verification_method')->first()?->live_values)
                                                        <select class="js-select theme-input-style w-100" name="forget_password_verification_method">
                                                            <option value="" selected disabled>{{translate('---Select_Method---')}}</option>
                                                            <option value="email" {{$method=='email'?'selected':''}}>{{translate('email')}}</option>
                                                            <option value="phone" {{$method=='phone'?'selected':''}}>{{translate('phone')}}</option>
                                                        </select>
                                                    </div>

                                                    <!-- lity for chatting -->
                                                    <div class="col-md-6 col-12 mb-30 gap-3">
                                                        @php($value=$data_values->where('key_name','phone_number_visibility_for_chatting')->first()->live_values??null)
                                                        <div class="border p-3 rounded d-flex justify-content-between">
                                                            <div class="d-flex align-items-center gap-2">{{translate('Phone number visibility for chatting')}}
                                                                <i class="material-icons" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                   title="{{translate('Customers or providers can not see each other phone numbers during chatting')}}"
                                                                >info</i>
                                                            </div>
                                                            <label class="switcher">
                                                                <input class="switcher_input" type="checkbox" name="phone_number_visibility_for_chatting" value="1"
                                                                       {{isset($value) && $value == '1' ? 'checked' : ''}}>
                                                                <span class="switcher_control"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12 mb-30 gap-3">
                                                        @php($value=$data_values->where('key_name','direct_provider_booking')->first()->live_values??null)
                                                        <div class="border p-3 rounded d-flex justify-content-between">
                                                            <div class="d-flex align-items-center gap-2">{{translate('Direct Provider Booking')}}
                                                                <i class="material-icons" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                   title="{{translate('Customers can directly book any provider')}}"
                                                                >info</i>
                                                            </div>
                                                            <label class="switcher">
                                                                <input class="switcher_input" type="checkbox" name="direct_provider_booking" value="1"
                                                                       {{isset($value) && $value == '1' ? 'checked' : ''}}>
                                                                <span class="switcher_control"></span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 mb-30">
                                                        <div class="form-floating">
                                                            <input type="text" class="form-control" name="footer_text"
                                                                   placeholder="{{translate('ex:_all_right_reserved')}} *"
                                                                   required=""
                                                                   value="{{$data_values->where('key_name','footer_text')->first()->live_values}}">
                                                            <label>{{translate('footer_text')}} *</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-30">
                                                        <div class="form-floating">
                                                            <textarea type="text" class="form-control" name="cookies_text"
                                                                   placeholder="{{translate('ex:_al_right_reserved')}} *"
                                                                   required>{{$data_values->where('key_name','cookies_text')->first()->live_values??null}}</textarea>
                                                            <label>{{translate('cookies_text')}} *</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <button type="reset" class="btn btn-secondary">
                                                    {{translate('reset')}}
                                                </button>
                                                <button type="submit" class="btn btn--primary">
                                                    {{translate('update')}}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='service_setup')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='service_setup'?'active show':''}}"
                                 id="business-info">
                                <div class="card">
                                    <div class="card-body p-30">
                                        <div class="row">
                                            <?php
                                            $array = collect([
                                                [
                                                    'key' => 'provider_can_cancel_booking',
                                                    'info_message' => null,
                                                ],
                                                [
                                                    'key' => 'service_man_can_cancel_booking',
                                                    'info_message' => null,
                                                ],
                                                [
                                                    'key' => 'provider_self_registration',
                                                    'info_message' => null,
                                                ],
                                                [
                                                    'key' => 'phone_verification',
                                                    'info_message' => 'During registration & Login Customers have to verify via phone',
                                                ],
                                                [
                                                    'key' => 'email_verification',
                                                    'info_message' => 'During registration & Login Customers have to verify via email',
                                                ],
                                                [
                                                    'key' => 'cash_after_service',
                                                    'info_message' => 'Customer can pay with cash after receiving the service',
                                                ],
                                                [
                                                    'key' => 'digital_payment',
                                                    'info_message' => 'Customer can pay with digital payments',
                                                ],
                                                [
                                                    'key' => 'wallet_payment',
                                                    'info_message' => 'Customer can pay with wallet balance',
                                                ],
                                            ]);
                                            ?>

                                            @foreach($data_values->whereIn('key_name', $array->pluck('key')->toArray())->all() as $key=>$value)
                                                <div class="col-md-6 col-12 mb-30">
                                                    <div class="border p-3 rounded d-flex justify-content-between">
                                                        <div>
                                                            <span class="text-capitalize">{{str_replace('_',' ',$value['key_name'])}}</span>

                                                            @php($item = $array->firstWhere('key', $value['key_name']))
                                                                @if(!is_null($item['info_message']))
                                                                <i class="material-icons px-1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                   title="{{translate($item['info_message'] ?? null)}}"
                                                                >info</i>
                                                            @endif
                                                        </div>
                                                        <label class="switcher">
                                                            <input class="switcher_input" id="{{$value['key_name']}}__switch"
                                                                   onclick="update_action_status('{{$value['key_name']}}',$(this).is(':checked')===true?1:0, 'service_setup')"
                                                                   type="checkbox" {{$value->live_values?'checked':''}}>
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='bidding_system')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='bidding_system'?'active show':''}}"
                                 id="business-info">
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form action="javascript:void(0)" method="POST" id="bidding-system-update-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-4">
                                                <!-- bidding status -->
                                                <div class="col-md-6 col-12">
                                                    <div class="border p-3 rounded d-flex justify-content-between">
                                                        <div>
                                                            <span>{{translate('Bidding Option')}}</span>
                                                            <i class="material-icons px-1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                               title="{{translate('Users can use the bid feature while the option is enabled')}}"
                                                            >info</i>
                                                        </div>
                                                        <label class="switcher">
                                                            <input class="switcher_input" type="checkbox" name="bidding_status" value="1"
                                                                {{$data_values->where('key_name', 'bidding_status')->first()?->live_values ?'checked':''}}>
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <!-- bidding post validation -->
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating">
                                                        <input class="form-control" name="bidding_post_validity"
                                                               placeholder="{{translate('Post Validation (days)')}} *"
                                                               type="number" required
                                                        value="{{$data_values->where('key_name', 'bidding_post_validity')->first()->live_values ?? ''}}">
                                                        <label>{{translate('Post Validation (days)')}} *</label>
                                                    </div>
                                                </div>

                                                <!-- See other provider offers -->
                                                <div class="col-md-6 col-12">
                                                    <div class="border p-3 rounded d-flex justify-content-between">
                                                        <div>
                                                            <span>{{translate('See Other Provider Offers')}}</span>
                                                            <i class="material-icons px-1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                               title="{{translate('Provider can see the bid offers of other provider')}}"
                                                            >info</i>
                                                        </div>
                                                        <label class="switcher">
                                                            <input class="switcher_input" type="checkbox" name="bid_offers_visibility_for_providers" value="1"
                                                                {{$data_values->where('key_name', 'bid_offers_visibility_for_providers')->first()?->live_values ?'checked':''}}>
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="d-flex gap-2 justify-content-end">
                                                <button type="reset" class="btn btn-secondary">
                                                    {{translate('reset')}}
                                                </button>
                                                <button type="submit" class="btn btn--primary">
                                                    {{translate('update')}}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='promotional_setup')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='promotional_setup'?'active show':''}}">
                                <div class="row">
                                    <!-- Normal Discount -->
                                    @php($data = $data_values->where('key_name', 'discount_cost_bearer')->first()->live_values ?? null)
                                    <div class="col-lg-6 col-12 mb-30">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="page-title d-flex align-items-center gap-2">
                                                    <i class="material-icons">redeem</i>
                                                    {{translate('Normal_Discount')}}
                                                </h4>
                                            </div>
                                            <div class="card-body p-30">
                                                <h5 class="pb-4">{{translate('Discount_Cost_Bearer')}}</h5>
                                                <form action="{{route('admin.business-settings.set-promotion-setup')}}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-3">
                                                        <div class="d-flex align-items-start flex-column gap-3 gap-xl-4 mb-30 flex-grow-1">
                                                            <div class="custom-radio">
                                                                <input type="radio" id="admin-select__discount" name="bearer" value="admin" {{isset($data) && $data['bearer'] == 'admin' ? 'checked' : ''}}>
                                                                <label for="admin-select__discount">{{translate('Admin')}}</label>
                                                            </div>
                                                            <div class="custom-radio">
                                                                <input type="radio" id="provider-select__discount" name="bearer" value="provider" {{isset($data) && $data['bearer'] == 'provider' ? 'checked' : ''}}>
                                                                <label for="provider-select__discount">{{translate('Provider')}}</label>
                                                            </div>
                                                            <div class="custom-radio">
                                                                <input type="radio" id="both-select__discount" name="bearer" value="both" {{isset($data) && $data['bearer'] == 'both' ? 'checked' : ''}}>
                                                                <label for="both-select__discount">{{translate('Both')}}</label>
                                                            </div>
                                                        </div>

                                                        <div class="flex-grow-1 {{isset($data) && ($data['bearer'] != 'admin' && $data['bearer'] != 'provider') ? '' : 'd-none'}}" id="bearer-section__discount">
                                                            <div class="mb-30">
                                                                <div class="form-floating">
                                                                    <input type="number" class="form-control"
                                                                           name="admin_percentage"
                                                                           id="admin_percentage__discount"
                                                                           placeholder="{{translate('Admin_Percentage')}} (%)"
                                                                           value="{{!is_null($data) ? $data['admin_percentage'] : ''}}"
                                                                           min="0" max="100" step="any">
                                                                    <label>{{translate('Admin_Percentage')}} (%)</label>
                                                                </div>
                                                            </div>
                                                            <div class="mb-30">
                                                                <div class="form-floating">
                                                                    <input type="number" class="form-control"
                                                                           name="provider_percentage"
                                                                           id="provider_percentage__discount"
                                                                           placeholder="{{translate('Provider_Percentage')}} (%)"
                                                                           value="{{!is_null($data) ? $data['provider_percentage'] : ''}}"
                                                                           min="0" max="100" step="any">
                                                                    <label>{{translate('Provider_Percentage')}} (%)</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="text" name="type" value="discount" class="d-none">

                                                    <div class="d-flex justify-content-end gap-20">
                                                        <button type="submit" class="btn btn--primary demo_check">
                                                            {{translate('update')}}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Campaign Discount -->
                                    @php($data = $data_values->where('key_name', 'campaign_cost_bearer')->first()->live_values ?? null)
                                    <div class="col-lg-6 col-12 mb-30">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="page-title d-flex align-items-center gap-2">
                                                    <i class="material-icons">campaign</i>
                                                    {{translate('Campaign_Discount')}}
                                                </h4>
                                            </div>
                                            <div class="card-body p-30">
                                                <h5 class="pb-4">{{translate('Campaign_Cost_Bearer')}}</h5>
                                                <form action="{{route('admin.business-settings.set-promotion-setup')}}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-3">
                                                        <div class="d-flex align-items-start flex-column gap-3 gap-xl-4 mb-30 flex-grow-1">
                                                            <div class="custom-radio">
                                                                <input type="radio" id="admin-select__campaign" name="bearer" value="admin" {{isset($data) && $data['bearer'] == 'admin' ? 'checked' : ''}}>
                                                                <label for="admin-select__campaign">{{translate('Admin')}}</label>
                                                            </div>
                                                            <div class="custom-radio">
                                                                <input type="radio" id="provider-select__campaign" name="bearer" value="provider" {{isset($data) && $data['bearer'] == 'provider' ? 'checked' : ''}}>
                                                                <label for="provider-select__campaign">{{translate('Provider')}}</label>
                                                            </div>
                                                            <div class="custom-radio">
                                                                <input type="radio" id="both-select__campaign" name="bearer" value="both" {{isset($data) && $data['bearer'] == 'both' ? 'checked' : ''}}>
                                                                <label for="both-select__campaign">{{translate('Both')}}</label>
                                                            </div>
                                                        </div>

                                                        <div class="flex-grow-1 {{isset($data) && ($data['bearer'] != 'admin' && $data['bearer'] != 'provider') ? '' : 'd-none'}}" id="bearer-section__campaign">
                                                            <div class="mb-30">
                                                                <div class="form-floating">
                                                                    <input type="number" class="form-control"
                                                                           name="admin_percentage"
                                                                           id="admin_percentage__campaign"
                                                                           placeholder="{{translate('Admin_Percentage')}} (%)"
                                                                           value="{{!is_null($data) ? $data['admin_percentage'] : ''}}"
                                                                           min="0" max="100" step="any">
                                                                    <label>{{translate('Admin_Percentage')}} (%)</label>
                                                                </div>
                                                            </div>
                                                            <div class="mb-30">
                                                                <div class="form-floating">
                                                                    <input type="number" class="form-control"
                                                                           name="provider_percentage"
                                                                           id="provider_percentage__campaign"
                                                                           placeholder="{{translate('Provider_Percentage')}} (%)"
                                                                           value="{{!is_null($data) ? $data['provider_percentage'] : ''}}"
                                                                           min="0" max="100" step="any">
                                                                    <label>{{translate('Provider_Percentage')}} (%)</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="text" name="type" value="campaign" class="d-none">

                                                    <div class="d-flex justify-content-end gap-20">
                                                        <button type="submit" class="btn btn--primary demo_check">
                                                            {{translate('update')}}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Coupon Discount -->
                                    @php($data = $data_values->where('key_name', 'coupon_cost_bearer')->first()->live_values ?? null)
                                    <div class="col-lg-6 col-12 mb-30">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="page-title d-flex align-items-center gap-2">
                                                    <i class="material-icons">sell</i>
                                                    {{translate('Coupon_Discount')}}
                                                </h4>
                                            </div>
                                            <div class="card-body p-30">
                                                <h5 class="pb-4">{{translate('Coupon_Cost_Bearer')}}</h5>
                                                <form action="{{route('admin.business-settings.set-promotion-setup')}}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-3">
                                                        <div class="d-flex align-items-start flex-column gap-3 gap-xl-4 mb-30 flex-grow-1">
                                                            <div class="custom-radio">
                                                                <input type="radio" id="admin-select__coupon" name="bearer" value="admin" {{isset($data) && $data['bearer'] == 'admin' ? 'checked' : ''}}>
                                                                <label for="admin-select__coupon">{{translate('Admin')}}</label>
                                                            </div>
                                                            <div class="custom-radio">
                                                                <input type="radio" id="provider-select__coupon" name="bearer" value="provider" {{isset($data) && $data['bearer'] == 'provider' ? 'checked' : ''}}>
                                                                <label for="provider-select__coupon">{{translate('Provider')}}</label>
                                                            </div>
                                                            <div class="custom-radio">
                                                                <input type="radio" id="both-select__coupon" name="bearer" value="both" {{isset($data) && $data['bearer'] == 'both' ? 'checked' : ''}}>
                                                                <label for="both-select__coupon">{{translate('Both')}}</label>
                                                            </div>
                                                        </div>

                                                        <div class="flex-grow-1 {{isset($data) && ($data['bearer'] != 'admin' && $data['bearer'] != 'provider') ? '' : 'd-none'}}" id="bearer-section__coupon">
                                                            <div class="mb-30">
                                                                <div class="form-floating">
                                                                    <input type="number" class="form-control"
                                                                           name="admin_percentage"
                                                                           id="admin_percentage__coupon"
                                                                           placeholder="{{translate('Admin_Percentage')}} (%)"
                                                                           value="{{!is_null($data) ? $data['admin_percentage'] : ''}}"
                                                                           min="0" max="100" step="any">
                                                                    <label>{{translate('Admin_Percentage')}} (%)</label>
                                                                </div>
                                                            </div>
                                                            <div class="mb-30">
                                                                <div class="form-floating">
                                                                    <input type="number" class="form-control"
                                                                           name="provider_percentage"
                                                                           id="provider_percentage__coupon"
                                                                           placeholder="{{translate('Provider_Percentage')}} (%)"
                                                                           value="{{!is_null($data) ? $data['provider_percentage'] : ''}}"
                                                                           min="0" max="100" step="any">
                                                                    <label>{{translate('Provider_Percentage')}} (%)</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="text" name="type" value="coupon" class="d-none">

                                                    <div class="d-flex justify-content-end gap-20">
                                                        <button type="submit" class="btn btn--primary demo_check">
                                                            {{translate('update')}}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='otp_login_setup')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='otp_login_setup'?'active show':''}}"
                                 id="business-info">
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form action="{{route('admin.business-settings.set-otp-login-information')}}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-4">
                                                <!-- temporary_login_block_time -->
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating">
                                                        <input class="form-control remove-spin" name="temporary_login_block_time"
                                                               placeholder="{{translate('Temporary Login Block Time')}} *"
                                                               type="number" min="0" required
                                                               value="{{$data_values->where('key_name', 'temporary_login_block_time')->first()->live_values ?? ''}}"
                                                               >
                                                        <label>{{translate('Temporary Login Block Time')}}* <small class="text-danger">({{translate('In Second')}})</small>
                                                        </label>

                                                        <span class="material-icons" data-bs-toggle="tooltip"
                                                               title="{{ translate('Temporary login block time refers to a security measure implemented by systems to restrict access for a specified period of time for wrong Password submission.') }}">info</span>
                                                    </div>
                                                </div>

                                                <!-- maximum_hit_count -->
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating">
                                                        <input class="form-control remove-spin" name="maximum_login_hit"
                                                               placeholder="{{translate('Maximum Login Hit')}} *"
                                                               type="number" min="0" required
                                                               value="{{$data_values->where('key_name', 'maximum_login_hit')->first()->live_values ?? ''}}"
                                                               >
                                                        <label>{{translate('Maximum Login Hit')}}* </label>

                                                        <span class="material-icons" data-bs-toggle="tooltip"
                                                               title="{{ translate('The maximum login hit is a measure of how many times a user can submit password within a time.') }}">info</span>
                                                    </div>
                                                </div>

                                                <!-- temporary_otp_block_time -->
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating">
                                                        <input class="form-control remove-spin" name="temporary_otp_block_time"
                                                               placeholder="{{translate('Temporary OTP Block Time')}} *"
                                                               type="number" min="0" required
                                                               value="{{$data_values->where('key_name', 'temporary_otp_block_time')->first()->live_values ?? ''}}"
                                                               >
                                                        <label>{{translate('Temporary OTP Block Time')}}* <small class="text-danger">({{translate('In Second')}})</small></label>

                                                        <span class="material-icons" data-bs-toggle="tooltip"
                                                               title="{{ translate('Temporary OTP block time refers to a security measure implemented by systems to restrict access to OTP service for a specified period of time for wrong OTP submission.') }}">info</span>
                                                    </div>
                                                </div>

                                                <!-- maximum_otp_hit -->
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating">
                                                        <input class="form-control remove-spin" name="maximum_otp_hit"
                                                               placeholder="{{translate('Maximum OTP Hit')}} *"
                                                               type="number" min="0" required
                                                               value="{{$data_values->where('key_name', 'maximum_otp_hit')->first()->live_values ?? ''}}">
                                                        <label>{{translate('Maximum OTP Hit')}} *</label>

                                                        <span class="material-icons" data-bs-toggle="tooltip"
                                                               title="{{ translate('The maximum OTP hit is a measure of how many times a specific one-time password has been generated and used within a time.') }}">info</span>
                                                    </div>
                                                </div>

                                                <!-- otp_resend_time -->
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating">
                                                        <input class="form-control remove-spin" name="otp_resend_time"
                                                               placeholder="{{translate('OTP Resend Time')}} *"
                                                               type="number" min="0" required
                                                               value="{{$data_values->where('key_name', 'otp_resend_time')->first()->live_values ?? ''}}"
                                                               >

                                                        <label>{{translate('OTP Resend Time')}}* <small class="text-danger">({{translate('In Second')}})</small></label>

                                                        <span class="material-icons" data-bs-toggle="tooltip"
                                                               title="{{ translate('If the user fails to get the OTP within a certain time, user can request a resend.') }}">info</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 justify-content-end mt-4">
                                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}
                                                </button>
                                                <button type="submit" class="btn btn--primary">{{translate('update')}}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                @endif
                    <!-- End Tab Content -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('public/assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.js-select').select2();
        });
    </script>
    <script src="{{asset('public/assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.js"></script>
    <script src="{{asset('public/assets/admin-module')}}/plugins/dataTables/dataTables.select.min.js"></script>

    <script>
        $('#business-info-update-form').on('submit', function (event) {
            event.preventDefault();

            var form = $('#business-info-update-form')[0];
            var formData = new FormData(form);
            // Set header if need any otherwise remove setup part
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{route('admin.business-settings.set-business-information')}}",
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                success: function (response) {
                    toastr.success('{{translate('successfully_updated')}}');
                },
                error: function (jqXHR, exception) {
                    toastr.error(jqXHR.responseJSON.message);
                    setTimeout(location.reload.bind(location), 1000);
                }
            });
        });

        $('#bidding-system-update-form').on('submit', function (event) {
            event.preventDefault();

            var form = $('#bidding-system-update-form')[0];
            var formData = new FormData(form);
            // Set header if need any otherwise remove setup part
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{route('admin.business-settings.set-bidding-system')}}",
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                success: function (response) {
                    toastr.success('{{translate('successfully_updated')}}');
                },
                error: function (jqXHR, exception) {
                    toastr.error(jqXHR.responseJSON.message);
                    setTimeout(location.reload.bind(location), 1000);
                }
            });
        });

        function update_action_status(key_name, value, settings_type, will_reload = false) {
            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: '{{translate('want_to_update_status')}}',
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
                        url: "{{route('admin.business-settings.update-action-status')}}",
                        data: {
                            key: key_name,
                            value: value,
                            settings_type: settings_type,
                        },
                        type: 'put',
                        success: function (response) {
                            toastr.success('{{translate('successfully_updated')}}');

                            if(will_reload) {
                                setTimeout(() => {
                                    document.location.reload();
                                }, 3000);
                            }
                        },
                        error: function () {

                        }
                    });
                }
            })
        }
    </script>

    <script>
        $(window).on('load', function() {
            //DISCOUNT SECTION
            $("#admin-select__discount, #provider-select__discount").on('click', function (e) {
                $("#bearer-section__discount").addClass('d-none');
            })

            $("#both-select__discount").on('click', function (e) {
                $("#bearer-section__discount").removeClass('d-none');
            })

            $( "#admin_percentage__discount" ).keyup(function(e) {
                if(this.value >=0 && this.value <= 100) {
                    $( "#provider_percentage__discount" ).val( (100-this.value) );
                }
            });

            $( "#provider_percentage__discount" ).keyup(function(e) {
                if(this.value >=0 && this.value <= 100) {
                    $( "#admin_percentage__discount" ).val( (100-this.value) );
                }
            });

            //CAMPAIGN SECTION
            $("#admin-select__campaign, #provider-select__campaign").on('click', function (e) {
                $("#bearer-section__campaign").addClass('d-none');
            })

            $("#both-select__campaign").on('click', function (e) {
                $("#bearer-section__campaign").removeClass('d-none');
            })

            $( "#admin_percentage__campaign" ).keyup(function(e) {
                if(this.value >=0 && this.value <= 100) {
                    $( "#provider_percentage__campaign" ).val( (100-this.value) );
                }
            });

            $( "#provider_percentage__campaign" ).keyup(function(e) {
                if(this.value >=0 && this.value <= 100) {
                    $( "#admin_percentage__campaign" ).val( (100-this.value) );
                }
            });

            //COUPON SECTION
            $("#admin-select__coupon, #provider-select__coupon").on('click', function (e) {
                $("#bearer-section__coupon").addClass('d-none');
            })

            $("#both-select__coupon").on('click', function (e) {
                $("#bearer-section__coupon").removeClass('d-none');
            })

            $( "#admin_percentage__coupon" ).keyup(function(e) {
                if(this.value >=0 && this.value <= 100) {
                    $( "#provider_percentage__coupon" ).val( (100-this.value) );
                }
            });

            $( "#provider_percentage__coupon" ).keyup(function(e) {
                if(this.value >=0 && this.value <= 100) {
                    $( "#admin_percentage__coupon" ).val( (100-this.value) );
                }
            });
        })
    </script>

    <script>

        $(document).ready(function($) {
            $("#phone_verification__switch").on('change', function () {
                const phoneVerification = $(this).is(':checked') === true ? 1 : 0;

                if(phoneVerification === 1) {
                    $("#email_verification__switch").prop('checked', false);
                }
            });

            $("#email_verification__switch").on('change', function () {
                const emailVerification = $(this).is(':checked') === true ? 1 : 0;

                if(emailVerification === 1) {
                    $("#phone_verification__switch").prop('checked', false);
                }
            });
        });
    </script>
@endpush
