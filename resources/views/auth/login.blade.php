@extends('layouts.master2')

@section('title')
تسجيل دخول - برنامج ناينوكس
@stop

@section('css')
    <link href="{{URL::asset('assets/plugins/sidemenu-responsive-tabs/css/sidemenu-responsive-tabs.css')}}" rel="stylesheet">
    <style>
        /* Global & Body Styles */
        body {
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            background: url('https://via.placeholder.com/1920x1080/ADD8E6/FFFFFF?text=Blurred+Background') no-repeat center center fixed; /* Placeholder for a blurry background image */
            /* يمكنك استبدال الرابط أعلاه برابط صورة خلفية ضبابية خاصة بك */
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            color: #ffffff; /* White text for contrast on blurred background */
            position: relative;
        }

        /* Overlay for better text readability */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.25); /* Slightly darker overlay for better contrast */
            z-index: -1;
        }

        /* Container for the login elements to keep them centered and grouped */
        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center; /* Center items horizontally */
            justify-content: center; /* Center items vertically (though body handles overall centering) */
            padding: 10px;
            width: 100%;
            max-width: 420px; /* Adjust max-width for the overall group */
            text-align: center;
        }

        /* Branding/Logo - Now directly on the background */
        .brand-logo {
            font-family: 'Open Sans', sans-serif;
            font-size: 4.8rem !important;
            color: #ffffff !important;
            font-weight: 300;
            letter-spacing: 1.5px;
            margin-bottom: 40px; /* More space below logo */
            display: block;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
            white-space: nowrap; /* Prevent logo from wrapping */
        }

        /* Input Group Styling */
        .form-group {
            margin-bottom: 20px; /* Space between input fields */
            position: relative;
            width: 100%;
            display: flex; /* Use flex for icon & input alignment */
            align-items: center;
            background-color: rgba(255, 255, 255, 0.2); /* Semi-transparent background for input group */
            border-radius: 8px; /* Rounded corners for the input group */
            overflow: hidden; /* Ensures border radius applies correctly */
            border: 1px solid rgba(255, 255, 255, 0.4); /* Thin white border around the input group */
            transition: all 0.3s ease;
        }

        .form-group:focus-within {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.7);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.2);
        }

        /* Input Field Styling */
        .form-control {
            background-color: transparent; /* Fully transparent */
            border: none; /* No border for the input itself */
            border-radius: 0;
            padding: 15px 15px; /* Padding for text */
            height: auto;
            font-size: 1.05rem;
            color: #ffffff; /* White text color */
            width: calc(100% - 50px); /* Adjust width to make space for icon */
            flex-grow: 1; /* Allow input to fill available space */
            text-align: right; /* Align placeholder to right as in the image */
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7); /* Lighter placeholder */
            opacity: 1;
            font-weight: 300; /* Lighter font-weight for placeholder */
            text-align: left; /* Placeholder text direction */
        }
         [dir="rtl"] .form-control::placeholder {
            text-align: right;
        }


        .form-control:focus {
            box-shadow: none;
            outline: none;
            border-color: transparent;
        }

        /* Input Icons */
        .form-group .input-icon {
            padding: 0 15px; /* Padding for the icon itself */
            color: rgba(0, 0, 0, 0.8); /* White icon color */
            font-size: 1.1rem;
            pointer-events: none;
            transition: color 0.3s ease;
            order: -1; /* Place icon on the left */
        }

        /* RTL adjustments for icon and placeholder */
        [dir="rtl"] .form-group .input-icon {
            order: 1; /* Place icon on the right for RTL */
            padding: 0 15px; /* Adjust padding if needed */
        }


        /* Checkbox (Remember Me) */
        .form-check {
            margin-top: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .form-check-input {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1px solid rgba(14, 13, 13, 0.7);
            border-radius: 4px;
            background-color: transparent;
            margin-left: 8px; /* For LTR */
            margin-right: 0;
            cursor: pointer;
            position: relative;
            outline: none;
        }
        [dir="rtl"] .form-check-input {
            margin-right: 8px; /* For RTL */
            margin-left: 0;
        }


        .form-check-input:checked {
            background-color: rgba(18, 17, 17, 0.9);
            border-color: rgba(15, 15, 15, 0.9);
        }

        .form-check-input:checked::before {
            content: '\2713';
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            color: #333;
        }

        .form-check-label {
            color: rgba(18, 18, 18, 0.9);
            cursor: pointer;
        }

        /* Login Button */
        .btn-submit {
            background-color: #ffffff; /* White solid button */
            border: none;
            padding: 14px 25px;
            font-size: 1.15rem;
            border-radius: 8px;
            font-weight: 600;
            color: #333;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            width: 100%;
            max-width: 200px; /* Make button narrower as in the image */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Forgot Password Link - Remove for this design, as not in the example image */
        .forgot-password-link {
            display: none; /* Hide forgot password link */
        }

        /* Error Messages */
        .invalid-feedback {
            text-align: right; /* Align errors to the right for RTL */
            font-size: 0.8rem;
            margin-top: 5px;
            color: #ffdddd;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            width: 100%; /* Ensure it spans full width */
        }

        /* Responsive Adjustments */
        @media (max-width: 500px) {
            .login-container {
                padding: 15px;
                max-width: 90%;
            }
            .brand-logo {
                font-size: 3rem !important;
                margin-bottom: 30px;
            }
            .form-group {
                border-radius: 6px;
            }
            .form-control {
                padding: 12px 10px;
                font-size: 0.95rem;
            }
            .input-icon {
                padding: 0 12px;
                font-size: 1rem;
            }
            .btn-submit {
                padding: 12px 20px;
                font-size: 1rem;
                max-width: 180px;
            }
            .form-check-label {
                font-size: 0.9rem;
            }
            .invalid-feedback {
                font-size: 0.75rem;
            }
        }

        /* Font Awesome for Icons */
        /* If you don't have Font Awesome, include this line in master2.blade.php or here */
        /* <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" /> */
    </style>
@endsection

@section('content')
    <div class="login-container">
        <h1 class="brand-logo">Nine9x</h1>

        {{-- The image does not show "Welcome" or "Sign in to your account" --}}
        {{-- If you still want these, you can uncomment them and adjust styling --}}
         <div class="welcome-text">
            <h2 class="text-center">أهلاً بك مجدداً</h2>
            <h6 class="text-center">سجل الدخولك إلى حسابك</h6>
        </div> 
<br>
<br>
        <form method="POST" action="{{ route('login') }}" style="width: 100%;">
            @csrf
            <div class="form-group">
                <span class="input-icon"><i class="fas fa-user"></i></span>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="اسم المستخدم">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <span class="input-icon"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="كلمة المرور">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    {{ __('تذكرني') }}
                </label>
            </div>

            <button type="submit" class="btn-submit">
                {{ __('تسجيل الدخول') }}
            </button>

            {{-- The image does not show a "Forgot Password" link --}}
            {{-- If you still want it, uncomment this div --}}
            {{-- <div class="text-center">
                <a href="{{ route('password.request') }}" class="forgot-password-link">هل نسيت كلمة المرور؟</a>
            </div> --}}
        </form>
    </div>
@endsection

@section('js')
@endsection