
<?php

use App\Http\Controllers\VerificationController;
use App\Http\Controllers\NominatimProxyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KboApiCallController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\IdAnalyzerController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\AddressServiceController;
use App\Http\Controllers\DirigeantsServiceController;
use App\Http\Controllers\ShareHolderController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\OpenAIProxyController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\StripeWebhookController;
// use App\Http\Controllers\DownloadAllController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\AdminCouponController;
use App\Http\Controllers\DepositaireController;
use App\Http\Controllers\ClaudeController;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::post('/getEnterpriseByNumber', [KboApiCallController::class, 'callApi'])->name('api.getEnterpriseByNumber');
// Stripe subscription webhooks
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Auth routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('email/verify/{token}', [\App\Http\Controllers\EmailVerificationController::class, 'verify']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
Route::post('/idanalyzer/send-verification-link', [IdAnalyzerController::class, 'sendVerificationLink'])->middleware('auth:sanctum');
Route::post('/idanalyzer/resend-verification-link', [IdAnalyzerController::class, 'resendVerificationLink'])->middleware('auth:sanctum');
Route::post('/idanalyzer/update-phone-number', [IdAnalyzerController::class, 'updatePhoneNumber'])->middleware('auth:sanctum');
Route::post('/idanalyzer/webhook', [IdAnalyzerController::class, 'handle']);

// Billing & Subscription routes (new subscription model)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/billing/subscribe', [BillingController::class, 'subscribe']);
    Route::post('/billing/purchase-one-dossier', [BillingController::class, 'purchaseOneDossier']);
    Route::get('/billing/checkout/success', [BillingController::class, 'checkoutSuccess']);
    Route::post('/billing/validate-coupon', [BillingController::class, 'validateCoupon']);
    Route::post('/billing/apply-coupon', [BillingController::class, 'applyCoupon']);
    Route::post('/billing/remove-coupon', [BillingController::class, 'removeCoupon']);
    Route::get('/billing/subscription', [BillingController::class, 'getSubscription']);
    Route::get('/billing/dossier-price', [BillingController::class, 'dossierPrice']);
    Route::post('/billing/cancel', [BillingController::class, 'cancelSubscription']);
});

// routes for demande 
Route::apiResource('demandes', DemandeController::class)->middleware('auth:sanctum');
// Note: Store now handles subscription check internally instead of credits middleware
Route::post('/demandes', [DemandeController::class, 'store'])->middleware('auth:sanctum');
/* POST /demandes → create (store) now requires active subscription */


// get demandes of auth user 
Route::get('/mydemandes',[DemandeController::class,'getMyDemandes'])->middleware('auth:sanctum');
Route::post('/user/language', [AuthController::class, 'updateLanguage'])->middleware('auth:sanctum');

// route to update progress of a demande
Route::post('/demandes/{demande_id}/progress', [DemandeController::class, 'updateProgress'])->middleware('auth:sanctum');

// change the state of request
// routes/api.php
Route::post('/demandes/change_state/{demande_id}',
[DemandeController::class, 'changeState'])
    ->middleware('auth:sanctum');

// routes for address service
Route::apiResource('addressService', AddressServiceController::class)->middleware('auth:sanctum');
Route::get('/addressService/getByIdDemande/{idDemande}', [AddressServiceController::class, 'show'])->middleware('auth:sanctum');
// routes for dirigeants service
Route::apiResource('dirigeantsService', DirigeantsServiceController::class)->middleware('auth:sanctum');

// routes for depositaire service
Route::get('/depositaires/{demandeId}', [DepositaireController::class, 'showByDemande'])->middleware('auth:sanctum');

// update the GSM and send the message :

Route::put('/updateGSM/{demande}',[DemandeController::class,'updateGSM'])->middleware('auth:sanctum');
Route::get('/findGSM/{gsm}',[DemandeController::class,'findGSM'])->middleware('auth:sanctum');

Route::get('/send-sms', [SmsController::class, 'sendSms'])->name('send.sms');

// get auth user demandes
Route::get('/user-demandes', [DemandeController::class, 'userDemandes'])->middleware('auth:sanctum');


// routes for tickets
Route::apiResource('tickets', TicketController::class)->middleware('auth:sanctum');
// get tickets of auth user
Route::get('/mytickets',[TicketController::class,'getMyTickets'])->middleware('auth:sanctum');
// return the last 5 demandes
Route::get('/last-demandes', [DemandeController::class, 'lastDemandes'])->middleware('auth:sanctum');
// return the last 5 tickets
Route::get('/last-tickets', [TicketController::class, 'lastTickets'])->middleware('auth:sanctum');

// return the numbre of demandes and tickets and users
Route::get('/count', [DemandeController::class, 'count'])->middleware('auth:sanctum');
// return the numbre of demandes and tickets
Route::get('/count-client', [DemandeController::class, 'countClient'])->middleware('auth:sanctum');
//enregister text de publication 
Route::post('/demandes/{demande_id}/text', [DemandeController::class, 'storeText'])->middleware('auth:sanctum');
Route::get('/demandes/{demande}/missing-pdf-fields', [DemandeController::class, 'missingPdfFields'])->middleware('auth:sanctum');
Route::get('/demandes/{demande}/pdf-fields', [DemandeController::class, 'pdfFields'])->middleware('auth:sanctum');
Route::put('/demandes/{demande}/pdf-fields', [DemandeController::class, 'updatePdfFields'])->middleware('auth:sanctum');
Route::post('/demandes/{demande}/pdf-fields/claude', [ClaudeController::class, 'suggestPdfFields'])->middleware('auth:sanctum');
// get demnade avec share holders
Route::get('/demandes/{demande_id}/shareholders', [DemandeController::class, 'getWithSharesHolders'])->middleware('auth:sanctum');


