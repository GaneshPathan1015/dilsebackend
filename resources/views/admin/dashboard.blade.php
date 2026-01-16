@extends('admin.layouts.master')
@section('main_section')
<style>
    .hidden-card {
        opacity: 0.6;
        background-color: #f8f9fa;
    }

    .hidden-card .card-body {
        filter: blur(1px);
    }

    .card-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }

    .section-header {
        cursor: pointer;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 15px;
    }

    .section-header:hover {
        background-color: #e9ecef;
    }

    /* Search Bar Container */
    .navbar-nav .nav-item {
        position: relative;
    }

    /* Search Input */
    #dashboard-global-search {
        width: 300px;
        transition: width 0.3s ease;
    }

    #dashboard-global-search:focus {
        width: 400px;
        outline: none;
    }

    /* Search Results Dropdown */
    #global-search-results {
        position: fixed !important;
        z-index: 9999 !important;
    }

    /* Card Filter Effect */
    .card-filtered-out {
        opacity: 0.3;
        filter: blur(1px);
        transform: scale(0.98);
        transition: all 0.3s ease;
    }

    /* Dashboard specific styles */
    .dashboard-stats-card {
        transition: all 0.3s ease;
        border: 1px solid #e0e0e0;
    }
    
    .dashboard-stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .chart-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .highlight-card {
        animation: highlightCard 2s ease;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
        border-radius: 8px;
        position: relative;
        z-index: 1;
    }
    
    @keyframes highlightCard {
        0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
        50% { box-shadow: 0 0 0 20px rgba(13, 110, 253, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }

    .year-filter-active {
        background-color: #e7f1ff;
        color: #0d6efd !important;
        font-weight: 600;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        #dashboard-global-search {
            width: 200px;
        }
        
        #dashboard-global-search:focus {
            width: 250px;
        }
        
        #global-search-results {
            width: 300px !important;
            left: 50% !important;
            transform: translateX(-50%);
        }
    }

    @media (max-width: 576px) {
        #dashboard-global-search {
            width: 150px;
        }
        
        #global-search-results {
            width: 280px !important;
        }
    }
