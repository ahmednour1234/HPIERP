<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use function App\CPU\translate;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }


    /**
     * @return Application|Factory|View
     */
    public function login(): View|Factory|Application
    {
        return view('admin-views.auth.login');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
public function submit(Request $request)
{
    // Validate the input data
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // Check if the "remember me" option was selected
    $remember_me = $request->has('remember');

    // Attempt to authenticate the admin with the provided credentials
    if (auth('admin')->attempt(['email' => $request->email, 'password' => $request->password], $remember_me)) {
        // If successful, redirect to a different route to prevent form resubmission
        return redirect()->route('admin.welcome');
    }

    // If authentication fails, redirect back with an error message
    return redirect()->back()->withInput($request->only('email', 'remember'))
        ->withErrors([__('Credentials do not match')]);
}


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        auth()->guard('admin')->logout();
        return redirect()->route('admin.auth.login');
    }
}
