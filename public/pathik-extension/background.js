chrome.runtime.onMessage.addListener(function(msg, sender, sendResponse) {
  if (msg.action === 'FETCH_PENDING') {
    const { crmUrl, apiToken, pendingToken } = msg;
    const url = crmUrl.replace(/\/$/, '') + '/pathik/pending?token=' +
      encodeURIComponent(pendingToken) + '&api_token=' + encodeURIComponent(apiToken);

    fetch(url)
      .then(r => r.json())
      .then(data => sendResponse(data))
      .catch(err => sendResponse({ error: err.message }));

    return true;
  }

  if (msg.action === 'CLEAR_PENDING') {
    const { crmUrl, apiToken, pendingToken } = msg;
    fetch(crmUrl.replace(/\/$/, '') + '/pathik/clear', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: pendingToken, api_token: apiToken }),
    }).catch(() => {});
    return false;
  }
});
