<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController,SubmissionController,DocumentController,AnalysisController,ReviewController};
Route::post('login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('login');
Route::middleware('auth:sanctum')->group(function() {
    Route::post('logout',[AuthController::class,'logout']); Route::get('me',[AuthController::class,'me']);
    Route::get('configuration',[SubmissionController::class,'configuration']);
    Route::get('submissions',[SubmissionController::class,'index']); Route::post('submissions',[SubmissionController::class,'store'])->name('submissions.store');
    Route::prefix('submissions/{submission}')->middleware(\App\Http\Middleware\SerializeSubmissionActions::class)->group(function() {
        Route::get('',[SubmissionController::class,'show']); Route::patch('',[SubmissionController::class,'update'])->name('submissions.update');
        Route::post('messages',[AnalysisController::class,'messages'])->middleware('throttle:20,1')->name('messages');
        Route::post('documents',[DocumentController::class,'store'])->name('documents.store');
        Route::delete('documents/{documentId}',[DocumentController::class,'destroy'])->name('documents.destroy');
        Route::get('documents/{documentId}/download',[DocumentController::class,'download']);
        Route::post('precheck',[AnalysisController::class,'precheck'])->middleware('throttle:10,1')->name('precheck');
        Route::post('submit',[SubmissionController::class,'submit'])->name('submit'); Route::post('resubmit',[SubmissionController::class,'submit'])->name('resubmit');
        Route::post('reviewer-summary',[AnalysisController::class,'summary'])->name('reviewer-summary'); Route::post('review',ReviewController::class)->name('review');
        Route::post('revision-plan',[AnalysisController::class,'plan'])->name('revision-plan'); Route::post('revision-plans/{planId}/apply',[AnalysisController::class,'apply'])->name('plans.apply');
    });
});
