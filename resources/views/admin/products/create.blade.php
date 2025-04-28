@extends('admin.layouts.app')

@push('page-css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }
    .product-source-container {
        margin-bottom: 30px;
        padding: 15px;
        border: 1px solid #f0f0f0;
        border-radius: 5px;
        background: #fafafa;
    }
    .product-source-tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
    }
    .product-source-tab {
        padding: 10px 15px;
        margin-right: 5px;
        cursor: pointer;
        border: 1px solid transparent;
        border-bottom: none;
        border-radius: 5px 5px 0 0;
        background: #f8f8f8;
    }
    .product-source-tab.active {
        background: #fff;
        border-color: #ddd;
        border-bottom-color: #fff;
        margin-bottom: -1px;
        font-weight: bold;
        color: #20c0f3;
    }
    .product-form-container {
        display: none;
    }
    .product-form-container.active {
        display: block;
    }
    #new-product-form .form-group, 
    #purchase-product-form .form-group {
        margin-bottom: 20px;
    }
    .image-preview {
        width: 150px;
        height: 150px;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 5px;
        margin-top: 10px;
        display: none;
    }
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .form-subtitle {
        font-size: 16px;
        margin-bottom: 15px;
        font-weight: 500;
        color: #272b41;
        padding-bottom: 5px;
        border-bottom: 1px solid #f0f0f0;
    }
</style>
@endpush    

