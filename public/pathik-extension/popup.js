const $ = id => document.getElementById(id);

function setStatus(msg, type) {
  const el = $('statusMsg');
  el.textContent = msg;
  el.className = 'status status-' + (type || 'info');
}

function showGuest(guest) {
  $('guestName').textContent = guest.name || 'Unknown';
  $('guestMeta').textContent = [
    guest.phone,
    guest.check_in_date ? 'Check-in: ' + guest.check_in_date : null,
    guest.room_number ? 'Room: ' + guest.room_number : null,
  ].filter(Boolean).join(' · ');
  $('guestCard').style.display = 'block';
  $('btnAutofill').style.display = 'flex';
  $('btnOpenPortal').style.display = 'flex';
}

function hideGuest() {
  $('guestCard').style.display = 'none';
  $('btnAutofill').style.display = 'none';
  $('btnOpenPortal').style.display = 'none';
}

function openSettingsPage() {
  chrome.tabs.create({ url: chrome.runtime.getURL('options.html') });
}

function clearData() {
  chrome.storage.local.remove(['pathik_current_guest', 'pathik_pending_token'], function() {
    hideGuest();
    setStatus('Data cleared.', 'warn');
  });
}

function openPortal() {
  chrome.tabs.create({ url: 'https://pathik.gujarat.gov.in' });
}

function checkReady() {
  chrome.storage.local.get(['crmUrl', 'apiToken'], function(settings) {
    if (!settings.crmUrl || !settings.apiToken) {
      setStatus('Please configure CRM URL and API Token.', 'warn');
      return;
    }

    chrome.storage.local.get(['pathik_current_guest'], function(local) {
      if (local.pathik_current_guest) {
        setStatus('Guest data loaded and ready.', 'success');
        showGuest(local.pathik_current_guest);
      } else {
        setStatus('No pending guest. Click a booking in CRM first.', 'info');
        hideGuest();
      }
    });
  });
}

function fetchGuest() {
  $('btnFetch').disabled = true;
  setStatus('Fetching from CRM...', 'info');

  chrome.storage.local.get(['crmUrl', 'apiToken'], function(settings) {
    if (!settings.crmUrl || !settings.apiToken) {
      setStatus('CRM URL or API Token not set.', 'error');
      $('btnFetch').disabled = false;
      return;
    }

    chrome.storage.local.get(['pathik_pending_token'], function(local) {
      const token = local.pathik_pending_token;
      if (!token) {
        setStatus('No pending guest token. Click "Fill Pathik Portal" on a booking first.', 'warn');
        $('btnFetch').disabled = false;
        return;
      }

      const url = settings.crmUrl + '/pathik/pending?token=' + encodeURIComponent(token) + '&api_token=' + encodeURIComponent(settings.apiToken);
      fetch(url)
        .then(r => r.json())
        .then(data => {
          if (data.ok && data.guest) {
            chrome.storage.local.set({ pathik_current_guest: data.guest }, function() {
              setStatus('Guest data loaded!', 'success');
              showGuest(data.guest);
            });
          } else {
            setStatus(data.error || 'Failed to fetch guest data.', 'error');
          }
          $('btnFetch').disabled = false;
        })
        .catch(err => {
          setStatus('Network error: ' + err.message, 'error');
          $('btnFetch').disabled = false;
        });
    });
  });
}

function autofillNow() {
  chrome.tabs.query({ active: true, currentWindow: true }, function(tabs) {
    if (!tabs[0]) {
      setStatus('No active tab found.', 'error');
      return;
    }
    chrome.storage.local.get(['pathik_current_guest'], function(local) {
      if (!local.pathik_current_guest) {
        setStatus('No guest data loaded. Fetch first.', 'warn');
        return;
      }
      chrome.tabs.sendMessage(tabs[0].id, {
        action: 'PATHIK_AUTOFILL',
        guest: local.pathik_current_guest,
      }, function(response) {
        if (chrome.runtime.lastError) {
          setStatus('Cannot autofill on this page. Open Pathik Portal first.', 'error');
          return;
        }
        if (response && response.ok) {
          setStatus('Autofill complete! Check the form.', 'success');
        } else {
          setStatus(response ? response.error : 'Autofill failed.', 'error');
        }
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', checkReady);
