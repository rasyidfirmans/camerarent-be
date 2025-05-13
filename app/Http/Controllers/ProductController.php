<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(
                ['auth:sanctum', 'abilities:access-token,access-admin'], 
                except: ['index', 'show']
            ),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Product::all();
        return response()->json([
            'status' => true,
            'message' => 'Products retrieved successfully',
            'data' => $data->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'category' => $product->category->category_name,
                    'image' => $product->image,
                ];
            }),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
            'price' => 'required|integer',
            'stock' => 'required|integer',
            'product_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $category = \App\Models\Category::firstOrCreate(
            ['category_name' => $validated['category']]
        );

        $product = \App\Models\Product::where('name', $validated['name'])->first();

        if (!$product) {
            $uploadFolder = 'storage/images/products/';
            $image = $request->file('product_image');
            $imageName = bin2hex(random_bytes(10)) . '.' . $image->getClientOriginalExtension();
            $image->move($uploadFolder, $imageName);
            $validated['product_image'] = $uploadFolder . $imageName;

            $product = \App\Models\Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'category_id' => $category->id,
            'image' => $validated['product_image'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Product added successfully',
            'data' => (object) [
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'category' => $category->category_name,
                'image' => $product->image,
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Product retrieved successfully',
            'data' => (object) [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'category' => $product->category->category_name,
                'image' => $product->image,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
            'price' => 'required|integer',
            'stock' => 'required|integer',
            'product_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $category = \App\Models\Category::firstOrCreate(
            ['category_name' => $validated['category']]
        );

        if ($request->hasFile('product_image')) {
            $uploadFolder = 'storage/images/products/';
            $image = $request->file('product_image');
            $imageName = bin2hex(random_bytes(10)) . '.' . $image->getClientOriginalExtension();
            $image->move($uploadFolder, $imageName);
            $validated['product_image'] = $uploadFolder . $imageName;
        }

        $updated_product = $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'category_id' => $category->id,
            'image' => $validated['product_image'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted_product = Product::find($id);
        if (!$deleted_product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $deleted_product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}
