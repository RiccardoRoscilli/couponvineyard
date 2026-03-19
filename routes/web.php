<?php
use App\Http\Controllers\activityController;
use App\Http\Controllers\reservationController;
use App\Http\Controllers\clientController;
use App\Http\Controllers\couponController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DebugExpirationController;
use App\Models\Activity;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LocationController;
use App\Models\Location;

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


Route::get('/run/bookings-sync', function (\Illuminate\Http\Request $request) {
    $secret = config('services.sync.secret');
    if (!$secret || $request->query('key') !== $secret)
        abort(403, 'Forbidden');

    $args = array_filter([
        '--from' => $request->query('from'),
        '--to' => $request->query('to'),
        '--prenota' => $request->query('prenota'),
        '--location' => $request->query('location'),
        '--dry-run' => $request->boolean('dry', false) ? true : null,
        '--show-payload' => $request->boolean('show', false) ? true : null, // 👈 nuovo
    ], fn($v) => !is_null($v) && $v !== '');

    Artisan::call('bookings:sync', $args);
    return response("<pre>" . e(Artisan::output()) . "</pre>", 200)
        ->header('Content-Type', 'text/html; charset=utf-8');
})->name('bookings.sync');

// Debug routes for expiration dates
Route::get('/debug/expiration', [DebugExpirationController::class, 'index'])->name('debug.expiration');
Route::get('/debug/expiration/update', [DebugExpirationController::class, 'update'])->name('debug.expiration.update');

Route::get('/get-products-ipratico', [activityController::class, 'getProductsiPratico'])->name('activities.category_products');




Route::get('/reservations/{id}/create', [reservationController::class, 'create'])->name('reservations.create');
Route::get('/reservations/showQR', [reservationController::class, 'showQR']);
Route::get('/reservations/successemail', [reservationController::class, 'successEmail'])->name('reservations.successemail');
Route::get('/error-coupon', [reservationController::class, 'errorCoupon'])->name('reservations.errorcoupon');
Route::post('/send-email', [reservationController::class, 'sendEmail']);
Route::get('/test-ipratico/{id}', [couponController::class, 'testIpratico']);




// route autenticate
Route::middleware('auth')->group(function () {

    Route::get('/ipratico/promo-codes', [\App\Http\Controllers\IpraticoPromoCodeController::class, 'index']);


    Route::get('/api/getlocations', [LocationController::class, 'getLocations'])->name('getlocations');
    Route::resource('locations', LocationController::class);

    Route::get('/activities/datatables', [App\Http\Controllers\activityController::class, 'getActivitiesDatatable'])->name('getactivities');
    Route::resource('/activities', activityController::class); // Index

    Route::get('/reservations', [reservationController::class, 'index'])->name('reservations.index');
    Route::get('/activities/create', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');

    Route::get('/activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');

    Route::get('/activities/{activity}/duplicate', [ActivityController::class, 'duplicate'])->name('activities.duplicate');
    Route::post('/activities/{activity}/replicate', [ActivityController::class, 'replicate'])->name('activities.replicate');

    Route::get('/', function () {

        return redirect(route('reservations.index'));

    });

    Route::get('/dashboard', function () {

        return redirect(route('activities.index'));

    });
    Route::post('/regenerate-voucher', [couponController::class, 'regenerateVoucher']);
    Route::get('coupon/{reservation}/edit', [couponController::class, 'edit'])->name('coupon.edit');
    Route::get('coupon/arriving/{reservation}/edit', [couponController::class, 'edit'])->name('coupon.edit');
    Route::get('coupon/used/{reservation}/edit', [couponController::class, 'edit'])->name('coupon.edit');
    Route::get('coupon/waiting/{reservation}/edit', [couponController::class, 'edit'])->name('coupon.edit');
    Route::get('coupon/deleted/{reservation}/edit', [couponController::class, 'edit'])->name('coupon.edit');
    Route::get('/coupon/waiting', [couponController::class, 'index'])->name('couponWaiting');
    Route::get('/coupon/arriving', [couponController::class, 'arriving'])->name('couponArriving');
    Route::get('/coupon/used', [couponController::class, 'used'])->name('couponUsed');
    Route::get('/coupon/deleted', [couponController::class, 'deleted'])->name('couponDeleted');
    Route::resource('/coupon', couponController::class);
    Route::resource('/user', UserController::class);
    Route::get('/api/users', [UserController::class, 'getUsers'])->name('getusers');


    Route::get('/api/reservations/waiting', [couponController::class, 'getReservationsWaiting'])->name('getreservationswaiting');
    Route::get('/api/reservations/arriving', [couponController::class, 'getReservationsArriving'])->name('getreservationsarriving');
    Route::get('/api/reservations/used', [couponController::class, 'getReservationsUsed'])->name('getreservationsused');
    Route::get('/api/reservations/deleted', [couponController::class, 'getReservationsDeleted'])->name('getreservationsdeleted');
    Route::get('/api/getIpraticoClientData/{customer_email}', [couponController::class, 'getIpraticoClientData'])->name('getipraticoclienteid');


    Route::PUT('coupon/{coupon}/', [couponController::class, 'update'])->name('coupon.update');
    Route::PUT('reservation/{reservation}', [couponController::class, 'edit'])->name('reservation.update');
});


Route::get('/coupon/allcoupons', [couponController::class, 'getAllCouponsIpratico'])->name('allcoupons');


Route::POST('/api/send/mail', [couponController::class, 'sendmail'])->name('api.send.mail');

Route::PUT('coupon/{reservation}/', [couponController::class, 'update'])->name('coupon.update');
Route::DELETE('coupon/{reservation}/', [couponController::class, 'destroy'])->name('coupon.destroy');

Route::post('/checkBusinessActor', [clientController::class, 'checkBusinessActor'])->name('reservations.checkbusinessactor');
Route::post('/reservations/store', [reservationController::class, 'store']);

Route::post('/reservations/createresource', [reservationController::class, 'checkClient'])->name('reservation.checkclient');
Route::post('/reservations/sendemail', [reservationController::class, 'sendEmail'])->name('reservations.email');

Route::get('/dashboard', function () {
    return redirect('/coupon');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
