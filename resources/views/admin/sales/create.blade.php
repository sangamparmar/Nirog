@extends('admin.layouts.app')


@push('page-css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
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
    .no-products-message {
        color: #dc3545;
        padding: 10px;
        font-size: 14px;
        margin-top: 5px;
        background-color: rgba(220, 53, 69, 0.1);
        border-radius: 4px;
    }
</style>
@endpush 

@push('page-header')
<div class="col-sm-12">
	<h3 class="page-title">Create Sale</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item active">Create Sale</li>
	</ul>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-sm-12">
		<div class="card">
			<div class="card-body custom-edit-service">
                <!-- Create Sale -->
                <form method="POST" action="{{route('sales.store')}}">
					@csrf
					<div class="row form-row">
						<div class="col-12">
							<div class="form-group">
								<label>Product <span class="text-danger">*</span></label>
								<select class="select2 form-select form-control" name="product" id="product-select"> 
                                    <option disabled selected> Select Product</option>
									@forelse ($products as $product)
										@if (!empty($product->purchase))
											<option value="{{$product->id}}" data-price="{{$product->price}}" data-stock="{{$product->purchase->quantity}}">
												{{$product->purchase->product}} 
												(Stock: {{$product->purchase->quantity}})
											</option>
										@else
											<option value="{{$product->id}}" data-price="{{$product->price}}" data-stock="{{$product->stock_quantity}}">
												{{$product->name}} 
												(Stock: {{$product->stock_quantity}})
											</option>
										@endif
                                    @empty
                                        <option disabled>No products available</option>
									@endforelse
								</select>
                                @if($products->isEmpty())
                                    <div class="no-products-message mt-2">
                                        <i class="fa fa-exclamation-triangle"></i> No products available. Please add products first.
                                    </div>
                                @endif
							</div>
						</div>
						<div class="col-12">
							<div class="form-group">
								<label>Quantity</label>
								<input type="number" value="1" min="1" class="form-control" name="quantity" id="quantity">
                                <small class="text-muted" id="stock-info"></small>
							</div>
						</div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Total Price</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">{{settings('app_currency','$')}}</span>
                                    </div>
                                    <input type="text" class="form-control" id="total-price" readonly>
                                </div>
                            </div>
                        </div>
					</div>
					<button type="submit" class="btn btn-primary btn-block">Save Changes</button>
				</form>
                <!--/ Create Sale -->
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
            width: '100%',
            placeholder: 'Select a product'
        });

        // Update total price and stock info when product changes
        $('#product-select').on('change', function() {
            calculateTotal();
            updateStockInfo();
        });

        // Update total price when quantity changes
        $('#quantity').on('input', function() {
            calculateTotal();
        });

        function calculateTotal() {
            var selectedOption = $('#product-select option:selected');
            var price = selectedOption.data('price') || 0;
            var quantity = $('#quantity').val() || 0;
            var total = price * quantity;
            $('#total-price').val(total.toFixed(2));
        }

        function updateStockInfo() {
            var selectedOption = $('#product-select option:selected');
            var stock = selectedOption.data('stock') || 0;
            $('#stock-info').text('Available stock: ' + stock);
            $('#quantity').attr('max', stock);
        }
        
        // Debug log to check available products
        console.log('Available products: ' + $('#product-select option').length);
    });
</script>
@endpush