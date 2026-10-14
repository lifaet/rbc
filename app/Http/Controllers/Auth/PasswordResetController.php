<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
class PasswordResetController extends Controller {
    public function requestForm() { return view('auth.forgot-password'); }
    public function sendLink(Request $request) {
        $request->validate(['email'=>'required|email']);
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::ResetLinkSent ? back()->with('status', __($status)) : back()->withErrors(['email'=>__($status)]);
    }
    public function resetForm(string $token) { return view('auth.reset-password', ['token'=>$token]); }
    public function reset(Request $request) {
        $request->validate(['token'=>'required','email'=>'required|email','password'=>'required|min:8|confirmed']);
        $status = Password::reset($request->only('email','password','password_confirmation','token'), function ($user, string $password) {
            $user->forceFill(['password'=>Hash::make($password)])->setRememberToken(Str::random(60)); $user->save();
        });
        return $status === Password::PasswordReset ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email'=>__($status)]);
    }
}
