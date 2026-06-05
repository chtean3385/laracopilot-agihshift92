<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Hotel — CRM</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
    <link rel="stylesheet" href="/css/font-awesome.min.css">
</head>
<body class="min-h-screen flex items-center justify-center" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 60%,#0f172a 100%);">

<div class="w-full max-w-md mx-auto px-4 py-10">

    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);box-shadow:0 8px 24px rgba(6,182,212,.4);">
            <i class="fas fa-hotel text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white mb-1">Select Your Hotel</h1>
        <p class="text-slate-400 text-sm">You have access to multiple properties. Choose one to continue.</p>
    </div>

    @if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5;">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <!-- Hotel cards -->
    <div class="space-y-3">
        @foreach($options as $option)
        <form method="POST" action="{{ route('select.hotel.post') }}">
            @csrf
            <input type="hidden" name="hotel_id" value="{{ $option['hotel_id'] }}">
            <button type="submit" class="w-full text-left group" style="background:none;border:none;cursor:pointer;">
                <div class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-150"
                     style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);"
                     onmouseover="this.style.background='rgba(6,182,212,.12)';this.style.borderColor='rgba(6,182,212,.35)';"
                     onmouseout="this.style.background='rgba(255,255,255,.05)';this.style.borderColor='rgba(255,255,255,.08)';">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-lg"
                         style="background:linear-gradient(135deg,#0891b2,#2563eb);">
                        {{ strtoupper(substr($option['hotel_name'], 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-white font-semibold text-sm truncate">{{ $option['hotel_name'] }}</div>
                        <div class="text-slate-400 text-xs mt-0.5">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                  style="background:rgba(6,182,212,.15);color:#67e8f9;">{{ $option['role'] }}</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-slate-500 text-sm group-hover:text-[#c9a96e] transition-colors"></i>
                </div>
            </button>
        </form>
        @endforeach
    </div>

    <!-- Logout -->
    <div class="text-center mt-8">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-slate-500 hover:text-slate-300 text-sm transition-colors">
                <i class="fas fa-sign-out-alt mr-1"></i> Sign out
            </button>
        </form>
    </div>

</div>
</body>
</html>