Route::get('/idanalyzer/session/{sessionId}/media', [IdAnalyzerController::class, 'getPersonMedia'])->name('idanalyzer.session.media');
Route::get('/idanalyzer/{sessionId}/person', [IdAnalyzerController::class, 'getDecision'])->name('api.idanalyzer.person');

// Debug endpoint to test media fetching
Route::get('/idanalyzer/test-media/{mediaId}', function($mediaId) {
    $service = app(\App\Services\IdAnalyzerService::class);
    return $service->getMediaContent($mediaId);
});

// get verification data for demande
Route::get('/demandes/{demande_id}/verification', [DemandeController::class, 'getVerificationData'])->middleware('auth:sanctum');
Route::post('/demandes/{demande_id}/verification/sync', [DemandeController::class, 'syncVerificationSession'])->middleware('auth:sanctum');

// get media content by media ID
Route::get('/media/{media_id}', [DemandeController::class, 'getMediaContent']);

// Veriff media proxy (public — image tags can't send bearer tokens; media IDs are unguessable UUIDs)
Route::get('/veriff/media/{mediaId}', [VerificationController::class, 'media']);

// Get ID Analyzer auth signature (keeps keys secure on backend)
Route::get('/idanalyzer-auth/{media_id}', [DemandeController::class, 'getIdAnalyzerAuth'])->middleware('auth:sanctum');



Route::get('/idanalyzer/media/{mediaId}', [IdAnalyzerController::class, 'getMediaContent'])->name('idanalyzer.media');

// Proxy to external OpenAI conversion service to avoid CORS issues
Route::get('/openai/convert/{enterprise}', [OpenAIProxyController::class, 'convert']);

// shareholders route 
Route::apiResource('shareholders',ShareHolderController::class)->middleware('auth:sanctum');
// 767a4770-0398-4f42-ba73-1c7c52b4e0dd   Media Id 
// <img src="{{ url('/media/c59f4a31-e599-4680-b51b-bd153ba65d92') }}" alt="Selfie Video Frame" width="300">
// Route::get('/media/{mediaId}', 'MediaController@getMediaContent'); as a route 

Route::get('/getTextWithIA/{demandeId}', [OpenAIController::class, 'getChatCompletion']);
Route::post('/openai/improve-text', [OpenAIController::class, 'improveText'])->middleware('auth:sanctum');


Route::get('/profile', [ProfileController::class, 'show'])
      ->middleware('auth:sanctum');
Route::put('/profile', [ProfileController::class, 'update'])
    ->middleware('auth:sanctum');

      Route::get('/invoices', [InvoiceController::class, 'index'])
     ->middleware('auth:sanctum');

     Route::get('/receipts', [InvoiceController::class, 'receipts'])
     ->middleware('auth:sanctum');
Route::get('/readEnterprise', [\App\Http\Controllers\EnterpriseControllerNew::class, 'read']);
Route::put('/demandes/{id}', [DemandeController::class, 'updatenbShares'])->middleware('auth:sanctum');

Route::resource('verifications',VerificationController::class)->middleware('auth:sanctum');



// Download all forms and ID images as ZIP
// Route::get('/demandes/{demande_id}/download-all', [DownloadAllController::class, 'downloadAll']);
Route::get('/getExtraitTextWithIA/{demande_id}', [OpenAIController::class, 'getExtraitTextWithIA']);
Route::post('/demandes/{demande_id}/extraitText', [DemandeController::class, 'storeExtraitText'])->middleware('auth:sanctum');
// Admin marketing users list
Route::get('/admin/users', [AdminUsersController::class, 'index'])->middleware('auth:sanctum');
Route::get('/admin/users/all', [AdminUsersController::class, 'all'])->middleware('auth:sanctum');

// Admin coupon management
Route::middleware('auth:sanctum')->prefix('admin/coupons')->group(function () {
    Route::get('/', [AdminCouponController::class, 'index']);
    Route::get('/statistics', [AdminCouponController::class, 'statistics']);
    Route::get('/generate-code', [AdminCouponController::class, 'generateCode']);
    Route::get('/{id}', [AdminCouponController::class, 'show']);
    Route::post('/', [AdminCouponController::class, 'store']);
    Route::put('/{id}', [AdminCouponController::class, 'update']);
    Route::delete('/{id}', [AdminCouponController::class, 'destroy']);
    Route::post('/{id}/toggle', [AdminCouponController::class, 'toggleStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
     Route::get('/nominatim/search', [NominatimProxyController::class, 'search']);
     Route::get('/nominatim/reverse', [NominatimProxyController::class, 'reverse']);
    Route::post('/depositaires', [DepositaireController::class, 'store']);
    Route::get('/depositaires/{demande}', [DepositaireController::class, 'showByDemande']);
});