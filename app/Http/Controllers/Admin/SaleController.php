<?php

namespace App\Http\Controllers\Admin;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Events\PurchaseOutStock;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $title = 'sales';
        if($request->ajax()){
            $sales = Sale::with('product.purchase')->latest();
            
            // Apply filters if specified
            if ($request->has('filter')) {
                if ($request->filter === 'today') {
                    $sales->whereDate('created_at', Carbon::today());
                } elseif ($request->filter === 'week') {
                    $sales->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                } elseif ($request->filter === 'month') {
                    $sales->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                }
            }
            
            return DataTables::of($sales)
                    ->addIndexColumn()
                    ->addColumn('product',function($sale){
                        if(!empty($sale->product) && !empty($sale->product->purchase)) {
                            $image = '';
                            if(!empty($sale->product->purchase->image)){
                                $image = '<span class="avatar avatar-sm mr-2">
                                <img class="avatar-img" src="'.asset("storage/purchases/".$sale->product->purchase->image).'" alt="product image">
                                </span>';
                            }
                            return $sale->product->purchase->product. ' ' . $image;
                        }
                        return 'N/A';                
                    })
                    ->addColumn('quantity', function($sale) {
                        return '<span class="badge badge-pill badge-primary">' . $sale->quantity . '</span>';
                    })
                    ->addColumn('total_price',function($sale){                   
                        return '<span class="text-success font-weight-bold">' . settings('app_currency','₹').' '. number_format($sale->total_price, 2) . '</span>';
                    })
                    ->addColumn('date',function($row){
                        $date = Carbon::parse($row->created_at);
                        $now = Carbon::now();
                        
                        if ($date->isToday()) {
                            return '<span class="text-primary">' . $date->format('h:i A') . ' (Today)</span>';
                        } elseif ($date->isYesterday()) {
                            return '<span>' . $date->format('h:i A') . ' (Yesterday)</span>';
                        } else {
                            return '<span>' . $date->format('d M, Y') . '</span>';
                        }
                    })
                    ->addColumn('action', function ($row) {
                        $editbtn = '<a href="'.route("sales.edit", $row->id).'" class="editbtn"><button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button></a>';
                        $deletebtn = '<a data-id="'.$row->id.'" data-route="'.route('sales.destroy', $row->id).'" href="javascript:void(0)" id="deletebtn"><button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></a>';
                        if (!auth()->user()->hasPermissionTo('edit-sale')) {
                            $editbtn = '';
                        }
                        if (!auth()->user()->hasPermissionTo('destroy-sale')) {
                            $deletebtn = '';
                        }
                        $btn = $editbtn.' '.$deletebtn;
                        return $btn;
                    })
                    ->rawColumns(['product', 'quantity', 'total_price', 'date', 'action'])
                    ->make(true);
        }
        
        // Get available products with stock for the dropdown
        $products = Product::whereHas('purchase', function($query) {
            $query->where('quantity', '>', 0);
        })->get();
        
        return view('admin.sales.index', compact('title', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'create sales';
        
        // Get all products with stock (both standalone and purchase-linked)
        $products = Product::where(function($query) {
            // Get standalone products with stock
            $query->whereNull('purchase_id')
                  ->where('stock_quantity', '>', 0);
        })->orWhere(function($query) {
            // Get purchase-linked products with stock
            $query->whereNotNull('purchase_id')
                  ->whereHas('purchase', function($q) {
                      $q->where('quantity', '>', 0);
                  });
        })->with('purchase', 'category')->get();
        
        return view('admin.sales.create', compact('title','products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'product' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        $sold_product = Product::find($request->product);
        
        if (!$sold_product) {
            return redirect()->back()->with(notify('Product not found', 'error'));
        }
        
        // Handle standalone product (directly managed stock)
        if (empty($sold_product->purchase_id) && isset($sold_product->stock_quantity)) {
            // Check if enough stock is available
            if ($sold_product->stock_quantity < $request->quantity) {
                return redirect()->back()->with(notify('Not enough stock available', 'warning'));
            }
            
            // Update the stock quantity
            $new_quantity = $sold_product->stock_quantity - $request->quantity;
            $sold_product->update([
                'stock_quantity' => $new_quantity
            ]);
            
            // Calculate item's total price
            $total_price = $request->quantity * $sold_product->price;
            
            // Create sale record
            Sale::create([
                'product_id' => $request->product,
                'quantity' => $request->quantity,
                'total_price' => $total_price,
            ]);
            
            // Check if stock is running low
            if ($new_quantity <= 5 && $new_quantity > 0) {
                return redirect()->route('sales.index')->with(notify('Product has been sold successfully, but stock is running low!', 'warning'));
            }
            
            return redirect()->route('sales.index')->with(notify('Product has been sold successfully'));
        }
        // Handle purchase-linked product
        elseif ($sold_product->purchase) {
            $purchased_item = Purchase::find($sold_product->purchase->id);
            $new_quantity = ($purchased_item->quantity) - ($request->quantity);
            
            if ($new_quantity < 0) {
                return redirect()->back()->with(notify('Not enough stock available', 'warning'));
            }
    
            // Update the stock quantity
            $purchased_item->update([
                'quantity' => $new_quantity,
            ]);
    
            // Calculate item's total price
            $total_price = ($request->quantity) * ($sold_product->price);
            
            // Create sale record
            Sale::create([
                'product_id' => $request->product,
                'quantity' => $request->quantity,
                'total_price' => $total_price,
            ]);
    
            // Determine notification message
            if ($new_quantity <= 1 && $new_quantity != 0) {
                // Send notification for low stock
                $product = Purchase::where('quantity', '<=', 1)->first();
                event(new PurchaseOutStock($product));
                return redirect()->route('sales.index')->with(notify('Sale completed, but product is running out of stock!', 'warning'));
            } 
            
            return redirect()->route('sales.index')->with(notify('Product has been sold successfully'));
        }
        else {
            return redirect()->back()->with(notify('Product has no available stock information', 'error'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \app\Models\Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        $title = 'edit sale';
        
        // Get all products including the one in this sale (even if out of stock)
        $currentProductId = $sale->product_id;
        
        $products = Product::where(function($query) use ($currentProductId) {
            $query->whereHas('purchase', function($q) {
                $q->where('quantity', '>', 0);
            })->orWhere('id', $currentProductId);
        })->get();
        
        return view('admin.sales.edit', compact('title', 'sale', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \app\Models\Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $sale)
    {
        $this->validate($request, [
            'product' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $sold_product = Product::find($request->product);
        
        if (!$sold_product || !$sold_product->purchase) {
            return redirect()->back()->with(notify('Product not found or has no purchase information'));
        }
        
        $purchased_item = Purchase::find($sold_product->purchase->id);
        
        // If we're changing products or quantities, we need to update stock
        if ($sale->product_id != $request->product || $sale->quantity != $request->quantity) {
            // Return the old product's quantity to stock if changing products
            if ($sale->product_id != $request->product && $sale->product && $sale->product->purchase) {
                $old_purchased_item = Purchase::find($sale->product->purchase->id);
                $old_purchased_item->update([
                    'quantity' => $old_purchased_item->quantity + $sale->quantity
                ]);
            }
            
            // Calculate new stock level
            $adjustment = $sale->product_id == $request->product ? 
                           $request->quantity - $sale->quantity : 
                           $request->quantity;
                           
            $new_quantity = $purchased_item->quantity - $adjustment;
            
            if ($new_quantity < 0) {
                return redirect()->back()->with(notify('Not enough stock available', 'warning'));
            }
            
            // Update the new product's stock
            $purchased_item->update([
                'quantity' => $new_quantity
            ]);
            
            // Calculate new total price
            $total_price = $request->quantity * $sold_product->price;
            
            // Update the sale
            $sale->update([
                'product_id' => $request->product,
                'quantity' => $request->quantity,
                'total_price' => $total_price,
            ]);
            
            // Check if stock is running low
            if ($new_quantity <= 1 && $new_quantity != 0) {
                event(new PurchaseOutStock($purchased_item));
                return redirect()->route('sales.index')->with(notify('Sale updated, but product is running out of stock!', 'warning'));
            }
            
            return redirect()->route('sales.index')->with(notify('Sale updated successfully'));
        }
        
        return redirect()->route('sales.index')->with(notify('No changes made to the sale'));
    }

    /**
     * Generate sales reports index
     *
     * @return \Illuminate\Http\Response
     */
    public function reports(Request $request){
        $title = 'sales reports';
        return view('admin.sales.reports', compact('title'));
    }

    /**
     * Generate sales report form post
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateReport(Request $request){
        $this->validate($request, [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);
        
        $title = 'sales reports';
        $from_date = Carbon::parse($request->from_date)->startOfDay();
        $to_date = Carbon::parse($request->to_date)->endOfDay();
        
        // Get sales with eager loading of product relationship and its relationships
        $sales = Sale::with(['product' => function($query) {
            $query->with(['purchase', 'category']);
        }])
        ->whereBetween('created_at', [$from_date, $to_date])
        ->latest()
        ->get();
        
        // Calculate metrics
        $total_sales = $sales->count();
        $total_revenue = $sales->sum('total_price');
        $avg_sale_value = $total_sales > 0 ? $total_revenue / $total_sales : 0;
        
        // Get top products by quantity sold
        $productSales = [];
        foreach ($sales as $sale) {
            // Skip if the sale doesn't have a product
            if (!$sale->product) continue;
            
            // Determine the product name (handle both standalone and purchase-linked products)
            if (!empty($sale->product->purchase)) {
                $productName = $sale->product->purchase->product ?? 'Unknown Product';
            } else {
                $productName = $sale->product->name ?? 'Unknown Product';
            }
            
            $productId = $sale->product_id;
            
            if (!isset($productSales[$productId])) {
                $productSales[$productId] = [
                    'name' => $productName,
                    'quantity' => 0,
                    'revenue' => 0
                ];
            }
            
            $productSales[$productId]['quantity'] += $sale->quantity;
            $productSales[$productId]['revenue'] += $sale->total_price;
        }
        
        // Sort by quantity sold and take top 5
        if (!empty($productSales)) {
            uasort($productSales, function($a, $b) {
                return $b['quantity'] <=> $a['quantity'];
            });
            
            $topProducts = array_slice($productSales, 0, 5, true);
        } else {
            $topProducts = [];
        }
        
        return view('admin.sales.reports', compact(
            'sales', 'title', 'from_date', 'to_date', 'total_sales', 
            'total_revenue', 'avg_sale_value', 'topProducts'
        ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $sale = Sale::findOrFail($request->id);
        
        // Return the product's quantity to stock before deleting
        if ($sale->product && $sale->product->purchase) {
            $purchased_item = Purchase::find($sale->product->purchase->id);
            $purchased_item->update([
                'quantity' => $purchased_item->quantity + $sale->quantity
            ]);
        }
        
        return $sale->delete();
    }
}