@push('page-header')
<div class="col-sm-12">
	<h3 class="page-title">Add Product</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item active">Add Product</li>
	</ul>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-sm-12">
		<div class="card">
			<div class="card-header">
                <h4 class="card-title">Add New Product</h4>
                <p class="card-text">Create a new product for your pharmacy inventory</p>
            </div>
			<div class="card-body custom-edit-service">
                <!-- Product Source Selection -->
                <div class="product-source-container">
                    <h5 class="mb-3">Choose Product Source</h5>
                    <div class="product-source-tabs">
                        <div class="product-source-tab active" data-target="new-product-form">
                            <i class="fas fa-plus-circle mr-1"></i> Add New Product
                        </div>
                        <div class="product-source-tab" data-target="purchase-product-form">
                            <i class="fas fa-shopping-basket mr-1"></i> From Purchase Item
                        </div>
                    </div>
                    <p class="mb-0">
                        <strong>Add New Product:</strong> Create a completely new product with its own stock management
                        <br>
                        <strong>From Purchase Item:</strong> Create a product linked to an existing purchase record 
                    </p>
                </div>
                
                <!-- Add Product Form -->
                <form method="post" enctype="multipart/form-data" id="product-form" action="{{route('products.store')}}">
                    @csrf
                    <input type="hidden" name="product_source" id="product-source" value="new">
                    
                    <!-- New Product Form -->
                    <div id="new-product-form" class="product-form-container active">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Product Name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="name" value="{{ old('name') }}" placeholder="Enter product name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Barcode (Optional)</label>
                                    <input class="form-control" type="text" name="barcode" value="{{ old('barcode') }}" placeholder="Enter product barcode">
                                    <small class="text-muted">Unique identifier for the product</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category <span class="text-danger">*</span></label>
                                    <select class="select2 form-control" name="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @if(count($categories) == 0)
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> No categories available. <a href="{{ route('categories.index') }}">Add a category</a> first.</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Selling Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ settings('app_currency', '$') }}</span>
                                        </div>
                                        <input class="form-control" type="number" step="0.01" name="price" value="{{ old('price') }}" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Discount (%)</label>
                                    <input class="form-control" type="number" step="0.01" min="0" max="100" name="discount" value="{{ old('discount', 0) }}">
                                    <small class="text-muted">Percentage discount on selling price</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Stock Quantity <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', 0) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input class="form-control" type="date" name="expiry_date" value="{{ old('expiry_date') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Product Image</label>
                                    <input class="form-control" type="file" name="image" id="product-image" accept="image/*">
                                    <div class="image-preview" id="image-preview">
                                        <img src="#" alt="Product Image Preview">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Description</label>
                            <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                            <small class="text-muted">Add details about the product including usage instructions, side effects, etc.</small>
                        </div>
                    </div>
                    
                    <!-- Purchase Product Form -->
                    <div id="purchase-product-form" class="product-form-container">
                        <div class="form-group">
                            <label>Purchase Item <span class="text-danger">*</span></label>
                            <select class="select2 form-control" name="purchase_id" id="purchase-select">
                                <option value="">Select Purchase Item</option>
                                @forelse($purchases as $purchase)
                                    <option value="{{ $purchase->id }}">{{ $purchase->product }} (Created: {{ date('d M Y', strtotime($purchase->created_at)) }})</option>
                                @empty
                                    <option value="" disabled>No purchase items available</option>
                                @endforelse
                            </select>
                            @if(count($purchases) == 0)
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-circle"></i> No purchase items available. Please <a href="{{ route('purchases.create') }}">add a purchase</a> first or use the "Add New Product" option.
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Selling Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ settings('app_currency', '$') }}</span>
                                        </div>
                                        <input class="form-control" type="number" step="0.01" name="price_purchase" value="{{ old('price_purchase') }}" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Discount (%)</label>
                                    <input class="form-control" type="number" step="0.01" min="0" max="100" name="discount_purchase" value="0">
                                    <small class="text-muted">Percentage discount on selling price</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Stock Quantity <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" name="stock_quantity_purchase" min="0" value="0">
                                    <small class="text-muted">The quantity available for sale</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Description</label>
                            <textarea class="form-control" name="description_purchase" rows="3">{{ old('description_purchase') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="submit-section">
                        <button class="btn btn-primary submit-btn" type="submit" name="form_submit" value="submit">
                            <i class="fas fa-save mr-1"></i> Save Product
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-times-circle mr-1"></i> Cancel
                        </a>
                    </div>
                </form>
			</div>
		</div>
	</div>			
</div>
@endsection

@push('page-js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%'
        });
        
        // Tab switching logic
        $('.product-source-tab').on('click', function() {
            // Remove active class from all tabs and hide all forms
            $('.product-source-tab').removeClass('active');
            $('.product-form-container').removeClass('active');
            
            // Add active class to clicked tab and show corresponding form
            $(this).addClass('active');
            const targetForm = $(this).data('target');
            $('#' + targetForm).addClass('active');
            
            // Update hidden input with selected product source
            if (targetForm === 'new-product-form') {
                $('#product-source').val('new');
            } else {
                $('#product-source').val('purchase');
            }
        });
        
        // Image preview for new product
        $('#product-image').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#image-preview img').attr('src', e.target.result);
                    $('#image-preview').show();
                };
                reader.readAsDataURL(file);
            } else {
                $('#image-preview').hide();
            }
        });
        
        // Handle form submission based on active tab
        $('#product-form').on('submit', function(e) {
            const productSource = $('#product-source').val();
            
            if (productSource === 'new') {
                // Validate new product form fields
                const name = $('input[name="name"]').val();
                const price = $('input[name="price"]').val();
                const category = $('select[name="category_id"]').val();
                
                if (!name || !price || !category) {
                    e.preventDefault();
                    alert('Please fill in all required fields for the new product.');
                    return false;
                }
            } else {
                // Validate purchase product form fields
                const purchaseId = $('select[name="purchase_id"]').val();
                const price = $('input[name="price_purchase"]').val();
                const stockQuantity = $('input[name="stock_quantity_purchase"]').val();
                
                if (!purchaseId || !price || !stockQuantity) {
                    e.preventDefault();
                    alert('Please fill in all required fields for the purchase product.');
                    return false;
                }
                
                // Map purchase form fields to main form fields
                $('input[name="price"]').val($('input[name="price_purchase"]').val());
                $('input[name="discount"]').val($('input[name="discount_purchase"]').val());
                $('input[name="stock_quantity"]').val($('input[name="stock_quantity_purchase"]').val());
                $('textarea[name="description"]').val($('textarea[name="description_purchase"]').val());
            }
        });
    });
</script>
@endpush