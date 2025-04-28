<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use QCod\AppSettings\Setting\AppSettings;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $title = 'products';
        if ($request->ajax()) {
            $products = Product::with('category')->latest();
            return DataTables::of($products)
                ->addColumn('product', function($product){
                    $image = '';
                    if(!empty($product->image)){
                        $image = '<span class="avatar avatar-sm mr-2">
                        <img class="avatar-img" src="'.asset("storage/products/".$product->image).'" alt="image">
                        </span>';
                    } elseif(!empty($product->purchase) && !empty($product->purchase->image)){
                        $image = '<span class="avatar avatar-sm mr-2">
                        <img class="avatar-img" src="'.asset("storage/purchases/".$product->purchase->image).'" alt="image">
                        </span>';
                    }
                    
                    return $product->product_name . ' ' . $image;
                })
                ->addColumn('category', function($product){
                    $category = null;
                    if(!empty($product->category)){
                        $category = $product->category->name;
                    } elseif(!empty($product->purchase) && !empty($product->purchase->category)){
                        $category = $product->purchase->category->name;
                    }
                    return $category;
                })
                ->addColumn('price', function($product){
                    return settings('app_currency','₹').' '. number_format($product->price, 2);
                })
                ->addColumn('quantity', function($product){
                    $stock = $product->current_stock;
                    $badge = 'bg-success';
                    if ($stock <= 5) {
                        $badge = 'bg-warning';
                    }
                    if ($stock <= 0) {
                        $badge = 'bg-danger';
                    }
                    return '<span class="badge '.$badge.'">'.$stock.'</span>';
                })
                ->addColumn('expiry_date', function($product){
                    if(!empty($product->expiry_date)){
                        $date = Carbon::parse($product->expiry_date);
                        $class = '';
                        if ($date->isPast()) {
                            $class = 'text-danger';
                        } elseif ($date->diffInDays(now()) <= 30) {
                            $class = 'text-warning';
                        }
                        return '<span class="'.$class.'">'.date_format($date, 'd M, Y').'</span>';
                    } elseif(!empty($product->purchase)){
                        $date = Carbon::parse($product->purchase->expiry_date);
                        $class = '';
                        if ($date->isPast()) {
                            $class = 'text-danger';
                        } elseif ($date->diffInDays(now()) <= 30) {
                            $class = 'text-warning';
                        }
                        return '<span class="'.$class.'">'.date_format($date, 'd M, Y').'</span>';
                    }
                    return '<span class="text-muted">No Date</span>';
                })
                ->addColumn('action', function ($row) {
                    $editbtn = '<a href="'.route("products.edit", $row->id).'" class="editbtn"><button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button></a>';
                    $deletebtn = '<a data-id="'.$row->id.'" data-route="'.route('products.destroy', $row->id).'" href="javascript:void(0)" class="deletebtn"><button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></a>';
                    $viewbtn = '<a href="'.route("products.show", $row->id).'" class="viewbtn"><button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button></a>';
                    
                    if (!auth()->user()->hasPermissionTo('edit-product')) {
                        $editbtn = '';
                    }
                    if (!auth()->user()->hasPermissionTo('destroy-product')) {
                        $deletebtn = '';
                    }
                    $btn = $viewbtn.' '.$editbtn.' '.$deletebtn;
                    return $btn;
                })
                ->rawColumns(['product', 'quantity', 'expiry_date', 'action'])
                ->make(true);
        }
        return view('admin.products.index', compact('title'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'add product';
        $purchases = Purchase::get();
        $categories = Category::get();
        return view('admin.products.create', compact('title', 'purchases', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Different validation rules based on product source
        if ($request->product_source === 'new') {
            $this->validate($request, [
                'name' => 'required|max:200',
                'price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0|max:100',
                'description' => 'nullable|max:1000',
                'category_id' => 'required|exists:categories,id',
                'stock_quantity' => 'required|integer|min:0',
                'expiry_date' => 'nullable|date|after:today',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'barcode' => 'nullable|max:50|unique:products,barcode',
            ]);
        } else {
            $this->validate($request, [
                'purchase_id' => 'required|exists:purchases,id',
                'price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0|max:100',
                'description' => 'nullable|max:1000',
                'stock_quantity' => 'required|integer|min:0',
            ]);
        }

        // Calculate price with discount
        $price = $request->price;
        if ($request->discount > 0) {
            $price = $request->price - ($request->discount * $request->price / 100);
        }

        // Handle image upload
        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = time().'.'.$request->image->extension();
            $request->image->storeAs('public/products', $imageName);
        }

        // Create product based on source
        if ($request->product_source === 'new') {
            // Create new standalone product
            Product::create([
                'name' => $request->name,
                'price' => $price,
                'discount' => $request->discount,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'stock_quantity' => $request->stock_quantity,
                'expiry_date' => $request->expiry_date,
                'image' => $imageName,
                'barcode' => $request->barcode,
            ]);

            $notification = notify("New product '{$request->name}' has been added with stock quantity of {$request->stock_quantity}");
        } else {
            // Create product linked to a purchase
            $purchase = Purchase::findOrFail($request->purchase_id);
            
            // Update purchase quantity
            $purchase->update([
                'quantity' => $request->stock_quantity
            ]);

            Product::create([
                'purchase_id' => $request->purchase_id,
                'price' => $price,
                'discount' => $request->discount,
                'description' => $request->description,
            ]);

            $notification = notify("Product from purchase '{$purchase->product}' has been added with stock quantity of {$request->stock_quantity}");
        }

        return redirect()->route('products.index')->with($notification);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        $title = 'product details';
        return view('admin.products.show', compact('title', 'product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $title = 'edit product';
        $purchases = Purchase::get();
        $categories = Category::get();
        return view('admin.products.edit', compact('title', 'product', 'purchases', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        // Validation
        if ($product->purchase_id) {
            // Updating linked product
            $this->validate($request, [
                'price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0|max:100',
                'description' => 'nullable|max:1000',
                'stock_quantity' => 'required|integer|min:0',
            ]);
        } else {
            // Updating standalone product
            $this->validate($request, [
                'name' => 'required|max:200',
                'price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0|max:100',
                'description' => 'nullable|max:1000',
                'category_id' => 'required|exists:categories,id',
                'stock_quantity' => 'required|integer|min:0',
                'expiry_date' => 'nullable|date',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'barcode' => 'nullable|max:50|unique:products,barcode,'.$product->id,
            ]);
        }

        // Calculate price with discount
        $price = $request->price;
        if ($request->discount > 0) {
            $price = $request->price - ($request->discount * $request->price / 100);
        }

        // Handle image upload for standalone products
        $imageName = $product->image;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::delete('public/products/' . $product->image);
            }
            $imageName = time().'.'.$request->image->extension();
            $request->image->storeAs('public/products', $imageName);
        }

        // Update based on product type
        if ($product->purchase_id) {
            // Update purchase quantity
            if ($product->purchase) {
                $product->purchase->update([
                    'quantity' => $request->stock_quantity
                ]);
            }

            $product->update([
                'price' => $price,
                'discount' => $request->discount,
                'description' => $request->description,
            ]);
            
            $notification = notify('Product has been updated');
        } else {
            // Update standalone product
            $product->update([
                'name' => $request->name,
                'price' => $price,
                'discount' => $request->discount,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'stock_quantity' => $request->stock_quantity,
                'expiry_date' => $request->expiry_date,
                'image' => $imageName,
                'barcode' => $request->barcode,
            ]);
            
            $notification = notify("Product '{$request->name}' has been updated");
        }

        return redirect()->route('products.index')->with($notification);
    }

    /**
     * Display a listing of expired resources.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function expired(Request $request)
    {
        $title = "expired products";
        
        if ($request->ajax()) {
            // Get products that are expired (either directly or via linked purchase)
            $products = Product::where(function($query) {
                $query->whereNotNull('expiry_date')
                      ->whereDate('expiry_date', '<=', Carbon::now());
            })->orWhereHas('purchase', function($q) {
                $q->whereDate('expiry_date', '<=', Carbon::now());
            })->get();
            
            return DataTables::of($products)
                ->addColumn('product', function($product) {
                    $image = '';
                    if(!empty($product->image)){
                        $image = '<span class="avatar avatar-sm mr-2">
                        <img class="avatar-img" src="'.asset("storage/products/".$product->image).'" alt="image">
                        </span>';
                    } elseif(!empty($product->purchase) && !empty($product->purchase->image)){
                        $image = '<span class="avatar avatar-sm mr-2">
                        <img class="avatar-img" src="'.asset("storage/purchases/".$product->purchase->image).'" alt="image">
                        </span>';
                    }
                    
                    return $product->product_name . ' ' . $image;
                })
                ->addColumn('category', function($product){
                    $category = null;
                    if(!empty($product->category)){
                        $category = $product->category->name;
                    } elseif(!empty($product->purchase) && !empty($product->purchase->category)){
                        $category = $product->purchase->category->name;
                    }
                    return $category;
                })
                ->addColumn('price', function($product){
                    return settings('app_currency','₹').' '. number_format($product->price, 2);
                })
                ->addColumn('quantity', function($product){
                    return $product->current_stock;
                })
                ->addColumn('expiry_date', function($product){
                    if(!empty($product->expiry_date)){
                        return '<span class="text-danger">'.date_format(date_create($product->expiry_date), 'd M, Y').'</span>';
                    } elseif(!empty($product->purchase)){
                        return '<span class="text-danger">'.date_format(date_create($product->purchase->expiry_date), 'd M, Y').'</span>';
                    }
                    return '';
                })
                ->addColumn('action', function ($row) {
                    $editbtn = '<a href="'.route("products.edit", $row->id).'" class="editbtn"><button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button></a>';
                    $deletebtn = '<a data-id="'.$row->id.'" data-route="'.route('products.destroy', $row->id).'" href="javascript:void(0)" class="deletebtn"><button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></a>';
                    
                    if (!auth()->user()->hasPermissionTo('edit-product')) {
                        $editbtn = '';
                    }
                    if (!auth()->user()->hasPermissionTo('destroy-product')) {
                        $deletebtn = '';
                    }
                    $btn = $editbtn.' '.$deletebtn;
                    return $btn;
                })
                ->rawColumns(['product', 'expiry_date', 'action'])
                ->make(true);
        }

        return view('admin.products.expired', compact('title'));
    }

    /**
     * Display a listing of out of stock resources.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function outstock(Request $request)
    {
        $title = "out of stock products";
        
        if ($request->ajax()) {
            // Get products that are out of stock (either directly or via linked purchase)
            $products = Product::where(function($query) {
                $query->whereNotNull('stock_quantity')
                      ->where('stock_quantity', '<=', 0);
            })->orWhereHas('purchase', function($q) {
                $q->where('quantity', '<=', 0);
            })->get();
            
            return DataTables::of($products)
                ->addColumn('product', function($product) {
                    $image = '';
                    if(!empty($product->image)){
                        $image = '<span class="avatar avatar-sm mr-2">
                        <img class="avatar-img" src="'.asset("storage/products/".$product->image).'" alt="image">
                        </span>';
                    } elseif(!empty($product->purchase) && !empty($product->purchase->image)){
                        $image = '<span class="avatar avatar-sm mr-2">
                        <img class="avatar-img" src="'.asset("storage/purchases/".$product->purchase->image).'" alt="image">
                        </span>';
                    }
                    
                    return $product->product_name . ' ' . $image;
                })
                ->addColumn('category', function($product){
                    $category = null;
                    if(!empty($product->category)){
                        $category = $product->category->name;
                    } elseif(!empty($product->purchase) && !empty($product->purchase->category)){
                        $category = $product->purchase->category->name;
                    }
                    return $category;
                })
                ->addColumn('price', function($product){
                    return settings('app_currency','₹').' '. number_format($product->price, 2);
                })
                ->addColumn('quantity', function($product){
                    return '<span class="badge bg-danger">0</span>';
                })
                ->addColumn('expiry_date', function($product){
                    if(!empty($product->expiry_date)){
                        $date = Carbon::parse($product->expiry_date);
                        $class = '';
                        if ($date->isPast()) {
                            $class = 'text-danger';
                        } elseif ($date->diffInDays(now()) <= 30) {
                            $class = 'text-warning';
                        }
                        return '<span class="'.$class.'">'.date_format($date, 'd M, Y').'</span>';
                    } elseif(!empty($product->purchase)){
                        $date = Carbon::parse($product->purchase->expiry_date);
                        $class = '';
                        if ($date->isPast()) {
                            $class = 'text-danger';
                        } elseif ($date->diffInDays(now()) <= 30) {
                            $class = 'text-warning';
                        }
                        return '<span class="'.$class.'">'.date_format($date, 'd M, Y').'</span>';
                    }
                    return '';
                })
                ->addColumn('action', function ($row) {
                    $editbtn = '<a href="'.route("products.edit", $row->id).'" class="editbtn"><button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button></a>';
                    $deletebtn = '<a data-id="'.$row->id.'" data-route="'.route("products.destroy", $row->id).'" href="javascript:void(0)" class="deletebtn"><button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></a>';
                    $restockbtn = '<a href="'.route("products.edit", $row->id).'?restock=1" class="restockbtn"><button class="btn btn-sm btn-success"><i class="fas fa-plus-circle"></i> Restock</button></a>';
                    
                    if (!auth()->user()->hasPermissionTo('edit-product')) {
                        $editbtn = '';
                        $restockbtn = '';
                    }
                    if (!auth()->user()->hasPermissionTo('destroy-product')) {
                        $deletebtn = '';
                    }
                    $btn = $restockbtn.' '.$editbtn.' '.$deletebtn;
                    return $btn;
                })
                ->rawColumns(['product', 'quantity', 'expiry_date', 'action'])
                ->make(true);
        }

        return view('admin.products.outstock', compact('title'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $product = Product::findOrFail($request->id);
        
        // Delete product image if exists
        if ($product->image) {
            Storage::delete('public/products/' . $product->image);
        }
        
        return $product->delete();
    }
}