</style>

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Dashboard Header with Year Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Dashboard Overview</h5>
                        <p class="text-muted mb-0">Welcome back, {{ Auth::check() ? Auth::user()->name : 'Guest' }}!</p>
                        <small class="text-muted">Showing data for year: <strong>{{ $selectedYear }}</strong></small>
                    </div>
                    <div class="d-flex gap-2">
                        <!-- Year Filter Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="yearFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-calendar me-1"></i> Year: {{ $selectedYear }}
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="yearFilterDropdown">
                                @foreach($availableYears as $year)
                                <a class="dropdown-item {{ $selectedYear == $year ? 'year-filter-active' : '' }}" 
                                   href="{{ route('admin.dashboard', ['year' => $year]) }}">
                                    {{ $year }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manageCardsModal">
                            <i class="bx bx-cog me-1"></i> Manage Cards
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="resetViewBtn">
                            <i class="bx bx-reset me-1"></i> Reset View
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- First Row - Welcome Card -->
    <div class="row mb-4">
        <div class="col-12" id="welcome-card-container">
            <div class="card welcome-card dashboard-stats-card" data-card-id="welcome-card">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Congratulations {{ Auth::check() ? Auth::user()->name : 'Guest' }}! 🎉</h5>
                            <p class="mb-4">
                                You have done <span class="fw-bold">{{ number_format($salesPercentage, 2) }}%</span> more sales today. 
                                Total sales for {{ $selectedYear }}: <strong>₹{{ number_format($yearlySales, 2) }}</strong>
                            </p>
                            <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">View Orders</a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                            <img
                                src="../assets/img/illustrations/man-with-laptop-light.png"
                                height="140"
                                alt="View Badge User"
                                data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                data-app-light-img="illustrations/man-with-laptop-light.png"
                            />
                        </div>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Year: {{ $selectedYear }} | Updated: {{ now()->format('d M Y, h:i A') }}</small>
                        <button class="btn btn-sm btn-outline-danger hide-card-btn" data-card-id="welcome-card">
                            <i class="bx bx-hide"></i> Hide
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row - Quick Stats -->
    <div class="row mb-4">
        <!-- Total Diamonds Card -->
        <div class="col-xl-3 col-md-6 mb-4" id="total-diamonds-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="total-diamonds">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class='bx bx-diamond text-primary'></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('diamond-master.index') }}">View More</a>
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="total-diamonds">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Total Diamonds</span>
                    <h3 class="card-title mb-2">{{ $totalDiamonds }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-up-arrow-alt"></i> Total in database
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Products Card -->
        <div class="col-xl-3 col-md-6 mb-4" id="total-products-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="total-products">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class='bx bx-package text-success'></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('product.index') }}">View More</a>
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="total-products">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span>Total Products</span>
                    <h3 class="card-title text-nowrap mb-1">{{ $totalProducts }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-up-arrow-alt"></i> All products
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Variations Card -->
        <div class="col-xl-3 col-md-6 mb-4" id="total-variations-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="total-variations">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class='bx bx-slider-alt text-info'></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="total-variations">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span class="d-block mb-1">Total Variations</span>
                    <h3 class="card-title text-nowrap mb-2">{{ $totalVariations }}</h3>
                    <small class="text-info fw-semibold">
                        <i class="bx bx-info-circle"></i> Product variations
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="col-xl-3 col-md-6 mb-4" id="total-orders-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="total-orders">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class='bx bx-cart text-warning'></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('orders.index') }}">View More</a>
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="total-orders">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Total Orders ({{ $selectedYear }})</span>
                    <h3 class="card-title mb-2">{{ $totalOrders }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-rupee"></i> ₹{{ number_format($yearlySales, 2) }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Third Row - Revenue and Profile -->
    <div class="row mb-4">
        <!-- Total Revenue -->
        <div class="col-lg-8 mb-4" id="total-revenue-container">
            <div class="card dashboard-stats-card" data-card-id="total-revenue">
                <div class="row row-bordered g-0">
                    <div class="col-md-8">
                        <h5 class="card-header m-0 me-2 pb-3 d-flex justify-content-between align-items-center">
                            <span>Total Revenue {{ $selectedYear }}</span>
                            <div class="dropdown d-inline">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="chartYearDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ $selectedYear }}
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="chartYearDropdown">
                                    @foreach($availableYears as $year)
                                    <a class="dropdown-item {{ $selectedYear == $year ? 'year-filter-active' : '' }}" 
                                       href="{{ route('admin.dashboard', ['year' => $year]) }}">
                                        {{ $year }}
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                        </h5>
                        <div id="totalRevenueChart" class="px-2" style="min-height: 350px;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h6 class="mb-1">Yearly Comparison</h6>
                                <p class="text-muted">Revenue growth percentage</p>
                            </div>
                            <div id="growthChart"></div>
                            
                            <!-- Yearly Revenue Summary -->
                            <div class="mt-4">
                                <h6 class="mb-3">Yearly Revenue</h6>
                                @foreach($yearlyRevenue as $year => $amount)
                                <div class="d-flex align-items-center mb-2">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-label-primary p-2 me-2">
                                            <i class="bx bx-wallet text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">{{ $year }}</small>
                                            <h6 class="mb-0">₹{{ number_format($amount, 2) }}</h6>
                                        </div>
                                        @php
                                            $prevYear = $year - 1;
                                            $prevAmount = $yearlyRevenue[$prevYear] ?? 0;
                                            $growth = $prevAmount > 0 ? (($amount - $prevAmount) / $prevAmount) * 100 : 0;
                                        @endphp
                                        <small class="{{ $growth >= 0 ? 'text-success' : 'text-danger' }}">
                                            <i class="bx bx-chevron-{{ $growth >= 0 ? 'up' : 'down' }}"></i>
                                            {{ number_format(abs($growth), 2) }}%
                                        </small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Revenue data for {{ $selectedYear }} | Updated: {{ now()->format('d M Y, h:i A') }}
                        </small>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="exportChartData()">
                                <i class="bx bx-download"></i> Export
                            </button>
                            <button class="btn btn-sm btn-outline-danger hide-card-btn" data-card-id="total-revenue">
                                <i class="bx bx-hide"></i> Hide
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Report -->
        <div class="col-lg-4 mb-4" id="profile-report-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="profile-report">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                        <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                            <div class="card-title">
                                <h5 class="text-nowrap mb-2">Sales Report {{ $selectedYear }}</h5>
                                <span class="badge bg-label-warning rounded-pill">Year {{ $selectedYear }}</span>
                            </div>
                            <div class="mt-sm-auto">
                                <small class="text-success text-nowrap fw-semibold">
                                    <i class="bx bx-chevron-up"></i> {{ $salesPercentage }}%
                                </small>
                                <h3 class="mb-0">₹{{ number_format($yearlySales, 2) }}</h3>
                            </div>
                        </div>
                        <div id="profileReportChart"></div>
                    </div>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="bx bx-user"></i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>Customers</small>
                                    <h6 class="mb-0">{{ $customerStats->total_customers ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="bx bx-cart"></i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>Avg Order</small>
                                    <h6 class="mb-0">₹{{ number_format($customerStats->avg_order_value ?? 0, 2) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-outline-danger hide-card-btn" data-card-id="profile-report">
                            <i class="bx bx-hide"></i> Hide
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fourth Row - Statistics -->
    <div class="row mb-4">
        <!-- Order Statistics -->
        <div class="col-md-6 col-lg-4 mb-4" id="order-statistics-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="order-statistics">
                <div class="card-header d-flex align-items-center justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Order Statistics {{ $selectedYear }}</h5>
                        <small class="text-muted">₹{{ number_format($yearlySales, 2) }} Total Revenue</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach($availableYears as $year)
                            <a class="dropdown-item {{ $selectedYear == $year ? 'year-filter-active' : '' }}" 
                               href="{{ route('admin.dashboard', ['year' => $year]) }}">
                                View {{ $year }}
                            </a>
                            @endforeach
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="order-statistics">Hide Card</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex flex-column align-items-center gap-1">
                            <h2 class="mb-2">{{ $totalOrders }}</h2>
                            <span>Total Orders</span>
                        </div>
                        <div id="orderStatisticsChart"></div>
                    </div>
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-diamond"></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Diamond Orders</h6>
                                    <small>Total: ₹{{ number_format($orderSales->diamond_sales ?? 0, 2) }}</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{ $orderSales->diamond_orders ?? 0 }} orders</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-ring"></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Jewellery Orders</h6>
                                    <small>Total: ₹{{ number_format($orderSales->jewelry_sales ?? 0, 2) }}</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{ $orderSales->jewelry_orders ?? 0 }} orders</small>
                                </div>
                            </div>
                        </li>
                        @if($customerStats)
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-user"></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Customers</h6>
                                    <small>Unique customers</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{ $customerStats->total_customers ?? 0 }}</small>
                                </div>
                            </div>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sales Comparison -->
        <div class="col-md-6 col-lg-4 mb-4" id="sales-comparison-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="sales-comparison">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sales Comparison</h5>
                    <small class="text-muted">{{ $selectedYear }} vs {{ $selectedYear - 1 }}</small>
                </div>
                <div class="card-body">
                    <div id="salesComparisonChart"></div>
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-6">
                                <div class="border rounded p-3 text-center">
                                    <h6 class="mb-1">{{ $selectedYear }}</h6>
                                    <h4 class="mb-0 text-primary">₹{{ number_format($salesComparison[$selectedYear]['total'] ?? 0, 2) }}</h4>
                                    <small class="text-muted">Total Revenue</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 text-center">
                                    <h6 class="mb-1">{{ $selectedYear - 1 }}</h6>
                                    <h4 class="mb-0 text-secondary">₹{{ number_format($salesComparison[$selectedYear - 1]['total'] ?? 0, 2) }}</h4>
                                    <small class="text-muted">Total Revenue</small>
                                </div>
                            </div>
                        </div>
                        @php
                            $currentYearTotal = $salesComparison[$selectedYear]['total'] ?? 0;
                            $previousYearTotal = $salesComparison[$selectedYear - 1]['total'] ?? 0;
                            $growthPercent = $previousYearTotal > 0 ? (($currentYearTotal - $previousYearTotal) / $previousYearTotal) * 100 : 0;
                        @endphp
                        <div class="mt-3 text-center">
                            <span class="badge bg-{{ $growthPercent >= 0 ? 'success' : 'danger' }}">
                                <i class="bx bx-chevron-{{ $growthPercent >= 0 ? 'up' : 'down' }}"></i>
                                {{ number_format(abs($growthPercent), 2) }}% {{ $growthPercent >= 0 ? 'Growth' : 'Decline' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-outline-danger hide-card-btn" data-card-id="sales-comparison">
                            <i class="bx bx-hide"></i> Hide
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="col-md-6 col-lg-4 mb-4" id="transactions-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="transactions">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title m-0 me-2">Transactions {{ $selectedYear }}</h5>
                        <small class="text-muted">Payment modes distribution</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="javascript:void(0);">View Details</a>
                            <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="transactions">Hide Card</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="p-0 m-0">
                        @foreach($transactions as $transaction)
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                @if($transaction->payment_mode == 'cash')
                                <i class="bx bx-money text-success"></i>
                                @elseif($transaction->payment_mode == 'card')
                                <i class="bx bx-credit-card text-primary"></i>
                                @elseif($transaction->payment_mode == 'upi')
                                <i class="bx bx-transfer text-info"></i>
                                @elseif($transaction->payment_mode == 'paypal')
                                <i class="bx bx-paypal text-warning"></i>
                                @else
                                <i class="bx bx-wallet text-secondary"></i>
                                @endif
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <small class="text-muted d-block mb-1">Payment Mode</small>
                                    <h6 class="mb-0">{{ ucfirst($transaction->payment_mode) }}</h6>
                                </div>
                                <div class="user-progress d-flex align-items-center gap-1">
                                    <h6 class="mb-0">₹{{ number_format($transaction->total_amount, 2) }}</h6>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @if($transactions->isEmpty())
                    <div class="text-center py-4">
                        <i class="bx bx-wallet text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No transactions for {{ $selectedYear }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-stats-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Status Distribution {{ $selectedYear }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($orderStatuses as $status)
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ ucfirst($status->order_status) }}</h6>
                                        <small class="text-muted">{{ $status->count }} orders</small>
                                    </div>
                                    <div class="avatar avatar-sm">
                                        @php
                                            $color = match($status->order_status) {
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="avatar-initial rounded bg-label-{{ $color }}">
                                            <i class="bx bx-{{ match($status->order_status) {
                                                'pending' => 'time-five',
                                                'confirmed' => 'check-circle',
                                                'shipped' => 'package',
                                                'delivered' => 'check-double',
                                                'cancelled' => 'x-circle',
                                                default => 'info-circle'
                                            } }}"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Value: ₹{{ number_format($status->total_value, 2) }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if($orderStatuses->isEmpty())
                        <div class="col-12 text-center py-4">
                            <i class="bx bx-package text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">No orders for {{ $selectedYear }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diamond Masters Section -->
    <div class="row mb-4 diamond-masters-section">
        <div class="col-12">
            <h5 class="pb-1 mb-4">
                <i class="bx bx-diamond me-2"></i> Diamond Masters
                <button class="btn btn-sm btn-outline-secondary toggle-section-btn" data-section="diamond-masters">
                    <i class="bx bx-chevron-up"></i> Collapse
                </button>
            </h5>
        </div>
        
        @php
            $diamondCards = [
                ['id' => 'shades', 'title' => 'Total Shades', 'count' => $totalShades, 'icon' => 'bx-palette', 'route' => 'shades.index', 'color' => 'primary'],
                ['id' => 'shapes', 'title' => 'Total Shapes', 'count' => $totalShapes, 'icon' => 'bx-shape-circle', 'route' => 'shapes.index', 'color' => 'success'],
                ['id' => 'sizes', 'title' => 'Total Sizes', 'count' => $totalSizes, 'icon' => 'bx-ruler', 'route' => 'sizes.index', 'color' => 'info'],
                ['id' => 'clarity', 'title' => 'Total Clarity', 'count' => $totalClarity, 'icon' => 'bx-search-alt', 'route' => 'clarity.index', 'color' => 'warning'],
                ['id' => 'colors', 'title' => 'Total Colors', 'count' => $totalColors, 'icon' => 'bx-color-fill', 'route' => 'color.index', 'color' => 'danger'],
                ['id' => 'cuts', 'title' => 'Total Cuts', 'count' => $totalCuts, 'icon' => 'bx-cut', 'route' => 'cut.index', 'color' => 'primary'],
                ['id' => 'girdle', 'title' => 'Total Girdle', 'count' => $totalGirdle, 'icon' => 'bx-circle', 'route' => 'girdle.index', 'color' => 'success'],
                ['id' => 'culet', 'title' => 'Total Culet', 'count' => $totalCulet, 'icon' => 'bx-dots-horizontal-rounded', 'route' => 'culet.index', 'color' => 'info'],
                ['id' => 'fancy-color', 'title' => 'Fancy Color', 'count' => $totalFancyColor, 'icon' => 'bx-palette', 'route' => 'fancyColor.index', 'color' => 'warning'],
                ['id' => 'fancy-color-intensity', 'title' => 'Fancy Color Intensity', 'count' => $totalFancyColorIntensity, 'icon' => 'bx-tone', 'route' => 'fancy-color-intensity.index', 'color' => 'danger'],
                ['id' => 'diamond-weight-groups', 'title' => 'Weight Groups', 'count' => $totalDiamondWeightGroups, 'icon' => 'bx-weight', 'route' => 'diamond-weight-groups.index', 'color' => 'primary'],
                ['id' => 'diamond-polish', 'title' => 'Polish', 'count' => $totalDiamondPolish, 'icon' => 'bx-brush', 'route' => 'diamondpolish.index', 'color' => 'success'],
                ['id' => 'diamond-lab', 'title' => 'Lab', 'count' => $totalDiamondLab, 'icon' => 'bx-test-tube', 'route' => 'diamondlab.index', 'color' => 'info'],
                ['id' => 'key-to-symbols', 'title' => 'Key to Symbols', 'count' => $totalKeyToSymbols, 'icon' => 'bx-key', 'route' => 'keytosymbols.index', 'color' => 'warning'],
            ];
        @endphp

        @foreach($diamondCards as $card)
        <div class="col-xl-3 col-md-6 mb-4" id="{{ $card['id'] }}-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="{{ $card['id'] }}">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class='bx {{ $card['icon'] }} text-{{ $card['color'] }}'></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @if(isset($card['route']))
                                <a class="dropdown-item" href="{{ route($card['route']) }}">View More</a>
                                @endif
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="{{ $card['id'] }}">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ $card['title'] }}</span>
                    <h3 class="card-title mb-2">{{ $card['count'] }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-data"></i> Total records
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Jewellery Section -->
    <div class="row mb-4 jewellery-section">
        <div class="col-12">
            <h5 class="pb-1 mb-4">
                <i class="fa fa-ring me-2"></i> Jewellery Masters
                <button class="btn btn-sm btn-outline-secondary toggle-section-btn" data-section="jewellery">
                    <i class="bx bx-chevron-up"></i> Collapse
                </button>
            </h5>
        </div>
        
        @php
            $jewelleryCards = [
                ['id' => 'metaltype', 'title' => 'Metal Types', 'count' => $totalMetalTypes, 'icon' => 'fa-ring', 'route' => 'metaltype.index', 'color' => 'primary'],
                ['id' => 'category', 'title' => 'Categories', 'count' => $totalCategories, 'icon' => 'bx-category', 'route' => 'category.index', 'color' => 'success'],
                ['id' => 'quality-groups', 'title' => 'Quality Groups', 'count' => $totalQualityGroups, 'icon' => 'bx-award', 'route' => 'diamondqualitygroup.index', 'color' => 'info'],
                ['id' => 'product-clarity', 'title' => 'Product Clarity', 'count' => $totalProductClarity, 'icon' => 'bx-search-alt', 'route' => 'ProductClarity.index', 'color' => 'warning'],
                ['id' => 'product-color', 'title' => 'Product Color', 'count' => $totalProductColor, 'icon' => 'bx-color-fill', 'route' => 'product-color.index', 'color' => 'danger'],
                ['id' => 'product-cut', 'title' => 'Product Cut', 'count' => $totalProductCut, 'icon' => 'bx-cut', 'route' => 'product-cut.index', 'color' => 'primary'],
                ['id' => 'tax-classes', 'title' => 'Tax Classes', 'count' => $totalTaxClasses, 'icon' => 'bx-receipt', 'route' => 'tax-classes.index', 'color' => 'success'],
                ['id' => 'tax-rates', 'title' => 'Tax Rates', 'count' => $totalTaxRates, 'icon' => 'bx-percentage', 'route' => 'tax-rates.index', 'color' => 'info'],
                ['id' => 'product-style-category', 'title' => 'Style Categories', 'count' => $totalProductStyleCategories, 'icon' => 'bx-category-alt', 'route' => 'product-style-category.index', 'color' => 'warning'],
                ['id' => 'collections', 'title' => 'Collections', 'count' => $totalCollections, 'icon' => 'bx-collection', 'route' => 'collections.index', 'color' => 'danger'],
                ['id' => 'style-groups', 'title' => 'Style Groups', 'count' => $totalStyleGroups, 'icon' => 'bx-group', 'route' => 'style-groups.index', 'color' => 'primary'],
            ];
        @endphp

        @foreach($jewelleryCards as $card)
        <div class="col-xl-3 col-md-6 mb-4" id="{{ $card['id'] }}-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="{{ $card['id'] }}">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            @if(strpos($card['icon'], 'fa-') !== false)
                            <i class='fa {{ $card['icon'] }} text-{{ $card['color'] }}'></i>
                            @else
                            <i class='bx {{ $card['icon'] }} text-{{ $card['color'] }}'></i>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @if(isset($card['route']))
                                <a class="dropdown-item" href="{{ route($card['route']) }}">View More</a>
                                @endif
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="{{ $card['id'] }}">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ $card['title'] }}</span>
                    <h3 class="card-title mb-2">{{ $card['count'] }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-data"></i> Total records
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Other Sections -->
    <div class="row mb-4 other-section">
        <div class="col-12">
            <h5 class="pb-1 mb-4">
                <i class="bx bx-grid-alt me-2"></i> Other Sections
                <button class="btn btn-sm btn-outline-secondary toggle-section-btn" data-section="other">
                    <i class="bx bx-chevron-up"></i> Collapse
                </button>
            </h5>
        </div>
        
        @php
            $otherCards = [
                ['id' => 'users', 'title' => 'Total Users', 'count' => $totalUsers, 'icon' => 'bx-group', 'route' => 'users.index', 'color' => 'primary'],
                ['id' => 'coupons', 'title' => 'Total Coupons', 'count' => $totalCoupons, 'icon' => 'bx-purchase-tag-alt', 'route' => 'admin.coupons.index', 'color' => 'success'],
                ['id' => 'blogs', 'title' => 'Total Blogs', 'count' => $totalBlogs, 'icon' => 'fa-blog', 'route' => 'admin.blogs.index', 'color' => 'info'],
                ['id' => 'metal-prices', 'title' => 'Metal Prices', 'count' => $totalMetalPrices, 'icon' => 'bx-coin', 'route' => 'metal-prices.index', 'color' => 'warning'],
                ['id' => 'appointments', 'title' => 'Appointments', 'count' => $totalAppointments, 'icon' => 'fa-calendar-alt', 'route' => 'admin.appointments.index', 'color' => 'danger'],
                ['id' => 'vendors', 'title' => 'Total Vendors', 'count' => $totalVendors, 'icon' => 'bx-store', 'route' => 'vendor.index', 'color' => 'primary'],
                ['id' => 'enquiries', 'title' => 'Total Enquiries', 'count' => $totalEnquiries, 'icon' => 'fa-comment-dots', 'route' => 'enquiries.index', 'color' => 'success'],
                ['id' => 'contact-us', 'title' => 'Contact Us', 'count' => $totalContactUs, 'icon' => 'fa-envelope', 'route' => 'admin.contactus.index', 'color' => 'info'],
            ];
        @endphp

        @foreach($otherCards as $card)
        <div class="col-xl-3 col-md-6 mb-4" id="{{ $card['id'] }}-container">
            <div class="card h-100 dashboard-stats-card" data-card-id="{{ $card['id'] }}">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            @if(strpos($card['icon'], 'fa-') !== false)
                            <i class='fa {{ $card['icon'] }} text-{{ $card['color'] }}'></i>
                            @else
                            <i class='bx {{ $card['icon'] }} text-{{ $card['color'] }}'></i>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @if(isset($card['route']))
                                <a class="dropdown-item" href="{{ route($card['route']) }}">View More</a>
                                @endif
                                <a class="dropdown-item hide-card-btn" href="javascript:void(0);" data-card-id="{{ $card['id'] }}">Hide Card</a>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ $card['title'] }}</span>
                    <h3 class="card-title mb-2">{{ $card['count'] }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="bx bx-data"></i> Total records
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
<!-- / Content -->

<!-- Manage Cards Modal -->
<div class="modal fade" id="manageCardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Manage Dashboard Cards</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-3">Main Cards</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-welcome-card" data-card-id="welcome-card" checked>
                            <label class="form-check-label" for="toggle-welcome-card">Welcome Card</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-total-diamonds" data-card-id="total-diamonds" checked>
                            <label class="form-check-label" for="toggle-total-diamonds">Total Diamonds</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-total-products" data-card-id="total-products" checked>
                            <label class="form-check-label" for="toggle-total-products">Total Products</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-total-variations" data-card-id="total-variations" checked>
                            <label class="form-check-label" for="toggle-total-variations">Total Variations</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-total-orders" data-card-id="total-orders" checked>
                            <label class="form-check-label" for="toggle-total-orders">Total Orders</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-3">Charts & Reports</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-total-revenue" data-card-id="total-revenue" checked>
                            <label class="form-check-label" for="toggle-total-revenue">Total Revenue</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-profile-report" data-card-id="profile-report" checked>
                            <label class="form-check-label" for="toggle-profile-report">Profile Report</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-order-statistics" data-card-id="order-statistics" checked>
                            <label class="form-check-label" for="toggle-order-statistics">Order Statistics</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-sales-comparison" data-card-id="sales-comparison" checked>
                            <label class="form-check-label" for="toggle-sales-comparison">Sales Comparison</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-transactions" data-card-id="transactions" checked>
                            <label class="form-check-label" for="toggle-transactions">Transactions</label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-3">Diamond Masters</h6>
                        @foreach($diamondCards as $card)
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-{{ $card['id'] }}" data-card-id="{{ $card['id'] }}" checked>
                            <label class="form-check-label" for="toggle-{{ $card['id'] }}">{{ $card['title'] }}</label>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-3">Jewellery Masters</h6>
                        @foreach($jewelleryCards as $card)
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-{{ $card['id'] }}" data-card-id="{{ $card['id'] }}" checked>
                            <label class="form-check-label" for="toggle-{{ $card['id'] }}">{{ $card['title'] }}</label>
                        </div>
                        @endforeach
                        
                        <h6 class="mb-3 mt-4">Other Sections</h6>
                        @foreach($otherCards as $card)
                        <div class="form-check mb-2">
                            <input class="form-check-input card-toggle" type="checkbox" id="toggle-{{ $card['id'] }}" data-card-id="{{ $card['id'] }}" checked>
                            <label class="form-check-label" for="toggle-{{ $card['id'] }}">{{ $card['title'] }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    Hidden cards are stored in your browser's localStorage. They will remain hidden until you show them again.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCardPreferencesBtn">Save Changes</button>
                <button type="button" class="btn btn-danger" id="resetAllCardsBtn">Reset All Cards</button>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }
    
    .card.hidden {
        opacity: 0.5;
        border-style: dashed;
        border-color: #dc3545;
    }
    
    .hidden-card-container {
        display: none !important;
    }
    
    .section-collapsed .row > [class*="col-"] {
        display: none !important;
    }
    
    .section-collapsed .row > [class*="col-"]:first-child {
        display: block !important;
    }
    
    .toggle-section-btn.collapsed .bx-chevron-up::before {
        content: "\e9af";
    }
    
    .hide-card-btn {
        font-size: 0.8rem;
        padding: 0.15rem 0.5rem;
    }
    
    .dropdown-item.hide-card-btn {
        color: #dc3545;
    }
    
    .dropdown-item.hide-card-btn:hover {
        background-color: #dc3545;
        color: white;
    }
</style>

<script>
// ApexCharts initialization with dynamic data
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all charts
    initializeCharts();
    
    // Year filter change handler
    document.querySelectorAll('#yearFilterDropdown + .dropdown-menu a').forEach(item => {
        item.addEventListener('click', function(e) {
            if (!this.classList.contains('active')) {
                showLoading();
                // Page will reload with new year parameter
            }
        });
    });
    
    // Auto refresh chart data every 5 minutes
    setInterval(() => {
        refreshChartData();
    }, 300000); // 5 minutes
    
    // Load hidden cards from localStorage
    loadCardPreferences();
    
    // Handle hide card buttons
    document.querySelectorAll('.hide-card-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const cardId = this.getAttribute('data-card-id');
            hideCard(cardId);
            showNotification(`"${getCardTitle(cardId)}" card hidden`, 'info');
        });
    });
    
    // Handle section collapse/expand
    document.querySelectorAll('.toggle-section-btn').forEach(button => {
        button.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            const sectionElement = document.querySelector(`.${section}-section`);
            
            if (sectionElement.classList.contains('section-collapsed')) {
                sectionElement.classList.remove('section-collapsed');
                this.innerHTML = '<i class="bx bx-chevron-up"></i> Collapse';
                showNotification('Section expanded', 'success');
            } else {
                sectionElement.classList.add('section-collapsed');
                this.innerHTML = '<i class="bx bx-chevron-down"></i> Expand';
                showNotification('Section collapsed', 'info');
            }
            
            // Save section state
            saveSectionState(section, sectionElement.classList.contains('section-collapsed'));
        });
    });
    
    // Load section states
    loadSectionStates();
    
    // Handle card toggles in modal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('card-toggle')) {
            const cardId = e.target.getAttribute('data-card-id');
            
            if (e.target.checked) {
                showCard(cardId);
            } else {
                hideCard(cardId);
            }
        }
    });
    
    // Save button in modal
    document.getElementById('saveCardPreferencesBtn').addEventListener('click', function() {
        saveCardPreferences();
    });
    
    // Reset All Cards button in modal
    document.getElementById('resetAllCardsBtn').addEventListener('click', function() {
        resetDashboard();
    });
    
    // Reset View button in header
    document.getElementById('resetViewBtn').addEventListener('click', function() {
        resetDashboard();
    });
    
    // Dropdown hide card buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('dropdown-item') && e.target.classList.contains('hide-card-btn')) {
            e.preventDefault();
            const cardId = e.target.getAttribute('data-card-id');
            hideCard(cardId);
            showNotification(`"${getCardTitle(cardId)}" card hidden`, 'info');
        }
    });
});

