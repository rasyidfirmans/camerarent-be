<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class CartController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(
                ['auth:sanctum', 'ability:access-token'],
                except: ['show']
            ),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user_id = $request->user()->id;
        $cart = Cart::where('user_id', $user_id)->first();
        if ($cart) {
            $total_price = 0;
            $cart_items = $cart->products()->wherePivot('cart_id', $cart->id)->get();
            $formatted_cart_items = $cart_items->map(function ($item) use (&$total_price) {
                $total_price += $item->price * $item->pivot->quantity;
                return (object) [
                    'product' => (object) [
                        'id' => $item->id,
                        'name' => $item->name,
                        'category' => $item->category->category_name,
                        'description' => $item->description,
                        'stock' => $item->stock,
                        'price' => $item->price,
                        'image' => $item->image,

                    ],
                    'id' => $item->pivot->id,
                    'quantity' => $item->pivot->quantity,
                    'start_date' => $item->pivot->start_date,
                    'end_date' => $item->pivot->end_date,
                    'date_diff' => $item->pivot->date_diff,
                ];
            });

            return response()->json([
                'code' => 200,
                'message' => 'Cart retrieved successfully',
                'total_price' => $total_price,
                'data' => $formatted_cart_items,
            ]);
        } else {
            return response()->json([
                'code' => 404,
                'message' => 'Cart not found',
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $cart = Cart::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);
        $existingProduct = $cart->products()
            ->wherePivot('product_id', $validated['product_id'])
            ->wherePivot('start_date', $validated['start_date'])
            ->wherePivot('end_date', $validated['end_date'])
            ->first();

        if ($existingProduct) {
            $cart->products()->updateExistingPivot($validated['product_id'], [
                'quantity' => $existingProduct->pivot->quantity + $validated['quantity'],
            ]);
        } else {
            $cart->products()->attach([
                $validated['product_id'] => [
                    'cart_id' => $cart->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'date_diff' => date_diff(
                        date_create($validated['start_date']),
                        date_create($validated['end_date'])
                    )->days,
                ],
            ]);
        }

        $cart_items = $cart->products()->wherePivot('cart_id', $cart->id)->get();
        $formatted_cart_items = $cart_items->map(function ($item) {
            return (object) [
                'product' => (object) [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category->category_name,
                    'description' => $item->description,
                    'stock' => $item->stock,
                    'price' => $item->price,
                    'image' => $item->image,

                ],
                'id' => $item->pivot->id,
                'quantity' => $item->pivot->quantity,
                'start_date' => $item->pivot->start_date,
                'end_date' => $item->pivot->end_date,
                'date_diff' => $item->pivot->date_diff,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Cart created successfully',
            'data' => (object) [
                'user_id' => $cart->user_id,
                'cart_id' => $cart->id,
                'cart_items' => $formatted_cart_items,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();
        $rental_item = DB::table('cart_product')
            ->where('id', $validated['id'])
            ->where('cart_id', $cart->id);
        if ($rental_item->exists()) {
            $rental_item = $rental_item->update([
                'quantity' => $validated['quantity'],
            ]);
        } else {
            return response()->json([
                'code' => 404,
                'message' => 'Product not found in cart',
            ], 404);
        }

        $cart_product_item = $cart->products()->wherePivot('cart_id', $cart->id)->get();
        $formatted_response = $cart_product_item->map(function ($item) {
            return (object) [
                'product' => (object) [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category->category_name,
                    'description' => $item->description,
                    'stock' => $item->stock,
                    'price' => $item->price,
                    'image' => $item->image,

                ],
                'id' => $item->pivot->id,
                'quantity' => $item->pivot->quantity,
                'start_date' => $item->pivot->start_date,
                'end_date' => $item->pivot->end_date,
                'date_diff' => $item->pivot->date_diff,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Cart updated successfully',
            'data' => $formatted_response
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $cart = Cart::where('user_id', $request->user()->id)->first();
        $rental_item = DB::table('cart_product')
            ->where('id', $validated['id'])
            ->where('cart_id', $cart->id);
        if ($rental_item->exists()) {
            $rental_item->delete();
        } else {
            return response()->json([
                'code' => 404,
                'message' => 'Product not found in cart',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Product deleted successfully',
        ]);
    }
}
