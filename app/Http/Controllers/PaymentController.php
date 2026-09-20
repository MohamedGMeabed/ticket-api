<?php

namespace App\Http\Controllers;

use App\Domain\Reservations\ReservationService;
use App\Models\Payment;
use App\Models\Reservation;
use App\Payments\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function checkout(Request $request, Reservation $reservation, PaymentGatewayInterface $gateway)
    {
        $reservation->loadMissing('seats');

        abort_unless($reservation->user_id === $request->user()->id, 403);

        $amount = $reservation->seats()->sum('price_cents');

        $payment = DB::table('payments')->insertGetId([
            'reservation_id' => $reservation->id,
            'provider' => config('services.payment.driver', 'mock'),
            'provider_reference' => null,
            'status' => 'pending',
            'amount_cents' => $amount,
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $successUrl = url('/api/payments/success?reservation=' . $reservation->id);
        $cancelUrl = url('/api/payments/cancel?reservation=' . $reservation->id);

        $session = $gateway->createCheckoutSession((object) ['id' => $payment], $successUrl, $cancelUrl);

        DB::table('payments')->where('id', $payment)->update([
            'provider_reference' => $session->providerReference,
            'updated_at' => now(),
        ]);

        return response()->json([
            'payment_id' => $payment,
            'checkout_url' => $session->url,
            'provider_reference' => $session->providerReference,
        ]);
    }

    public function mockCheckout(Request $request, string $reference)
    {
        $payment = Payment::where('provider_reference', $reference)->firstOrFail();
        $reservation = $payment->reservation;
        $reservation->loadMissing('seats');

        $successUrl = $request->query('success', url('/api/payments/success?reservation=' . $reservation->id));
        $cancelUrl = $request->query('cancel', url('/api/payments/cancel?reservation=' . $reservation->id));

        return view('mock-checkout', [
            'payment' => $payment,
            'reservation' => $reservation,
            'successUrl' => $successUrl,
            'cancelUrl' => $cancelUrl,
            'reference' => $reference,
        ]);
    }

    public function success(Request $request)
    {
        $reservationId = $request->query('reservation');
        $reservation = Reservation::with('seats')->findOrFail($reservationId);

        $payment = $reservation->payments()->where('status', 'pending')->first();

        if ($payment) {
            $payment->update(['status' => 'succeeded']);
            app(ReservationService::class)->markPaid($reservation);
        }

        return response()->json([
            'message' => 'Payment completed successfully',
            'reservation' => $reservation->load(['seats', 'event']),
        ]);
    }

    public function cancel(Request $request)
    {
        $reservationId = $request->query('reservation');
        $reservation = Reservation::with('seats')->findOrFail($reservationId);

        $payment = $reservation->payments()->where('status', 'pending')->first();

        if ($payment) {
            $payment->update(['status' => 'failed']);
            app(ReservationService::class)->release($reservation, 'cancelled');
        }

        return response()->json([
            'message' => 'Payment cancelled',
            'reservation' => $reservation->load(['seats', 'event']),
        ]);
    }

    public function webhook(Request $request, PaymentGatewayInterface $gateway, ReservationService $service)
    {
        $event = $gateway->verifyWebhook($request);

        $payment = Payment::query()
            ->where('provider_reference', $event->reference)
            ->firstOrFail();

        $reservation = $payment->reservation;
        $reservation->loadMissing('seats');

        if ($event->status === 'succeeded') {
            $payment->update(['status' => 'succeeded']);
            $service->markPaid($reservation);

            return response()->json(['message' => 'Payment succeeded']);
        }

        $payment->update(['status' => 'failed']);
        $service->release($reservation, 'cancelled');

        return response()->json(['message' => 'Payment failed']);
    }
}
