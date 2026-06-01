import React, { useState, useEffect, useRef, useCallback } from 'react'
import ReactDOM from 'react-dom/client'

// ── Helpers ────────────────────────────────────────────────────────────────

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

async function api(method, path, body = null) {
  const opts = { method, headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } }
  if (body !== null) {
    opts.headers['Content-Type'] = 'application/json'
    opts.body = JSON.stringify(body)
  }
  const r = await fetch(`/platform/wa-inbox/api${path}`, opts)
  const json = await r.json()
  if (!r.ok) throw new Error(json.error || `HTTP ${r.status}`)
  return json
}

function encPhone(p) { return encodeURIComponent(p) }

function leadBadge(status) {
  if (!status) return null
  const map = {
    hot:       { label: '🔥 HOT',  bg: '#fef2f2', color: '#dc2626' },
    warm:      { label: '🟡 WARM', bg: '#fefce8', color: '#ca8a04' },
    cold:      { label: '❄️ COLD', bg: '#eff6ff', color: '#2563eb' },
    nurture:   { label: '💤',      bg: '#f0fdf4', color: '#16a34a' },
    opted_out: { label: '🚫',      bg: '#f9fafb', color: '#6b7280' },
  }
  const s = map[status]
  if (!s) return null
  return <span style={{ fontSize: 11, padding: '1px 6px', borderRadius: 10, background: s.bg, color: s.color, fontWeight: 600 }}>{s.label}</span>
}

function parseCsvJs(text) {
  const lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').map(l => l.trim()).filter(Boolean)
  if (lines.length < 2) throw new Error('CSV must have a header row and at least one data row.')

  function parseLine(line) {
    const cols = []
    let cur = '', inQ = false
    for (let i = 0; i < line.length; i++) {
      const ch = line[i]
      if (ch === '"') { inQ = !inQ; continue }
      if (ch === ',' && !inQ) { cols.push(cur); cur = ''; continue }
      cur += ch
    }
    cols.push(cur)
    return cols
  }

  const rawHeaders = parseLine(lines[0])
  const headers = rawHeaders.map(h => h.trim().toLowerCase())

  const phoneAliases = ['phone','mobile','phone_number','mobile_number','whatsapp','contact','number','ph']
  let phoneIdx = null
  for (let i = 0; i < headers.length; i++) {
    if (phoneAliases.includes(headers[i])) { phoneIdx = i; break }
  }
  if (phoneIdx === null) throw new Error('No phone column found. Name a column: phone, mobile, phone_number, or whatsapp.')

  const varAliases = [
    ['full_name','name','customer_name','lead_name','contact_name','first_name'],
    ['what_best','property_type','hotel_type','type','property','what_best_describe'],
    ['how_many_rooms','rooms','room_count','total_rooms','no_of_rooms','num_rooms'],
    ['when_do_you_plan_to_implement','timeline','plan','implementation_timeline','when','plan_to_implement'],
    ['city','location','area','region'],
  ]

  const colToVar = {}
  let nameIdx = null
  varAliases.forEach((aliases, varPos) => {
    for (let i = 0; i < headers.length; i++) {
      if (aliases.includes(headers[i])) {
        colToVar[i] = varPos
        if (varPos === 0) nameIdx = i
        break
      }
    }
  })

  const leads = []
  for (let li = 1; li < lines.length; li++) {
    const row = parseLine(lines[li])
    const rawPhone = (row[phoneIdx] || '').trim()
    if (!rawPhone) continue
    const phone = rawPhone.replace(/[^0-9]/g, '')
    const normalized = phone.length === 10 ? '91' + phone : phone
    if (normalized.length < 10 || normalized.length > 15) continue
    const vars = ['','','','','']
    Object.entries(colToVar).forEach(([ci, vp]) => { vars[vp] = (row[+ci] || '').trim() })
    leads.push({
      phone: normalized, raw_phone: rawPhone,
      name: (nameIdx !== null ? (row[nameIdx] || '').trim() : '') || rawPhone,
      vars,
    })
  }
  if (!leads.length) throw new Error('No valid phone numbers found in CSV.')
  return { leads, headers }
}

// ── Toast ──────────────────────────────────────────────────────────────────

function Toast({ msg, onClose }) {
  useEffect(() => { const t = setTimeout(onClose, 3500); return () => clearTimeout(t) }, [])
  if (!msg) return null
  const ok = msg.type === 'ok'
  return (
    <div style={{ position:'fixed', bottom:24, right:24, zIndex:9999, background: ok?'#f0fdf4':'#fef2f2', border:`1px solid ${ok?'#bbf7d0':'#fecaca'}`, color: ok?'#166534':'#991b1b', padding:'10px 16px', borderRadius:10, boxShadow:'0 4px 12px rgba(0,0,0,.15)', maxWidth:320, fontSize:14, display:'flex', alignItems:'center', gap:8 }}>
      <span>{ok ? '✅' : '❌'}</span>
      <span>{msg.text}</span>
      <button onClick={onClose} style={{ marginLeft:'auto', background:'none', border:'none', cursor:'pointer', color:'inherit', fontSize:16, lineHeight:1 }}>×</button>
    </div>
  )
}

// ── ContactItem ────────────────────────────────────────────────────────────

