<!-- Service Update Modal -->
<div class="modal fade" id="serviceUpdateModal--{{$booking['id']}}" tabindex="-1"
     aria-labelledby="serviceUpdateModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h3>{{translate('Update Booking')}}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body m-4">
                <!-- Add Service -->
                <form action="{{route('admin.booking.service.add')}}" method="post">
                    <div class="row">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <select class="theme-input-style w-100" id="category_selector__select"
                                        name="category_id">
                                    <option value="" selected disabled>{{translate('Select category')}}</option>
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <select class="theme-input-style w-100"
                                        id="sub_category_selector__select" name="sub_category_id">
                                    <option value="" selected disabled>{{translate('Select Sub Category')}}</option>
                                    @foreach($sub_categories as $sub_category)
                                        <option value="{{$sub_category->id}}">{{$sub_category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <select class="theme-input-style w-100" id="service_selector__select"
                                        name="service_id">
                                    <option value="" selected disabled>{{translate('Select Service')}}</option>
                                    @foreach($services as $service)
                                        <option value="{{$service->id}}">{{$service->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <select class="theme-input-style w-100"
                                        id="service_variation_selector__select" name="variation_id">
                                    <option selected disabled>{{translate('Select Service Variation')}}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <div class="form-floating">
                                    <input type="number" class="form-control" name="service_quantity"
                                           placeholder="{{translate('service_quantity')}}" min="1" required>
                                    <label>{{translate('service_quantity')}}</label>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="booking_id" value="{{$booking->id}}">
                        <div class="col-12 d-flex justify-content-end mb-10">
                            <button type="submit" class="btn btn--primary">{{translate('Add Service')}}</button>
                        </div>
                    </div>
                </form>

                <!-- Service table -->
                <div class="table-responsive border-bottom">
                    <table class="table text-nowrap align-middle mb-0">
                        <thead>
                        <tr>
                            <th class="ps-lg-3">{{translate('Service')}}</th>
                            <th>{{translate('Price')}}</th>
                            <th>{{translate('Qty')}}</th>
                            <th>{{translate('Discount')}}</th>
                            <th>{{translate('Total')}}</th>
                            <th>{{translate('Action')}}</th>
                        </tr>
                        </thead>

                        <tbody>
                        @php($sub_total=0)
                        @foreach($booking->detail as $key=>$detail)
                            <tr id="service-row--{{$key}}">
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
                                <td>{{with_currency_symbol($detail->total_cost)}}</td>
                                <td>
                                    @if($booking->detail->count() > 1)
                                        <span style="cursor: pointer" class="material-icons text-danger"
                                              onclick="removeService('{{$booking->id}}', '{{$detail->id}}', '{{$detail->service->id}}', '{{$detail?->variant_key}}', '{{$booking->zone_id}}')">delete</span>
                                    @else
                                        <span class="material-icons text-danger disabled">delete</span>
                                    @endif
                                </td>
                            </tr>
                            @php($sub_total += $detail->service_cost*$detail->quantity)
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- End table -->

            </div>
            <div class="modal-footer d-flex justify-content-end gap-3 border-0 pt-0 pb-4">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal" aria-label="Close">
                    {{translate('Cancel')}}</button>
            </div>
        </div>
    </div>
</div>
