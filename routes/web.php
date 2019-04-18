<?php
use App\Models\User;
use App\Notifications\Haha;
use App\Notifications\VerifyEmailNotification;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Todo:: Properly solve this.
Auth::routes(['verify' => true]);

Route::get('/haha', function () {
    User::first()->notify(new VerifyEmailNotification);
});
