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
</style>
@endpush

@push('page-header')
<div class="col-sm-12">
	<h3 class="page-title">Edit Sale</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item active">Edit Sale</li>
	</ul>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-sm-12">
		<div class="card">
			<div class="card-body custom-edit-service">
                <!-- Edit Sale -->
                <form method="POST" action="{{route('sales.update',$sale)}}">
					@csrf
					@method("PUT")
					<div class="row form-row">
						<div class="col-12">
							<div class="form-group">
								<label>Product <span class="text-danger">*</span></label>
								<select class="select2 form-select form-control" name="product" id="product-select"> 
									@foreach ($products as $product)
										@if (!empty($product->purchase))
											@if (!($product->purchase->quantity <= 0) || ($product->purchase->id == $sale->product->purchase_id))
												<option {{($product->id == $sale->product_id) ? 'selected': ''}} 
                                                    value="{{$product->id}}" 
                                                    data-price="{{$product->price}}" 
                                                    data-stock="{{$product->purchase->quantity}}">
                                                    {{$product->purchase->product}} (Stock: {{$product->purchase->quantity}})
                                                </option>
											@endif
										@endif
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-12">
							<div class="form-group">
								<label>Quantity</label>
								<input type="number" class="form-control" id="quantity" value="{{$sale->quantity ?? '1'}}" min="1" name="quantity">
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
                                    <input type="text" class="form-control" id="total-price" value="{{$sale->total_price}}" readonly>
                                </div>
                            </div>
                        </div>
					</div>
					<button type="submit" class="btn btn-primary btn-block">Save Changes</button>
				</form>
                <!--/ Edit Sale -->
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

        // Update stock info on page load
        updateStockInfo();

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
            var currentSaleId = {{$sale->id}};
            var currentQuantity = {{$sale->quantity}};
            // Don't restrict the current sale's quantity to available stock
            if (currentSaleId) {
                $('#quantity').attr('max', stock + currentQuantity);
            } else {
                $('#quantity').attr('max', stock);
            }
        }
    });
</script>
@endpush