<?php

use App\Http\Controllers\gestionDesPdsController;
use App\Http\Controllers\KboApiCallController;
use App\Http\Middleware\VeriffMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\OpenAIProxyController;


// Route for return short link
Route::get('/s/{code}', [ShortLinkController::class, 'redirect']);

// Serve storage files (fallback if symlink doesn't work)
Route::get('/storage/{path}', function (string $path) {
    $file = storage_path('app/public/' . $path);
    
    if (!file_exists($file)) {
        abort(404, 'File not found');
    }
    
    // Security: prevent directory traversal attacks
    $realPath = realpath($file);
    $realBase = realpath(storage_path('app/public'));
    
    if (!$realPath || !$realBase) {
        abort(403, 'Forbidden');
    }
    
    // Normalize paths for Windows compatibility
    $realPath = str_replace('\\', '/', $realPath);
    $realBase = str_replace('\\', '/', $realBase);
    
    if (!str_starts_with($realPath, $realBase)) {
        abort(403, 'Forbidden');
    }
    
    return response()->file($file, ['Cache-Control' => 'public, max-age=31536000']);
})->where('path', '.*')->name('storage.file');

Route::prefix('/pdfs')->group(function(){
    Route::get('/', function () {return view('pdfs');})->name('pdfs');
    Route::controller(gestionDesPdsController::class)->group(
        function(){
            Route::get('/formulaire1', 'formulaire1')->name('formulaire1');
            Route::get('/formulaire2', 'formulaire2')->name('formulaire2');
            Route::get('/formulaire3', 'formulaire3')->name('formulaire3');
            Route::get('/formulaire4', 'formulaire4')->name('formulaire4');
            Route::get('/formulaire5', 'modele_a')->name('modeleA');
            Route::get('/formulaire6', 'modele_b')->name('modeleB');
            Route::get('/pv_ag', 'pvAg')->name('pv_ag');
            Route::get('/payment_conditions', 'paymentConditions')->name('paymentConditions');
        }
    );
});


Route::get('/', function () {
    return view('welcome');
});

// Public test route for media functionality
Route::get('/test-media-public', function() {
    $idAnalyzerService = new \App\Services\IdAnalyzerService();
    return $idAnalyzerService->getMediaContent('2b736271-7649-492d-b17f-28ff63fca706');
});

//  http://your-app-url/getEnterpriseByNumber?langue=en&enterprise-number=12345


Route::post('/getEnterpriseByNumber', [KboApiCallController::class, 'callApi'])->name('getEnterpriseByNumber');

// Fallback for /api/openai/convert when api routes are not loaded
Route::get('/api/openai/convert/{enterprise}', [OpenAIProxyController::class, 'convert']);


Route::get('/error', function () {return view('error');})->name('error');
Route::get('/pdfs', function () {return view('pdfs');})->name('pdfs');



// Route::prefix('/pdfs')->middleware(VeriffMiddleware::class)->group(function(){
//     Route::get('/', function () {return view('pdfs');})->name('pdfs');
//     Route::controller(gestionDesPdsController::class)->group(
//         function(){
//             Route::get('/formulaire1', 'formulaire1')->name('formulaire1');
//             Route::get('/formulaire2', 'formulaire2')->name('formulaire2');
//         }
//     );
// });


use App\Http\Controllers\IdAnalyzerController;

// Routes for ID Analyzer
Route::prefix('/idanalyzer')->controller(IdAnalyzerController::class)->group(function(){
    Route::get('/upload','upload')->name('idanalyzer.upload');
    Route::post('/session','createSession')->name('idanalyzer.session');
    Route::get('/session/{sessionId}',  'getSessionStatus')->name('idanalyzer.status');
    Route::get('/callback', 'handleCallback')->name('idanalyzer.callback');
    Route::get('/session/{sessionId}/person','getPersonData')->name('idanalyzer.person');
    Route::get('/session/{sessionId}/d','getDecision')->name('idanalyzer.decision');
    Route::get('/not-found','notFoundPage')->name('idanalyzer.not_found');
    Route::get('/verification_declined','veriffDeclinedPage')->name('idanalyzer.verification_declined');
    Route::get('/error_page', 'errorPage')->name('idanalyzer.error_page');
});




Route::get('/clear-config', function () {
    dd( '✅ config:clear exécuté');
});

Route::get('/clear-view', function () {
    Artisan::call('view:clear');
    return '✅ view:clear exécuté';
});

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    return '✅ cache:clear exécuté';
});

Route::get('/clear-all', function () {
    Artisan::call('optimize:clear');
    dd( '✅ Tous les caches ont été vidés (config, route, view)');
});

Route::get('/installMpodels', function () {
   
     exec('composer install 2>&1', $output);
    dd(nl2br(implode("\n", $output)));
});

Route::get('/migrate', function () {
    $token = request()->query('token');
    if (!$token || $token !== env('MIGRATE_TOKEN')) {
        return response('Unauthorized', 403);
    }
    Artisan::call('migrate', ['--force' => true]); // --force évite la confirmation
    return '✅ Migration exécutée avec succès';
});

// Subscription success/failure pages (redirect from Stripe)
Route::get('/subscription/success', function () {
    return view('welcome');
});

Route::get('/subscription/fail', function () {
    return view('welcome');
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');




