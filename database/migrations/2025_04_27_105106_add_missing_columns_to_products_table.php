<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Check if columns don't exist before adding them to avoid errors
            if (!Schema::hasColumn('products', 'name')) {
                $table->string('name')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->nullable();
            }
            
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            }
            
            if (!Schema::hasColumn('products', 'expiry_date')) {
                $table->date('expiry_date')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable();
            }
            
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->unique();
            }
            
            // Making purchase_id column nullable if it exists and isn't already nullable
            if (Schema::hasColumn('products', 'purchase_id')) {
                $table->unsignedBigInteger('purchase_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Only drop columns if they exist
            $columns = ['name', 'stock_quantity', 'category_id', 'expiry_date', 'image', 'barcode'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
