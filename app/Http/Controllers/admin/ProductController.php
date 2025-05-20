<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\TempImage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{   
    //This method is used to get all products
    public function index(){
        $products = Product::orderBy('created_at', 'DESC')->get();
        return response()->json([
            'status' => 200,
            'data' => $products
        ],200);

    }
    //This method will store a new product
    public function store(Request $request){
        //Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'price' => 'required|numeric',
            'category' => 'required|integer',
            'sku' => 'required|unique:products,sku',
            'is_featured' => 'required',
            'status' => 'required',
        ]);
        //Check if validation fails
        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors()
            ],400);
        }
        //Store the product
        $product = new Product();
        $product->title = $request->title;
        $product->price = $request->price;
        $product->compare_price = $request->compare_price;
        $product->category_id = $request->category;
        $product->brand_id = $request->brand;
        $product->sku = $request->sku;
        $product->qty = $request->qty;
        $product->description = $request->description;
        $product->short_description = $request->short_description;
        $product->status = $request->status;
        $product->is_featured = $request->is_featured;
        $product->barcode = $request->barcode;
        $product->save();

        if(!empty($request->gallery)){
            foreach($request->gallery as $key => $tempImageId){
                $tempImage = TempImage::find($tempImageId);
                
                //Large thumbnail

                $extArray = explode('.', $tempImage->name);
                $ext = end($extArray);

                $imageName = $product->id. '-' .time(). '.' .$ext;
                
                $manager = new ImageManager(Driver::class);
                $img = $manager->read(public_path('uploads/temp/'. $tempImage->name));
                $img->scaleDown(1200);
                $img->save(public_path('uploads/products/large/'. $imageName));

                //Small thumbnail
                $manager = new ImageManager(Driver::class);
                $img = $manager->read(public_path('uploads/temp/'. $tempImage->name));
                $img->coverDown(400, 460);
                $img->save(public_path('uploads/products/small/'. $imageName));

                $productImage = new ProductImage();
                $productImage->image = $imageName;
                $productImage->product_id = $product->id;
                $productImage->save();

                if($key == 0){
                    $product->image = $imageName;
                    $product->save();
                }
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Product added successfully'
        ],200);

    }
    //This method will return a single product
    public function show($id){
        $product = Product::find($id);

        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ],404);
        }

        return response()->json([
            'status'=> 200,
            'data'=> $product
        ],200);
    }
    //This method will update a product
    public function update($id, Request $request){
        $product = Product::find($id);

        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found',
            ],404);
        }

        //Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'price' => 'required|numeric',
            'category' => 'required|integer',
            'sku' => 'required|unique:products,sku, '.$id. ',id',
            'is_featured' => 'required',
            'status' => 'required',
        ]);
        //Check if validation fails
        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors()
            ],400);
        }
        //Update the product
        $product->title = $request->title;
        $product->price = $request->price;
        $product->compare_price = $request->compare_price;
        $product->category_id = $request->category;
        $product->brand_id = $request->brand;
        $product->sku = $request->sku;
        $product->qty = $request->qty;
        $product->description = $request->description;
        $product->short_description = $request->short_description;
        $product->status = $request->status;
        $product->is_featured = $request->is_featured;
        $product->barcode = $request->barcode;
        $product->save();

        return response()->json([
            'status' => 200,
            'message' => 'Product has been updated successfully'
        ],200);
    }
    //This method will delete a product
    public function destroy($id){
        $product = Product::find($id);

        if($product == null){
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ],404);
        }
        $product->delete();

        return response()->json([
            'status'=> 200,
            'message'=> 'Product has been deleted successfully'
        ],200);
    }
}
