<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Submission;
use App\Services\SubmissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** File locks also serialize SQLite writes and prevent duplicate concurrent AI runs. */
class SerializeSubmissionActions
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethodSafe()) return $next($request);
        $submission=$request->route('submission');
        if (!$submission instanceof Submission) $submission=Submission::findOrFail($submission);
        app(SubmissionService::class)->authorize($submission,$request->user());
        $seconds=config('flowfix.langflow.run_timeout')+config('flowfix.extraction_timeout')+30;
        $lock=Cache::store('file')->lock('flowfix-submission-'.$submission->id,$seconds);
        if (!$lock->get()) throw new ApiException('ACTION_IN_PROGRESS','Pengajuan ini sedang diproses. Tunggu hingga selesai lalu muat ulang.',409);
        try { $submission->refresh(); return $next($request); }
        finally { $lock->release(); }
    }
}
