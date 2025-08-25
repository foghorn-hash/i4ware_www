<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtlassianSalesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route for Atlassian sales report
Route::group(['prefix' => 'reports', 'middleware' => 'CORS'], function ($router) {
	Route::get('/sales-report', [AtlassianSalesController::class, 'getSalesReport']);
	Route::get('/cumulative-sales', [AtlassianSalesController::class, 'getCumulativeSales']);
	Route::get('/transactions', [AtlassianSalesController::class, 'getTransactions']);
	Route::get('/combined-sales', [AtlassianSalesController::class, 'getCombinedSales']);
	Route::get('/merged-sales', [AtlassianSalesController::class, 'getMergedSales']);
	Route::get('/sales-distribution', [AtlassianSalesController::class, 'getYearlySalesDistribution']);
	Route::get('/merged-monthly-sums', [AtlassianSalesController::class, 'getIncomeByMonthAllYears']);
	Route::get('/income-years', [AtlassianSalesController::class, 'getIncomeYears']);
});
