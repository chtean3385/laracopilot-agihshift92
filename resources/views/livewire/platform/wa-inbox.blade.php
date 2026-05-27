<style>
@keyframes wa-hot-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(239,68,68,0.7); }
    70%  { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
    100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
}
@keyframes wa-hot-badge-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.35; }
}
.wa-hot-row      { animation: wa-hot-pulse 2s infinite; }
.wa-hot-badge    { animation: wa-hot-badge-blink 1.2s ease-in-out infinite; }
</style>

<div wire:poll.4000ms style="display:flex;height:calc(100vh - 140px);background:#fff;border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;position:relative;">

    {{-- ── Conversations List ─────────────────────────────────────────── --}}
    <div style="width:300px;flex-shrink:0;border-right:1px solid #f1f5f9;display:flex;flex-direction:column;overflow:hidden;">

        <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:34px;height:34px;background:linear-gradient(135deg,#25d366,#128c43);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fab fa-whatsapp" style="color:#fff;font-size:16px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:#0f172a;">WA Inbox</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $conversations->count() }} conversation{{ $conversations->count() !== 1 ? 's' : '' }}</div>
                </div>
                @if($totalUnread > 0)
                <span style="background:#ef4444;color:#fff;border-radius:999px;font-size:11px;font-weight:700;padding:2px 8px;min-width:22px;text-align:center;">{{ $totalUnread }}</span>
                @endif
                {{-- Bulk Blast button --}}
                <button wire:click="openBlast"
                        title="Bulk Send — paste numbers and send approved template"
                        style="width:30px;height:30px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border:none;border-radius:8px;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-plus" style="font-size:13px;"></i>
                </button>
            </div>
        </div>

        <div style="flex:1;overflow-y:auto;">
            @forelse($conversations as $convo)
            @php
                $isSelected = $selectedContact && $selectedContact->phone === $convo->phone;
                $isHot      = $convo->lead_status === 'hot';
                $isWarm     = $convo->lead_status === 'warm';
                $isCold     = $convo->lead_status === 'cold';
                $isNurture  = $convo->lead_status === 'nurture';
            @endphp
            <div style="position:relative;">
                <div wire:click="selectContact('{{ $convo->phone }}')"
                     class="{{ $isHot ? 'wa-hot-row' : '' }}"
                     style="padding:11px 13px;padding-right:36px;cursor:pointer;border-bottom:1px solid #f8fafc;transition:background .12s;
                            {{ $isSelected ? 'background:#ede9fe;border-left:3px solid #7c3aed;' : ($isHot ? 'background:#fff5f5;border-left:3px solid #ef4444;' : 'border-left:3px solid transparent;') }}"
                     onmouseover="if(!{{ $isSelected ? 'true' : 'false' }})this.style.background='#f8fafc'"
                     onmouseout="if(!{{ $isSelected ? 'true' : 'false' }})this.style.background='{{ $isHot ? '#fff5f5' : '' }}'">
                    <div style="display:flex;align-items:flex-start;gap:9px;">
                        <div style="position:relative;flex-shrink:0;">
                            <div style="width:40px;height:40px;background:{{ $convo->type_color }};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;opacity:.9;">
                                {{ mb_strtoupper(mb_substr($convo->name, 0, 1)) }}
                            </div>
                            <span style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;border:2px solid #fff;background:{{ $convo->consented ? '#25d366' : '#e2e8f0' }};"></span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:2px;">
                                <span style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;">
                                    @if($isHot)<span class="wa-hot-badge" style="font-size:13px;margin-right:2px;">🔥</span>@endif
                                    {{ $convo->name }}
                                </span>
                                <span style="font-size:10px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $convo->time_ago }}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">
                                <span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:{{ $convo->type_bg }};color:{{ $convo->type_color }};flex-shrink:0;letter-spacing:.3px;">{{ strtoupper($convo->type_label) }}</span>
                                @if($isHot)<span class="wa-hot-badge" style="font-size:9px;font-weight:800;padding:1px 5px;border-radius:4px;background:#fee2e2;color:#dc2626;flex-shrink:0;border:1px solid #fca5a5;">🔥 HOT</span>@endif
                                @if($isWarm)<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:#fef3c7;color:#d97706;flex-shrink:0;">🟡 WARM</span>@endif
                                @if($isCold)<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:#eff6ff;color:#3b82f6;flex-shrink:0;">❄️ COLD</span>@endif
                                @if($isNurture)<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:#f8fafc;color:#64748b;flex-shrink:0;">💤 NURTURE</span>@endif
                                @if(!$convo->subscribed)<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:#f1f5f9;color:#94a3b8;flex-shrink:0;">STOPPED</span>@endif
                                <span style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;">{{ $convo->preview }}</span>
                                @if($convo->unread > 0)
                                <span style="background:#25d366;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;flex-shrink:0;min-width:18px;text-align:center;">{{ $convo->unread }}</span>
                                @endif
                            </div>
                            <div style="font-size:10px;color:#94a3b8;display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                @if($convo->hotel_name)
                                <span>{{ $convo->hotel_name }}</span>
                                @else
                                <span>{{ $convo->phone }}</span>
                                @endif
                                @if($convo->lead_role)
                                <span style="color:#cbd5e1;">·</span>
                                <span style="font-size:9px;font-weight:700;padding:1px 4px;border-radius:4px;background:#f1f5f9;color:#64748b;">{{ $convo->lead_role }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ℹ️ Lead info button — positioned absolutely to avoid triggering selectContact --}}
                <button wire:click.stop="openLeadInfo('{{ $convo->phone }}')"
                        title="View lead details"
                        style="position:absolute;top:50%;right:8px;transform:translateY(-50%);width:22px;height:22px;background:{{ $convo->lead_status ? '#ede9fe' : '#f1f5f9' }};border:1px solid {{ $convo->lead_status ? '#c4b5fd' : '#e2e8f0' }};border-radius:6px;cursor:pointer;color:{{ $convo->lead_status ? '#7c3aed' : '#94a3b8' }};display:flex;align-items:center;justify-content:center;font-size:11px;z-index:10;transition:all .15s;"
                        onmouseover="this.style.background='#ede9fe';this.style.color='#7c3aed';this.style.borderColor='#a78bfa'"
                        onmouseout="this.style.background='{{ $convo->lead_status ? '#ede9fe' : '#f1f5f9' }}';this.style.color='{{ $convo->lead_status ? '#7c3aed' : '#94a3b8' }}'">
                    <i class="fas fa-info" style="font-size:9px;"></i>
                </button>
            </div>
            @empty
            <div style="padding:40px 20px;text-align:center;color:#94a3b8;">
                <i class="fab fa-whatsapp" style="font-size:36px;opacity:.3;display:block;margin-bottom:10px;"></i>
                <div style="font-size:13px;font-weight:600;">Waiting for first message</div>
                <div style="font-size:11px;margin-top:4px;line-height:1.6;">Conversations appear here only when someone messages your WhatsApp number first. Hotel owners sharing the same phone show as one conversation.</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Thread Panel ────────────────────────────────────────────────── --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;">

        @if($selectedContact)

        {{-- Thread header --}}
        <div style="padding:11px 16px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
            <button wire:click="backToList" style="width:30px;height:30px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:3px;">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i>
            </button>
            <div style="width:36px;height:36px;background:{{ $selectedContact->type_color }};opacity:.9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;flex-shrink:0;">
                {{ mb_strtoupper(mb_substr($selectedContact->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                    @if($selectedContact->lead_status === 'hot')<span style="font-size:16px;">🔥</span>@endif
                    {{ $selectedContact->name }}
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;background:{{ $selectedContact->type_bg }};color:{{ $selectedContact->type_color }};letter-spacing:.3px;">{{ strtoupper($selectedContact->type_label) }}</span>
                    @if($selectedContact->lead_status === 'hot')
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;background:#fee2e2;color:#dc2626;">HOT LEAD</span>
                    @elseif($selectedContact->lead_status === 'warm')
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;background:#fef3c7;color:#d97706;">WARM LEAD</span>
                    @endif
                </div>
                <div style="font-size:11px;color:#94a3b8;margin-top:1px;">
                    {{ $selectedContact->phone }}
                    @if($selectedContact->hotel_name) &nbsp;·&nbsp; {{ $selectedContact->hotel_name }} @endif
                </div>
                @if($selectedContact->bot_state === 'completed' && $selectedContact->bot_service)
                <div style="font-size:11px;color:#64748b;margin-top:2px;">
                    <i class="fas fa-robot" style="font-size:9px;color:#7c3aed;margin-right:3px;"></i>
                    {{ $selectedContact->bot_service }}
                    @if($selectedContact->bot_budget) &nbsp;·&nbsp; {{ $selectedContact->bot_budget }} @endif
                </div>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap;">
                {{-- Consent --}}
                @if($selectedContact->consented)
                <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <span style="width:6px;height:6px;background:#15803d;border-radius:50%;"></span> Consented
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <i class="fas fa-clock" style="font-size:9px;"></i> No consent
                </span>
                @endif
                {{-- 24h --}}
                @if($within24h)
                <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <span style="width:6px;height:6px;background:#15803d;border-radius:50%;"></span> 24h open
                </span>
                @endif
                {{-- Subscription toggle --}}
                @if($selectedContact->subscribed)
                <button wire:click="toggleSubscription"
                        style="height:28px;padding:0 10px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;cursor:pointer;color:#15803d;font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;"
                        title="Click to unsubscribe this contact from bot messages">
                    <i class="fas fa-bell" style="font-size:10px;"></i> Subscribed
                </button>
                @else
                <button wire:click="toggleSubscription"
                        style="height:28px;padding:0 10px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;cursor:pointer;color:#dc2626;font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;"
                        title="Click to re-subscribe this contact to bot messages">
                    <i class="fas fa-bell-slash" style="font-size:10px;"></i> Stopped
                </button>
                @endif

                {{-- Edit button --}}
                <button wire:click="openEditContact"
                        style="height:28px;padding:0 10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#475569;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;"
                        title="Edit contact name and type">
                    <i class="fas fa-user-edit" style="font-size:10px;"></i> Edit
                </button>
            </div>
        </div>

        {{-- Inline contact edit form --}}
        @if($editingContact)
        <div style="padding:12px 16px;background:#f0f9ff;border-bottom:1px solid #bae6fd;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:200px;">
                <label style="font-size:12px;font-weight:600;color:#0369a1;white-space:nowrap;">Name:</label>
                <input wire:model="editName" type="text" placeholder="Contact name"
                       style="flex:1;border:1.5px solid #7dd3fc;border-radius:8px;padding:5px 9px;font-size:12px;outline:none;color:#0f172a;"
                       onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#7dd3fc'">
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:600;color:#0369a1;white-space:nowrap;">Type:</label>
                <select wire:model="editType"
                        style="border:1.5px solid #7dd3fc;border-radius:8px;padding:5px 9px;font-size:12px;outline:none;color:#0f172a;background:#fff;">
                    <option value="unknown">Unknown</option>
                    <option value="owner">Owner</option>
                    <option value="guest">Guest</option>
                </select>
            </div>
            <div style="display:flex;gap:6px;">
                <button wire:click="saveContact"
                        style="height:30px;padding:0 14px;background:#0ea5e9;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    Save
                </button>
                <button wire:click="cancelEdit"
                        style="height:30px;padding:0 14px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
            </div>
        </div>
        @endif

        {{-- Messages --}}
        <div id="wa-messages-scroll"
             style="flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:10px;background:#efeae2;">

            @if($messages->isEmpty())
            <div style="text-align:center;color:#94a3b8;padding:40px 0;margin:auto;">
                <i class="fab fa-whatsapp" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px;"></i>
                <div style="font-size:13px;font-weight:600;">No messages yet</div>
                <div style="font-size:11px;margin-top:4px;max-width:240px;margin-left:auto;margin-right:auto;line-height:1.5;">
                    Send the first message below. The auto-greeting bot will respond automatically to inbound messages.
                </div>
            </div>
            @endif

            @foreach($messages as $msg)
            @php $isOutgoing = $msg->direction === 'outgoing'; @endphp
            <div style="display:flex;flex-direction:column;align-items:{{ $isOutgoing ? 'flex-end' : 'flex-start' }};">
                <div style="font-size:10px;color:#94a3b8;margin-bottom:2px;padding:0 4px;">
                    <span style="font-weight:700;color:{{ $msg->tag_color }};font-size:10px;">{{ $msg->tag }}</span>
                    &nbsp;·&nbsp; {{ $msg->time }}
                </div>
                @php
                    $txt = $msg->text;
                    $mediaMap = [
                        '📷' => ['fa-image',          '#0891b2', '#e0f2fe'],
                        '🎥' => ['fa-video',           '#7c3aed', '#ede9fe'],
                        '🎵' => ['fa-microphone',      '#059669', '#d1fae5'],
                        '📄' => ['fa-file-alt',        '#d97706', '#fef3c7'],
                        '🪄' => ['fa-magic',           '#ec4899', '#fce7f3'],
                        '📍' => ['fa-map-marker-alt',  '#dc2626', '#fee2e2'],
                        '👤' => ['fa-address-card',    '#64748b', '#f1f5f9'],
                        '⚠️' => ['fa-exclamation-triangle', '#b45309', '#fef3c7'],
                        '📨' => ['fa-envelope',        '#0891b2', '#e0f2fe'],
                    ];
                    $mediaIcon = null; $mediaColor = null; $mediaBg = null;
                    foreach ($mediaMap as $emoji => [$icon, $color, $bg]) {
                        if (mb_substr($txt, 0, mb_strlen($emoji)) === $emoji) {
                            $mediaIcon = $icon; $mediaColor = $color; $mediaBg = $bg; break;
                        }
                    }
                @endphp
                <div style="max-width:68%;background:{{ $isOutgoing ? 'linear-gradient(135deg,#7c3aed,#5b21b6)' : ($mediaIcon && !$isOutgoing ? $mediaBg : '#fff') }};color:{{ $isOutgoing ? '#fff' : '#1e293b' }};border-radius:{{ $isOutgoing ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};padding:10px 14px;box-shadow:0 1px 4px rgba(0,0,0,.1);">
                    @if($mediaIcon && !$isOutgoing)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;background:{{ $mediaColor }}22;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas {{ $mediaIcon }}" style="color:{{ $mediaColor }};font-size:14px;"></i>
                        </div>
                        <span style="font-size:13px;color:#374151;font-weight:500;">{{ $txt }}</span>
                    </div>
                    @else
                    <div style="font-size:13px;line-height:1.5;word-break:break-word;white-space:pre-wrap;">{{ $txt }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Reply area --}}
        <div style="padding:12px 16px;border-top:1px solid #f1f5f9;background:#fff;">

            @if($sendResult)
            @php $isOk = str_starts_with($sendResult, 'ok:'); $resTxt = substr($sendResult, strpos($sendResult, ':') + 1); @endphp
            <div style="padding:7px 12px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:8px;background:{{ $isOk ? '#dcfce7' : '#fee2e2' }};color:{{ $isOk ? '#15803d' : '#b91c1c' }};border:1px solid {{ $isOk ? '#bbf7d0' : '#fecaca' }};">
                <i class="fas fa-{{ $isOk ? 'check-circle' : 'exclamation-circle' }}" style="margin-right:5px;"></i>{{ $resTxt }}
            </div>
            @endif

            @if(!$selectedContact->subscribed)
            <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:7px 12px;font-size:11px;color:#64748b;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-bell-slash"></i>
                <span>This contact has <strong>unsubscribed</strong> (sent STOP). Auto-bot is paused. You can still send manual messages. Click <strong>Stopped</strong> above to re-subscribe.</span>
            </div>
            @endif

            @if(!$within24h && $messages->isNotEmpty())
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:7px 12px;font-size:11px;color:#92400e;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-exclamation-circle"></i>
                No reply in last 24h — Meta may restrict free-text messages. Message will still be attempted.
            </div>
            @endif

            {{-- Attachment preview (managed by JS) --}}
            <div id="wa-attach-preview" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:9px 12px;margin-bottom:8px;align-items:center;gap:10px;flex-wrap:wrap;">
                <div id="wa-attach-thumb" style="flex-shrink:0;"></div>
                <div style="flex:1;min-width:0;">
                    <div id="wa-attach-name" style="font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                    <div id="wa-attach-status" style="font-size:11px;color:#64748b;margin-top:2px;"></div>
                </div>
                <button onclick="window.waClearAttachment()" style="width:24px;height:24px;background:#fee2e2;border:none;border-radius:6px;cursor:pointer;color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-times" style="font-size:10px;"></i>
                </button>
            </div>

            <div style="display:flex;gap:8px;align-items:flex-end;">
                {{-- Attachment button --}}
                <label for="wa-file-input" title="Attach image or PDF"
                       style="width:40px;height:44px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;"
                       onmouseover="this.style.borderColor='#8b5cf6';this.style.background='#ede9fe'"
                       onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
                    <i class="fas fa-paperclip" style="color:#64748b;font-size:15px;"></i>
                </label>
                <input id="wa-file-input" type="file" accept="image/jpeg,image/png,application/pdf" style="display:none;"
                       onchange="window.waHandleFileSelect(this)">

                {{-- Text area --}}
                <textarea wire:model="replyText"
                          id="wa-reply-textarea"
                          placeholder="Type a message… (emoji ✅ supported)"
                          rows="2"
                          style="flex:1;border:1.5px solid #e2e8f0;border-radius:12px;padding:9px 13px;font-size:13px;color:#1e293b;resize:none;outline:none;transition:border-color .15s;font-family:inherit;"
                          onfocus="this.style.borderColor='#8b5cf6'"
                          onblur="this.style.borderColor='#e2e8f0'"
                          wire:keydown.ctrl.enter="sendReply"></textarea>

                {{-- Send button --}}
                <button wire:click="sendReply"
                        wire:loading.attr="disabled"
                        id="wa-send-text-btn"
                        style="height:44px;padding:0 18px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;flex-shrink:0;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <span wire:loading.remove wire:target="sendReply"><i class="fas fa-paper-plane"></i> Send</span>
                    <span wire:loading wire:target="sendReply"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
            <div style="font-size:10px;color:#94a3b8;margin-top:4px;padding-left:2px;">
                Ctrl+Enter to send &nbsp;·&nbsp; 📎 Attach image or PDF &nbsp;·&nbsp; Emoji copy-paste supported
            </div>
        </div>

        @else

        {{-- Empty state --}}
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;padding:40px;">
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                <i class="fab fa-whatsapp" style="font-size:38px;color:#25d366;"></i>
            </div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:6px;">WhatsApp Inbox</div>
            <div style="font-size:13px;text-align:center;max-width:340px;line-height:1.6;color:#64748b;">
                Conversations appear only when someone messages your WhatsApp number first. If the same admin manages multiple hotels with the same phone, it shows as <strong>one conversation</strong>.
            </div>
            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
                <span style="font-size:11px;background:#ede9fe;color:#7c3aed;padding:4px 10px;border-radius:6px;font-weight:700;">OWNER — Hotel phone match</span>
                <span style="font-size:11px;background:#e0f2fe;color:#0891b2;padding:4px 10px;border-radius:6px;font-weight:700;">GUEST — Customer match</span>
                <span style="font-size:11px;background:#f1f5f9;color:#64748b;padding:4px 10px;border-radius:6px;font-weight:700;">UNKNOWN — New contact</span>
                <span style="font-size:11px;background:#fee2e2;color:#dc2626;padding:4px 10px;border-radius:6px;font-weight:700;">🔥 HOT LEAD — Budget ₹1lac+</span>
            </div>
            <div style="margin-top:14px;font-size:11px;text-align:center;background:#f0fdf4;border-radius:10px;padding:9px 14px;color:#15803d;border:1px solid #bbf7d0;">
                <i class="fas fa-robot" style="margin-right:5px;"></i>
                Auto-greeting bot active — greets new contacts and qualifies leads automatically
            </div>
            <div style="margin-top:8px;font-size:11px;text-align:center;background:#f1f5f9;border-radius:10px;padding:9px 14px;color:#64748b;">
                <i class="fas fa-sync-alt" style="margin-right:5px;"></i>
                Auto-refreshes every 4 seconds &nbsp;·&nbsp; Green dot = consented
            </div>
        </div>

        @endif
    </div>

    {{-- ── Bulk Blast Modal ────────────────────────────────────────────── --}}
    @if($showBlast)
    <div style="position:absolute;inset:0;background:rgba(15,23,42,.55);z-index:100;display:flex;align-items:flex-start;justify-content:center;padding:24px 16px;overflow-y:auto;backdrop-filter:blur(2px);">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:640px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">

            {{-- Header --}}
            <div style="padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,#7c3aed,#5b21b6);">
                <div style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-paper-plane" style="color:#fff;font-size:16px;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:15px;font-weight:800;color:#fff;">Bulk WhatsApp Blast</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.7);">Select an approved template, add numbers, send</div>
                </div>
                <button wire:click="closeBlast" style="width:32px;height:32px;background:rgba(255,255,255,.2);border:none;border-radius:8px;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-times" style="font-size:13px;"></i>
                </button>
            </div>

            <div style="padding:20px 22px;display:flex;flex-direction:column;gap:18px;">

                @if($blastError)
                <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;color:#b91c1c;font-weight:600;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-exclamation-circle"></i> {{ $blastError }}
                </div>
                @endif

                @if(!$blastDone)

                {{-- Step 1: Template selector --}}
                <div>
                    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
                        <span style="background:#7c3aed;color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;margin-right:5px;">1</span>
                        Select Approved Template
                    </label>
                    <select wire:change="selectBlastTemplate($event.target.value)"
                            style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 12px;font-size:13px;color:#0f172a;background:#fff;outline:none;"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                        <option value="0">— Choose a template —</option>
                        @foreach($approvedTemplates as $tmpl)
                        <option value="{{ $tmpl->id }}" {{ $blastTemplateId === $tmpl->id ? 'selected' : '' }}>
                            {{ $tmpl->template_name }}
                            @if($tmpl->trigger_event) ({{ $tmpl->trigger_event }}) @endif
                        </option>
                        @endforeach
                    </select>
                    @if($approvedTemplates->isEmpty())
                    <p style="font-size:11px;color:#f59e0b;margin-top:5px;"><i class="fas fa-exclamation-triangle"></i> No approved templates found. Go to WA Templates, submit one to Meta, and sync status.</p>
                    @endif
                </div>

                {{-- Template preview --}}
                @if($blastPreview)
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;">
                    <div style="font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;letter-spacing:.5px;">TEMPLATE PREVIEW</div>
                    <div style="font-size:13px;color:#374151;white-space:pre-wrap;line-height:1.6;">{{ $blastPreview }}</div>
                </div>
                @endif

                {{-- Variable inputs --}}
                @if(!empty($blastVarNames))
                <div>
                    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:8px;">
                        <span style="background:#7c3aed;color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;margin-right:5px;">2</span>
                        Fill Template Variables
                    </label>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach($blastVarNames as $idx => $varName)
                        <div style="display:flex;align-items:center;gap:10px;">
                            <label style="font-size:12px;font-weight:600;color:#475569;white-space:nowrap;min-width:120px;">
                                @php echo e('{{' . $varName . '}}'); @endphp
                            </label>
                            <input wire:model="blastVars.{{ $idx }}"
                                   type="text"
                                   placeholder="Value for {{ $varName }}"
                                   style="flex:1;border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:12px;color:#0f172a;outline:none;"
                                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Step: Phone numbers --}}
                <div>
                    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
                        <span style="background:#7c3aed;color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;margin-right:5px;">{{ empty($blastVarNames) ? '2' : '3' }}</span>
                        Phone Numbers <span style="font-weight:400;color:#94a3b8;">(one per line — 10-digit or full with country code)</span>
                    </label>
                    <textarea wire:model="blastNumbers"
                              rows="6"
                              placeholder="9876543210&#10;917890123456&#10;8012345678&#10;..."
                              style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;color:#0f172a;resize:vertical;outline:none;font-family:monospace;box-sizing:border-box;"
                              onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                    @php $lineCount = $blastNumbers ? count(array_filter(array_map('trim', explode("\n", $blastNumbers)))) : 0; @endphp
                    <div style="font-size:11px;color:#64748b;margin-top:4px;display:flex;justify-content:space-between;">
                        <span>{{ $lineCount }} number{{ $lineCount !== 1 ? 's' : '' }} entered</span>
                        <span style="color:{{ $lineCount > 100 ? '#dc2626' : '#94a3b8' }};">Max 200 per blast</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:4px;">
                    <button wire:click="closeBlast"
                            style="height:40px;padding:0 20px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">
                        Cancel
                    </button>
                    <button wire:click="sendBlast"
                            wire:loading.attr="disabled"
                            style="height:40px;padding:0 24px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border:none;border-radius:10px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;display:flex;align-items:center;gap:7px;transition:opacity .15s;"
                            onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <span wire:loading.remove wire:target="sendBlast">
                            <i class="fas fa-paper-plane"></i>
                            Send to {{ $lineCount }} Number{{ $lineCount !== 1 ? 's' : '' }}
                        </span>
                        <span wire:loading wire:target="sendBlast">
                            <i class="fas fa-spinner fa-spin"></i> Sending… (this may take a moment)
                        </span>
                    </button>
                </div>

                @else

                {{-- Results view --}}
                @php
                    $sentCount  = collect($blastResults)->where('status', 'sent')->count();
                    $failCount  = collect($blastResults)->where('status', 'fail')->count();
                    $skipCount  = collect($blastResults)->where('status', 'skip')->count();
                @endphp
                <div style="background:{{ $failCount === 0 ? '#f0fdf4' : '#fffbeb' }};border:1px solid {{ $failCount === 0 ? '#bbf7d0' : '#fde68a' }};border-radius:12px;padding:14px 16px;">
                    <div style="font-size:14px;font-weight:800;color:{{ $failCount === 0 ? '#15803d' : '#92400e' }};margin-bottom:4px;">
                        <i class="fas fa-{{ $failCount === 0 ? 'check-circle' : 'exclamation-triangle' }}"></i>
                        Blast Complete
                    </div>
                    <div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap;">
                        <div style="text-align:center;">
                            <div style="font-size:22px;font-weight:900;color:#15803d;">{{ $sentCount }}</div>
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Accepted by Meta</div>
                        </div>
                        @if($failCount > 0)
                        <div style="text-align:center;">
                            <div style="font-size:22px;font-weight:900;color:#dc2626;">{{ $failCount }}</div>
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Failed (API error)</div>
                        </div>
                        @endif
                        @if($skipCount > 0)
                        <div style="text-align:center;">
                            <div style="font-size:22px;font-weight:900;color:#f59e0b;">{{ $skipCount }}</div>
                            <div style="font-size:11px;color:#64748b;font-weight:600;">Skipped</div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Delivery note --}}
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;font-size:12px;color:#92400e;line-height:1.6;">
                    <strong><i class="fas fa-info-circle"></i> About delivery:</strong>
                    "Accepted by Meta" means Meta queued the message — actual delivery depends on:
                    <ul style="margin:5px 0 0 16px;padding:0;">
                        <li>Template must be <strong>approved</strong> in Meta Business Manager (not just in this system)</li>
                        <li>Recipient must have an active WhatsApp account on that number</li>
                        <li>First-time contacts must have messaged your number in the last 24h <em>or</em> the template must be a utility/marketing category</li>
                    </ul>
                    To verify template status: <strong>WA Templates → Sync from Meta</strong>.
                    Messages sent are logged in the inbox — click the contact on the left to view.
                </div>

                {{-- Per-number results --}}
                <div style="max-height:200px;overflow-y:auto;border:1px solid #f1f5f9;border-radius:10px;">
                    @foreach($blastResults as $r)
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid #f8fafc;font-size:12px;">
                        <i class="fas fa-{{ $r['status'] === 'sent' ? 'check-circle' : ($r['status'] === 'skip' ? 'minus-circle' : 'times-circle') }}"
                           style="color:{{ $r['status'] === 'sent' ? '#15803d' : ($r['status'] === 'skip' ? '#f59e0b' : '#dc2626') }};font-size:14px;flex-shrink:0;"></i>
                        <span style="font-family:monospace;color:#374151;flex:1;">{{ $r['phone'] }}</span>
                        <span style="color:{{ $r['status'] === 'sent' ? '#15803d' : ($r['status'] === 'skip' ? '#92400e' : '#dc2626') }};font-weight:600;">
                            {{ $r['status'] === 'sent' ? 'Accepted ✓' : $r['msg'] }}
                        </span>
                    </div>
                    @endforeach
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button wire:click="openBlast"
                            style="height:38px;padding:0 18px;background:#ede9fe;border:1px solid #ddd6fe;border-radius:10px;font-size:13px;font-weight:600;color:#7c3aed;cursor:pointer;">
                        <i class="fas fa-redo" style="font-size:11px;margin-right:5px;"></i> New Blast
                    </button>
                    <button wire:click="closeBlast"
                            style="height:38px;padding:0 18px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">
                        <i class="fas fa-inbox" style="font-size:11px;margin-right:5px;"></i> Close &amp; View Inbox
                    </button>
                </div>

                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Lead Info Popup Modal ────────────────────────────────────────── --}}
    @if($showLeadInfo)
    <div style="position:absolute;inset:0;background:rgba(15,23,42,.45);z-index:50;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(2px);"
         wire:click.self="closeLeadInfo">
        <div style="background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:420px;max-width:90vw;overflow:hidden;">
            {{-- Header --}}
            <div style="padding:16px 20px;background:linear-gradient(135deg,#7c3aed,#5b21b6);display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-info" style="color:#fff;font-size:13px;"></i>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:800;color:#fff;">Lead Details</div>
                        <div style="font-size:11px;color:rgba(255,255,255,.7);">{{ $leadInfoPhone }}</div>
                    </div>
                </div>
                <button wire:click="closeLeadInfo"
                        style="width:28px;height:28px;background:rgba(255,255,255,.2);border:none;border-radius:8px;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-times" style="font-size:11px;"></i>
                </button>
            </div>

            {{-- Body --}}
            <div style="padding:18px 20px;">
                @if(!($leadInfo['found'] ?? false))
                <div style="text-align:center;padding:30px 0;color:#94a3b8;">
                    <i class="fas fa-user-clock" style="font-size:32px;opacity:.4;display:block;margin-bottom:10px;"></i>
                    <div style="font-size:14px;font-weight:600;color:#64748b;">No lead data yet</div>
                    <div style="font-size:12px;margin-top:4px;line-height:1.5;">This contact hasn't started the qualification flow, or hasn't answered any questions yet.</div>
                </div>
                @else
                {{-- Lead status + score badges --}}
                <div style="margin-bottom:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @php
                        $rawStatus = $leadInfo['raw_status'] ?? 'new';
                        $statusBg  = match($rawStatus) {
                            'hot'       => '#fee2e2', 'warm' => '#fef3c7', 'cold' => '#eff6ff',
                            'nurture'   => '#f8fafc', 'opted_out' => '#f1f5f9', 'completed' => '#dcfce7',
                            default     => '#f8fafc',
                        };
                        $statusColor = match($rawStatus) {
                            'hot'       => '#dc2626', 'warm' => '#d97706', 'cold' => '#3b82f6',
                            'nurture'   => '#64748b', 'opted_out' => '#94a3b8', 'completed' => '#15803d',
                            default     => '#64748b',
                        };
                        $scoreRaw   = $leadInfo['lead_score'] ?? null;
                        $scoreBg    = match($scoreRaw) {
                            'hot'  => '#fee2e2', 'warm' => '#fef3c7', 'cold' => '#eff6ff', default => null,
                        };
                        $scoreColor = match($scoreRaw) {
                            'hot'  => '#dc2626', 'warm' => '#d97706', 'cold' => '#3b82f6', default => null,
                        };
                        $scoreLabel = match($scoreRaw) {
                            'hot'  => '🔥 HOT LEAD', 'warm' => '🟡 WARM LEAD', 'cold' => '❄️ COLD LEAD', default => null,
                        };
                    @endphp
                    <span style="font-size:13px;font-weight:800;padding:4px 12px;border-radius:8px;background:{{ $statusBg }};color:{{ $statusColor }};">
                        {{ $leadInfo['status'] }}
                    </span>
                    @if($scoreLabel)
                    <span style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:8px;background:{{ $scoreBg }};color:{{ $scoreColor }};border:1px solid {{ $scoreColor }}33;">
                        {{ $scoreLabel }}
                    </span>
                    @endif
                    <span style="font-size:11px;color:#94a3b8;margin-left:auto;">Step: {{ $leadInfo['step'] }}</span>
                </div>

                {{-- Fields grid --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                    @php
                        $fields = [
                            ['fas fa-user',         '#7c3aed', 'Name',           $leadInfo['name']],
                            ['fas fa-hotel',         '#0891b2', 'Hotel / Resort', $leadInfo['hotel_name']],
                            ['fas fa-bed',           '#059669', 'Rooms',          $leadInfo['room_count']],
                            ['fas fa-laptop',        '#d97706', 'Current System', $leadInfo['software']],
                            ['fas fa-id-badge',      '#6366f1', 'Role',           $leadInfo['role']],
                            ['fas fa-map-marker-alt','#dc2626', 'City',           $leadInfo['city']],
                            ['fas fa-clock',         '#0891b2', 'Timeline',       $leadInfo['timeline']],
                            ['fas fa-calendar-check','#059669', 'Demo Slot',      $leadInfo['demo']],
                        ];
                    @endphp
                    @foreach($fields as [$icon, $color, $label, $value])
                    <div style="background:#f8fafc;border-radius:10px;padding:10px 12px;">
                        <div style="font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:3px;display:flex;align-items:center;gap:5px;">
                            <i class="{{ $icon }}" style="color:{{ $color }};font-size:9px;"></i>
                            {{ strtoupper($label) }}
                        </div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;word-break:break-word;">{{ $value }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Last seen --}}
                <div style="font-size:11px;color:#94a3b8;text-align:center;">
                    <i class="fas fa-clock" style="margin-right:4px;"></i>Last message {{ $leadInfo['last_seen'] }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>

@script
<script>
window.waScrollToBottom = function () {
    var el = document.getElementById('wa-messages-scroll');
    if (el) el.scrollTop = el.scrollHeight;
};

// ── Attachment state ──────────────────────────────────────────────────────
var _waAttach = { mediaId: null, type: null, fileName: null };

window.waClearAttachment = function () {
    _waAttach = { mediaId: null, type: null, fileName: null };
    var preview = document.getElementById('wa-attach-preview');
    if (preview) preview.style.display = 'none';
    var fi = document.getElementById('wa-file-input');
    if (fi) fi.value = '';
};

window.waHandleFileSelect = function (input) {
    var file = input.files[0];
    if (!file) return;

    var maxMB = 20;
    if (file.size > maxMB * 1024 * 1024) {
        alert('File too large. Maximum size is ' + maxMB + 'MB.');
        input.value = '';
        return;
    }

    var isImage = file.type.startsWith('image/');
    var thumb   = document.getElementById('wa-attach-thumb');
    var nameEl  = document.getElementById('wa-attach-name');
    var statusEl= document.getElementById('wa-attach-status');
    var preview = document.getElementById('wa-attach-preview');

    // Show preview panel
    preview.style.display = 'flex';
    nameEl.textContent    = file.name;
    statusEl.textContent  = 'Uploading…';
    statusEl.style.color  = '#64748b';

    // Thumb
    if (isImage) {
        var reader = new FileReader();
        reader.onload = function (e) {
            thumb.innerHTML = '<img src="' + e.target.result + '" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">';
        };
        reader.readAsDataURL(file);
    } else {
        thumb.innerHTML = '<div style="width:48px;height:48px;background:#fee2e2;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-file-pdf" style="color:#dc2626;font-size:22px;"></i></div>';
    }

    // Upload via AJAX (CSRF from meta tag)
    var formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/platform/wa/upload-media', {
        method: 'POST',
        body: formData,
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.media_id) {
            _waAttach.mediaId   = data.media_id;
            _waAttach.type      = data.type;
            _waAttach.fileName  = data.name;
            statusEl.textContent = '✅ Ready to send';
            statusEl.style.color = '#15803d';
        } else {
            statusEl.textContent = '❌ ' + (data.error || 'Upload failed');
            statusEl.style.color = '#dc2626';
            _waAttach = { mediaId: null, type: null, fileName: null };
        }
    })
    .catch(function (err) {
        statusEl.textContent = '❌ Upload error: ' + err.message;
        statusEl.style.color = '#dc2626';
    });
};

// Override send button: if attachment is ready, send attachment; else send text
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(window.waScrollToBottom, 300);
});

Livewire.on('wa-scroll-to-bottom', window.waScrollToBottom);
Livewire.on('wa-clear-attachment', window.waClearAttachment);

// Intercept send button click to route to attachment send when ready
document.addEventListener('click', function (e) {
    var btn = e.target.closest('#wa-send-text-btn');
    if (!btn) return;

    if (_waAttach.mediaId) {
        e.stopImmediatePropagation();
        e.preventDefault();
        // Delegate to Livewire sendAttachment
        @this.sendAttachment(_waAttach.mediaId, _waAttach.type, _waAttach.fileName, '');
    }
    // else normal Livewire sendReply fires naturally
}, true);
</script>
@endscript
