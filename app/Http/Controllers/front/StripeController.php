<?php

namespace App\Http\Controllers\front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function makePayment(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'product_id' => 'required|array',
            'product_id.*' => 'integer|exists:products,id',
            'cart' => 'required|array',
        ]);

        $products = Product::whereIn('id', $request->product_id)->get();

        Log::info('Valid product IDs after DB fetch:', $products->pluck('id')->toArray());

        if ($products->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'No valid products found'
            ]);
        }

        $lineItems = [];

        foreach ($products as $product) {
            if (!$product instanceof Product || empty($product->title)) {
                continue;
            }

            $priceInCents = (int)($product->price * 100); 

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $priceInCents,
                    'product_data' => [
                        'name' => $product->title,
                        'description' => $product->description ?: 'No description',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        if (empty($lineItems)) {
            return response()->json([
                'status' => 400,
                'message' => 'No valid line items created.'
            ]);
        }

        Stripe::setApiKey(config('stripe.secret_key'));

        Log::info('Stripe line items:', $lineItems);

        $session = Session::create([
            'payment_method_types' => ['card', 'cashapp'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => 'http://localhost:5173/order/confirmation/stripe?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://localhost:5173/checkout',
            'locale' => 'auto',
            'customer_email' => $request->email,
            'metadata' => [
                'user_id' => optional($request->user())->id ?? 'guest',
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'product_ids' => implode(',', array_map(fn($item) => $item['id'], $request->cart ?? []))
            ]
        ]);

        return response()->json([
            'url' => $session->url
        ]);
    }

    public function saveOrderFromStripe(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret_key'));

        try {
            $session  = Session::retrieve($request->session_id);

            $existing = \App\Models\Order::where('stripe_session_id', $session->id)->first();
            if ($existing) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Order already placed',
                    'order_id' => $existing->id
                ]);
            }

            $customer = $session->customer_details;
            $addr     = $customer->address ?? new \stdClass();

            $metadata = $session->metadata ?? new \stdClass();

            $cart = $request->cart ?? [];
            if (!is_array($cart) || count($cart) === 0) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No cart data received!'
                ], 400);
            }

            $order = Order::create([
                'stripe_session_id' => $session->id,
                'user_id' => $request->user()->id,
                'subtotal' => ($session->amount_subtotal ?? 0) / 100,
                'shipping' => 5.00,
                'grand_total' => (($session->amount_subtotal ?? 0) / 100) + 5.00,
                'discount' => 0,
                'payment_status' => 'paid',
                'status' => 'pending',
                'payment_method' => 'stripe',
                'name' => $customer->name   ?? $metadata->name   ?? 'N/A',
                'email' => $customer->email  ?? $metadata->email  ?? 'N/A',
                'mobile' => $customer->phone  ?? $metadata->mobile ?? 'N/A',
                'address' => $addr->line1       ?? $metadata->address ?? '',
                'city' => $addr->city        ?? $metadata->city    ?? '',
                'state' => $addr->state       ?? $metadata->state   ?? '',
                'zip' => $addr->postal_code ?? $metadata->zip     ?? '',
            ]);

            foreach ($cart as $item) {
                $order->items()->create([
                    'order_id'   => $order->id,
                    'product_id' => intval($item['product_id'] ?? $item['id'] ?? 0),
                    'name'       => $item['title'] ?? '',
                    'size'       => $item['size'] ?? null,
                    'qty'        => $item['qty'] ?? 1,
                    'unit_price' => $item['price'] ?? 0,
                    'price'      => ($item['price'] ?? 0) * ($item['qty'] ?? 1),
                ]);
            }

            return response()->json([
                'status'   => 200,
                'message'  => 'Order placed successfully',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Failed to save order: ' . $e->getMessage()
            ], 500);
        }
    }

}