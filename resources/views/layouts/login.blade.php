<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" type="text/css" href="/css/style.css">
    <link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/893329edc3.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
    <script src="/js/jquery-ui.min.js"></script>
    @yield('css')
</head>
    <body>
        <section class="login">
            <div class="form-container">
                <div class="login-area login-bg">
                    <div class="container">
                        <div class="col-12 text-center"><img src="/images/frontlogo2.jpg" width="250px"></div>
                        <div class="col-12 text-center p-3 "><h1>{{ env('APP_NAME') }}</h1></div>
                        <div class="col-12 text-center">

                            <div class="login-box">
                                <form method="GET" action="{{ route('login') }}">
                                    {{ csrf_field() }}
                                    <div class="login-form-body">
                                        <button class="btn btn-lg btn-microsoft btn-block text-uppercase" type="submit"><i class="fab fa-microsoft mr-2"></i>Авторизоваться при помощи Office365</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </body>
</html>