// Function to initialize all charts
function initializeCharts() {
    // Total Revenue Chart
    const revenueChartEl = document.getElementById('totalRevenueChart');
    if (revenueChartEl) {
        const revenueChartOptions = {
            series: [{
                name: 'Revenue',
                data: @json($chartRevenue)
            }],
            chart: {
                height: 350,
                type: 'line',
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            colors: ['#7367F0'],
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                }
            },
            markers: {
                size: 6
            },
            xaxis: {
                categories: @json($chartLabels),
                title: {
                    text: 'Months'
                }
            },
            yaxis: {
                title: {
                    text: 'Revenue (₹)'
                },
                labels: {
                    formatter: function(value) {
                        return '₹' + value.toLocaleString('en-IN');
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return '₹' + value.toLocaleString('en-IN');
                    }
                }
            }
        };
        
        window.totalRevenueChart = new ApexCharts(revenueChartEl, revenueChartOptions);
        window.totalRevenueChart.render();
    }
    
    // Order Statistics Chart (Pie Chart)
    const orderStatsChartEl = document.getElementById('orderStatisticsChart');
    if (orderStatsChartEl) {
        const orderStatsOptions = {
            series: [{{ $orderSales->diamond_orders ?? 0 }}, {{ $orderSales->jewelry_orders ?? 0 }}],
            chart: {
                type: 'donut',
                height: 165
            },
            labels: ['Diamond Orders', 'Jewellery Orders'],
            colors: ['#7367F0', '#28C76F'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%'
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' orders';
                    }
                }
            }
        };
        
        window.orderStatisticsChart = new ApexCharts(orderStatsChartEl, orderStatsOptions);
        window.orderStatisticsChart.render();
    }
    
    // Sales Comparison Chart
    const salesComparisonEl = document.getElementById('salesComparisonChart');
    if (salesComparisonEl) {
        const comparisonOptions = {
            series: [{
                name: '{{ $selectedYear }}',
                data: @json($salesComparison[$selectedYear]['monthly'] ?? array_fill(0, 12, 0))
            }, {
                name: '{{ $selectedYear - 1 }}',
                data: @json($salesComparison[$selectedYear - 1]['monthly'] ?? array_fill(0, 12, 0))
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            colors: ['#7367F0', '#EA5455'],
            xaxis: {
                categories: @json($chartLabels)
            },
            yaxis: {
                title: {
                    text: 'Revenue (₹)'
                },
                labels: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN');
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN');
                    }
                }
            }
        };
        
        window.salesComparisonChart = new ApexCharts(salesComparisonEl, comparisonOptions);
        window.salesComparisonChart.render();
    }
    
    // Profile Report Chart
    const profileReportChartEl = document.getElementById('profileReportChart');
    if (profileReportChartEl) {
        const profileOptions = {
            series: [{
                data: @json($chartRevenue)
            }],
            chart: {
                type: 'area',
                height: 100,
                sparkline: {
                    enabled: true
                }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#7367F0'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.5,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN');
                    }
                }
            }
        };
        
        window.profileReportChart = new ApexCharts(profileReportChartEl, profileOptions);
        window.profileReportChart.render();
    }
}

