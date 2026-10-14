<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AuthenticatedSessionController extends Controller {
    public function create() { return view('auth.login'); }
    public function store(Request $request) {
        $credentials = $request->validate(['email'=>['required','email'],'password'=>['required']]);
        $key = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['email'=>'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) { RateLimiter::hit($key, 60); return back()->withErrors(['email'=>'The provided credentials do not match our records.'])->onlyInput('email'); }
        $user = Auth::user();
        if (! $user->is_active || ($user->factory && $user->factory->status !== 'active')) {
            Auth::logout(); RateLimiter::hit($key, 60);
            return back()->withErrors(['email'=>'This account is inactive. Please contact the administrator.'])->onlyInput('email');
        }
        RateLimiter::clear($key); $request->session()->regenerate();
        $user->forceFill(['last_login_at'=>now()])->saveQuietly();
        return $user->isAdmin() ? redirect()->intended(route('admin.dashboard')) : redirect()->intended(route('survey.index'));
    }
    public function destroy(Request $request) { Auth::guard('web')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login'); }
}
