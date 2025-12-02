<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in'
        ]);

        $room = Room::find($request->room_id);

        if ($room->stock <= 0) {
            return response()->json(['message' => 'Kamar tidak tersedia'], 400);
        }

        // hitung harga total
        $days = (strtotime($request->check_out) - strtotime($request->check_in)) / 86400;
        $total = $days * $room->price;

        //generate ID transaksi untuk midtrans
        $orderId = 'BOOK-' . time();

        // buat reservasi
        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'room_id' => $room->id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'total_price' => $total,
            'status' => 'pending',
            'payment_status' => 'pending',
            'transaction_id' => $orderId
        ]);

        // kurangi stock kamar
        $room->decrement('stock');

        // midtrans payment
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $total,
            ],
            'customer_details' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'item_details' => [
                [
                    'id' => $room->id,
                    'price' => $room->price,
                    'quantity' => $days,
                    'name' => $room->name,
                ],
            ],
        ];

        $snap = \Midtrans\Snap::createTransaction($params);
        $paymentUrl = $snap->redirect_url;

        // Simpan ke DB
        $reservation->update([
            'payment_url' => $paymentUrl,
            'transaction_id' => $params['transaction_details']['order_id']
        ]);

        return response()->json([
            'message' => 'Booking berhasil — lanjutkan pembayaran',
            'reservation' => $reservation,
            'payment_url' => $paymentUrl,
        ], 201);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Reservation::with('room')->where('user_id', auth()->id())->get();
    }

    // ADMIN - lihat semua reservasi
    public function all() {
        return Reservation::with(['room', 'user'])->get();
    }

    // USER lihat booking miliknya
    public function myBooking()
    {
        $bookings = Reservation::where('user_id', Auth::id())->with('room')->get();
        return response()->json($bookings);
    }

    // ADMIN & STAFF update status booking
    public function updateStatus(Request $request, $id)
    {
        $booking = Reservation::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $booking->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status booking diperbarui',
            'data' => $booking
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function callback(Request $request)
    {
        $serverKey = config('midtrans.serverKey');
        $hashed = hash('sha512',
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        // validasi signature dari midtrans
        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $reservation = Reservation::where('transaction_id', $request->order_id)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        // Mapping status pembayaran
        $transaction = $request->transaction_status;

        if ($transaction == 'settlement') {
            $reservation->update([
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        } elseif ($transaction == 'pending') {
            $reservation->update([
                'payment_status' => 'pending',
                'status' => 'pending'
            ]);
        } elseif ($transaction == 'expire') {
            $reservation->update([
                'payment_status' => 'expired',
                'status' => 'rejected'
            ]);
        } elseif (in_array($transaction, ['cancel', 'deny', 'failure'])) {
            $reservation->update([
                'payment_status' => 'failed',
                'status' => 'rejected'
            ]);
        }

        return response()->json(['message' => 'Callback processed'], 200);
    }

}