// Function to refresh chart data via AJAX
function refreshChartData() {
    const year = {{ $selectedYear }};
    
    fetch(`/admin/dashboard/chart-data?year=${year}`)
        .then(response => response.json())
        .then(data => {
            // Update Total Revenue Chart
            if (window.totalRevenueChart) {
                window.totalRevenueChart.updateSeries([{
                    name: 'Revenue',
                    data: data.revenue
                }]);
            }
            
            // Update other charts if needed
            showNotification('Chart data refreshed', 'success');
        })
        .catch(error => {
            console.error('Error refreshing chart data:', error);
        });
}

// Function to export chart data
function exportChartData() {
    const year = {{ $selectedYear }};
    const data = {
        labels: @json($chartLabels),
        revenue: @json($chartRevenue),
        orders: @json($chartOrders)
    };
    
    // Create CSV content
    let csvContent = "Month,Revenue,Orders\n";
    data.labels.forEach((label, index) => {
        csvContent += `${label},${data.revenue[index]},${data.orders[index]}\n`;
    });
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `revenue-data-${year}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification('Data exported successfully', 'success');
}

// Loading functions
function showLoading() {
    // Add loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'chartLoading';
    loadingDiv.className = 'chart-loading-overlay';
    loadingDiv.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.querySelector('.container-xxl').appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById('chartLoading');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Dashboard card management functions
function saveCardPreferences() {
    const checkboxes = document.querySelectorAll('.card-toggle:checked');
    const allCheckboxes = document.querySelectorAll('.card-toggle');
    
    const hiddenCards = [];
    
    allCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            hiddenCards.push(checkbox.getAttribute('data-card-id'));
        }
    });
    
    localStorage.setItem('hiddenDashboardCards', JSON.stringify(hiddenCards));
    
    // Update UI
    loadCardPreferences();
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('manageCardsModal'));
    modal.hide();
    
    showNotification('Card preferences saved!', 'success');
}

function resetDashboard() {
    if (confirm('Are you sure you want to reset all cards to default view?')) {
        localStorage.removeItem('hiddenDashboardCards');
        localStorage.removeItem('dashboardSectionStates');
        
        // Show all cards
        document.querySelectorAll('.hidden-card-container').forEach(container => {
            container.classList.remove('hidden-card-container');
        });
        
        document.querySelectorAll('.card.hidden').forEach(card => {
            card.classList.remove('hidden');
        });
        
        // Check all checkboxes
        document.querySelectorAll('.card-toggle').forEach(checkbox => {
            checkbox.checked = true;
        });
        
        // Expand all sections
        document.querySelectorAll('.section-collapsed').forEach(section => {
            section.classList.remove('section-collapsed');
        });
        
        document.querySelectorAll('.toggle-section-btn').forEach(button => {
            button.innerHTML = '<i class="bx bx-chevron-up"></i> Collapse';
        });
        
        showNotification('Dashboard reset to default view', 'success');
    }
}

function loadCardPreferences() {
    const hiddenCards = JSON.parse(localStorage.getItem('hiddenDashboardCards')) || [];
    
    // Update checkboxes in modal
    hiddenCards.forEach(cardId => {
        const checkbox = document.querySelector(`#toggle-${cardId}`);
        if (checkbox) {
            checkbox.checked = false;
        }
    });
    
    // Hide cards on page
    hiddenCards.forEach(cardId => {
        const container = document.getElementById(`${cardId}-container`);
        if (container) {
            container.classList.add('hidden-card-container');
        }
        
        const card = document.querySelector(`[data-card-id="${cardId}"]`);
        if (card) {
            card.classList.add('hidden');
        }
    });
}

