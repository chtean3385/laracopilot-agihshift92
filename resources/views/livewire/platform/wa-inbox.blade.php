<div wire:poll.4000ms style="display:flex;height:calc(100vh - 140px);background:#fff;border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">

    {{-- ── Conversations List ─────────────────────────────────────────── --}}
    <div style="width:300px;flex-shrink:0;border-right:1px solid #f1f5f9;display:flex;flex-direction:column;overflow:hidden;">

        {{-- Header --}}
        <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:34px;height:34px;background:linear-gradient(135deg,#25d366,#128c43);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fab fa-whatsapp" style="color:#fff;font-size:16px;"></i>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:800;color:#0f172a;">WA Inbox</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $conversations->count() }} conversation{{ $conversations->count() !== 1 ? 's' : '' }}</div>
                </div>
                @if($totalUnread > 0)
                <span style="margin-left:auto;background:#ef4444;color:#fff;border-radius:999px;font-size:11px;font-weight:700;padding:2px 8px;min-width:22px;text-align:center;">{{ $totalUnread }}</span>
                @endif
            </div>
        </div>

        {{-- List --}}
        <div style="flex:1;overflow-y:auto;">
            @forelse($conversations as $convo)
            @php $isSelected = $selectedContact && $selectedContact->phone === $convo->phone; @endphp

            <div wire:click="selectContact('{{ $convo->phone }}')"
                 style="padding:11px 13px;cursor:pointer;border-bottom:1px solid #f8fafc;transition:background .12s;{{ $isSelected ? 'background:#ede9fe;border-left:3px solid #7c3aed;' : 'border-left:3px solid transparent;' }}"
                 onmouseover="if(!{{ $isSelected ? 'true' : 'false' }})this.style.background='#f8fafc'"
                 onmouseout="if(!{{ $isSelected ? 'true' : 'false' }})this.style.background=''">
                <div style="display:flex;align-items:flex-start;gap:9px;">
                    {{-- Avatar --}}
                    <div style="position:relative;flex-shrink:0;">
                        <div style="width:40px;height:40px;background:{{ $convo->type_color }};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;opacity:.9;">
                            {{ mb_strtoupper(mb_substr($convo->name, 0, 1)) }}
                        </div>
                        {{-- Consent dot --}}
                        <span style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;border:2px solid #fff;background:{{ $convo->consented ? '#25d366' : '#e2e8f0' }};"></span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:2px;">
                            <span style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;">{{ $convo->name }}</span>
                            <span style="font-size:10px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $convo->time_ago }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">
                            {{-- Type badge --}}
                            <span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:{{ $convo->type_bg }};color:{{ $convo->type_color }};flex-shrink:0;letter-spacing:.3px;">{{ strtoupper($convo->type_label) }}</span>
                            <span style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;">{{ $convo->preview }}</span>
                            @if($convo->unread > 0)
                            <span style="background:#25d366;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;flex-shrink:0;min-width:18px;text-align:center;">{{ $convo->unread }}</span>
                            @endif
                        </div>
                        @if($convo->hotel_name)
                        <div style="font-size:10px;color:#94a3b8;">{{ $convo->hotel_name }}</div>
                        @else
                        <div style="font-size:10px;color:#94a3b8;">{{ $convo->phone }}</div>
                        @endif
                    </div>
                </div>
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

    {{-- ── Thread / Empty State ────────────────────────────────────────── --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;">

        @if($selectedContact)

        {{-- Thread header --}}
        <div style="padding:11px 16px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;gap:10px;">
            <button wire:click="backToList" style="width:30px;height:30px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i>
            </button>
            <div style="width:36px;height:36px;background:{{ $selectedContact->type_color }};opacity:.9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;flex-shrink:0;">
                {{ mb_strtoupper(mb_substr($selectedContact->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:7px;">
                    {{ $selectedContact->name }}
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;background:{{ $selectedContact->type_bg }};color:{{ $selectedContact->type_color }};letter-spacing:.3px;">{{ strtoupper($selectedContact->type_label) }}</span>
                </div>
                <div style="font-size:11px;color:#94a3b8;">
                    {{ $selectedContact->phone }}
                    @if($selectedContact->hotel_name) &nbsp;·&nbsp; {{ $selectedContact->hotel_name }} @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                {{-- Consent status --}}
                @if($selectedContact->consented)
                <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <span style="width:6px;height:6px;background:#15803d;border-radius:50%;display:inline-block;"></span>
                    Consented
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <i class="fas fa-clock" style="font-size:9px;"></i>
                    No consent
                </span>
                @endif
                {{-- 24h window --}}
                @if($within24h)
                <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <span style="width:6px;height:6px;background:#15803d;border-radius:50%;display:inline-block;"></span>
                    24h open
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;">
                    <i class="fas fa-clock" style="font-size:9px;"></i>
                    No reply yet
                </span>
                @endif
            </div>
        </div>

        {{-- Messages --}}
        <div id="wa-messages-scroll"
             style="flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:10px;background:#efeae2;">

            @if($messages->isEmpty())
            <div style="text-align:center;color:#94a3b8;padding:40px 0;margin:auto;">
                <i class="fab fa-whatsapp" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px;"></i>
                <div style="font-size:13px;font-weight:600;">No messages yet</div>
                <div style="font-size:11px;margin-top:4px;max-width:240px;margin-left:auto;margin-right:auto;line-height:1.5;">
                    Send the first message below.
                    @if(!$selectedContact->consented)
                    The contact will be marked as consented once they reply.
                    @endif
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
                <div style="max-width:68%;background:{{ $isOutgoing ? 'linear-gradient(135deg,#7c3aed,#5b21b6)' : '#fff' }};color:{{ $isOutgoing ? '#fff' : '#1e293b' }};border-radius:{{ $isOutgoing ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};padding:10px 14px;box-shadow:0 1px 4px rgba(0,0,0,.1);">
                    <div style="font-size:13px;line-height:1.5;word-break:break-word;">{{ $msg->text }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Reply area --}}
        <div style="padding:12px 16px;border-top:1px solid #f1f5f9;background:#fff;">

            @if($sendResult)
            @php
                $isOk   = str_starts_with($sendResult, 'ok:');
                $resTxt = substr($sendResult, strpos($sendResult, ':') + 1);
            @endphp
            <div style="padding:7px 12px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:8px;background:{{ $isOk ? '#dcfce7' : '#fee2e2' }};color:{{ $isOk ? '#15803d' : '#b91c1c' }};border:1px solid {{ $isOk ? '#bbf7d0' : '#fecaca' }};">
                <i class="fas fa-{{ $isOk ? 'check-circle' : 'exclamation-circle' }}" style="margin-right:5px;"></i>{{ $resTxt }}
            </div>
            @endif

            @if(!$within24h && $messages->isNotEmpty())
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:7px 12px;font-size:11px;color:#92400e;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-exclamation-circle"></i>
                Contact hasn't replied recently — Meta may reject free-text outside the 24h window. Message will still be attempted.
            </div>
            @endif

            @if(!$selectedContact->consented && $messages->isEmpty())
            <div style="background:#e0f2fe;border:1px solid #bae6fd;border-radius:8px;padding:7px 12px;font-size:11px;color:#0369a1;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-info-circle"></i>
                Tip: Once they reply to your first message, consent is automatically recorded.
            </div>
            @endif

            <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea wire:model="replyText"
                          placeholder="Type a message…"
                          rows="2"
                          style="flex:1;border:1.5px solid #e2e8f0;border-radius:12px;padding:9px 13px;font-size:13px;color:#1e293b;resize:none;outline:none;transition:border-color .15s;font-family:inherit;"
                          onfocus="this.style.borderColor='#8b5cf6'"
                          onblur="this.style.borderColor='#e2e8f0'"
                          wire:keydown.ctrl.enter="sendReply"></textarea>
                <button wire:click="sendReply"
                        wire:loading.attr="disabled"
                        style="height:44px;padding:0 18px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;flex-shrink:0;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <span wire:loading.remove wire:target="sendReply"><i class="fas fa-paper-plane"></i> Send</span>
                    <span wire:loading wire:target="sendReply"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
            <div style="font-size:10px;color:#94a3b8;margin-top:4px;padding-left:2px;">Ctrl+Enter to send</div>
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
            <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
                <span style="font-size:11px;background:#ede9fe;color:#7c3aed;padding:4px 10px;border-radius:6px;font-weight:700;">OWNER — Hotel phone match</span>
                <span style="font-size:11px;background:#e0f2fe;color:#0891b2;padding:4px 10px;border-radius:6px;font-weight:700;">GUEST — Customer match</span>
                <span style="font-size:11px;background:#f1f5f9;color:#64748b;padding:4px 10px;border-radius:6px;font-weight:700;">UNKNOWN — New contact</span>
            </div>
            <div style="margin-top:14px;font-size:11px;text-align:center;background:#f1f5f9;border-radius:10px;padding:9px 14px;color:#64748b;">
                <i class="fas fa-sync-alt" style="margin-right:5px;"></i>
                Auto-refreshes every 4 seconds &nbsp;·&nbsp; Green dot = consented
            </div>
        </div>

        @endif
    </div>

</div>

@script
<script>
    window.waInboxScrollToBottom = function () {
        var el = document.getElementById('wa-messages-scroll');
        if (el) el.scrollTop = el.scrollHeight;
    };

    Livewire.on('wa-scroll-to-bottom', window.waInboxScrollToBottom);

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(window.waInboxScrollToBottom, 300);
    });
</script>
@endscript
