<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock Payment Checkout</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        .info { background: #f5f5f5; padding: 12px; border-radius: 4px; margin: 16px 0; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; text-decoration: none; margin: 8px 8px 8px 0; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="card">
        <h1>💳 Mock Payment Page</h1>
        <p>This simulates a hosted payment provider page (like Stripe Checkout).</p>

        <div class="info">
            <strong>Payment Reference:</strong> {{ $reference }}<br>
            <strong>Reservation:</strong> #{{ $reservation->id }}<br>
            <strong>Amount:</strong> ${{ number_format($payment->amount_cents / 100, 2) }} {{ $payment->currency }}<br>
            <strong>Status:</strong> <span style="color: #ffc107;">{{ ucfirst($payment->status) }}</span>
        </div>

        <p>In a real integration, this would be the payment provider's hosted page where the user enters card details.</p>

        <a href="{{ $successUrl }}" class="btn btn-success">✅ Simulate Successful Payment</a>
        <a href="{{ $cancelUrl }}" class="btn btn-danger">❌ Simulate Cancelled Payment</a>
    </div>
</body>
</html>