function ContactItem({ c, selected, onClick, onMenu }) {
  const initials = (c.name || c.phone).slice(0,2).toUpperCase()
  return (
    <div
      onClick={() => onClick(c.phone)}
      style={{ display:'flex', alignItems:'center', gap:10, padding:'10px 14px', cursor:'pointer', background: selected ? '#ede9fe' : 'transparent', borderLeft: selected ? '3px solid #7c3aed' : '3px solid transparent', transition:'background .15s' }}
      onMouseEnter={e => { if (!selected) e.currentTarget.style.background = '#f8f7ff' }}
      onMouseLeave={e => { if (!selected) e.currentTarget.style.background = 'transparent' }}
    >
      <div style={{ width:38, height:38, borderRadius:'50%', background: c.type === 'owner' ? '#ede9fe' : c.type === 'guest' ? '#e0f2fe' : '#f1f5f9', display:'flex', alignItems:'center', justifyContent:'center', fontWeight:700, fontSize:13, color: c.type === 'owner' ? '#7c3aed' : c.type === 'guest' ? '#0891b2' : '#64748b', flexShrink:0, position:'relative' }}>
        {initials}
        {c.unread > 0 && <span style={{ position:'absolute', top:-3, right:-3, background:'#dc2626', color:'#fff', borderRadius:'50%', width:16, height:16, fontSize:9, display:'flex', alignItems:'center', justifyContent:'center', fontWeight:700 }}>{c.unread > 9 ? '9+' : c.unread}</span>}
      </div>
      <div style={{ flex:1, minWidth:0 }}>
        <div style={{ display:'flex', alignItems:'center', gap:6, justifyContent:'space-between' }}>
          <span style={{ fontWeight: c.unread > 0 ? 700 : 500, fontSize:13, color:'#111', whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis', maxWidth:140 }}>{c.name}</span>
          <span style={{ fontSize:10, color:'#94a3b8', flexShrink:0 }}>{c.time_ago}</span>
        </div>
        <div style={{ display:'flex', alignItems:'center', gap:4, marginTop:2 }}>
          <span style={{ fontSize:11, color:'#64748b', whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis', flex:1 }}>{c.preview}</span>
          {leadBadge(c.lead_status)}
        </div>
      </div>
      <button
        onClick={e => { e.stopPropagation(); onMenu(c.phone) }}
        style={{ background:'none', border:'none', cursor:'pointer', color:'#94a3b8', padding:'2px 4px', fontSize:16, flexShrink:0 }}
        title="Actions"
      >⋯</button>
    </div>
  )
}

// ── MessageBubble ──────────────────────────────────────────────────────────

function MessageBubble({ msg }) {
  const isOut = msg.is_outgoing
  return (
    <div style={{ display:'flex', flexDirection:'column', alignItems: isOut ? 'flex-end' : 'flex-start', marginBottom:12 }}>
      <div style={{ maxWidth:'72%', background: isOut ? '#7c3aed' : '#f1f5f9', color: isOut ? '#fff' : '#1e293b', borderRadius: isOut ? '18px 4px 18px 18px' : '4px 18px 18px 18px', padding:'9px 14px', fontSize:13.5, lineHeight:1.5, boxShadow:'0 1px 3px rgba(0,0,0,.08)', whiteSpace:'pre-wrap', wordBreak:'break-word' }}>
        {msg.text}
      </div>
      <span style={{ fontSize:10, color:'#94a3b8', marginTop:3 }}>{msg.tag} · {msg.time}</span>
    </div>
  )
}

// ── ContactEditModal ────────────────────────────────────────────────────────

function ContactEditModal({ contact, onSave, onClose }) {
  const [name, setName] = useState(contact?.name || '')
  const [type, setType] = useState(contact?.type || 'unknown')
  const [saving, setSaving] = useState(false)

  const save = async () => {
    if (!name.trim()) return
    setSaving(true)
    try {
      await api('PATCH', `/contacts/${encPhone(contact.phone)}`, { display_name: name.trim(), contact_type: type })
      onSave({ ...contact, name: name.trim(), type })
    } finally { setSaving(false) }
  }

  return (
    <Overlay onClose={onClose}>
      <ModalBox title="Edit Contact" onClose={onClose}>
        <label style={labelStyle}>Name</label>
        <input value={name} onChange={e => setName(e.target.value)} style={inputStyle} autoFocus />
        <label style={{ ...labelStyle, marginTop:12 }}>Type</label>
        <select value={type} onChange={e => setType(e.target.value)} style={inputStyle}>
          <option value="unknown">Unknown</option>
          <option value="owner">Owner</option>
          <option value="guest">Guest</option>
        </select>
        <div style={{ display:'flex', gap:8, marginTop:20 }}>
          <button onClick={save} disabled={saving || !name.trim()} style={btnPrimary}>{saving ? 'Saving…' : 'Save'}</button>
          <button onClick={onClose} style={btnSecondary}>Cancel</button>
        </div>
      </ModalBox>
    </Overlay>
  )
}

// ── LeadInfoModal ──────────────────────────────────────────────────────────

function LeadInfoModal({ phone, onClose }) {
  const [info, setInfo] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api('GET', `/leads/${encPhone(phone)}`).then(d => setInfo(d)).finally(() => setLoading(false))
  }, [phone])

  return (
    <Overlay onClose={onClose}>
      <ModalBox title="📊 Lead Information" onClose={onClose}>
        {loading && <p style={{ color:'#94a3b8', textAlign:'center' }}>Loading…</p>}
        {!loading && !info?.found && <p style={{ color:'#64748b' }}>No lead data collected yet for this contact.</p>}
        {!loading && info?.found && (
          <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:'8px 16px', fontSize:13 }}>
            {[
              ['Status', info.status], ['Step', info.step], ['Name', info.name],
              ['Hotel', info.hotel_name], ['Rooms', info.room_count], ['Role', info.role],
              ['City', info.city], ['Software', info.software], ['Timeline', info.timeline],
              ['Demo slot', info.demo], ['Last seen', info.last_seen],
            ].map(([k, v]) => (
              <div key={k}>
                <div style={{ color:'#94a3b8', fontSize:10, fontWeight:600, textTransform:'uppercase', letterSpacing:.5 }}>{k}</div>
                <div style={{ color:'#1e293b', fontWeight:500 }}>{v || '—'}</div>
              </div>
            ))}
          </div>
        )}
        <div style={{ marginTop:20 }}>
          <button onClick={onClose} style={btnSecondary}>Close</button>
        </div>
      </ModalBox>
    </Overlay>
  )
}

// ── TemplatePickerModal ────────────────────────────────────────────────────

