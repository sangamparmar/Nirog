@extends('admin.layouts.app')

<x-assets.datatables />

@push('page-css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">
<!-- Add DataTables Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
<style>
    .card {
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    .metrics-container {
        background: linear-gradient(to right, #4facfe, #00f2fe);
        padding: 20px;
        border-radius: 10px;
        color: #fff;
        margin-bottom: 20px;
    }
    .metric-card {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: transform 0.3s;
        backdrop-filter: blur(10px);
    }
    .metric-card:hover {
        transform: translateY(-5px);
    }
    .metric-value {
        font-size: 2rem;
        font-weight: 600;
    }
    .metric-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
    }
    .date-range-display {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .date-range-text {
        color: #495057;
    }
    .generate-report-btn {
        background: linear-gradient(to right, #4facfe, #00f2fe);
        border: none;
    }
    .export-btn {
        background-color: #6c757d;
        border: none;
    }
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .no-data-message {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    .no-data-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    .dropdown-item {
        cursor: pointer;
    }
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('page-header')
<div class="col-sm-7 col-auto">
	<h3 class="page-title">Sales Reports</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item active">Sales Reports</li>
	</ul>
</div>
<div class="col-sm-5 col">
	<a href="#generate_report" data-toggle="modal" class="btn btn-primary float-right mt-2 generate-report-btn">
        <i class="fas fa-chart-line mr-1"></i> Generate Report
    </a>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-md-12">
	
		@isset($sales)
            @if(count($sales) > 0)
                <!-- Report Metrics -->
                <div class="metrics-container">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value">{{ $total_sales }}</div>
                                <div class="metric-label"><i class="fas fa-shopping-cart mr-1"></i> Total Sales</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value">{{ settings('app_currency','₹') }} {{ number_format($total_revenue, 2) }}</div>
                                <div class="metric-label"><i class="fas fa-money-bill-wave mr-1"></i> Total Revenue</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value">{{ settings('app_currency','₹') }} {{ number_format($avg_sale_value, 2) }}</div>
                                <div class="metric-label"><i class="fas fa-chart-line mr-1"></i> Average Sale Value</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Date Range Display -->
                <div class="date-range-display">
                    <div class="date-range-text">
                        <i class="fas fa-calendar-alt mr-1"></i> Report Period: 
                        <strong>{{ $from_date->format('M d, Y') }}</strong> to 
                        <strong>{{ $to_date->format('M d, Y') }}</strong>
                    </div>
                    <div>
                        <a href="#generate_report" data-toggle="modal" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-filter mr-1"></i> Change Dates
                        </a>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Sales Trend</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Top Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="topProductsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Report Table -->
                <div class="card">
                    <div class="card-header report-header">
                        <h5 class="card-title">Sales Details</h5>
                        <div class="export-options">
                            <button class="btn btn-sm export-btn" id="export-btn">
                                <i class="fas fa-download mr-1"></i> Export Data
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="sales-table" class="datatable table table-hover table-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Medicine Name</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sales as $sale)
                                        <tr>
                                            <td>
                                                @if (!empty($sale->product))
                                                    @if (!empty($sale->product->purchase))
                                                        {{$sale->product->purchase->product}}
                                                        @if (!empty($sale->product->purchase->image))
                                                            <span class="avatar avatar-sm mr-2">
                                                            <img class="avatar-img" src="{{asset("storage/purchases/".$sale->product->purchase->image)}}" alt="image">
                                                            </span>
                                                        @endif
                                                    @else
                                                        {{$sale->product->name}}
                                                        @if (!empty($sale->product->image))
                                                            <span class="avatar avatar-sm mr-2">
                                                            <img class="avatar-img" src="{{asset("storage/products/".$sale->product->image)}}" alt="image">
                                                            </span>
                                                        @endif
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-pill badge-primary">{{$sale->quantity}}</span>
                                            </td>
                                            <td>
                                                <span class="text-success font-weight-bold">
                                                    {{AppSettings::get('app_currency', '$')}} {{number_format($sale->total_price, 2)}}
                                                </span>
                                            </td>
                                            <td>{{date_format(date_create($sale->created_at),"d M, Y h:i A")}}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <!-- No Data View -->
                <div class="card">
                    <div class="card-body no-data-message">
                        <div class="no-data-icon">
                            <i class="fas fa-chart-area"></i>
                        </div>
                        <h4>No Sales Data Found</h4>
                        <p>There are no sales recorded between {{ $from_date->format('M d, Y') }} and {{ $to_date->format('M d, Y') }}</p>
                        <a href="#generate_report" data-toggle="modal" class="btn btn-primary mt-3">
                            <i class="fas fa-calendar-alt mr-1"></i> Try Different Dates
                        </a>
                    </div>
                </div>
            @endif
        @else
            <!-- Initial State -->
            <div class="card">
                <div class="card-body no-data-message">
                    <div class="no-data-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h4>Generate a Sales Report</h4>
                    <p>Please select a date range to generate a comprehensive sales report</p>
                    <div class="mt-4">
                        <a href="#generate_report" data-toggle="modal" class="btn btn-primary mt-3">
                            <i class="fas fa-calendar-alt mr-1"></i> Select Date Range
                        </a>
                        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary mt-3 ml-2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Sales
                        </a>
                    </div>
                </div>
            </div>
        @endisset
	</div>
</div>

<!-- Generate Modal -->
<div class="modal fade" id="generate_report" aria-hidden="true" role="dialog">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Generate Sales Report</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{route('sales.report')}}">
					@csrf
					<div class="row form-row">
						<div class="col-12">
							<div class="form-group">
                                <label>Select Date Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>From</label>
                                            <input type="date" name="from_date" class="form-control from_date" required>
                                            <small class="form-text text-muted">Start date for report period</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>To</label>
                                            <input type="date" name="to_date" class="form-control to_date" required>
                                            <small class="form-text text-muted">End date for report period</small>
                                        </div>
                                    </div>
                                </div>
							</div>
                            <div class="form-group">
                                <label>Quick Selections</label>
                                <div class="row">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-block date-preset" data-days="7">Last 7 Days</button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-block date-preset" data-days="30">Last 30 Days</button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-block date-preset" data-days="90">Last 3 Months</button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-block date-preset" data-days="365">Last Year</button>
                                    </div>
                                </div>
                            </div>
						</div>
					</div>
					<button type="submit" class="btn btn-primary btn-block submit_report">Generate Report</button>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- /Generate Modal -->
@endsection

@push('page-js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<!-- DataTables Export Libraries -->
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function(){
        // Initialize DataTable with export buttons - IMPORTANT! This is the key part
        var salesTable = $('#sales-table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: 'PDF',
                    title: 'Sales Report - {{ isset($from_date) ? $from_date->format("d M Y") : "" }} to {{ isset($to_date) ? $to_date->format("d M Y") : "" }}',
                    className: 'hidden-export-btn'
                },
                {
                    extend: 'excel',
                    text: 'Excel',
                    title: 'Sales Report - {{ isset($from_date) ? $from_date->format("d M Y") : "" }} to {{ isset($to_date) ? $to_date->format("d M Y") : "" }}',
                    className: 'hidden-export-btn'
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    title: 'Sales Report - {{ isset($from_date) ? $from_date->format("d M Y") : "" }} to {{ isset($to_date) ? $to_date->format("d M Y") : "" }}',
                    className: 'hidden-export-btn'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    title: 'Sales Report - {{ isset($from_date) ? $from_date->format("d M Y") : "" }} to {{ isset($to_date) ? $to_date->format("d M Y") : "" }}',
                    className: 'hidden-export-btn'
                }
            ]
        });
        
        // Hide the default DataTables buttons but keep them accessible
        $('.hidden-export-btn').css('display', 'none');
        
        // Custom export button click handler - simple and direct
        $('#export-btn').on('click', function() {
            // Create dropdown menu for export options
            var $dropdown = $('<div class="dropdown-menu export-dropdown"></div>');
            
            // Add export options with direct click handlers to the built-in buttons
            $dropdown.append('<a class="dropdown-item" href="#" data-export="pdf">Export as PDF</a>');
            $dropdown.append('<a class="dropdown-item" href="#" data-export="excel">Export as Excel</a>');
            $dropdown.append('<a class="dropdown-item" href="#" data-export="csv">Export as CSV</a>');
            $dropdown.append('<a class="dropdown-item" href="#" data-export="print">Print Report</a>');
            
            // Position the dropdown
            $dropdown.css({
                display: 'block',
                position: 'absolute',
                top: $(this).offset().top + $(this).outerHeight(),
                left: $(this).offset().left,
                zIndex: 1000,
                padding: '0.5rem 0',
                margin: '0.125rem 0 0',
                backgroundColor: '#fff',
                border: '1px solid rgba(0,0,0,.15)',
                borderRadius: '0.25rem'
            }).appendTo('body');
            
            // Set up click handlers for each export option
            $dropdown.find('a.dropdown-item').on('click', function(e) {
                e.preventDefault();
                var exportType = $(this).data('export');
                
                // Find and trigger the corresponding hidden DataTables button
                $('.buttons-' + exportType + ':first').trigger('click');
                
                // Remove the dropdown
                $dropdown.remove();
            });
            
            // Close dropdown when clicking outside
            $(document).on('click.export-dropdown', function(e) {
                if (!$(e.target).closest('#export-btn').length && !$(e.target).closest('.export-dropdown').length) {
                    $('.export-dropdown').remove();
                    $(document).off('click.export-dropdown');
                }
            });
        });
        
        // Date presets for report generation
        $('.date-preset').on('click', function() {
            const days = $(this).data('days');
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - days);
            
            $('.to_date').val(formatDate(endDate));
            $('.from_date').val(formatDate(startDate));
        });
        
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Initialize charts if sales data exists
        @if(isset($sales) && count($sales) > 0 && isset($topProducts))
            // Process data for charts
            const salesData = processSalesData();
            
            // Initialize Sales Trend Chart
            const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
            const trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: salesData.labels,
                    datasets: [{
                        label: 'Sales Amount',
                        data: salesData.values,
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#4facfe',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return '{{ settings('app_currency','₹') }}' + tooltipItem.yLabel.toFixed(2);
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return '{{ settings('app_currency','₹') }}' + value;
                                }
                            }
                        }]
                    }
                }
            });
            
            // Initialize Top Products Chart using the topProducts variable from PHP
            const productsCtx = document.getElementById('topProductsChart').getContext('2d');
            const productsChart = new Chart(productsCtx, {
                type: 'horizontalBar',
                data: {
                    labels: [
                        @foreach($topProducts as $product)
                            "{{ $product['name'] }}",
                        @endforeach
                    ],
                    datasets: [{
                        label: 'Sales Quantity',
                        data: [
                            @foreach($topProducts as $product)
                                {{ $product['quantity'] }},
                            @endforeach
                        ],
                        backgroundColor: generateColors({{ count($topProducts) }}),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                callback: function(value) {
                                    // Truncate long product names
                                    if (value.length > 18) {
                                        return value.substr(0, 15) + '...';
                                    }
                                    return value;
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            title: function(tooltipItem, data) {
                                return data.labels[tooltipItem[0].index];
                            },
                            label: function(tooltipItem, data) {
                                return 'Quantity Sold: ' + tooltipItem.xLabel;
                            }
                        }
                    }
                }
            });
            
            // Helper function to process sales data for the trend chart
            function processSalesData() {
                const salesByDate = {};
                @foreach($sales as $sale)
                    const date = '{{ date_format(date_create($sale->created_at), "Y-m-d") }}';
                    if (!salesByDate[date]) {
                        salesByDate[date] = 0;
                    }
                    salesByDate[date] += {{ $sale->total_price }};
                @endforeach
                
                // Sort dates and prepare data for chart
                const sortedDates = Object.keys(salesByDate).sort();
                const labels = sortedDates.map(date => {
                    const [year, month, day] = date.split('-');
                    return `${day}/${month}`;
                });
                const values = sortedDates.map(date => salesByDate[date]);
                
                return { labels, values };
            }
            
            // Generate random colors for chart
            function generateColors(count) {
                const colors = [
                    '#4facfe', '#00f2fe', '#f093fb', '#f5576c', '#43e97b',
                    '#38f9d7', '#fa709a', '#fee140', '#a18cd1', '#fbc2eb'
                ];
                
                // If we need more colors than available in our array, generate them
                if (count > colors.length) {
                    for (let i = colors.length; i < count; i++) {
                        const r = Math.floor(Math.random() * 255);
                        const g = Math.floor(Math.random() * 255);
                        const b = Math.floor(Math.random() * 255);
                        colors.push(`rgba(${r},${g},${b},0.7)`);
                    }
                }
                
                return colors.slice(0, count);
            }
        @endif
    });
</script>
@endpush