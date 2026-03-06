<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Azure Paradise Resort CRM</title>
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
            <div class="inline-flex w-20 h-20 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-3xl items-center justify-center shadow-2xl mb-4">
                <i class="fas fa-umbrella-beach text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">Azure Paradise</h1>
            <p class="text-cyan-300 text-sm mt-1">Resort & Spa • Staff Portal</p>
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
                        <input type="email" name="email" value="{{ old('email', 'admin@resort.com') }}"
                            class="w-full bg-white/10 border border-white/20 text-white placeholder-slate-400 pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-cyan-400 focus:border-transparent outline-none transition-all"
                            placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center">
                            <i class="fas fa-lock text-slate-400 text-sm"></i>
                        </div>
                        <input type="password" name="password" value="admin123"
                            class="w-full bg-white/10 border border-white/20 text-white placeholder-slate-400 pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-cyan-400 focus:border-transparent outline-none transition-all"
                            placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3.5 rounded-xl font-bold text-sm hover:from-cyan-600 hover:to-blue-700 transition-all duration-200 shadow-lg hover:shadow-cyan-500/25 hover:-translate-y-0.5 transform">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In to CRM
                </button>
            </form>
        </div>

        <!-- Demo Credentials -->
        <div class="mt-6 bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Demo Credentials</p>
            <div class="space-y-2">
                <div class="flex items-center justify-between bg-white/5 rounded-lg px-3 py-2">
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-500/30 text-purple-300 mr-2">Admin</span>
                        <span class="text-slate-300 text-xs">admin@resort.com</span>
                    </div>
                    <span class="text-slate-400 text-xs font-mono">admin123</span>
                </div>
                <div class="flex items-center justify-between bg-white/5 rounded-lg px-3 py-2">
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-500/30 text-blue-300 mr-2">Manager</span>
                        <span class="text-slate-300 text-xs">manager@resort.com</span>
                    </div>
                    <span class="text-slate-400 text-xs font-mono">manager123</span>
                </div>
                <div class="flex items-center justify-between bg-white/5 rounded-lg px-3 py-2">
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-500/30 text-cyan-300 mr-2">Receptionist</span>
                        <span class="text-slate-300 text-xs">receptionist@resort.com</span>
                    </div>
                    <span class="text-slate-400 text-xs font-mono">recept123</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
