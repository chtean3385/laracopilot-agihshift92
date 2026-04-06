<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->resort_name ?? 'Hotel CRM' }} — Hotel & Resort Property Management System</title>
    <meta name="description" content="{{ $settings->resort_name ?? 'Hotel CRM' }} — Powerful hotel and resort management system. Manage bookings, guest check-ins, room availability, time-slot pricing, housekeeping, payments, and business reports all in one place.">
    <meta name="keywords" content="hotel CRM, resort management software, hotel booking system, hotel property management, resort CRM, hotel PMS, hotel check-in system, hotel guest management, resort booking software, hotel room management">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="icon" type="image/png" href="{{ asset('hotel-crm-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('hotel-crm-logo.png') }}">
    <meta name="theme-color" content="#0f172a">
    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $settings->resort_name ?? 'Hotel CRM' }} — Hotel & Resort Management System">
    <meta property="og:description" content="Complete hotel and resort management — bookings, check-ins, rooms, guests, payments, and reports.">
    <meta property="og:image" content="{{ asset('hotel-crm-logo.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    {{-- Twitter --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $settings->resort_name ?? 'Hotel CRM' }} — Hotel & Resort Management System">
    <meta name="twitter:description" content="Complete hotel CRM — bookings, check-ins, rooms, guests, and reports.">
    <meta name="twitter:image" content="{{ asset('hotel-crm-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-900 flex items-center justify-center p-4">
    <!-- Background pattern -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-slate-800/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <!-- Logo Card -->
        <div class="text-center mb-8">
            <img src="{{ asset('hotel-crm-logo.png') }}" alt="{{ $settings->resort_name ?? 'Hotel CRM' }}"
                 class="w-20 h-20 rounded-3xl shadow-2xl mb-4 mx-auto object-cover"
                 style="box-shadow:0 8px 32px rgba(6,182,212,.35);">
            <h1 class="text-2xl font-bold text-white">All in One Hotel / Resort CRM</h1>
            <p class="text-cyan-300 text-sm mt-1">Staff Portal</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 border border-white/20 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6 text-center">Sign In to Continue</h2>

            @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/40 text-red-200 rounded-xl px-4 py-3 mb-5 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-300 mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center">
                            <i class="fas fa-envelope text-slate-400 text-sm"></i>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="w-full bg-white/10 border border-white/20 text-white placeholder-slate-400 pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-cyan-400 focus:border-transparent outline-none transition-all"
                            placeholder="Enter your email" required autocomplete="email">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center">
                            <i class="fas fa-lock text-slate-400 text-sm"></i>
                        </div>
                        <input type="password" name="password" value=""
                            class="w-full bg-white/10 border border-white/20 text-white placeholder-slate-400 pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-cyan-400 focus:border-transparent outline-none transition-all"
                            placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3.5 rounded-xl font-bold text-sm hover:from-cyan-600 hover:to-blue-700 transition-all duration-200 shadow-lg hover:shadow-cyan-500/25 hover:-translate-y-0.5 transform">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In to CRM
                </button>

                <div class="text-center mt-4">
                    <a href="{{ route('password.request') }}" class="text-slate-400 text-sm hover:text-cyan-400 transition-colors">
                        <i class="fas fa-key mr-1 text-xs"></i> Forgot Password?
                    </a>
                </div>
            </form>
        </div>

    </div>
</body>
</html>
