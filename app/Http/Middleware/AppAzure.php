<?php

namespace App\Http\Middleware;

use Closure;
use RootInc\LaravelAzureMiddleware\Azure as Azure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    View,
    Auth,
    Redirect,
    Session
};
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\User;

class AppAzure extends Azure {

    public function handle($request, Closure $next, $guard = null) {

        if (Auth::user()) {
            $user = Auth::user();
            View::share('user', $user);
        } else {
            return redirect()->route('main');
        }
        return $next($request);
    }

    public function azure(Request $request) {
        return redirect()->away($this->baseUrl . env('AZURE_TENANT_ID') . $this->route . "authorize?response_type=code&client_id=" . env('AZURE_CLIENT_ID') . "&redirect_uri=" . urlencode(env('AZURE_REDIRECT_URI')));
    }

    public function azurecallback(Request $request) {
        $client = new Client();

        $code = $request->input('code');

        try {
            $response = $client->request('POST', $this->baseUrl . env('AZURE_TENANT_ID') . $this->route . "token", [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => env('AZURE_REDIRECT_URI'),
                    'client_id' => env('AZURE_CLIENT_ID'),
                    'client_secret' => env('AZURE_CLIENT_SECRET'),
                    'code' => $code
                ]
            ]);

            $contents = json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            return $this->fail($request, $e);
        }

        $access_token = $contents->access_token;

        $refresh_token = $contents->refresh_token;
        $profile = json_decode(base64_decode(explode(".", $contents->id_token)[1]));

        $profile->avatar = $this->getUserAvatar(str_replace('@promodo.com', '', $profile->unique_name));

        $request->session()->put('_rootinc_azure_access_token', $access_token);
        $request->session()->put('_rootinc_azure_refresh_token', $refresh_token);

        return $this->success($request, $access_token, $refresh_token, $profile);
    }

    protected function success($request, $access_token, $refresh_token, $profile) {

        $email = strtolower($profile->unique_name);
        $username = explode("@", $email)[0];

        $user = User::updateOrCreate(['email' => $email], [
                    'username' => $username,
                    'secret' => hash('sha256', $username . date('Y-m-d H:i:s')),
                    'avatar' => $profile->avatar,
                    'updated_at' => date("Y-m-d H:i:s"),
                    'name' => $profile->given_name . " " . $profile->family_name
        ]);

        $request->session()->put('user_id', $user->id);
        Auth::login($user);

        return parent::success($request, $access_token, $refresh_token, $profile);
    }

    protected function handlecallback($request, Closure $next, $access_token, $refresh_token) {
        $user_id = $request->session()->get('user_id');

        if ($user_id) {
            $user = User::find($user_id);

            \App::singleton('user', function() use($user) {
                return $user;
            });
        }

        return parent::handlecallback($request, $next, $access_token, $refresh_token);
    }

    public function logout() {
        Session::flush();
        $_SESSION = array();

        if (Auth::check()) {
            Auth::logout();
        }

        $redirectURl = 'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['HTTP_HOST'];
        //  $url = 'https://login.microsoftonline.com/common/oauth2/logout?post_logout_redirect_uri='.$redirectURl;
        return Redirect::to($redirectURl);
    }

    private function getUserAvatar($login) {

        $ch = curl_init('https://in.promodo.ru/api_request/get_user_avatar/' . $login);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ));
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

}
