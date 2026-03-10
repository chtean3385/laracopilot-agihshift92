function showAlert(msg, type) {
  var el = document.getElementById('alertBox');
  el.className = 'alert alert-' + type;
  el.innerHTML = (type === 'success' ? '&#10003; ' : '&#9888; ') + msg;
  el.style.display = 'flex';
  if (type === 'success') {
    setTimeout(function() { el.style.display = 'none'; }, 4000);
  }
}

function setBtnState(text, bg, color) {
  var btn = document.getElementById('btnSave');
  if (!btn) return;
  btn.innerHTML = text;
  btn.style.background = bg || '';
  btn.style.color = color || '';
}

function copyField(inputId, btnId) {
  var val = document.getElementById(inputId).value.trim();
  if (!val) { showAlert('Field is empty — nothing to copy.', 'error'); return; }
  navigator.clipboard.writeText(val).then(function() {
    var btn = document.getElementById(btnId);
    var orig = btn.innerHTML;
    btn.innerHTML = '&#10003; Copied!';
    btn.classList.add('copied');
    setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('copied'); }, 2000);
  }).catch(function() {
    showAlert('Copy failed — please select and copy manually.', 'error');
  });
}

function saveSettings() {
  var url = document.getElementById('crmUrl').value.trim().replace(/\/$/, '');
  var token = document.getElementById('apiToken').value.trim();
  if (!url || !token) {
    showAlert('Both CRM URL and API Token are required.', 'error');
    return;
  }
  setBtnState('Saving...', '#94a3b8', '#fff');
  chrome.storage.local.set({ crmUrl: url, apiToken: token }, function() {
    if (chrome.runtime.lastError) {
      showAlert('Save failed: ' + chrome.runtime.lastError.message, 'error');
      setBtnState('&#10003; Save Settings', '', '');
      return;
    }
    setBtnState('&#10003; Saved!', '#16a34a', '#fff');
    showAlert('Settings saved! You can now switch tabs freely.', 'success');
    setTimeout(function() { setBtnState('&#10003; Save Settings', '', ''); }, 3000);
  });
}

document.addEventListener('DOMContentLoaded', function() {
  chrome.storage.local.get(['crmUrl', 'apiToken'], function(settings) {
    if (settings.crmUrl) document.getElementById('crmUrl').value = settings.crmUrl;
    if (settings.apiToken) document.getElementById('apiToken').value = settings.apiToken;
  });

  document.getElementById('btnSave').addEventListener('click', saveSettings);
  document.getElementById('btnCopyUrl').addEventListener('click', function() { copyField('crmUrl', 'btnCopyUrl'); });
  document.getElementById('btnCopyToken').addEventListener('click', function() { copyField('apiToken', 'btnCopyToken'); });
});
