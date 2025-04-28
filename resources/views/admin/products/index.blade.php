@extends('admin.layouts.app')

<x-assets.datatables />

@push('page-css')
<style>
    .product-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 5px;
        margin-right: 10px;
    }
    .badge-stock {
        min-width: 60px;
        font-weight: 500;
    }
    .product-title {
        font-weight: 500;
        color: #333;
    }
    .product-info {
        display: flex;
        align-items: center;
    }
    .action-btns {
        white-space: nowrap;
    }
    .delete-btn {
        color: #e63c3c;
        cursor: pointer;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .categories-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 15px;
    }
    .category-pill {
        padding: 5px 15px;
        border-radius: 20px;
        background-color: #f5f5f5;
        cursor: pointer;
        transition: all 0.2s;
    }
    .category-pill:hover {
        background-color: #e0e0e0;
    }
    .category-pill.active {
        background-color: #20c0f3;
        color: white;
    }
    .product-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-card {
        flex: 1;
        min-width: 200px;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-align: center;
    }
    .stat-primary {
        background: linear-gradient(to right, #0575E6, #00F2FE);
        color: white;
    }
    .stat-secondary {
        background: linear-gradient(to right, #4776e6, #8e54e9);
        color: white;
    }
    .stat-warning {
        background: linear-gradient(to right, #f2994a, #f2c94c);
        color: white;
    }
    .stat-danger {
        background: linear-gradient(to right, #eb3349, #f45c43);
        color: white;
    }
    .stat-count {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
    }
    .modal-confirm {
        color: #636363;
        width: 400px;
    }
    .modal-confirm .modal-content {
        padding: 20px;
        border-radius: 5px;
        border: none;
    }
    .modal-confirm .modal-header {
        border-bottom: none;   
        position: relative;
    }
    .modal-confirm .modal-footer {
        border: none;
        text-align: center;
        border-radius: 5px;
        padding: 10px;
    }
    .modal-confirm .modal-title {
        text-align: center;
        font-size: 26px;
        margin: 30px 0 10px;
    }
    .modal-confirm .close {
        position: absolute;
        top: -5px;
        right: -5px;
    }
    .modal-confirm .icon-box {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        border-radius: 50%;
        z-index: 9;
        text-align: center;
        border: 3px solid #f15e5e;
    }
    .modal-confirm .icon-box i {
        color: #f15e5e;
        font-size: 46px;
        display: inline-block;
        margin-top: 13px;
    }
    .modal-confirm .btn-danger {
        color: #fff;
        border-radius: 4px;
        background: #f15e5e;
        text-decoration: none;
        transition: all 0.4s;
        line-height: normal;
        min-width: 120px;
        border: none;
        margin: 0 5px;
    }
    .modal-confirm .btn-secondary {
        color: #4e555b;
        border-radius: 4px;
        background: #dee2e6;
        text-decoration: none;
        transition: all 0.4s;
        line-height: normal;
        min-width: 120px;
        border: none;
        margin: 0 5px;
    }
    .modal-confirm .btn-danger:hover, .modal-confirm .btn-danger:focus {
        background: #ee3535;
        outline: none;
    }
</style>
@endpush

@push('page-header')
<div class="col-sm-7 col-auto">
	<h3 class="page-title">Products</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item active">Products</li>
	</ul>
</div>
<div class="col-sm-5 col">
	<a href="{{route('products.create')}}" class="btn btn-primary float-right mt-2">
	    <i class="fas fa-plus-circle mr-1"></i> Add New Product
	</a>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-md-12">
	    <!-- Product Stats -->
	    <div class="product-stats">
	        <div class="stat-card stat-primary">
	            <div class="stat-count" id="total-products">...</div>
	            <div class="stat-label">Total Products</div>
	        </div>
	        <div class="stat-card stat-secondary">
	            <div class="stat-count" id="total-categories">...</div>
	            <div class="stat-label">Categories</div>
	        </div>
	        <div class="stat-card stat-warning">
	            <div class="stat-count" id="low-stock">...</div>
	            <div class="stat-label">Low Stock</div>
	        </div>
	        <div class="stat-card stat-danger">
	            <div class="stat-count" id="expired">...</div>
	            <div class="stat-label">Expired Products</div>
	        </div>
	    </div>
	    
		<!-- Products -->
		<div class="card">
		    <div class="card-header">
                <h4 class="card-title">Product List</h4>
                <div>
                    <a href="{{route('products.create')}}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle mr-1"></i> New Product
                    </a>
                    <a href="{{route('expired')}}" class="btn btn-sm btn-danger ml-1">
                        <i class="fas fa-exclamation-circle mr-1"></i> Expired Products
                    </a>
                    <a href="{{route('outstock')}}" class="btn btn-sm btn-warning ml-1">
                        <i class="fas fa-box-open mr-1"></i> Out of Stock
                    </a>
                </div>
            </div>
			<div class="card-body">
			    <div class="categories-filter mb-3">
			        <span class="category-pill active" data-category="all">All Products</span>
			        <!-- Categories will be added dynamically via JavaScript -->
			    </div>
				<div class="table-responsive">
					<table id="product-table" class="datatable table table-hover table-center mb-0">
						<thead>
							<tr>
								<th>Product Name</th>
								<th>Category</th>
								<th>Price</th>
								<th>Quantity</th>
								<th>Expiry Date</th>
								<th class="text-center">Action</th>
							</tr>
						</thead>
						<tbody>
						    <!-- Table data loaded via AJAX -->					
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<!-- /Products -->
	</div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-confirm">
        <div class="modal-content">
            <div class="modal-header">
                <div class="icon-box">
                    <i class="fas fa-trash"></i>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <h4 class="modal-title">Are you sure?</h4>
                <p class="text-center">Do you really want to delete this product? This process cannot be undone.</p>
                <p class="product-to-delete text-center font-weight-bold"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endsection

@push('page-js')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var productTable = $('#product-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{route('products.index')}}",
            columns: [
                {data: 'product', name: 'product'},
                {data: 'category', name: 'category'},
                {data: 'price', name: 'price'},
                {data: 'quantity', name: 'quantity'},
                {data: 'expiry_date', name: 'expiry_date'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'collection',
                    text: '<i class="fas fa-download mr-1"></i> Export',
                    className: 'btn-outline-secondary',
                    buttons: [
                        'csv',
                        'excel',
                        'pdf',
                        'print'
                    ]
                }
            ],
        });
        
        // Handle delete button click
        var deleteRoute;
        var productName;
        
        // Using event delegation for the delete buttons that are created dynamically
        $(document).on('click', '.deletebtn', function(e) {
            e.preventDefault();
            deleteRoute = $(this).data('route');
            productName = $(this).data('name');
            $('.product-to-delete').text(productName);
            $('#deleteConfirmModal').modal('show');
        });
        
        // Handle delete confirmation
        $('#confirmDelete').on('click', function() {
            if (!deleteRoute) return;
            
            $.ajax({
                url: deleteRoute,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#deleteConfirmModal').modal('hide');
                    productTable.ajax.reload();
                    
                    // Show success notification
                    toastr.success('Product deleted successfully', 'Success');
                    
                    // Update the product counts
                    loadProductStats();
                },
                error: function(xhr) {
                    $('#deleteConfirmModal').modal('hide');
                    toastr.error('There was an error deleting the product', 'Error');
                }
            });
        });
        
        // Load product statistics
        function loadProductStats() {
            $.ajax({
                url: "{{ route('product.stats') }}",
                type: 'GET',
                success: function(data) {
                    $('#total-products').text(data.totalProducts || 0);
                    $('#total-categories').text(data.totalCategories || 0);
                    $('#low-stock').text(data.lowStock || 0);
                    $('#expired').text(data.expired || 0);
                },
                error: function() {
                    console.log('Error loading product statistics');
                }
            });
        }
        
        // Load categories for filtering
        function loadCategories() {
            $.ajax({
                url: "{{ route('api.categories') }}",
                type: 'GET',
                success: function(categories) {
                    if (categories && categories.length) {
                        categories.forEach(function(category) {
                            $('.categories-filter').append(
                                `<span class="category-pill" data-category="${category.id}">${category.name}</span>`
                            );
                        });
                        
                        // Handle category filter clicks
                        $('.category-pill').on('click', function() {
                            $('.category-pill').removeClass('active');
                            $(this).addClass('active');
                            
                            const category = $(this).data('category');
                            if (category === 'all') {
                                productTable.column(1).search('').draw();
                            } else {
                                productTable.column(1).search($(this).text()).draw();
                            }
                        });
                    }
                },
                error: function() {
                    console.log('Error loading categories');
                }
            });
        }
        
        // Initialize the page
        loadProductStats();
        loadCategories();
        
        // Set up a polling mechanism to refresh product stats every 5 minutes
        setInterval(loadProductStats, 300000);
    });
</script> 
@endpush