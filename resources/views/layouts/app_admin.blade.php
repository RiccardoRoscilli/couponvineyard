<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous">
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/c32fa91947.js" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.min.js"
        integrity="sha384-Atwg2Pkwv9vp0ygtn1JAojH0nYbwNJLPhwyoVbhoPwBhjQPR5VtM2+xf0Uwh9KtT" crossorigin="anonymous">
    </script>


    <link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <!-- Timepicker CSS e JavaScript -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>

    <style>
        .selected .nav-link {
            font-weight: bold;
            /* Cambia il font desiderato */
        }

        .button-group {
            display: flex;
        }

        .button {
            margin-right: 10px;
            padding: 10px 20px;
            border: 2px solid #000;
            /* Bordo nero */
            border-radius: 5px;
            /* Bordo arrotondato */
            background-color: #fff;
            /* Sfondo bianco */
            color: #000;
            /* Colore del testo */
            cursor: pointer;
        }

        .button:hover {
            background-color: #f0f0f0;
            /* Cambia il colore al passaggio del mouse */
        }

        .dropzone {
            border: 2px dashed #007bff;
            border-radius: 5px;
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 16px;
            color: #6c757d;
        }

        .dropzone:hover {
            background: #e2e6ea;
        }

        .dropzone .dz-message {
            font-weight: bold;
            color: #007bff;
        }

        .dropzone .dz-preview .dz-error-message {
            color: #dc3545;
        }
    </style>
</head>


<body>


    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-light shadow-sm">
            <div class="px-3"> <img width="150" src="/logos/logo-intraweb.png" /></div>
            <div class="container">



                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        <!--    <li class="nav-item">
                            <a class="nav-link {{ Request::is('activities*') ? 'fw-bold' : '' }}"
                                href="{{ route('activities.index') }}">{{ __('Dashboard') }}</a>
                        </li>
                    -->
                        <li class="nav-item">
                            <a class="nav-link {{ Str::startsWith(request()->path(), 'coupon/waiting') ? 'fw-bold' : '' }}"
                                href="{{ route('couponWaiting') }}">{{ __('Coupon in Attesa') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Str::startsWith(request()->path(), 'coupon/arriving') ? 'fw-bold' : '' }}"
                                href="{{ route('couponArriving') }}">{{ __('Coupon in Arrivo') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Str::startsWith(request()->path(), 'coupon/used') ? 'fw-bold' : '' }}"
                                href="{{ route('couponUsed') }}">{{ __('Coupon Usufruiti') }}</a>
                        </li>
                        @if (auth()->user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith(request()->path(), 'coupon/deleted') ? 'fw-bold' : '' }}"
                                    href="{{ route('couponDeleted') }}">{{ __('Coupon Cancellati') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith(request()->path(), 'user') ? 'fw-bold' : '' }}"
                                    href="{{ route('user.index') }}">{{ __('Utenti') }}</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith(request()->path(), 'activities') ? 'fw-bold' : '' }}"
                                    href="{{ route('activities.index') }}">{{ __('Prodotti') }}</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link {{ Str::startsWith(request()->path(), 'reservations') ? 'fw-bold' : '' }}"
                                href="{{ route('reservations.index') }}">{{ __('Vouchers') }}</a>
                        </li>
                        @if (auth()->user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith(request()->path(), 'locations') ? 'fw-bold' : '' }}"
                                    href="{{ route('locations.index') }}">{{ __('Locations') }}</a>
                            </li>
                        @endif
                    </ul>
                </div>




                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ms-auto">
                    <!-- Authentication Links -->
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                        @endif

                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }}
                            </a>

                            <!-- Aggiungi la classe "dropdown-menu" al menu a discesa -->
                            <div class="dropdown-menu dropdown-menu-end" data-bs-toggle="dropdown"
                                aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                    document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>

                        </li>

                    @endguest
                </ul>
            </div>
    </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>
    </div>
</body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

</html>
