<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Hotel & Resort CRM | Property Management System</title>
    <meta name="description" content="Hotel admin login — manage rooms, bookings, guests, check-ins, time-slot pricing, housekeeping and revenue reports from a single powerful CRM.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/hotel-crm-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-amber-900 flex items-center justify-center p-4">

    <!-- Background pattern -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-4xl grid grid-cols-1 lg:grid-cols-2 gap-0 bg-white rounded-3xl shadow-2xl overflow-hidden">

        <!-- Left panel -->
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-amber-900 p-10 flex flex-col justify-between">
            <div>
                <img src="/hotel-crm-logo.png" alt="Hotel CRM Logo"
                     class="w-16 h-16 rounded-2xl mb-6 shadow-xl object-cover" style="box-shadow:0 8px 24px rgba(0,0,0,.4);">
                <h1 class="text-3xl font-bold text-white mb-2">Grand Paradise Resort</h1>
                <p class="text-amber-400 font-medium mb-6">Property Management System</p>
                <p class="text-slate-400 text-sm leading-relaxed">Complete resort operations at your fingertips. Manage bookings, guests, rooms, check-ins, payments and generate reports — all in one powerful platform.</p>
            </div>
            <div class="mt-10 space-y-3">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Test Credentials</p>
                <div class="space-y-2">
                    <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                        <p class="text-amber-400 text-xs font-semibold"><i class="fas fa-crown mr-2"></i>Admin</p>
                        <p class="text-white text-xs mt-1">admin@resort.com / <span class="text-amber-300">admin123</span></p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                        <p class="text-blue-400 text-xs font-semibold"><i class="fas fa-briefcase mr-2"></i>Manager</p>
                        <p class="text-white text-xs mt-1">manager@resort.com / <span class="text-blue-300">manager123</span></p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                        <p class="text-green-400 text-xs font-semibold"><i class="fas fa-concierge-bell mr-2"></i>Receptionist</p>
                        <p class="text-white text-xs mt-1">reception@resort.com / <span class="text-green-300">reception123</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel -->
        <div class="p-10 flex flex-col justify-center">
            <h2 class="text-2xl font-bold text-slate-800 mb-1">Welcome Back</h2>
            <p class="text-slate-500 text-sm mb-8">Sign in to your account to continue</p>

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-slate-400"><i class="fas fa-envelope text-sm"></i></span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email"
                            class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent bg-slate-50 text-sm" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-slate-400"><i class="fas fa-lock text-sm"></i></span>
                        <input type="password" name="password" placeholder="Enter your password"
                            class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent bg-slate-50 text-sm" required>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold py-3 rounded-xl transition-all duration-200 shadow-lg shadow-amber-200 flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> Sign In to CRM
                </button>
            </form>

            <p class="text-center text-slate-400 text-xs mt-8">
                © {{ date('Y') }} Grand Paradise Resort. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
