<?php

namespace Modules\BookingModule\Http\Controllers\Web\Provider;

use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingScheduleHistory;
use Illuminate\Http\RedirectResponse;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{

    private Booking $booking;
    private BookingStatusHistory $booking_status_history;
    private BookingScheduleHistory $booking_schedule_history;
    private $subscribed_sub_categories;
    private Category $category;
    private Zone $zone;
    private Serviceman $serviceman;
    private Provider $provider;
    private SubscribedService $subscribed_service;
    private User $user;
    private UserAddress $user_address;

    public function __construct(Booking $booking, BookingStatusHistory $booking_status_history, BookingScheduleHistory $booking_schedule_history, SubscribedService $subscribedService, Category $category, Zone $zone, Serviceman $serviceman, Provider $provider, SubscribedService $subscribed_service, User $user, UserAddress $user_address)
    {
        $this->booking = $booking;
        $this->booking_status_history = $booking_status_history;
        $this->booking_schedule_history = $booking_schedule_history;
        $this->category = $category;
        $this->zone = $zone;
        $this->serviceman = $serviceman;
        $this->provider = $provider;
        $this->subscribed_service = $subscribed_service;
        $this->user = $user;
        $this->user_address = $user_address;
        try {
            $this->subscribed_sub_categories = $subscribedService->where(['provider_id' => auth('api')->user()->provider->id])
                ->where(['is_subscribed' => 1])->pluck('sub_category_id')->toArray();
        } catch (\Exception $exception) {
            $this->subscribed_sub_categories = $subscribedService->pluck('sub_category_id')->toArray();
        }
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        $request->validate([
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
        ]);

        $query_param = [];
        $filter_counter = 0;

        if ($request->has('category_ids')) {
            $category_ids = $request['category_ids'];
            $query_param['category_ids'] = $category_ids;
            $filter_counter += count($category_ids);
        }

        if ($request->has('sub_category_ids')) {
            $sub_category_ids = $request['sub_category_ids'];
            $query_param['sub_category_ids'] = $sub_category_ids;
            $filter_counter += count($sub_category_ids);
        }

        if ($request->has('start_date')) {
            $start_date = $request['start_date'];
            $query_param['start_date'] = $start_date;
            if (!is_null($request['start_date'])) $filter_counter++;
        } else {
            $query_param['start_date'] = null;
        }

        if ($request->has('end_date')) {
            $end_date = $request['end_date'];
            $query_param['end_date'] = $end_date;
            if (!is_null($request['end_date'])) $filter_counter++;
        } else {
            $query_param['end_date'] = null;
        }

        if ($request->has('search')) {
            $search = $request['search'];
            $query_param['search'] = $search;
        }

        if ($request->has('booking_status')) {
            $booking_status = $request['booking_status'];
            $query_param['booking_status'] = $booking_status;
        } else {
            $query_param['booking_status'] = 'pending';
        }

        $sub_category_ids = $this->subscribed_service->where('provider_id', $request->user()->provider->id)->ofSubscription(1)->pluck('sub_category_id')->toArray();

        $bookings = $this->booking->with(['customer'])
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when(!in_array($request['booking_status'], ['pending', 'all']), function ($query) use ($request) {
                $query->ofBookingStatus($request['booking_status'])
                    ->where('provider_id', $request->user()->provider->id);
            })
            ->when(in_array($request['booking_status'], ['pending', 'all']), function ($query) use ($request, $sub_category_ids) {
                $query->whereIn('sub_category_id', $sub_category_ids)->where('zone_id', $request->user()->provider->zone_id);
            })
            ->when($request['booking_status'] == 'all', function ($query) use ($request) {
                $query->where('provider_id', $request->user()->provider->id);
            })->when($request['booking_status'] == 'pending', function ($query) use ($request) {
                $query->ofBookingStatus($request['booking_status'])->whereIn('sub_category_id', $this->subscribed_sub_categories);
            })->when($query_param['start_date'] != null && $query_param['end_date'] != null, function ($query) use ($request) {
                if ($request['start_date'] == $request['end_date']) {
                    $query->whereDate('created_at', Carbon::parse($request['start_date'])->startOfDay());
                } else {
                    $query->whereBetween('created_at', [Carbon::parse($request['start_date'])->startOfDay(), Carbon::parse($request['end_date'])->endOfDay()]);
                }
            })->when($request->has('sub_category_ids'), function ($query) use ($request) {
                $query->whereIn('sub_category_id', $request['sub_category_ids']);
            })->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })->latest()->paginate(pagination_limit())->appends($query_param);

        //for filter
        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();
        $sub_categories = $this->category->select('id', 'parent_id', 'name')->where('position', 2)->get();

        return view('bookingmodule::provider.booking.list', compact('bookings', 'categories', 'sub_categories', 'query_param', 'filter_counter'));
    }

    /**
     * Display a listing of the resource.
     * @return void
     */
    public function check_booking($id)
    {
        $this->booking->where('id', $id)->whereIn('sub_category_id', $this->subscribed_sub_categories)
            ->where('is_checked', 0)->update(['is_checked' => 1]); //update the unseen bookings
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function details($id, Request $request)
    {
        Validator::make($request->all(), [
            'web_page' => 'required|in:details,status',
        ]);

        $web_page = $request->has('web_page') ? $request['web_page'] : 'details';
        $booking = $this->booking->with(['detail.service'=>function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'service_address', 'serviceman', 'service_address', 'status_histories.user'])->find($id);

        if ($booking['booking_status'] != 'pending' && $booking['provider_id'] != $request->user()->provider->id) {
            Toastr::error(ACCESS_DENIED['message']);
            return redirect(route('provider.booking.list'));
        }

        if ($request->web_page == 'details') {
            $servicemen = $this->serviceman->with(['user'])
                ->whereHas('user', function($q){
                    $q->ofStatus(1);
                })
                ->where('provider_id', $this->provider->where('user_id', $request->user()->id)->first()->id)
                ->latest()
                ->get();

            //service address
            $customer_addresses = $this->user_address->where(['user_id' => $booking?->customer?->id])->get();

            return view('bookingmodule::provider.booking.details', compact('booking', 'servicemen', 'web_page', 'customer_addresses'));

        } elseif ($request->web_page == 'status') {
            return view('bookingmodule::provider.booking.status', compact('booking', 'web_page'));
        }
    }

    /**
     * Display a listing of the resource.
     * @param $booking_id
     * @param Request $request
     * @return JsonResponse
     */
    public function status_update($booking_id, Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'booking_status' => 'required|in:' . implode(',', array_column(BOOKING_STATUSES, 'key')),
        ]);

        $booking = $this->booking->where('id', $booking_id)->where(function ($query) use ($request) {
            return $query->where('provider_id', $request->user()->provider->id)->orWhereNull('provider_id');
        })->first();

        if (isset($booking)) {
            $booking->booking_status = $request['booking_status'];
            $booking->provider_id = $request->user()->provider->id;

            $booking_status_history = $this->booking_status_history;
            $booking_status_history->booking_id = $booking_id;
            $booking_status_history->changed_by = $request->user()->id;
            $booking_status_history->booking_status = $request['booking_status'];

            if ($booking->isDirty('booking_status')) {
                DB::transaction(function () use ($booking_status_history, $booking) {
                    $booking->save();
                    $booking_status_history->save();
                });

                self::check_booking($booking->id);

                return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
            }
            return response()->json(NO_CHANGES_FOUND, 200);
        }
        return response()->json(DEFAULT_204, 200);
    }

    /**
     * Display a listing of the resource.
     * @param $booking_id
     * @param Request $request
     * @return JsonResponse
     */
    public function payment_update($booking_id, Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'payment_status' => 'required|in:paid,unpaid',
        ]);

        $booking = $this->booking->where('id', $booking_id)->where(function ($query) use ($request) {
            return $query->where('provider_id', $request->user()->provider->id)->orWhereNull('provider_id');
        })->first();

        if (isset($booking)) {
            $booking->is_paid = $request->payment_status == 'paid' ? 1 : 0;

            if ($booking->isDirty('is_paid')) {
                $booking->save();
                return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
            }
            return response()->json(NO_CHANGES_FOUND, 200);
        }
        return response()->json(DEFAULT_204, 200);
    }

    /**
     * Display a listing of the resource.
     * @param $booking_id
     * @param Request $request
     * @return JsonResponse
     */
    public function schedule_upadte($booking_id, Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'service_schedule' => 'required',
        ]);

        $booking = $this->booking->where('id', $booking_id)->where(function ($query) use ($request) {
            return $query->where('provider_id', $request->user()->provider->id)->orWhereNull('provider_id');
        })->first();

        if (isset($booking)) {
            $booking->service_schedule = Carbon::parse($request->service_schedule)->toDateTimeString();

            //history
            $booking_schedule_history = $this->booking_schedule_history;
            $booking_schedule_history->booking_id = $booking_id;
            $booking_schedule_history->changed_by = $request->user()->id;
            $booking_schedule_history->schedule = $request['service_schedule'];

            if ($booking->isDirty('service_schedule')) {
                $booking->save();
                $booking_schedule_history->save();
                return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
            }
            return response()->json(NO_CHANGES_FOUND, 200);
        }
        return response()->json(DEFAULT_204, 200);
    }

    /**
     * Display a listing of the resource.
     * @param $booking_id
     * @param Request $request
     * @return JsonResponse
     */
    public function serviceman_update($booking_id, Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'serviceman_id' => 'required|uuid',
        ]);

        $booking = $this->booking->where('id', $booking_id)->where(function ($query) use ($request) {
            return $query->where('provider_id', $request->user()->provider->id)->orWhereNull('provider_id');
        })->first();

        if (isset($booking)) {
            $booking->serviceman_id = $request->serviceman_id;

            if ($booking->isDirty('serviceman_id')) {
                $booking->save();
                return response()->json(DEFAULT_STATUS_UPDATE_200, 200);
            }
            return response()->json(NO_CHANGES_FOUND, 200);
        }
        return response()->json(DEFAULT_204, 200);
    }

    /**
     * Display a listing of the resource.
     * @param $booking_id
     * @param Request $request
     * @return RedirectResponse
     */
    public function service_address_update($booking_id, Request $request): RedirectResponse
    {
        Validator::make($request->all(), [
            'service_address_id' => 'required|uuid',
        ]);

        $booking = $this->booking->where('id', $booking_id)->first();

        if (isset($booking)) {
            $booking->service_address_id = $request->service_address_id;

            if ($booking->isDirty('service_address_id')) {
                $booking->save();

                Toastr::success(DEFAULT_STATUS_UPDATE_200['message']);
                return back();
            }
            Toastr::info(NO_CHANGES_FOUND['message']);
            return back();
        }
        Toastr::success(DEFAULT_204['message']);
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return string|StreamedResponse
     */
    public function download(Request $request): string|StreamedResponse
    {
        $request->validate([
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
        ]);

        $query_param = [];

        if ($request->has('category_ids')) {
            $category_ids = $request['category_ids'];
            $query_param['category_ids'] = $category_ids;
        }

        if ($request->has('sub_category_ids')) {
            $sub_category_ids = $request['sub_category_ids'];
            $query_param['sub_category_ids'] = $sub_category_ids;
        }

        if ($request->has('start_date')) {
            $start_date = $request['start_date'];
            $query_param['start_date'] = $start_date;
        } else {
            $query_param['start_date'] = null;
        }

        if ($request->has('end_date')) {
            $end_date = $request['end_date'];
            $query_param['end_date'] = $end_date;
        } else {
            $query_param['end_date'] = null;
        }

        if ($request->has('search')) {
            $search = $request['search'];
            $query_param['search'] = $search;
        }

        if ($request->has('booking_status')) {
            $booking_status = $request['booking_status'];
            $query_param['booking_status'] = $booking_status;
        } else {
            $query_param['booking_status'] = 'pending';
        }

        $sub_category_ids = $this->subscribed_service->where('provider_id', $request->user()->provider->id)->ofSubscription(1)->pluck('sub_category_id')->toArray();

        $items = $this->booking->with(['customer'])
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when(!in_array($request['booking_status'], ['pending', 'all']), function ($query) use ($request) {
                $query->ofBookingStatus($request['booking_status'])
                    ->where('provider_id', $request->user()->provider->id);
            })
            ->when(in_array($request['booking_status'], ['pending', 'all']), function ($query) use ($request, $sub_category_ids) {
                $query->whereIn('sub_category_id', $sub_category_ids);
            })
            ->when($request['booking_status'] == 'all', function ($query) use ($request) {
                $query->where('provider_id', $request->user()->provider->id);
            })->when($request['booking_status'] == 'pending', function ($query) use ($request) {
                $query->ofBookingStatus($request['booking_status'])->whereIn('sub_category_id', $this->subscribed_sub_categories);
            })->when($query_param['start_date'] != null && $query_param['end_date'] != null, function ($query) use ($request) {
                $query->whereBetween('created_at', [$request['start_date'], $request['end_date']]);
            })->when($request->has('sub_category_ids'), function ($query) use ($request) {
                $query->whereIn('sub_category_id', $request['sub_category_ids']);
            })->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })->latest()->get();


        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }


    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable
     */
    public function invoice($id, Request $request): Renderable
    {
        $booking = $this->booking->with(['detail.service'=>function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'service_address', 'serviceman', 'service_address', 'status_histories.user'])->find($id);
        return view('bookingmodule::provider.booking.invoice', compact('booking'));
    }
}
