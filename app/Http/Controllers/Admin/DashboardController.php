<?php

namespace App\Http\Controllers\Admin;

use App\Models\Sale;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        $title = 'dashboard';
        $total_purchases = Purchase::where('expiry_date','!=',Carbon::now())->count();
        $total_categories = Category::count();
        $total_suppliers = Supplier::count();
        $total_sales = Sale::count();
        
        $pieChart = app()->chartjs
                ->name('pieChart')
                ->type('pie')
                ->size(['width' => 400, 'height' => 200])
                ->labels(['Total Purchases', 'Total Suppliers','Total Sales'])
                ->datasets([
                    [
                        'backgroundColor' => ['#FF6384', '#36A2EB','#7bb13c'],
                        'hoverBackgroundColor' => ['#FF6384', '#36A2EB','#7bb13c'],
                        'data' => [$total_purchases, $total_suppliers,$total_sales]
                    ]
                ])
                ->options([]);
        
        $total_expired_products = Purchase::whereDate('expiry_date', '=', Carbon::now())->count();
        $latest_sales = Sale::whereDate('created_at','=',Carbon::now())->get();
        $today_sales = Sale::whereDate('created_at','=',Carbon::now())->sum('total_price');
        return view('admin.dashboard',compact(
            'title','pieChart','total_expired_products',
            'latest_sales','today_sales','total_categories'
        ));
    }

    /**
     * Get product statistics for AJAX requests
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function productStats()
    {
        $totalProducts = \App\Models\Product::count();
        $totalCategories = \App\Models\Category::count();
        $lowStock = \App\Models\Product::where(function($query) {
            $query->whereNotNull('stock_quantity')
                  ->where('stock_quantity', '<=', 5)
                  ->where('stock_quantity', '>', 0);
        })->orWhereHas('purchase', function($q) {
            $q->where('quantity', '<=', 5)
              ->where('quantity', '>', 0);
        })->count();
        
        $expired = \App\Models\Product::where(function($query) {
            $query->whereNotNull('expiry_date')
                  ->whereDate('expiry_date', '<=', now());
        })->orWhereHas('purchase', function($q) {
            $q->whereDate('expiry_date', '<=', now());
        })->count();
        
        return response()->json([
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'lowStock' => $lowStock,
            'expired' => $expired
        ]);
    }
    
    /**
     * Get categories for AJAX filtering 
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $categories = \App\Models\Category::select('id', 'name')->get();
        return response()->json($categories);
    }
}
