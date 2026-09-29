<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Models\User;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
    public function login(ApiRequest $r) {
        $user=User::where('email',$r->validated('email'))->first();
        if(!$user||!Hash::check($r->validated('password'),$user->password)) throw new ApiException('INVALID_CREDENTIALS','Email atau kata sandi tidak sesuai.',401);
        return ['data'=>['user'=>$user,'token'=>$user->createToken('flowfix-demo',['*'],now()->addDay())->plainTextToken]];
    }
    public function me(ApiRequest $r) { return ['data'=>$r->user()]; }
    public function logout(ApiRequest $r) { $r->user()->currentAccessToken()->delete(); return response()->noContent(); }
}
