<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Install — Hotel CRM</title>
<link rel="stylesheet" href="/css/tailwind.min.css">
<link rel="stylesheet" href="/css/font-awesome.min.css">
<style>
  body { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4c1d95 100%); min-height: 100vh; }
  .card { background: #fff; border-radius: 18px; box-shadow: 0 25px 60px rgba(0,0,0,0.35); }
  .step-bar .step { transition: all .3s; }
  .step-bar .step.active .circle { background: #7c3aed; color: #fff; box-shadow: 0 0 0 4px #ede9fe; }
  .step-bar .step.done  .circle { background: #16a34a; color: #fff; }
  .step-bar .step .circle { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;background:#e5e7eb;color:#6b7280;transition:all .3s; }
  .step-bar .line { flex:1;height:2px;background:#e5e7eb;margin:0 8px;position:relative;top:-18px; }
  .step-bar .line.done { background:#16a34a; }
  input:focus { outline:none; border-color:#7c3aed; box-shadow: 0 0 0 3px #ede9fe; }
  .btn-primary { background:#7c3aed;color:#fff;padding:10px 24px;border-radius:10px;font-weight:700;font-size:14px;border:none;cursor:pointer;transition:background .2s; }
  .btn-primary:hover { background:#6d28d9; }
  .btn-primary:disabled { background:#a78bfa;cursor:not-allowed; }
  .btn-secondary { background:#f1f5f9;color:#475569;padding:10px 24px;border-radius:10px;font-weight:700;font-size:14px;border:none;cursor:pointer;transition:background .2s; }
  .btn-secondary:hover { background:#e2e8f0; }
  .field label { display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em; }
  .field input { width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;color:#1e293b;background:#f8fafc;transition:all .2s; }
  .log-line { padding:5px 10px;border-radius:6px;margin-bottom:4px;font-size:13px;display:flex;align-items:flex-start;gap:8px; }
  .log-ok   { background:#f0fdf4;color:#166534; }
  .log-fail { background:#fef2f2;color:#991b1b; }
  .log-run  { background:#f8fafc;color:#475569; }
</style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<div class="card w-full max-w-xl p-8">

  {{-- Header --}}
  <div class="text-center mb-8">
    <div style="width:56px;height:56px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
      <i class="fas fa-hotel text-white text-2xl"></i>
    </div>
    <h1 class="text-2xl font-bold text-slate-800">Hotel CRM Installer</h1>
    <p class="text-slate-400 text-sm mt-1">Set up your hotel management system</p>
  </div>

  {{-- Step bar --}}
  <div class="step-bar flex items-start justify-center mb-8" id="stepBar">
    <div class="step text-center" id="sb1">
      <div class="circle mx-auto" id="sc1">1</div>
      <div class="text-xs font-600 mt-1 text-slate-500" style="font-size:11px;font-weight:700;">Database</div>
    </div>
    <div class="line" id="line1"></div>
    <div class="step text-center" id="sb2">
      <div class="circle mx-auto" id="sc2">2</div>
      <div class="text-xs font-600 mt-1 text-slate-500" style="font-size:11px;font-weight:700;">App & Admin</div>
    </div>
    <div class="line" id="line2"></div>
    <div class="step text-center" id="sb3">
      <div class="circle mx-auto" id="sc3">3</div>
      <div class="text-xs font-600 mt-1 text-slate-500" style="font-size:11px;font-weight:700;">Install</div>
    </div>
  </div>

  {{-- STEP 1 — Database --}}
  <div id="step1">
    <h2 class="text-lg font-bold text-slate-700 mb-4"><i class="fas fa-database mr-2 text-violet-500"></i>Database Connection</h2>
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="field col-span-2 sm:col-span-1">
        <label>DB Host</label>
        <input type="text" id="db_host" value="127.0.0.1" placeholder="127.0.0.1">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>DB Port</label>
        <input type="text" id="db_port" value="3306" placeholder="3306">
      </div>
      <div class="field col-span-2">
        <label>Database Name</label>
        <input type="text" id="db_database" placeholder="hotel_crm">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>Username</label>
        <input type="text" id="db_username" placeholder="root">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>Password</label>
        <input type="password" id="db_password" placeholder="(leave blank if none)">
      </div>
    </div>

    <div id="dbMsg" class="hidden mb-4 p-3 rounded-lg text-sm font-600"></div>

    <div class="flex gap-3 justify-end">
      <button class="btn-secondary" id="btnTestDb" onclick="testDb()">
        <i class="fas fa-plug mr-2"></i>Test Connection
      </button>
      <button class="btn-primary" id="btnNext1" onclick="goStep(2)" disabled>
        Next <i class="fas fa-arrow-right ml-2"></i>
      </button>
    </div>
  </div>

  {{-- STEP 2 — App & Admin --}}
  <div id="step2" class="hidden">
    <h2 class="text-lg font-bold text-slate-700 mb-4"><i class="fas fa-cog mr-2 text-violet-500"></i>App & Admin Setup</h2>
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="field col-span-2 sm:col-span-1">
        <label>App Name</label>
        <input type="text" id="app_name" value="Hotel CRM" placeholder="My Hotel CRM">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>App URL</label>
        <input type="text" id="app_url" value="http://localhost" placeholder="https://crm.myhotel.com">
      </div>
    </div>
    <hr class="my-4 border-slate-100">
    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Superadmin Account</p>
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="field col-span-2 sm:col-span-1">
        <label>Full Name</label>
        <input type="text" id="admin_name" placeholder="Admin User">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>Email</label>
        <input type="email" id="admin_email" placeholder="admin@myhotel.com">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>Password</label>
        <input type="password" id="admin_password" placeholder="Min 8 characters">
      </div>
      <div class="field col-span-2 sm:col-span-1">
        <label>Confirm Password</label>
        <input type="password" id="admin_password_confirm" placeholder="Repeat password">
      </div>
    </div>

    <div id="step2Err" class="hidden mb-4 p-3 rounded-lg text-sm font-600 bg-red-50 text-red-700"></div>

    <div class="flex gap-3 justify-between">
      <button class="btn-secondary" onclick="goStep(1)">
        <i class="fas fa-arrow-left mr-2"></i>Back
      </button>
      <button class="btn-primary" onclick="validateStep2()">
        Next <i class="fas fa-arrow-right ml-2"></i>
      </button>
    </div>
  </div>

  {{-- STEP 3 — Install --}}
  <div id="step3" class="hidden">
    <h2 class="text-lg font-bold text-slate-700 mb-4"><i class="fas fa-rocket mr-2 text-violet-500"></i>Install</h2>

    <div class="bg-slate-50 rounded-xl p-4 mb-4 text-sm text-slate-600 border border-slate-100">
      <div class="grid grid-cols-2 gap-1 text-xs">
        <span class="text-slate-400">Database:</span><span id="s3db" class="font-bold text-slate-700"></span>
        <span class="text-slate-400">App Name:</span><span id="s3app" class="font-bold text-slate-700"></span>
        <span class="text-slate-400">Admin:</span><span id="s3admin" class="font-bold text-slate-700"></span>
      </div>
    </div>

    <div id="installLog" class="hidden rounded-xl border border-slate-100 overflow-auto mb-4" style="max-height:260px;background:#f8fafc;padding:12px;">
    </div>

    <div id="installSuccess" class="hidden p-4 rounded-xl bg-green-50 border border-green-200 text-center mb-4">
      <i class="fas fa-check-circle text-green-500 text-3xl mb-2 block"></i>
      <p class="font-bold text-green-800 text-lg">Installation Complete!</p>
      <p class="text-green-600 text-sm mt-1">Redirecting to login in <span id="countdown">3</span>s...</p>
    </div>

    <div id="installFail" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm mb-4"></div>

    <div class="flex gap-3 justify-between">
      <button class="btn-secondary" id="btnBack3" onclick="goStep(2)">
        <i class="fas fa-arrow-left mr-2"></i>Back
      </button>
      <button class="btn-primary" id="btnInstall" onclick="runInstall()">
        <i class="fas fa-bolt mr-2"></i>Install Now
      </button>
    </div>
  </div>

</div>

<script>
var CSRF       = document.querySelector('meta[name="csrf-token"]').content;
var URL_TESTDB = '{{ route('install.testDb') }}';
var URL_RUN    = '{{ route('install.run') }}';
var dbPassed   = false;
var currentStep = 1;

function goStep(n) {
  [1,2,3].forEach(function(i) {
    document.getElementById('step' + i).classList.add('hidden');
    document.getElementById('sc' + i).parentElement.classList.remove('active','done');
  });
  document.getElementById('step' + n).classList.remove('hidden');
  document.getElementById('sc' + n).parentElement.classList.add('active');
  // Mark previous steps done
  for (var i = 1; i < n; i++) {
    document.getElementById('sc' + i).parentElement.classList.add('done');
    document.getElementById('sc' + i).innerHTML = '<i class="fas fa-check" style="font-size:12px;"></i>';
    if (i < 3) document.getElementById('line' + i).classList.add('done');
  }
  currentStep = n;

  if (n === 3) {
    document.getElementById('s3db').textContent = document.getElementById('db_database').value + '@' + document.getElementById('db_host').value;
    document.getElementById('s3app').textContent = document.getElementById('app_name').value;
    document.getElementById('s3admin').textContent = document.getElementById('admin_email').value;
  }
}

function testDb() {
  var btn = document.getElementById('btnTestDb');
  var msg = document.getElementById('dbMsg');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';
  msg.className = 'mb-4 p-3 rounded-lg text-sm font-600 bg-slate-100 text-slate-600';
  msg.textContent = 'Connecting...';
  msg.classList.remove('hidden');

  fetch(URL_TESTDB, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({
      db_host:     document.getElementById('db_host').value,
      db_port:     document.getElementById('db_port').value,
      db_database: document.getElementById('db_database').value,
      db_username: document.getElementById('db_username').value,
      db_password: document.getElementById('db_password').value,
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      msg.className = 'mb-4 p-3 rounded-lg text-sm font-600 bg-green-50 text-green-700';
      msg.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
      dbPassed = true;
      document.getElementById('btnNext1').disabled = false;
      document.getElementById('btnNext1').classList.remove('opacity-50');
    } else {
      msg.className = 'mb-4 p-3 rounded-lg text-sm font-600 bg-red-50 text-red-700';
      msg.innerHTML = '<i class="fas fa-times-circle mr-2"></i>' + data.message;
      dbPassed = false;
      document.getElementById('btnNext1').disabled = true;
    }
  })
  .catch(function(e) {
    msg.className = 'mb-4 p-3 rounded-lg text-sm font-600 bg-red-50 text-red-700';
    msg.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Request failed: ' + e.message;
  })
  .finally(function() {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plug mr-2"></i>Test Connection';
  });
}

function validateStep2() {
  var err = document.getElementById('step2Err');
  err.classList.add('hidden');

  var name  = document.getElementById('admin_name').value.trim();
  var email = document.getElementById('admin_email').value.trim();
  var pass  = document.getElementById('admin_password').value;
  var pass2 = document.getElementById('admin_password_confirm').value;
  var url   = document.getElementById('app_url').value.trim();
  var appName = document.getElementById('app_name').value.trim();

  if (!appName) { showErr('App name is required.'); return; }
  if (!url)     { showErr('App URL is required.'); return; }
  if (!name)    { showErr('Admin full name is required.'); return; }
  if (!email || !email.includes('@')) { showErr('A valid admin email is required.'); return; }
  if (pass.length < 8) { showErr('Password must be at least 8 characters.'); return; }
  if (pass !== pass2)  { showErr('Passwords do not match.'); return; }

  goStep(3);
}

function showErr(msg) {
  var el = document.getElementById('step2Err');
  el.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + msg;
  el.classList.remove('hidden');
}

function addLog(label, ok, note) {
  var log = document.getElementById('installLog');
  var div = document.createElement('div');
  div.className = 'log-line ' + (ok ? 'log-ok' : 'log-fail');
  var icon = ok ? '✓' : '✗';
  div.innerHTML = '<span style="font-size:16px;line-height:1;">' + icon + '</span><span>' + label + (note ? ' <span style="opacity:.6;font-size:11px;">(' + note + ')</span>' : '') + '</span>';
  log.appendChild(div);
  log.scrollTop = log.scrollHeight;
}

function runInstall() {
  var btn = document.getElementById('btnInstall');
  var log = document.getElementById('installLog');
  var fail = document.getElementById('installFail');
  var success = document.getElementById('installSuccess');

  btn.disabled = true;
  document.getElementById('btnBack3').disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Installing...';
  log.innerHTML = '';
  log.classList.remove('hidden');
  fail.classList.add('hidden');
  success.classList.add('hidden');

  // Add running indicator
  var running = document.createElement('div');
  running.className = 'log-line log-run';
  running.id = 'runningIndicator';
  running.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:14px;"></i><span>Running installation, please wait...</span>';
  log.appendChild(running);

  fetch(URL_RUN, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({
      db_host:          document.getElementById('db_host').value,
      db_port:          document.getElementById('db_port').value,
      db_database:      document.getElementById('db_database').value,
      db_username:      document.getElementById('db_username').value,
      db_password:      document.getElementById('db_password').value,
      app_name:         document.getElementById('app_name').value,
      app_url:          document.getElementById('app_url').value,
      admin_name:       document.getElementById('admin_name').value,
      admin_email:      document.getElementById('admin_email').value,
      admin_password:   document.getElementById('admin_password').value,
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    var ri = document.getElementById('runningIndicator');
    if (ri) ri.remove();

    if (data.steps) {
      data.steps.forEach(function(s) {
        addLog(s.label, s.ok, s.note || (s.error ? s.error.substring(0, 80) : null));
      });
    }

    if (data.ok) {
      success.classList.remove('hidden');
      var n = 3;
      var timer = setInterval(function() {
        n--;
        document.getElementById('countdown').textContent = n;
        if (n <= 0) {
          clearInterval(timer);
          window.location.href = '/login';
        }
      }, 1000);
    } else {
      fail.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i><strong>Installation failed:</strong> ' + (data.error || 'Unknown error');
      fail.classList.remove('hidden');
      btn.disabled = false;
      document.getElementById('btnBack3').disabled = false;
      btn.innerHTML = '<i class="fas fa-bolt mr-2"></i>Retry';
    }
  })
  .catch(function(e) {
    var ri = document.getElementById('runningIndicator');
    if (ri) ri.remove();
    fail.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Request failed: ' + e.message;
    fail.classList.remove('hidden');
    btn.disabled = false;
    document.getElementById('btnBack3').disabled = false;
    btn.innerHTML = '<i class="fas fa-bolt mr-2"></i>Retry';
  });
}

// Init
goStep(1);
</script>
</body>
</html>
