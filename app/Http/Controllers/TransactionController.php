<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Requests\UpdateTransactionRequest;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class TransactionController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(
                ['auth:sanctum', 'abilities:access-token'],
                ['index', 'store']
            ),
            new Middleware(
                ['auth:sanctum', 'abilities:access-token,access-admin'],
                ['update', 'getAllTransactions']
            )
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = Transaction::where('user_id', $user->id)
            ->with(['products.category'])
            ->get();

        $transactions = $transactions->map(function ($transaction) {
            $transaction->products->map(function ($product) {
                $product->category_name = $product->category->category_name;
                unset($product->category);
                unset($product->category_id);

                if (isset($product->pivot)) {
                    $product->quantity = $product->pivot->quantity;
                    $product->start_date = $product->pivot->start_date;
                    $product->end_date = $product->pivot->end_date;
                    unset($product->pivot);
                }

                $product->subtotal = $product->price * $product->quantity;

                return $product;
            });
            $transaction->total_price = $transaction->products->sum('subtotal');
            return $transaction;
        });

        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        $validatedData = $request->validated();
        $user = $request->user();

        $uploadFolder = 'storage/images/payment/';
        $image = $request->file('payment_proof');
        $imageName = bin2hex(random_bytes(10)) . '.' . $image->getClientOriginalExtension();
        $image->move($uploadFolder, $imageName);
        $validatedData['payment_proof'] = $uploadFolder . $imageName;

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'notes' => $validatedData['notes'] ?? null,
            'delivery' => $validatedData['delivery'],
            'status' => 'Pending',
            'payment_proof' => $validatedData['payment_proof'],
        ]);

        foreach ($validatedData['products'] as $product) {
            $transaction->products()->attach($product['product_id'], [
                'quantity' => $product['quantity'],
                'start_date' => $product['start_date'],
                'end_date' => $product['end_date'],
            ]);
        }

        return response()->json([
            'message' => 'Transaction created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, string $id)
    {
        $validatedData = $request->validated();
        $user = $request->user();
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $transaction->update([
            'status' => $validatedData['status'],
            'validated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Transaction updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getAllTransactions()
    {
        $transactions = Transaction::with(['user', 'products.category'])
            ->get()
            ->map(function ($transaction) {
                $transaction->products->map(function ($product) {
                    $category_name = $product->category->category_name;
                    unset($product->category);
                    $product->category = $category_name;
                    unset($product->category_id);
                    unset($product->created_at);
                    unset($product->updated_at);

                    if (isset($product->pivot)) {
                        $product->quantity = $product->pivot->quantity;
                        $product->start_date = $product->pivot->start_date;
                        $product->end_date = $product->pivot->end_date;
                        unset($product->pivot);
                    }

                    $product->subtotal = $product->price * $product->quantity;

                    return $product;
                });

                unset($transaction->user->id);
                unset($transaction->user->username);
                unset($transaction->user->password);
                unset($transaction->user->citizenship_image);
                unset($transaction->user->created_at);
                unset($transaction->user->updated_at);
                unset($transaction->user->is_admin);
                $transaction->total_price = $transaction->products->sum('subtotal');
                return $transaction;
            });

        return response()->json([
            'code' => 200,
            'message' => 'All transactions retrieved successfully',
            'data' => $transactions,
        ]);
    }
}
