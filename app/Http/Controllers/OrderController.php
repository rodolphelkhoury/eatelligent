<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $user = $request->user();
        $items = $request->input('items');
        $scheduledTime = $request->input('scheduled_time');
        $order = null;

        DB::beginTransaction();
        try {
            $total = 0;

            // Lock selected products for update to prevent race conditions
            $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            // Verify stock availability and compute total
            foreach ($items as $it) {
                $product = $products->get($it['product_id']);

                if (! $product || ! $product->is_active) {
                    throw new \Exception('Product not available: '.$it['product_id']);
                }

                if (isset($product->stock) && $product->stock < $it['quantity']) {
                    throw new \Exception('Insufficient stock for product: '.$product->id);
                }

                $total += (float) $product->price * (int) $it['quantity'];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending->value,
                'scheduled_time' => $scheduledTime,
                'total_price' => $total,
            ]);

            // Create order items and decrement stock
            foreach ($items as $it) {
                $product = $products->get($it['product_id']);
                $quantity = (int) $it['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);

                if (isset($product->stock)) {
                    $product->decrement('stock', $quantity);
                }
            }

            DB::commit();

            $order->load('orderItems.product');

            // Compute macros consumed in this order
            $macrosThisOrder = [
                'calories' => 0,
                'protein_g' => 0.0,
                'carbs_g' => 0.0,
                'fat_g' => 0.0,
            ];

            foreach ($order->orderItems as $item) {
                $p = $item->product;
                $qty = $item->quantity;

                $macrosThisOrder['calories'] += $p->calories * $qty;
                $macrosThisOrder['protein_g'] += (float) $p->protein_g * $qty;
                $macrosThisOrder['carbs_g'] += (float) $p->carbs_g * $qty;
                $macrosThisOrder['fat_g'] += (float) $p->fat_g * $qty;
            }

            return response()->json([
                'order' => $order,
                'macros_this_order' => $macrosThisOrder,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function index(IndexOrderRequest $request)
    {
        $user = $request->user();

        $query = Order::with('orderItems.product')
            ->where('user_id', $user->id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json($orders);
    }

    public function staffIndex(IndexOrderRequest $request)
    {
        $query = Order::with('orderItems.product');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json($orders);
    }

    public function confirm(Order $order)
    {
        if ($order->status === OrderStatus::Confirmed->value) {
            return response()->json(['message' => 'Order already confirmed.'], 400);
        }

        if ($order->status !== OrderStatus::Pending->value) {
            return response()->json(['message' => 'Only pending orders can be confirmed.'], 400);
        }

        $order->status = OrderStatus::Confirmed->value;
        $order->save();

        $order->load('orderItems.product');

        return response()->json(['order' => $order]);
    }

    public function complete(Order $order)
    {
        if ($order->status === OrderStatus::Completed->value) {
            return response()->json(['message' => 'Order already completed.'], 400);
        }

        if ($order->status !== OrderStatus::Confirmed->value) {
            return response()->json(['message' => 'Only confirmed orders can be completed.'], 400);
        }

        $order->status = OrderStatus::Completed->value;
        $order->save();

        $order->load('orderItems.product');

        return response()->json(['order' => $order]);
    }

    public function readyForPickup(Order $order)
    {
        if ($order->status === OrderStatus::ReadyForPickup->value) {
            return response()->json(['message' => 'Order already marked ready for pickup.'], 400);
        }

        if ($order->status !== OrderStatus::Confirmed->value) {
            return response()->json(['message' => 'Only confirmed orders can be marked ready for pickup.'], 400);
        }

        $order->status = OrderStatus::ReadyForPickup->value;
        $order->save();

        $order->load('orderItems.product');

        return response()->json(['order' => $order]);
    }

    public function cancel(Order $order)
    {
        if ($order->status === OrderStatus::Cancelled->value) {
            return response()->json(['message' => 'Order already cancelled.'], 400);
        }

        if ($order->status === OrderStatus::Completed->value) {
            return response()->json(['message' => 'Completed orders cannot be cancelled.'], 400);
        }

        $order->status = OrderStatus::Cancelled->value;
        $order->save();

        $order->load('orderItems.product');

        return response()->json(['order' => $order]);
    }
}
