<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn () => true);
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $status = $e instanceof \App\Exceptions\ApiException ? $e->status : ($e instanceof \Illuminate\Validation\ValidationException ? 422 : ($e instanceof \Illuminate\Auth\AuthenticationException ? 401 : ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500)));
            $code = $e instanceof \App\Exceptions\ApiException ? $e->errorCode : match($status) {401=>'UNAUTHENTICATED',403=>'FORBIDDEN',404=>'NOT_FOUND',409=>'CONFLICT',422=>'VALIDATION_ERROR',429=>'TOO_MANY_REQUESTS',default=>'SERVER_ERROR'};
            $message = $e instanceof \App\Exceptions\ApiException ? $e->getMessage() : match($status) {401=>'Silakan masuk kembali.',403=>'Anda tidak memiliki akses untuk tindakan ini.',404=>'Data tidak ditemukan.',422=>'Periksa kembali isian atau berkas Anda.',429=>'Terlalu banyak permintaan. Tunggu sebentar.',default=>'Permintaan belum dapat diproses. Coba lagi.'};
            return response()->json(['error'=>['code'=>$code,'message'=>$message,'request_id'=>(string) \Illuminate\Support\Str::uuid(),'fields'=>$e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : (object)[]]], $status);
        });
    })->create();
