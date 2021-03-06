<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\v1\ApiController;
use App\Repositories\Booking\BookingRepository;
use Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingsController extends ApiController
{
    private $booking;
    public function __construct(BookingRepository $booking)
    {
        $this->booking = $booking;
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slot_id' => 'required| integer |exists:slots,id',
            'game_id' => 'required| integer |exists:games,id',
            'user_id' => 'required| integer |exists:users,id',
            'booking_date' => 'required',
            'players' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->error(400, $validator->messages());
        }
        $requestdata = $request->all();
        if (!$this->booking->checkSlot($requestdata['slot_id'], $requestdata['game_id'], $requestdata['booking_date'])) {
            return $this->error(422, 'Selected Slot is not free');
        }
        $users = [];
        $users = explode(',', $requestdata['players']);
        $time = strtotime('18:59');
        $booking_hour = strtotime(date('H:i', $requestdata['booking_date']));
        if ($time > $booking_hour) {
            if (count($this->booking->findByUserId($requestdata['user_id'])) == 2) {
                return $this->error(401, 'Your All slots are booked');
            }
            if ($user = $this->booking->findMemberStatus($users)) {
                return $this->error(403, self::names($user) . "These user already booked all slot");
            }
        }
        $users[] = $requestdata['user_id'];
        $requestdata['players'] = json_encode($users);
        if ($booking = $this->booking->create($requestdata)) {
            $this->addMember($users, $booking->id, $requestdata['booking_date']);
            return $this->success([], 'Your Booking create Successfully');
        }
        return $this->error(422, 'Error to create booking');
    }

    private function addMember(array $users, $booking_id, $booking_date)
    {
        $add_booking = [];
        foreach ($users as $key => $val) {
            $add_booking[] = [
                'user_id' => $val,
                'booking_id' => $booking_id,
                'booking_date' => $booking_date,
            ];
        }
        $this->booking->addMember($add_booking);
        Artisan::queue('bookingMail:send', ['booking_id' => $booking_id]);
    }

    private static function names($users)
    {
        $name = '';
        foreach ($users as $val):
            $name .= $val['name'] . ', ';
        endforeach;
        return $name;
    }

    public function UsersBookings($user_id)
    {
        $bookings = $this->booking->userBookings($user_id);
        return $this->success($bookings, 'Users Bookings');
    }

    public function AllBookings()
    {
        $bookings = $this->booking->AllBookings();
        return $this->success($bookings, 'Users Bookings');
    }

    public function deleteBooking($booking_id, $user_id = 0)
    {
        if ($user_id != 0) {
        }
        $booking = $this->booking->bookingIdWithSlot($booking_id);
        if (strtotime('+1 hour', time()) > $booking['booking_date']) {
            return $this->error(408, 'Booking Time passed a way');
        }
        if ($this->booking->deleteBooking($booking_id)) {
            return $this->success([], 'Booking delete successfully');
        }
        return $this->error(403, 'oops!! Something want wrong');
    }

}
