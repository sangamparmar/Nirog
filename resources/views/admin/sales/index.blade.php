@extends('admin.layouts.app')

<x-assets.datatables />

@push('page-css')
<style>
    .card {
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .sales-summary {
        background: linear-gradient(to right, #4facfe, #00f2fe);
        color: #fff;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .summary-box {
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }
    .summary-box:hover {
        transform: translateY(-5px);
    }
    .summary-box h4 {
        font-size: 1.5rem;
        margin-bottom: 5px;
    }
    .summary-box p {
        margin-bottom: 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .action-btn {
        width: 80px;
    }
    .btn-primary {
        background-color: #4facfe;
        border-color: #4facfe;
    }
    .btn-primary:hover {
        background-color: #38a1fd;
        border-color: #38a1fd;
    }
    .table thead th {
        background-color: #f8f9fa;
    }
    .empty-sales {
        padding: 30px;
        text-align: center;
    }
</style>
@endpush

@push('page-header')
<div class="col-sm-7 col-auto">
	<h3 class="page-title">Sales</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item active">Sales</li>
	</ul>
</div>
@can('create-sale')
<div class="col-sm-5 col">
	<a href="{{route('sales.create')}}" class="btn btn-primary float-right mt-2">
        <i class="fas fa-plus-circle mr-1"></i> Add Sale
    </a>
</div>
@endcan
@endpush

@section('content')
<!-- Summary Cards -->
<div class="row sales-summary mb-4">
    <div class="col-md-4">
        <div class="card summary-box bg-primary text-white">
            <div class="card-body">
                <h4>{{ \App\Models\Sale::count() }}</h4>
                <p><i class="fas fa-shopping-cart mr-1"></i> Total Sales</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-box bg-success text-white">
            <div class="card-body">
                <h4>{{ settings('app_currency','₹') }} {{ \App\Models\Sale::sum('total_price') }}</h4>
                <p><i class="fas fa-money-bill-wave mr-1"></i> Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card summary-box bg-info text-white">
            <div class="card-body">
                <h4>{{ \App\Models\Sale::whereDate('created_at', today())->count() }}</h4>
                <p><i class="fas fa-calendar-day mr-1"></i> Today's Sales</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
	<div class="col-md-12">
        <!-- Quick Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5><i class="fas fa-filter mr-1"></i> Quick Filters</h5>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="{{route('sales.report')}}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line mr-1"></i> Sales Reports
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary btn-sm btn-block filter-btn" data-filter="today">Today's Sales</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary btn-sm btn-block filter-btn" data-filter="week">This Week</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary btn-sm btn-block filter-btn" data-filter="month">This Month</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary btn-sm btn-block filter-btn" data-filter="all">All Sales</button>
                    </div>
                </div>
            </div>
        </div>
		<!--  Sales -->
		<div class="card">
			<div class="card-body">
				<div class="table-responsive">
					<table id="sales-table" class="datatable table table-hover table-center mb-0">
						<thead>
							<tr>
								<th>Medicine Name</th>
								<th>Quantity</th>
								<th>Total Price</th>
								<th>Date</th>
								<th class="action-btn">Action</th>
							</tr>
						</thead>
						<tbody>
							<!-- Table data will be loaded via AJAX -->
						</tbody>
					</table>
                    <!-- Empty state for no sales -->
                    <div class="empty-sales d-none">
                        <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" alt="No sales" style="width: 100px; height: auto; opacity: 0.5;">
                        <p class="mt-3 text-muted">No sales records found</p>
                        @can('create-sale')
                        <a href="{{route('sales.create')}}" class="btn btn-sm btn-primary">Add your first sale</a>
                        @endcan
                    </div>
				</div>
			</div>
		</div>
		<!-- / sales -->
	</div>
</div>
@endsection

@push('page-js')
<script>
    $(document).ready(function() {
        var table = $('#sales-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{route('sales.index')}}",
            columns: [
                {data: 'product', name: 'product'},
                {data: 'quantity', name: 'quantity'},
                {data: 'total_price', name: 'total_price'},
				{data: 'date', name: 'date'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
            drawCallback: function(settings) {
                // Show empty state if no records
                if (settings.aoData.length === 0) {
                    $('.empty-sales').removeClass('d-none');
                    $('.dataTables_wrapper').addClass('d-none');
                } else {
                    $('.empty-sales').addClass('d-none');
                    $('.dataTables_wrapper').removeClass('d-none');
                }
            }
        });
        
        // Quick filters functionality
        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            var filter = $(this).data('filter');
            var url = "{{route('sales.index')}}";
            
            if (filter === 'today') {
                url += '?filter=today';
            } else if (filter === 'week') {
                url += '?filter=week';
            } else if (filter === 'month') {
                url += '?filter=month';
            }
            
            table.ajax.url(url).load();
        });
        
        // Handle delete button confirmations
        $('#sales-table').on('click', '#deletebtn', function() {
            var id = $(this).data('id');
            var deleteRoute = $(this).data('route');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteRoute,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "id": id
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'The sale has been deleted.',
                                'success'
                            );
                            table.ajax.reload();
                        }
                    });
                }
            });
        });
    });
</script> 
@endpush