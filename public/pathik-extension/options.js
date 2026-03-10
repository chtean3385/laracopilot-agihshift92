function showAlert(msg, type) {
  const el = document.getElementById('alertBox');
  el.className = 'alert alert-' + type;
  el.innerHTML = (type === 'success' ? '&#10003; ' : '&#9888; ') + msg;
  el.style.display = 'flex';
  if (type === 'success') setTimeout(() => { el.style.display = 'none'; }, 3000);
}

function copyField(inputId, btnId) {
  const val = document.getElementById(inputId).value.trim();
  if (!val) { showAlert('Field is empty — nothing to copy.', 'error'); return; }
  navigator.clipboard.writeText(val).then(() => {
    const btn = document.getElementById(btnId);
    const orig = btn.innerHTML;
    btn.innerHTML = '&#10003; Copied!';
    btn.classList.add('copied');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 2000);
  }).catch(() => {
    showAlert('Copy failed — please select and copy manually.', 'error');
  });
}

function saveSettings() {
  const url = document.getElementById('crmUrl').value.trim().replace(/\/$/, '');
  const token = document.getElementById('apiToken').value.trim();
  if (!url || !token) {
    showAlert('Both CRM URL and API Token are required.', 'error');
    return;
  }
  chrome.storage.sync.set({ crmUrl: url, apiToken: token }, function() {
    showAlert('Settings saved successfully! You can close this tab.', 'success');
  });
}

document.addEventListener('DOMContentLoaded', function() {
  chrome.storage.sync.get(['crmUrl', 'apiToken'], function(settings) {
    if (settings.crmUrl) document.getElementById('crmUrl').value = settings.crmUrl;
    if (settings.apiToken) document.getElementById('apiToken').value = settings.apiToken;
  });
});
