<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $selectedYear = $request->get('year', Carbon::now()->year);
        
        // Counts
        $totalDiamonds = DB::table('diamond_master')->count();
        $totalProducts = DB::table('products')->count();
        $totalVariations = DB::table('product_variations')->count();
        
        // Year-wise orders count
        $totalOrders = DB::table('orders')
            ->whereYear('created_at', $selectedYear)
            ->count();
        
        // Sales percentage for selected year
        $today = Carbon::today()->toDateString();
        $todaySales = DB::table('orders')
            ->whereDate('created_at', $today)
            ->sum('total_price');
            
        $yearlySales = DB::table('orders')
            ->whereYear('created_at', $selectedYear)
            ->sum('total_price');
            
        $totalSales = DB::table('orders')->sum('total_price');
        
        $salesPercentage = $yearlySales > 0
            ? round(($todaySales / $yearlySales) * 100, 2)
            : 0;
        
        // Order-based dynamic stats for selected year
        $orderSales = DB::table('orders')
            ->whereYear('created_at', $selectedYear)
            ->select(
                DB::raw("SUM(CASE WHEN product_type = 'diamond' THEN total_price ELSE 0 END) as diamond_sales"),
                DB::raw("SUM(CASE WHEN product_type = 'jewelry' THEN total_price ELSE 0 END) as jewelry_sales"),
                DB::raw("COUNT(CASE WHEN product_type = 'diamond' THEN 1 END) as diamond_orders"),
                DB::raw("COUNT(CASE WHEN product_type = 'jewelry' THEN 1 END) as jewelry_orders")
            )
            ->first();
        
        // Monthly revenue data for chart (current year)
        $monthlyRevenue = DB::table('orders')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(total_price) as total'),
                DB::raw('COUNT(*) as order_count')
            )
            ->whereYear('created_at', $selectedYear)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Prepare chart data
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartRevenue = array_fill(0, 12, 0);
        $chartOrders = array_fill(0, 12, 0);
        
        foreach ($monthlyRevenue as $data) {
            $chartRevenue[$data->month - 1] = $data->total;
            $chartOrders[$data->month - 1] = $data->order_count;
        }
        
        // Payment modes transactions for selected year
        $transactions = DB::table('orders')
            ->whereYear('created_at', $selectedYear)
            ->select('payment_mode', DB::raw('SUM(total_price) as total_amount'))
            ->groupBy('payment_mode')
            ->orderByDesc('total_amount')
            ->limit(6)
            ->get();
        
        // Yearly Revenue for last 5 years
        $yearlyRevenue = [];
        $currentYear = Carbon::now()->year;
        
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $total = DB::table('orders')
                ->whereYear('created_at', $year)
                ->sum('total_price');
            $yearlyRevenue[$year] = $total;
        }
        
        // Top selling products for selected year
        $topProducts = DB::table('orders')
            ->whereYear('orders.created_at', $selectedYear)
            ->join('products', function($join) {
                $join->on('orders.items_id', '=', DB::raw("CONCAT('[', products.products_id, ']')"));
            })
            ->select(
                'products.products_id',
                'products.products_name',
                DB::raw('SUM(orders.total_price) as total_sales'),
                DB::raw('COUNT(*) as total_orders')
            )
            ->groupBy('products.products_id', 'products.products_name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
        
        // Sales trend comparison (current year vs previous year)
        $salesComparison = [];
        for ($i = 0; $i < 2; $i++) {
            $year = $selectedYear - $i;
            $monthlyData = DB::table('orders')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(total_price) as total')
                )
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            $monthlyTotals = array_fill(0, 12, 0);
            foreach ($monthlyData as $data) {
                $monthlyTotals[$data->month - 1] = $data->total;
            }
            
            $salesComparison[$year] = [
                'total' => array_sum($monthlyTotals),
                'monthly' => $monthlyTotals
            ];
        }
        
        // Customer statistics for selected year
        $customerStats = DB::table('orders')
            ->whereYear('created_at', $selectedYear)
            ->select(
                DB::raw('COUNT(DISTINCT user_id) as total_customers'),
                DB::raw('AVG(total_price) as avg_order_value'),
                DB::raw('MAX(total_price) as max_order'),
                DB::raw('MIN(total_price) as min_order')
            )
            ->first();
        
        // Order status distribution for selected year
        $orderStatuses = DB::table('orders')
            ->whereYear('created_at', $selectedYear)
            ->select(
                'order_status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_price) as total_value')
            )
            ->groupBy('order_status')
            ->orderByDesc('count')
            ->get();
        
        // Get available years for dropdown
        $availableYears = DB::table('orders')
            ->select(DB::raw('YEAR(created_at) as year'))
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
        
        // If no orders yet, add current year
        if (empty($availableYears)) {
            $availableYears = [Carbon::now()->year];
        }
        
        // Additional counts for all cards
        $additionalCounts = [
            'totalShades' => DB::table('diamond_shade_master')->count(),
            'totalShapes' => DB::table('diamond_shape_master')->count(),
            'totalSizes' => DB::table('diamond_size_master')->count(),
            'totalClarity' => DB::table('products_clarity_master')->count(),
            'totalColors' => DB::table('products_color_master')->count(),
            'totalCuts' => DB::table('diamond_cut_master')->count(),
            'totalMetalTypes' => DB::table('metal_type')->count(),
            'totalCategories' => DB::table('categories')->count(),
            'totalUsers' => DB::table('users')->count(),
            'totalCoupons' => DB::table('coupons')->count(),
            'totalBlogs' => DB::table('blogs')->count(),
            'totalMetalPrices' => DB::table('metal_prices')->count(),
            'totalAppointments' => DB::table('appointments')->count(),
            'totalVendors' => DB::table('vendor_master')->count(),
            'totalEnquiries' => DB::table('enquiries')->count(),
            'totalContactUs' => DB::table('contact_us')->count(),
            'totalQualityGroups' => DB::table('diamond_quality_group')->count(),
            'totalProductClarity' => DB::table('products_clarity_master')->count(),
            'totalProductColor' => DB::table('products_color_master')->count(),
            'totalProductCut' => DB::table('products_cut_master')->count(),
            'totalTaxClasses' => DB::table('shop_tax_classes')->count(),
            'totalTaxRates' => DB::table('shop_tax_rates')->count(),
            'totalProductStyleCategories' => DB::table('products_style_category')->count(),
            'totalCollections' => DB::table('product_collections')->count(),
            'totalStyleGroups' => DB::table('products_style_group')->count(),
            'totalDiamondPolish' => DB::table('diamond_polish_master')->count(),
            'totalDiamondLab' => DB::table('diamond_lab_master')->count(),
            'totalKeyToSymbols' => DB::table('diamond_key_to_symbols_master')->count(),
            'totalGirdle' => DB::table('diamond_girdle_master')->count(),
            'totalCulet' => DB::table('diamond_culet_master')->count(),
            'totalFancyColor' => DB::table('diamond_fancycolor_overtones_master')->count(),
            'totalFancyColorIntensity' => DB::table('diamond_fancycolor_intensity_master')->count(),
            'totalDiamondWeightGroups' => DB::table('diamond_weight_group')->count(),
        ];

        return view('admin.dashboard', array_merge(compact(
            'totalDiamonds',
            'totalProducts',
            'totalVariations',
            'totalOrders',
            'salesPercentage',
            'yearlySales',
            'totalSales',
            'orderSales',
            'transactions',
            'yearlyRevenue',
            'monthlyRevenue',
            'chartLabels',
            'chartRevenue',
            'chartOrders',
            'topProducts',
            'salesComparison',
            'customerStats',
            'orderStatuses',
            'availableYears',
            'selectedYear',
            'search'
        ), $additionalCounts));
    }
    
    // AJAX endpoint for chart data
    public function getChartData(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        
        $monthlyData = DB::table('orders')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $revenue = array_fill(0, 12, 0);
        $orders = array_fill(0, 12, 0);
        
        foreach ($monthlyData as $data) {
            $revenue[$data->month - 1] = $data->revenue;
            $orders[$data->month - 1] = $data->orders;
        }
        
        // Get yearly comparison data
        $yearlyData = [];
        $currentYear = Carbon::now()->year;
        
        for ($i = 0; $i < 5; $i++) {
            $yr = $currentYear - $i;
            $total = DB::table('orders')
                ->whereYear('created_at', $yr)
                ->sum('total_price');
            $yearlyData[$yr] = $total;
        }
        
        return response()->json([
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orders,
            'yearlyData' => $yearlyData,
            'year' => $year
        ]);
    }
    
    // Get dashboard stats for specific year
    public function getDashboardStats(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        
        $stats = [
            'totalOrders' => DB::table('orders')->whereYear('created_at', $year)->count(),
            'yearlySales' => DB::table('orders')->whereYear('created_at', $year)->sum('total_price'),
            'orderSales' => DB::table('orders')
                ->whereYear('created_at', $year)
                ->select(
                    DB::raw("SUM(CASE WHEN product_type = 'diamond' THEN total_price ELSE 0 END) as diamond_sales"),
                    DB::raw("SUM(CASE WHEN product_type = 'jewelry' THEN total_price ELSE 0 END) as jewelry_sales"),
                    DB::raw("COUNT(CASE WHEN product_type = 'diamond' THEN 1 END) as diamond_orders"),
                    DB::raw("COUNT(CASE WHEN product_type = 'jewelry' THEN 1 END) as jewelry_orders")
                )
                ->first(),
            'customerStats' => DB::table('orders')
                ->whereYear('created_at', $year)
                ->select(
                    DB::raw('COUNT(DISTINCT user_id) as total_customers'),
                    DB::raw('AVG(total_price) as avg_order_value'),
                    DB::raw('MAX(total_price) as max_order'),
                    DB::raw('MIN(total_price) as min_order')
                )
                ->first(),
        ];
        
        return response()->json($stats);
    }
}