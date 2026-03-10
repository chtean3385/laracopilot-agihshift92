(function() {
  'use strict';

  function showIndicator(text, color) {
    let el = document.getElementById('pathik-crm-indicator');
    if (!el) {
      el = document.createElement('div');
      el.id = 'pathik-crm-indicator';
      el.style.cssText = [
        'position:fixed', 'bottom:20px', 'right:20px', 'z-index:999999',
        'padding:10px 16px', 'border-radius:10px', 'font-size:13px',
        'font-weight:700', 'font-family:sans-serif', 'box-shadow:0 4px 12px rgba(0,0,0,.15)',
        'transition:all .3s ease', 'cursor:pointer',
      ].join(';');
      el.onclick = () => el.remove();
      document.body.appendChild(el);
    }
    el.style.background = color || '#16a34a';
    el.style.color = '#fff';
    el.textContent = '🏨 ' + text;
    setTimeout(() => { if (el.parentNode) el.remove(); }, 5000);
  }

  function findField(selectors) {
    for (const sel of selectors) {
      try {
        const el = document.querySelector(sel);
        if (el) return el;
      } catch(e) {}
    }
    return null;
  }

  function findByLabel(labelText) {
    const labels = document.querySelectorAll('label');
    for (const label of labels) {
      if (label.textContent.toLowerCase().includes(labelText.toLowerCase())) {
        const forId = label.getAttribute('for');
        if (forId) {
          const el = document.getElementById(forId);
          if (el) return el;
        }
        const next = label.nextElementSibling;
        if (next && (next.tagName === 'INPUT' || next.tagName === 'TEXTAREA' || next.tagName === 'SELECT')) {
          return next;
        }
        const parent = label.parentElement;
        if (parent) {
          const inp = parent.querySelector('input, textarea, select');
          if (inp) return inp;
        }
      }
    }
    return null;
  }

  function findByNameOrPlaceholder(names) {
    for (const name of names) {
      const el = document.querySelector('[name*="' + name + '"]') ||
                 document.querySelector('[placeholder*="' + name + '"]') ||
                 document.querySelector('[id*="' + name + '"]');
      if (el) return el;
    }
    return null;
  }

  function setFieldValue(el, value) {
    if (!el || value === undefined || value === null || value === '') return false;
    el.focus();
    if (el.tagName === 'SELECT') {
      const opts = Array.from(el.options);
      const match = opts.find(o =>
        o.text.toLowerCase().includes(String(value).toLowerCase()) ||
        o.value.toLowerCase().includes(String(value).toLowerCase())
      );
      if (match) {
        el.value = match.value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
      }
      return false;
    }
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.blur();
    return true;
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return dd + '/' + mm + '/' + yyyy;
  }

  function autofill(guest) {
    let filled = 0;

    const fieldMap = [
      {
        value: guest.name,
        finders: [
          () => findByLabel('tourist name'),
          () => findByLabel('full name'),
          () => findByLabel('name'),
          () => findByNameOrPlaceholder(['tourist_name', 'full_name', 'name', 'tourist']),
        ],
      },
      {
        value: guest.phone,
        finders: [
          () => findByLabel('mobile'),
          () => findByLabel('phone'),
          () => findByLabel('contact'),
          () => findByNameOrPlaceholder(['mobile', 'phone', 'contact_no', 'mob']),
        ],
      },
      {
        value: guest.email,
        finders: [
          () => findByLabel('email'),
          () => findByNameOrPlaceholder(['email']),
          () => document.querySelector('input[type="email"]'),
        ],
      },
      {
        value: guest.address,
        finders: [
          () => findByLabel('address'),
          () => findByLabel('residential address'),
          () => findByNameOrPlaceholder(['address', 'res_address']),
        ],
      },
      {
        value: guest.city,
        finders: [
          () => findByLabel('city'),
          () => findByNameOrPlaceholder(['city']),
        ],
      },
      {
        value: guest.state,
        finders: [
          () => findByLabel('state'),
          () => findByNameOrPlaceholder(['state']),
        ],
      },
      {
        value: guest.country || 'India',
        finders: [
          () => findByLabel('country'),
          () => findByNameOrPlaceholder(['country']),
        ],
      },
      {
        value: guest.nationality || 'Indian',
        finders: [
          () => findByLabel('nationality'),
          () => findByNameOrPlaceholder(['nationality']),
        ],
      },
      {
        value: guest.id_number,
        finders: [
          () => findByLabel('id proof number'),
          () => findByLabel('id number'),
          () => findByLabel('aadhaar'),
          () => findByLabel('passport'),
          () => findByNameOrPlaceholder(['id_number', 'id_proof_no', 'aadhaar_no', 'doc_number']),
        ],
      },
      {
        value: guest.id_type,
        finders: [
          () => findByLabel('id proof type'),
          () => findByLabel('id type'),
          () => findByNameOrPlaceholder(['id_type', 'id_proof_type', 'document_type']),
        ],
      },
      {
        value: formatDate(guest.date_of_birth),
        finders: [
          () => findByLabel('date of birth'),
          () => findByLabel('dob'),
          () => findByLabel('birth date'),
          () => findByNameOrPlaceholder(['dob', 'date_of_birth', 'birth_date']),
        ],
      },
      {
        value: formatDate(guest.check_in_date),
        finders: [
          () => findByLabel('arrival date'),
          () => findByLabel('check in'),
          () => findByLabel('check-in'),
          () => findByNameOrPlaceholder(['arrival_date', 'check_in', 'checkin']),
        ],
      },
      {
        value: formatDate(guest.check_out_date),
        finders: [
          () => findByLabel('departure date'),
          () => findByLabel('check out'),
          () => findByLabel('check-out'),
          () => findByNameOrPlaceholder(['departure_date', 'check_out', 'checkout']),
        ],
      },
      {
        value: guest.adults,
        finders: [
          () => findByLabel('number of adults'),
          () => findByLabel('adults'),
          () => findByNameOrPlaceholder(['adults', 'no_adults', 'adult_count']),
        ],
      },
      {
        value: guest.room_number,
        finders: [
          () => findByLabel('room no'),
          () => findByLabel('room number'),
          () => findByLabel('accommodation'),
          () => findByNameOrPlaceholder(['room_no', 'room_number', 'accommodation_no']),
        ],
      },
    ];

    for (const field of fieldMap) {
      if (!field.value) continue;
      for (const finder of field.finders) {
        try {
          const el = finder();
          if (el && setFieldValue(el, field.value)) {
            filled++;
            break;
          }
        } catch(e) {}
      }
    }

    return filled;
  }

  chrome.runtime.onMessage.addListener(function(msg, sender, sendResponse) {
    if (msg.action === 'PATHIK_AUTOFILL') {
      try {
        const count = autofill(msg.guest);
        showIndicator('Autofilled ' + count + ' fields!', '#16a34a');
        sendResponse({ ok: true, filled: count });
      } catch(err) {
        showIndicator('Autofill error: ' + err.message, '#dc2626');
        sendResponse({ error: err.message });
      }
      return true;
    }
  });

  chrome.storage.local.get(['pathik_current_guest'], function(local) {
    if (local.pathik_current_guest) {
      showIndicator('CRM Autofill Ready — click Autofill Now in extension', '#f97316');
    }
  });

})();