function TemplatePickerModal({ phone, templates, onClose, onToast }) {
  const [selectedId, setSelectedId] = useState(0)
  const [vars, setVars] = useState([])
  const [varNames, setVarNames] = useState([])
  const [preview, setPreview] = useState('')
  const [sending, setSending] = useState(false)

  const selectTemplate = (t) => {
    setSelectedId(t.id)
    setVarNames(t.var_names || [])
    setVars(Array(t.var_names?.length || 0).fill(''))
    setPreview(t.body || '')
  }

  const send = async () => {
    if (!selectedId) return
    setSending(true)
    try {
      await api('POST', '/send-template', { phone, template_id: selectedId, vars })
      onToast({ type:'ok', text:'Template sent!' })
      onClose()
    } catch (e) {
      onToast({ type:'error', text: e.message })
    } finally { setSending(false) }
  }

  return (
    <Overlay onClose={onClose}>
      <ModalBox title="Send Template" onClose={onClose} wide>
        <div style={{ display:'flex', gap:16, height:360 }}>
          {/* Template list */}
          <div style={{ width:220, borderRight:'1px solid #e2e8f0', overflowY:'auto', paddingRight:12 }}>
            {templates.length === 0 && <p style={{ color:'#94a3b8', fontSize:12 }}>No approved templates.</p>}
            {templates.map(t => (
              <div key={t.id} onClick={() => selectTemplate(t)} style={{ padding:'8px 10px', borderRadius:8, cursor:'pointer', background: selectedId===t.id ? '#ede9fe' : 'transparent', marginBottom:4, fontSize:13 }}
                onMouseEnter={e => { if (selectedId!==t.id) e.currentTarget.style.background='#f8f7ff' }}
                onMouseLeave={e => { if (selectedId!==t.id) e.currentTarget.style.background='transparent' }}
              >
                <div style={{ fontWeight:600, color:'#7c3aed' }}>{t.name}</div>
                <div style={{ fontSize:11, color:'#64748b', marginTop:2, whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis' }}>{t.body?.slice(0,50)}…</div>
              </div>
            ))}
          </div>
          {/* Right panel */}
          <div style={{ flex:1, display:'flex', flexDirection:'column', gap:10, overflowY:'auto' }}>
            {!selectedId && <p style={{ color:'#94a3b8', fontSize:13, marginTop:8 }}>← Select a template to continue</p>}
            {!!selectedId && (
              <>
                <div style={{ background:'#f8f7ff', border:'1px solid #e5e7eb', borderRadius:10, padding:12, fontSize:13, color:'#374151', whiteSpace:'pre-wrap', maxHeight:140, overflowY:'auto' }}>{preview}</div>
                {varNames.length > 0 && (
                  <div>
                    <div style={{ fontSize:11, color:'#64748b', fontWeight:600, marginBottom:6, textTransform:'uppercase', letterSpacing:.5 }}>Variables</div>
                    {varNames.map((vn, i) => (
                      <div key={i} style={{ marginBottom:8 }}>
                        <label style={labelStyle}>{vn}</label>
                        <input value={vars[i]||''} onChange={e => { const v=[...vars]; v[i]=e.target.value; setVars(v) }} style={inputStyle} placeholder={`Enter ${vn}`} />
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
        </div>
        <div style={{ display:'flex', gap:8, marginTop:16 }}>
          <button onClick={send} disabled={!selectedId || sending} style={btnPrimary}>{sending ? 'Sending…' : 'Send Template'}</button>
          <button onClick={onClose} style={btnSecondary}>Cancel</button>
        </div>
      </ModalBox>
    </Overlay>
  )
}

// ── BlastModal ─────────────────────────────────────────────────────────────

function BlastModal({ templates, onClose, onToast }) {
  const [mode, setMode] = useState('manual')
  const [numbers, setNumbers] = useState('')
  const [templateId, setTemplateId] = useState(0)
  const [vars, setVars] = useState([])
  const [varNames, setVarNames] = useState([])
  const [preview, setPreview] = useState('')
  const [headerUrl, setHeaderUrl] = useState('')
  const [headerFmt, setHeaderFmt] = useState('none')
  const [results, setResults] = useState([])
  const [blasting, setBlasting] = useState(false)
  const [done, setDone] = useState(false)
  const [error, setError] = useState('')
  const [csvLeads, setCsvLeads] = useState([])
  const [csvError, setCsvError] = useState('')
  const [csvHeaders, setCsvHeaders] = useState([])
  const csvInputRef = useRef(null)

  const selectTemplate = (id) => {
    const t = templates.find(t => t.id === +id)
    if (!t) { setTemplateId(0); setVarNames([]); setVars([]); setPreview(''); setHeaderFmt('none'); setHeaderUrl(''); return }
    setTemplateId(t.id)
    setVarNames(t.var_names || [])
    setVars(Array(t.var_names?.length || 0).fill(''))
    setPreview(t.body || '')
    setHeaderFmt(t.header_format || 'none')
    setHeaderUrl(t.header_url || '')
  }

  const handleCsvFile = (file) => {
    if (!file) return
    if (!file.name.toLowerCase().endsWith('.csv')) { setCsvError('Please upload a .csv file.'); return }
    if (file.size > 5 * 1024 * 1024) { setCsvError('CSV is too large (max 5 MB).'); return }
    const reader = new FileReader()
    reader.onload = (e) => {
      try {
        const { leads, headers } = parseCsvJs(e.target.result)
        setCsvLeads(leads); setCsvHeaders(headers); setCsvError('')
      } catch (err) { setCsvError(err.message); setCsvLeads([]) }
    }
    reader.readAsText(file, 'UTF-8')
    if (csvInputRef.current) csvInputRef.current.value = ''
  }

  const buildLeads = () => {
    if (mode === 'csv') return csvLeads
    return numbers.split('\n').map(l => l.trim()).filter(Boolean).map(rawPhone => {
      const phone = rawPhone.replace(/[^0-9]/g, '')
      const normalized = phone.length === 10 ? '91' + phone : phone
      return { phone: normalized, raw_phone: rawPhone, name: '', vars }
    })
  }

  const send = async () => {
    setError(''); setResults([])
    if (!templateId) { setError('Select a template.'); return }
    const leads = buildLeads()
    if (!leads.length) { setError(mode === 'csv' ? 'Upload a CSV file first.' : 'Enter at least one phone number.'); return }
    if (leads.length > 500) { setError('Max 500 leads per blast.'); return }
    setBlasting(true)
    try {
      const data = await api('POST', '/blast', { template_id: templateId, leads, header_url: headerUrl })
      setResults(data.results || [])
      setDone(true)
    } catch (e) { setError(e.message) }
    finally { setBlasting(false) }
  }

  const sentCount  = results.filter(r => r.status === 'sent').length
  const failCount  = results.filter(r => r.status === 'fail').length
  const totalLeads = mode === 'csv' ? csvLeads.length : numbers.split('\n').filter(l => l.trim()).length

  return (
    <Overlay onClose={onClose}>
      <ModalBox title="📤 Bulk Blast" onClose={onClose} wide>
        {/* Mode tabs */}
        <div style={{ display:'flex', background:'#f1f5f9', borderRadius:8, padding:3, marginBottom:16, width:'fit-content', gap:2 }}>
          {['manual','csv'].map(m => (
            <button key={m} onClick={() => { setMode(m); setDone(false); setResults([]); setError('') }}
              style={{ padding:'6px 18px', borderRadius:6, border:'none', cursor:'pointer', fontWeight:600, fontSize:13, background: mode===m ? '#7c3aed' : 'transparent', color: mode===m ? '#fff' : '#64748b', transition:'all .15s' }}>
              {m === 'manual' ? '📋 Manual Blast' : '📂 CSV Campaign'}
            </button>
          ))}
        </div>

        {!done && (
          <>
            {/* Template selector */}
            <label style={labelStyle}>Template</label>
            <select value={templateId} onChange={e => selectTemplate(e.target.value)} style={inputStyle}>
              <option value={0}>— Select an approved template —</option>
              {templates.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
            </select>

            {/* Preview */}
            {preview && <div style={{ background:'#f8f7ff', border:'1px solid #e5e7eb', borderRadius:10, padding:10, fontSize:12, color:'#374151', whiteSpace:'pre-wrap', marginTop:8, maxHeight:100, overflowY:'auto' }}>{preview}</div>}

            {/* Header media URL */}
            {['image','video','document'].includes(headerFmt) && (
              <div style={{ marginTop:8 }}>
                <label style={labelStyle}>Header {headerFmt} URL (optional override)</label>
                <input value={headerUrl} onChange={e => setHeaderUrl(e.target.value)} style={inputStyle} placeholder={`https://…`} />
              </div>
            )}

            {/* Manual mode */}
            {mode === 'manual' && (
              <>
                {varNames.map((vn, i) => (
                  <div key={i} style={{ marginTop:8 }}>
                    <label style={labelStyle}>{`{{${i+1}}} ${vn}`}</label>
                    <input value={vars[i]||''} onChange={e => { const v=[...vars]; v[i]=e.target.value; setVars(v) }} style={inputStyle} placeholder={`Value for ${vn}`} />
                  </div>
                ))}
                <div style={{ marginTop:12 }}>
                  <label style={labelStyle}>Phone numbers (one per line)</label>
                  <textarea value={numbers} onChange={e => setNumbers(e.target.value)} rows={5} style={{ ...inputStyle, resize:'vertical', fontFamily:'monospace', fontSize:12 }} placeholder="919876543210&#10;918765432109" />
                  <div style={{ fontSize:11, color:'#94a3b8', marginTop:4 }}>{numbers.split('\n').filter(l=>l.trim()).length} numbers</div>
                </div>
              </>
            )}

            {/* CSV mode */}
            {mode === 'csv' && (
              <>
                <div
                  onClick={() => csvInputRef.current?.click()}
                  onDragOver={e => { e.preventDefault(); e.currentTarget.style.borderColor='#7c3aed' }}
                  onDragLeave={e => { e.currentTarget.style.borderColor='#cbd5e1' }}
                  onDrop={e => { e.preventDefault(); e.currentTarget.style.borderColor='#cbd5e1'; handleCsvFile(e.dataTransfer.files[0]) }}
                  style={{ marginTop:8, border:'2px dashed #cbd5e1', borderRadius:12, padding:'20px 0', textAlign:'center', cursor:'pointer', color:'#64748b', fontSize:13 }}
                >
                  <div style={{ fontSize:28, marginBottom:6 }}>📂</div>
                  <div>Drop CSV here or <span style={{ color:'#7c3aed', fontWeight:600 }}>click to upload</span></div>
                  <div style={{ fontSize:11, color:'#94a3b8', marginTop:4 }}>Columns: phone, full_name, what_best, how_many_rooms, when_do_you_plan_to_implement, city</div>
                </div>
                <input type="file" accept=".csv" ref={csvInputRef} onChange={e => handleCsvFile(e.target.files[0])} style={{ display:'none' }} />
                {csvError && <div style={{ marginTop:8, color:'#dc2626', fontSize:12 }}>{csvError}</div>}
                {csvLeads.length > 0 && (
                  <div style={{ marginTop:10 }}>
                    <div style={{ fontSize:12, color:'#16a34a', fontWeight:600, marginBottom:6 }}>✅ {csvLeads.length} leads loaded</div>
                    <div style={{ overflowX:'auto' }}>
                      <table style={{ width:'100%', fontSize:11, borderCollapse:'collapse' }}>
                        <thead>
                          <tr style={{ background:'#f1f5f9' }}>
                            {['Name','Phone','Var 1','Var 2','City'].map(h => <th key={h} style={{ padding:'4px 8px', textAlign:'left', color:'#64748b', fontWeight:600 }}>{h}</th>)}
                          </tr>
                        </thead>
                        <tbody>
                          {csvLeads.slice(0,5).map((l, i) => (
                            <tr key={i} style={{ borderTop:'1px solid #f1f5f9' }}>
                              <td style={{ padding:'4px 8px' }}>{l.name}</td>
                              <td style={{ padding:'4px 8px', fontFamily:'monospace' }}>{l.raw_phone}</td>
                              <td style={{ padding:'4px 8px' }}>{l.vars[0]||'—'}</td>
                              <td style={{ padding:'4px 8px' }}>{l.vars[1]||'—'}</td>
                              <td style={{ padding:'4px 8px' }}>{l.vars[4]||'—'}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                      {csvLeads.length > 5 && <div style={{ fontSize:11, color:'#94a3b8', marginTop:4, textAlign:'center' }}>…and {csvLeads.length - 5} more</div>}
                    </div>
                    <button onClick={() => { setCsvLeads([]); setCsvHeaders([]); setCsvError('') }} style={{ ...btnSecondary, marginTop:8, fontSize:11 }}>Clear CSV</button>
                  </div>
                )}
              </>
            )}

            {error && <div style={{ marginTop:8, color:'#dc2626', fontSize:12 }}>{error}</div>}

            <div style={{ display:'flex', gap:8, marginTop:16 }}>
              <button onClick={send} disabled={blasting} style={{ ...btnPrimary, background:'#16a34a', minWidth:180 }}>
                {blasting ? '⏳ Sending…' : mode === 'csv' && csvLeads.length > 0 ? `🚀 Send to ${csvLeads.length} Leads` : `🚀 Send Blast${totalLeads > 0 ? ` (${totalLeads})` : ''}`}
              </button>
              <button onClick={onClose} style={btnSecondary}>Cancel</button>
            </div>
          </>
        )}

        {/* Results */}
        {done && (
          <div>
            <div style={{ display:'flex', gap:16, marginBottom:12 }}>
              <div style={{ background:'#f0fdf4', color:'#16a34a', padding:'8px 16px', borderRadius:8, fontWeight:700, fontSize:14 }}>✅ {sentCount} Sent</div>
              {failCount > 0 && <div style={{ background:'#fef2f2', color:'#dc2626', padding:'8px 16px', borderRadius:8, fontWeight:700, fontSize:14 }}>❌ {failCount} Failed</div>}
            </div>
            <div style={{ maxHeight:240, overflowY:'auto' }}>
              <table style={{ width:'100%', fontSize:12, borderCollapse:'collapse' }}>
                <thead>
                  <tr style={{ background:'#f8fafc' }}>
                    {['Name','Phone','Status','Detail'].map(h => <th key={h} style={{ padding:'6px 8px', textAlign:'left', color:'#64748b', fontWeight:600 }}>{h}</th>)}
                  </tr>
                </thead>
                <tbody>
                  {results.map((r, i) => (
                    <tr key={i} style={{ borderTop:'1px solid #f1f5f9' }}>
                      <td style={{ padding:'5px 8px' }}>{r.name || '—'}</td>
                      <td style={{ padding:'5px 8px', fontFamily:'monospace' }}>{r.phone}</td>
                      <td style={{ padding:'5px 8px', color: r.status==='sent' ? '#16a34a' : '#dc2626', fontWeight:600 }}>{r.status === 'sent' ? '✅' : '❌'}</td>
                      <td style={{ padding:'5px 8px', color:'#64748b' }}>{r.msg}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <button onClick={onClose} style={{ ...btnPrimary, marginTop:12 }}>Done</button>
          </div>
        )}
      </ModalBox>
    </Overlay>
  )
}

// ── ContextMenu ────────────────────────────────────────────────────────────

function ContextMenu({ phone, isArchived, x, y, onClose, onArchive, onUnarchive, onDelete }) {
  useEffect(() => {
    const fn = () => onClose()
    window.addEventListener('click', fn)
    return () => window.removeEventListener('click', fn)
  }, [])

  return (
    <div onClick={e => e.stopPropagation()} style={{ position:'fixed', top:y, left:x, zIndex:9000, background:'#fff', border:'1px solid #e2e8f0', borderRadius:10, boxShadow:'0 8px 24px rgba(0,0,0,.15)', minWidth:160, padding:4 }}>
      {isArchived
        ? <MenuItem icon="📥" label="Unarchive" onClick={() => { onUnarchive(phone); onClose() }} />
        : <MenuItem icon="📦" label="Archive" onClick={() => { onArchive(phone); onClose() }} />
      }
      <MenuItem icon="🗑️" label="Delete all messages" onClick={() => {
        if (window.confirm('Delete ALL messages and this contact? This cannot be undone.')) { onDelete(phone); onClose() }
      }} danger />
    </div>
  )
}

function MenuItem({ icon, label, onClick, danger }) {
  return (
    <div onClick={onClick} style={{ display:'flex', alignItems:'center', gap:8, padding:'8px 12px', cursor:'pointer', borderRadius:6, fontSize:13, color: danger ? '#dc2626' : '#374151' }}
      onMouseEnter={e => e.currentTarget.style.background = danger ? '#fef2f2' : '#f8fafc'}
      onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
    >
      <span>{icon}</span>{label}
    </div>
  )
}

// ── Shared UI primitives ───────────────────────────────────────────────────

function Overlay({ children, onClose }) {
  return (
    <div onClick={e => { if (e.target === e.currentTarget) onClose() }} style={{ position:'fixed', inset:0, background:'rgba(0,0,0,.45)', zIndex:8000, display:'flex', alignItems:'center', justifyContent:'center', padding:16 }}>
      {children}
    </div>
  )
}

function ModalBox({ title, onClose, children, wide }) {
  return (
    <div style={{ background:'#fff', borderRadius:16, padding:24, width: wide ? 680 : 420, maxWidth:'95vw', maxHeight:'90vh', overflowY:'auto', boxShadow:'0 20px 60px rgba(0,0,0,.2)' }}>
      <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:16 }}>
        <h3 style={{ margin:0, fontSize:16, fontWeight:700, color:'#1e293b' }}>{title}</h3>
        <button onClick={onClose} style={{ background:'none', border:'none', fontSize:20, cursor:'pointer', color:'#94a3b8', lineHeight:1 }}>×</button>
      </div>
      {children}
    </div>
  )
}

const labelStyle = { display:'block', fontSize:12, fontWeight:600, color:'#374151', marginBottom:4, textTransform:'uppercase', letterSpacing:.4 }
const inputStyle  = { width:'100%', border:'1px solid #d1d5db', borderRadius:8, padding:'8px 10px', fontSize:13, outline:'none', boxSizing:'border-box', color:'#111', background:'#fff' }
const btnPrimary  = { background:'#7c3aed', color:'#fff', border:'none', borderRadius:8, padding:'9px 18px', fontSize:13, fontWeight:600, cursor:'pointer' }
const btnSecondary= { background:'#f1f5f9', color:'#374151', border:'1px solid #e2e8f0', borderRadius:8, padding:'9px 18px', fontSize:13, fontWeight:600, cursor:'pointer' }

// ── ReplyBox ───────────────────────────────────────────────────────────────

function ReplyBox({ phone, within24h, onSent, onToast }) {
  const [text, setText] = useState('')
  const [sending, setSending] = useState(false)
  const [showTemplates, setShowTemplates] = useState(false)
  const [templates, setTemplates] = useState([])
  const [attach, setAttach] = useState(null) // {mediaId, type, fileName}
  const [uploading, setUploading] = useState(false)
  const fileInputRef = useRef(null)

  useEffect(() => {
    api('GET', '/templates').then(d => setTemplates(d.templates || []))
  }, [])

  const sendText = async () => {
    const t = text.trim()
    if (!t && !attach) return
    setSending(true)
    try {
      if (attach) {
        await api('POST', '/send', { phone, text: `[Attachment: ${attach.fileName}]`, media_id: attach.mediaId, media_type: attach.type })
      } else {
        await api('POST', '/send', { phone, text: t })
      }
      setText('')
      setAttach(null)
      onSent()
      onToast({ type:'ok', text:'Message sent.' })
    } catch (e) { onToast({ type:'error', text: e.message }) }
    finally { setSending(false) }
  }

  const handleFile = async (file) => {
    if (!file) return
    setUploading(true)
    try {
      const fd = new FormData()
      fd.append('file', file)
      fd.append('_token', csrf())
      const r = await fetch('/platform/wa/upload-media', { method:'POST', body: fd })
      const d = await r.json()
      if (d.media_id) {
        setAttach({ mediaId: d.media_id, type: d.type, fileName: file.name })
        onToast({ type:'ok', text:'File ready to send.' })
      } else { onToast({ type:'error', text: d.error || 'Upload failed.' }) }
    } catch (e) { onToast({ type:'error', text: e.message }) }
    finally { setUploading(false) }
  }

  const onKey = (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendText() } }

  return (
    <div style={{ borderTop:'1px solid #e2e8f0', padding:'10px 16px', background:'#fff' }}>
      {!within24h && (
        <div style={{ background:'#fefce8', border:'1px solid #fde68a', borderRadius:8, padding:'6px 10px', marginBottom:8, fontSize:12, color:'#92400e' }}>
          ⚠️ Last message &gt;24h ago — free-form text may be blocked by Meta. Use a template instead.
        </div>
      )}
      {attach && (
        <div style={{ display:'flex', alignItems:'center', gap:8, background:'#f8fafc', border:'1px solid #e2e8f0', borderRadius:8, padding:'6px 10px', marginBottom:8, fontSize:12 }}>
          <span>📎 {attach.fileName}</span>
          <button onClick={() => setAttach(null)} style={{ marginLeft:'auto', background:'none', border:'none', cursor:'pointer', color:'#dc2626', fontSize:14 }}>×</button>
        </div>
      )}
      <div style={{ display:'flex', gap:8, alignItems:'flex-end' }}>
        <textarea
          value={text} onChange={e => setText(e.target.value)} onKeyDown={onKey}
          rows={2} placeholder="Type a message… (Enter to send, Shift+Enter for newline)"
          style={{ flex:1, border:'1px solid #d1d5db', borderRadius:10, padding:'8px 12px', fontSize:13, resize:'none', outline:'none', lineHeight:1.5 }}
        />
        <div style={{ display:'flex', flexDirection:'column', gap:6 }}>
          <button onClick={() => setShowTemplates(true)} title="Send template" style={{ ...btnSecondary, padding:'7px 10px', fontSize:13 }}>📨</button>
          <button onClick={() => fileInputRef.current?.click()} title="Attach file" style={{ ...btnSecondary, padding:'7px 10px', fontSize:13 }} disabled={uploading}>{uploading ? '⏳' : '📎'}</button>
          <button onClick={sendText} disabled={sending || (!text.trim() && !attach)} style={{ ...btnPrimary, padding:'7px 12px' }}>{sending ? '⏳' : '➤'}</button>
        </div>
      </div>
      <input type="file" ref={fileInputRef} style={{ display:'none' }} accept="image/*,.pdf" onChange={e => handleFile(e.target.files[0])} />
      {showTemplates && <TemplatePickerModal phone={phone} templates={templates} onClose={() => setShowTemplates(false)} onToast={onToast} />}
    </div>
  )
}

// ── Main Component ─────────────────────────────────────────────────────────

function PlatformWaInbox() {
  const [contacts, setContacts] = useState([])
  const [selectedPhone, setSelectedPhone] = useState('')
  const [messages, setMessages] = useState([])
  const [msgLoading, setMsgLoading] = useState(false)
  const [search, setSearch] = useState('')
  const [showArchived, setShowArchived] = useState(false)
  const [archivedCount, setArchivedCount] = useState(0)
  const [totalUnread, setTotalUnread] = useState(0)
  const [within24h, setWithin24h] = useState(false)
  const [loading, setLoading] = useState(true)
  const [toast, setToast] = useState(null)
  const [contextMenu, setContextMenu] = useState(null) // {phone, x, y, isArchived}
  const [showEdit, setShowEdit] = useState(false)
  const [showLeadInfo, setShowLeadInfo] = useState(false)
  const [showBlast, setShowBlast] = useState(false)
  const [templates, setTemplates] = useState([])
  const [mobileView, setMobileView] = useState('list') // 'list' | 'chat'
  const messagesEndRef = useRef(null)
  const contactsRef = useRef([])

  // Keep a ref in sync for stable callbacks
  contactsRef.current = contacts

  const selectedContact = contacts.find(c => c.phone === selectedPhone) || null

  // ── Fetch contacts ────────────────────────────────────────────────────────

  const fetchContacts = useCallback(async () => {
    try {
      const s = encodeURIComponent(search)
      const d = await api('GET', `/contacts?archived=${showArchived?1:0}&search=${s}`)
      setContacts(d.contacts || [])
      setArchivedCount(d.archived_count || 0)
      setTotalUnread(d.total_unread || 0)
    } catch {}
    finally { setLoading(false) }
  }, [search, showArchived])

  // ── Fetch messages ────────────────────────────────────────────────────────

  const fetchMessages = useCallback(async (phone, initial = false) => {
    if (!phone) return
    if (initial) setMsgLoading(true)
    try {
      const d = await api('GET', `/messages/${encPhone(phone)}`)
      setMessages(d.messages || [])
      setWithin24h(!!d.within24h)
      // Update contact's unread count locally
      setContacts(prev => prev.map(c => c.phone === phone ? { ...c, unread: 0 } : c))
    } catch {}
    finally { if (initial) setMsgLoading(false) }
  }, [])

  // ── Polling ───────────────────────────────────────────────────────────────

  useEffect(() => {
    fetchContacts()
    const id = setInterval(fetchContacts, 5000)
    return () => clearInterval(id)
  }, [fetchContacts])

  useEffect(() => {
    if (!selectedPhone) return
    fetchMessages(selectedPhone, true)
    const id = setInterval(() => fetchMessages(selectedPhone), 3000)
    return () => clearInterval(id)
  }, [selectedPhone, fetchMessages])

  useEffect(() => {
    if (templates.length === 0) {
      api('GET', '/templates').then(d => setTemplates(d.templates || []))
    }
  }, [])

  // Scroll to bottom on new messages
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  // ── Handlers ──────────────────────────────────────────────────────────────

  const selectContact = (phone) => {
    setSelectedPhone(phone)
    setMobileView('chat')
  }

  const openMenu = (e, phone) => {
    e.stopPropagation()
    const contact = contacts.find(c => c.phone === phone)
    const rect = e.currentTarget.getBoundingClientRect()
    setContextMenu({ phone, x: rect.left - 120, y: rect.bottom + 4, isArchived: contact?.is_archived || false })
  }

  const doArchive = async (phone) => {
    await api('POST', `/contacts/${encPhone(phone)}/archive`)
    setContacts(prev => prev.filter(c => c.phone !== phone))
    if (selectedPhone === phone) setSelectedPhone('')
    setArchivedCount(n => n + 1)
    toast_({ type:'ok', text:'Contact archived.' })
  }

  const doUnarchive = async (phone) => {
    await api('POST', `/contacts/${encPhone(phone)}/unarchive`)
    fetchContacts()
    toast_({ type:'ok', text:'Contact unarchived.' })
  }

  const doDelete = async (phone) => {
    await api('DELETE', `/contacts/${encPhone(phone)}`)
    setContacts(prev => prev.filter(c => c.phone !== phone))
    if (selectedPhone === phone) setSelectedPhone('')
    toast_({ type:'ok', text:'Contact deleted.' })
  }

  const doToggleSubscribe = async () => {
    if (!selectedPhone) return
    try {
      const d = await api('POST', `/contacts/${encPhone(selectedPhone)}/toggle-subscribe`)
      setContacts(prev => prev.map(c => c.phone === selectedPhone ? { ...c, subscribed: d.subscribed } : c))
      toast_({ type:'ok', text: d.message })
    } catch (e) { toast_({ type:'error', text: e.message }) }
  }

  const onContactSaved = (updated) => {
    setContacts(prev => prev.map(c => c.phone === updated.phone ? { ...c, name: updated.name, type: updated.type } : c))
    setShowEdit(false)
    toast_({ type:'ok', text:'Contact saved.' })
  }

  const toast_ = (msg) => setToast(msg)

  // ── Sidebar ───────────────────────────────────────────────────────────────

  const sidebar = (
    <div style={{ width:320, flexShrink:0, borderRight:'1px solid #e2e8f0', display:'flex', flexDirection:'column', height:'100%', background:'#fff' }}>
      {/* Header */}
      <div style={{ padding:'14px 16px', borderBottom:'1px solid #e2e8f0' }}>
        <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:10 }}>
          <div style={{ fontWeight:700, fontSize:15, color:'#1e293b' }}>
            WhatsApp Inbox
            {totalUnread > 0 && <span style={{ marginLeft:8, background:'#dc2626', color:'#fff', borderRadius:10, padding:'1px 7px', fontSize:11 }}>{totalUnread}</span>}
          </div>
          <div style={{ display:'flex', gap:6 }}>
            <button onClick={() => setShowBlast(true)} title="Bulk Blast" style={{ ...btnSecondary, padding:'5px 10px', fontSize:12 }}>📤 Blast</button>
          </div>
        </div>
        <input
          value={search} onChange={e => setSearch(e.target.value)}
          placeholder="🔍 Search contacts…"
          style={{ ...inputStyle, marginBottom:8 }}
        />
        <div style={{ display:'flex', gap:6 }}>
          <button onClick={() => setShowArchived(false)} style={{ flex:1, padding:'5px 0', borderRadius:6, border:'none', cursor:'pointer', fontSize:12, fontWeight:600, background: !showArchived ? '#7c3aed' : '#f1f5f9', color: !showArchived ? '#fff' : '#64748b' }}>Active</button>
          <button onClick={() => setShowArchived(true)} style={{ flex:1, padding:'5px 0', borderRadius:6, border:'none', cursor:'pointer', fontSize:12, fontWeight:600, background: showArchived ? '#7c3aed' : '#f1f5f9', color: showArchived ? '#fff' : '#64748b' }}>
            Archived {archivedCount > 0 ? `(${archivedCount})` : ''}
          </button>
        </div>
      </div>

      {/* List */}
      <div style={{ flex:1, overflowY:'auto' }}>
        {loading && <div style={{ padding:24, textAlign:'center', color:'#94a3b8' }}>Loading…</div>}
        {!loading && contacts.length === 0 && (
          <div style={{ padding:24, textAlign:'center', color:'#94a3b8', fontSize:13 }}>
            {search ? 'No contacts match your search.' : showArchived ? 'No archived contacts.' : 'No conversations yet.'}
          </div>
        )}
        {contacts.map(c => (
          <ContactItem key={c.phone} c={c} selected={c.phone === selectedPhone}
            onClick={selectContact}
            onMenu={(phone) => {
              const el = document.querySelector(`[data-phone="${phone}"] button`)
              const contact = contacts.find(x => x.phone === phone)
              // Use a simpler position approach
              setContextMenu({ phone, x: 160, y: 120, isArchived: contact?.is_archived || false })
            }}
          />
        ))}
      </div>
    </div>
  )

  // ── Message pane ──────────────────────────────────────────────────────────

  const msgPane = selectedContact ? (
    <div style={{ flex:1, display:'flex', flexDirection:'column', height:'100%', minWidth:0 }}>
      {/* Contact header */}
      <div style={{ padding:'12px 20px', borderBottom:'1px solid #e2e8f0', display:'flex', alignItems:'center', gap:12, background:'#fff' }}>
        {/* Mobile back button */}
        <button onClick={() => setMobileView('list')} className="wa-back-btn" style={{ display:'none', background:'none', border:'none', cursor:'pointer', color:'#7c3aed', fontSize:20, padding:0 }}>←</button>

        <div style={{ width:40, height:40, borderRadius:'50%', background: selectedContact.type==='owner'?'#ede9fe':'#e0f2fe', display:'flex', alignItems:'center', justifyContent:'center', fontWeight:700, fontSize:14, color: selectedContact.type==='owner'?'#7c3aed':'#0891b2', flexShrink:0 }}>
          {(selectedContact.name||selectedContact.phone).slice(0,2).toUpperCase()}
        </div>
        <div style={{ flex:1, minWidth:0 }}>
          <div style={{ fontWeight:700, fontSize:15, color:'#1e293b' }}>{selectedContact.name}</div>
          <div style={{ fontSize:12, color:'#64748b', display:'flex', gap:6, alignItems:'center', flexWrap:'wrap' }}>
            <span style={{ background: selectedContact.type==='owner'?'#ede9fe':'#e0f2fe', color: selectedContact.type==='owner'?'#7c3aed':'#0891b2', padding:'1px 7px', borderRadius:8, fontSize:11 }}>{selectedContact.type_label}</span>
            <span style={{ fontFamily:'monospace' }}>{selectedContact.phone}</span>
            {leadBadge(selectedContact.lead_status)}
            {!selectedContact.subscribed && <span style={{ background:'#fef2f2', color:'#dc2626', padding:'1px 6px', borderRadius:6, fontSize:11 }}>🚫 Unsubscribed</span>}
          </div>
        </div>
        <div style={{ display:'flex', gap:6 }}>
          <ActionBtn title="Lead info" icon="📊" onClick={() => setShowLeadInfo(true)} />
          <ActionBtn title="Edit contact" icon="✏️" onClick={() => setShowEdit(true)} />
          <ActionBtn title={selectedContact.subscribed ? 'Unsubscribe' : 'Re-subscribe'} icon={selectedContact.subscribed ? '🔕' : '🔔'} onClick={doToggleSubscribe} />
          <ActionBtn title="Archive" icon="📦" onClick={() => doArchive(selectedContact.phone)} />
        </div>
      </div>

      {/* Messages */}
      <div style={{ flex:1, overflowY:'auto', padding:'16px 20px', background:'#fafafa' }}>
        {msgLoading && <div style={{ textAlign:'center', color:'#94a3b8', padding:20 }}>Loading messages…</div>}
        {!msgLoading && messages.length === 0 && <div style={{ textAlign:'center', color:'#94a3b8', fontSize:13, marginTop:40 }}>No messages yet. Send the first one!</div>}
        {messages.map(msg => <MessageBubble key={msg.id} msg={msg} />)}
        <div ref={messagesEndRef} />
      </div>

      {/* Reply box */}
      <ReplyBox phone={selectedPhone} within24h={within24h} onSent={() => fetchMessages(selectedPhone)} onToast={toast_} />
    </div>
  ) : (
    <div style={{ flex:1, display:'flex', alignItems:'center', justifyContent:'center', color:'#94a3b8', fontSize:14, background:'#fafafa' }}>
      <div style={{ textAlign:'center' }}>
        <div style={{ fontSize:48, marginBottom:12 }}>💬</div>
        <div>Select a contact to start messaging</div>
      </div>
    </div>
  )

  return (
    <div style={{ display:'flex', height:'calc(100vh - 120px)', fontFamily:'system-ui,-apple-system,sans-serif', overflow:'hidden', background:'#fff', borderRadius:12, border:'1px solid #e2e8f0', boxShadow:'0 2px 12px rgba(0,0,0,.06)' }}>
      {/* Context menu */}
      {contextMenu && (
        <ContextMenu {...contextMenu}
          onClose={() => setContextMenu(null)}
          onArchive={doArchive}
          onUnarchive={doUnarchive}
          onDelete={doDelete}
        />
      )}

      {/* Sidebar */}
      <div className="wa-sidebar" style={{ display:'flex', width:320, flexShrink:0 }}>
        {sidebar}
      </div>

      {/* Message pane */}
      <div className="wa-pane" style={{ flex:1, display:'flex', minWidth:0 }}>
        {msgPane}
      </div>

      {/* Modals */}
      {showEdit && selectedContact && (
        <ContactEditModal contact={selectedContact} onSave={onContactSaved} onClose={() => setShowEdit(false)} />
      )}
      {showLeadInfo && selectedPhone && (
        <LeadInfoModal phone={selectedPhone} onClose={() => setShowLeadInfo(false)} />
      )}
      {showBlast && (
        <BlastModal templates={templates} onClose={() => setShowBlast(false)} onToast={toast_} />
      )}

      {/* Toast */}
      {toast && <Toast msg={toast} onClose={() => setToast(null)} />}

      {/* Mobile responsive styles */}
      <style>{`
        @media (max-width: 680px) {
          .wa-back-btn { display: block !important; }
        }
      `}</style>
    </div>
  )
}

function ActionBtn({ icon, title, onClick }) {
  return (
    <button onClick={onClick} title={title} style={{ background:'#f8fafc', border:'1px solid #e2e8f0', borderRadius:8, padding:'5px 8px', cursor:'pointer', fontSize:14, lineHeight:1 }}
      onMouseEnter={e => e.currentTarget.style.background='#ede9fe'}
      onMouseLeave={e => e.currentTarget.style.background='#f8fafc'}
    >{icon}</button>
  )
}

// ── Mount ──────────────────────────────────────────────────────────────────

const el = document.getElementById('wa-inbox-root')
if (el) {
  ReactDOM.createRoot(el).render(
    <React.StrictMode>
      <PlatformWaInbox />
    </React.StrictMode>
  )
}
