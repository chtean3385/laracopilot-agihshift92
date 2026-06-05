<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — {{ $settings->resort_name ?? 'Resort CRM' }}</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
    <link rel="stylesheet" href="/css/font-awesome.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-red-950 to-slate-900 flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-10 border border-white/20 shadow-2xl">
            <div class="w-20 h-20 bg-red-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-red-500/30">
                <i class="fas fa-shield-halved text-red-400 text-3xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white mb-2">Access Denied</h1>
            <p class="text-slate-400 mb-4">You don't have permission to view this page.</p>

            @if(session('crm_user_role'))
            <div class="inline-flex items-center gap-2 bg-slate-800/60 rounded-full px-4 py-2 mb-6">
                <i class="fas fa-user-tag text-slate-400 text-xs"></i>
                <span class="text-slate-300 text-sm">Your role: <strong class="text-white">{{ session('crm_user_role') }}</strong></span>
            </div>
            @endif

            <p class="text-slate-500 text-sm mb-8">Contact your Super Admin if you need access to this section.</p>

            <div class="flex gap-3 justify-center">
                <a href="javascript:history.back()" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
                    <i class="fas fa-arrow-left text-xs"></i> Go Back
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-all" style="background: linear-gradient(135deg, #c9a96e, #b08d56);" onmouseover="this.style.background='linear-gradient(135deg, #b08d56, #a07c4e)'" onmouseout="this.style.background='linear-gradient(135deg, #c9a96e, #b08d56)'">
                    <i class="fas fa-home text-xs"></i> Dashboard
                </a>
            </div>
        </div>

        <p class="text-slate-600 text-xs mt-6">{{ $settings->resort_name ?? 'Resort CRM' }} &bull; Error 403</p>
    </div>
</body>
</html>