function hideCard(cardId) {
    // Add to hidden cards
    const hiddenCards = JSON.parse(localStorage.getItem('hiddenDashboardCards')) || [];
    if (!hiddenCards.includes(cardId)) {
        hiddenCards.push(cardId);
        localStorage.setItem('hiddenDashboardCards', JSON.stringify(hiddenCards));
    }
    
    // Update checkbox
    const checkbox = document.querySelector(`#toggle-${cardId}`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    // Hide card
    const container = document.getElementById(`${cardId}-container`);
    if (container) {
        container.classList.add('hidden-card-container');
    }
    
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    if (card) {
        card.classList.add('hidden');
    }
}

function showCard(cardId) {
    // Remove from hidden cards
    let hiddenCards = JSON.parse(localStorage.getItem('hiddenDashboardCards')) || [];
    hiddenCards = hiddenCards.filter(id => id !== cardId);
    localStorage.setItem('hiddenDashboardCards', JSON.stringify(hiddenCards));
    
    // Update checkbox
    const checkbox = document.querySelector(`#toggle-${cardId}`);
    if (checkbox) {
        checkbox.checked = true;
    }
    
    // Show card
    const container = document.getElementById(`${cardId}-container`);
    if (container) {
        container.classList.remove('hidden-card-container');
    }
    
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    if (card) {
        card.classList.remove('hidden');
    }
}

function getCardTitle(cardId) {
    // Map card IDs to titles
    const cardTitles = {
        'welcome-card': 'Welcome Card',
        'total-diamonds': 'Total Diamonds',
        'total-products': 'Total Products',
        'total-variations': 'Total Variations',
        'total-orders': 'Total Orders',
        'total-revenue': 'Total Revenue',
        'profile-report': 'Profile Report',
        'order-statistics': 'Order Statistics',
        'sales-comparison': 'Sales Comparison',
        'expense-overview': 'Expense Overview',
        'transactions': 'Transactions',
    };
    
    return cardTitles[cardId] || cardId.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function saveSectionState(section, isCollapsed) {
    const sectionStates = JSON.parse(localStorage.getItem('dashboardSectionStates')) || {};
    sectionStates[section] = isCollapsed;
    localStorage.setItem('dashboardSectionStates', JSON.stringify(sectionStates));
}

function loadSectionStates() {
    const sectionStates = JSON.parse(localStorage.getItem('dashboardSectionStates')) || {};
    
    Object.keys(sectionStates).forEach(section => {
        if (sectionStates[section]) {
            const sectionElement = document.querySelector(`.${section}-section`);
            const button = document.querySelector(`[data-section="${section}"]`);
            
            if (sectionElement && button) {
                sectionElement.classList.add('section-collapsed');
                button.innerHTML = '<i class="bx bx-chevron-down"></i> Expand';
            }
        }
    });
}

function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Make functions available globally
window.saveCardPreferences = saveCardPreferences;
window.resetDashboard = resetDashboard;
window.hideCard = hideCard;
window.showCard = showCard;
window.exportChartData = exportChartData;
window.refreshChartData = refreshChartData;

// Dashboard Search Functions
document.addEventListener('DOMContentLoaded', function() {
    // Collect dashboard card data for search
    window.collectDashboardCardData = function() {
        const cards = [];
        
        // Function to add card data
        function addCard(id, title, category, icon = 'bx-cube', color = 'primary', keywords = []) {
            cards.push({
                id: id,
                title: title,
                category: category,
                icon: icon,
                color: color,
                keywords: keywords
            });
        }
        
        // Main cards
        addCard('welcome-card', 'Welcome Card', 'Overview', 'bx-user', 'primary', ['welcome', 'greeting', 'overview']);
        addCard('total-diamonds', 'Total Diamonds', 'Statistics', 'bx-diamond', 'primary', ['diamond', 'count', 'total']);
        addCard('total-products', 'Total Products', 'Statistics', 'bx-package', 'success', ['product', 'items', 'count']);
        addCard('total-variations', 'Total Variations', 'Statistics', 'bx-slider-alt', 'info', ['variation', 'options', 'count']);
        addCard('total-orders', 'Total Orders', 'Statistics', 'bx-cart', 'warning', ['order', 'sales', 'count']);
        addCard('total-revenue', 'Total Revenue', 'Charts', 'bx-dollar', 'danger', ['revenue', 'income', 'money', 'chart']);
        addCard('profile-report', 'Profile Report', 'Charts', 'bx-user-circle', 'primary', ['profile', 'report', 'user']);
        addCard('order-statistics', 'Order Statistics', 'Analytics', 'bx-stats', 'success', ['order', 'statistics', 'analytics']);
        addCard('expense-overview', 'Expense Overview', 'Analytics', 'bx-wallet', 'info', ['expense', 'overview', 'cost']);
        addCard('transactions', 'Transactions', 'Analytics', 'bx-transfer', 'warning', ['transaction', 'payment', 'transfer']);
        addCard('sales-comparison', 'Sales Comparison', 'Analytics', 'bx-bar-chart', 'info', ['comparison', 'sales', 'growth']);
        
        // Diamond cards
        @foreach($diamondCards as $card)
        addCard('{{ $card["id"] }}', '{{ $card["title"] }}', 'Diamond Masters', '{{ $card["icon"] }}', '{{ $card["color"] }}', ['diamond', '{{ strtolower($card["title"]) }}']);
        @endforeach
        
        // Jewellery cards
        @foreach($jewelleryCards as $card)
        @php
            $icon = strpos($card['icon'], 'fa-') !== false ? $card['icon'].' fa' : $card['icon'];
        @endphp
        addCard('{{ $card["id"] }}', '{{ $card["title"] }}', 'Jewellery Masters', '{{ $icon }}', '{{ $card["color"] }}', ['jewellery', '{{ strtolower($card["title"]) }}']);
        @endforeach
        
        // Other cards
        @foreach($otherCards as $card)
        @php
            $icon = strpos($card['icon'], 'fa-') !== false ? $card['icon'].' fa' : $card['icon'];
        @endphp
        addCard('{{ $card["id"] }}', '{{ $card["title"] }}', 'Other Sections', '{{ $icon }}', '{{ $card["color"] }}', ['{{ strtolower($card["title"]) }}', 'other']);
        @endforeach
        
        return cards;
    };
    
    // Check if card is hidden
    window.isCardHidden = function(cardId) {
        const container = document.getElementById(`${cardId}-container`);
        return container && container.classList.contains('hidden-card-container');
    };
    
    // Filter dashboard cards based on search
    window.filterDashboardCards = function(searchTerm) {
        // Clear previous filter
        if (window.clearDashboardFilter) {
            window.clearDashboardFilter();
        }
        
        if (!searchTerm) return;
        
        // Get all card containers
        const containers = document.querySelectorAll('[id$="-container"]');
        
        containers.forEach(container => {
            if (container.classList.contains('hidden-card-container')) {
                // Don't filter already hidden cards
                return;
            }
            
            const card = container.querySelector('.card');
            const cardId = container.id.replace('-container', '');
            const cardText = container.textContent.toLowerCase();
            
            if (!cardText.includes(searchTerm.toLowerCase())) {
                card.classList.add('card-filtered-out');
            }
        });
        
        // Filter sections
        const sections = document.querySelectorAll('.diamond-masters-section, .jewellery-section, .other-section');
        sections.forEach(section => {
            const visibleCards = section.querySelectorAll('.card:not(.card-filtered-out):not(.hidden)');
            const hiddenByUser = section.querySelectorAll('.hidden-card-container');
            
            if (visibleCards.length === hiddenByUser.length) {
                // All cards in section are filtered out
                section.style.opacity = '0.3';
                section.style.pointerEvents = 'none';
            }
        });
    };
    
    // Clear dashboard filter
    window.clearDashboardFilter = function() {
        // Remove filter from all cards
        document.querySelectorAll('.card-filtered-out').forEach(card => {
            card.classList.remove('card-filtered-out');
        });
        
        // Reset sections
        document.querySelectorAll('.diamond-masters-section, .jewellery-section, .other-section').forEach(section => {
            section.style.opacity = '';
            section.style.pointerEvents = '';
        });
    };
    
    // Scroll to card
    window.scrollToDashboardCard = function(cardId) {
        const container = document.getElementById(`${cardId}-container`);
        if (container) {
            // Show card if hidden by user
            if (container.classList.contains('hidden-card-container')) {
                showCard(cardId);
            }
            
            // Scroll to card with smooth animation
            container.scrollIntoView({ 
                behavior: 'smooth',
                block: 'center'
            });
            
            // Highlight card
            container.classList.add('highlight-card');
            setTimeout(() => {
                container.classList.remove('highlight-card');
            }, 2000);
        }
    };
    
    // Show card (simplified)
    function showCard(cardId) {
        const container = document.getElementById(`${cardId}-container`);
        if (container) {
            container.classList.remove('hidden-card-container');
            
            // Update localStorage
            let hiddenCards = JSON.parse(localStorage.getItem('hiddenDashboardCards')) || [];
            hiddenCards = hiddenCards.filter(id => id !== cardId);
            localStorage.setItem('hiddenDashboardCards', JSON.stringify(hiddenCards));
            
            // Update checkbox
            const checkbox = document.querySelector(`#toggle-${cardId}`);
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    }
    
    // Add highlight animation style
    const style = document.createElement('style');
    style.textContent = `
        .card-filtered-out {
            opacity: 0.3;
            transform: scale(0.95);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .highlight-card {
            animation: highlightCard 2s ease;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
            border-radius: 8px;
            position: relative;
            z-index: 1;
        }
        
        @keyframes highlightCard {
            0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
            50% { box-shadow: 0 0 0 20px rgba(13, 110, 253, 0); }
            100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize search when coming from navigation
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        // Set search input value
        const searchInput = document.getElementById('dashboard-global-search');
        if (searchInput) {
            searchInput.value = searchParam;
            // Trigger search
            if (window.performSearch) {
                // First collect data
                window.collectDashboardCardData();
                // Then perform search
                setTimeout(() => {
                    window.performSearch(searchParam);
                }, 100);
            }
        }
    }
    
    // Helper function to trigger search
    window.performSearch = function(searchTerm) {
        const searchInput = document.getElementById('dashboard-global-search');
        if (searchInput) {
            searchInput.value = searchTerm;
            // Create and dispatch input event
            const event = new Event('input', { bubbles: true });
            searchInput.dispatchEvent(event);
        }
    };
    
    // Expose to window
    window.triggerSearch = window.performSearch;
});
</script>
@endsection