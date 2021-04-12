<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Class LoginController
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @return Application|Factory|View
     *
     */
    public function showLoginPage()
    {
        return view('auth.login');
    }

    /**
     * @return SymfonyRedirectResponse
     */
    public function redirectToProvider(): SymfonyRedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * @return RedirectResponse
     *
     */
    public function authorizeUsingGitHub(): RedirectResponse
    {
        $githubUser = Socialite::driver('github')->user();

        $name = $githubUser->getName();
        $email = $githubUser->getEmail();
        $username = $githubUser->getNickname();

        $token = $githubUser->token;

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'username' => $username,
                'token'    => $token,
            ]);
        } else {
            $user->name = $name;
            $user->username = $username;
            $user->token = $token;
            $user->save();
        }

        auth()->login($user);

        return redirect()->route('home');
    }

    /**
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        auth()->logout();
        return redirect()->route('home');
    }
}
