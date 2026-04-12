<div wire:poll.4000ms style="display:flex;height:calc(100vh - 140px);background:#fff;border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">

    {{-- ── Conversations List ─────────────────────────────────────────── --}}
    <div style="width:320px;flex-shrink:0;border-right:1px solid #f1f5f9;display:flex;flex-direction:column;overflow:hidden;">

        {{-- Header --}}
        <div style="padding:16px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
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
            @php $isSelected = $selectedConversationId === $convo->id; @endphp
            <div wire:click="selectConversation({{ $convo->id }})"
                 style="padding:14px 16px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .12s;{{ $isSelected ? 'background:#ede9fe;border-left:3px solid #7c3aed;' : 'border-left:3px solid transparent;' }}"
                 onmouseover="if(!{{ $isSelected ? 'true' : 'false' }})this.style.background='#f8fafc'"
                 onmouseout="if(!{{ $isSelected ? 'true' : 'false' }})this.style.background=''">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fff;flex-shrink:0;">
                        {{ mb_strtoupper(mb_substr($convo->hotel_name, 0, 1)) }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;">
                            <span style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $convo->hotel_name }}</span>
                            <span style="font-size:10px;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $convo->time_ago }}</span>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;gap:4px;">
                            <span style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $convo->last_message_preview ?? 'No messages yet' }}</span>
                            @if($convo->unread_count > 0)
                            <span style="background:#25d366;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;flex-shrink:0;">{{ $convo->unread_count }}</span>
                            @endif
                        </div>
                        <div style="margin-top:2px;font-size:10px;color:#94a3b8;">{{ $convo->phone }}</div>
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:40px 20px;text-align:center;color:#94a3b8;">
                <i class="fab fa-whatsapp" style="font-size:36px;opacity:.3;display:block;margin-bottom:10px;"></i>
                <div style="font-size:13px;font-weight:600;">No conversations yet</div>
                <div style="font-size:11px;margin-top:4px;">Incoming WhatsApp messages from hotel owners will appear here.</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Thread / Empty State ────────────────────────────────────────── --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;">

        @if($selectedConvo)

        {{-- Thread header --}}
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;gap:12px;">
            <button wire:click="backToList" style="width:32px;height:32px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-arrow-left" style="font-size:12px;"></i>
            </button>
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fff;flex-shrink:0;">
                {{ mb_strtoupper(mb_substr($selectedConvo->hotel_name, 0, 1)) }}
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $selectedConvo->hotel_name }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $selectedConvo->phone }}</div>
            </div>
            <div style="margin-left:auto;">
                @if($within24h)
                <span style="display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">
                    <span style="width:6px;height:6px;background:#15803d;border-radius:50%;display:inline-block;"></span>
                    24h window active
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:5px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">
                    <i class="fas fa-clock" style="font-size:10px;"></i>
                    Window closed
                </span>
                @endif
            </div>
        </div>

        {{-- Messages --}}
        <div id="wa-messages-scroll" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px;background:#efeae2;"
             x-data x-init="$el.scrollTop = $el.scrollHeight">

            @if($messages->isEmpty())
            <div style="text-align:center;color:#94a3b8;padding:40px 0;">
                <i class="fab fa-whatsapp" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px;"></i>
                <div style="font-size:13px;">No messages in this conversation yet.</div>
            </div>
            @endif

            @foreach($messages as $msg)
            @php $isOutgoing = $msg->direction === 'outgoing'; @endphp
            <div style="display:flex;justify-content:{{ $isOutgoing ? 'flex-end' : 'flex-start' }};">
                <div style="max-width:65%;background:{{ $isOutgoing ? 'linear-gradient(135deg,#7c3aed,#5b21b6)' : '#fff' }};color:{{ $isOutgoing ? '#fff' : '#1e293b' }};border-radius:{{ $isOutgoing ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};padding:10px 14px;box-shadow:0 1px 4px rgba(0,0,0,.1);">
                    <div style="font-size:13px;line-height:1.5;word-break:break-word;">{{ $msg->text }}</div>
                    <div style="font-size:10px;margin-top:4px;opacity:.6;text-align:right;">{{ $msg->time }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Reply area --}}
        <div style="padding:14px 16px;border-top:1px solid #f1f5f9;background:#fff;">

            @if($sendResult)
            @php
                $isOk  = str_starts_with($sendResult, 'ok:');
                $msgTxt = substr($sendResult, strpos($sendResult, ':') + 1);
            @endphp
            <div style="padding:8px 12px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:10px;background:{{ $isOk ? '#dcfce7' : '#fee2e2' }};color:{{ $isOk ? '#15803d' : '#b91c1c' }};border:1px solid {{ $isOk ? '#bbf7d0' : '#fecaca' }};">
                <i class="fas fa-{{ $isOk ? 'check-circle' : 'exclamation-circle' }}" style="margin-right:5px;"></i>{{ $msgTxt }}
            </div>
            @endif

            @if($within24h)
            <div style="display:flex;gap:10px;align-items:flex-end;">
                <textarea wire:model="replyText"
                          placeholder="Type a message…"
                          rows="2"
                          style="flex:1;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px;font-size:13px;color:#1e293b;resize:none;outline:none;transition:border-color .15s;"
                          onfocus="this.style.borderColor='#8b5cf6'"
                          onblur="this.style.borderColor='#e2e8f0'"
                          wire:keydown.enter.prevent="sendReply"></textarea>
                <button wire:click="sendReply"
                        wire:loading.attr="disabled"
                        style="height:44px;padding:0 20px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;flex-shrink:0;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <span wire:loading.remove wire:target="sendReply"><i class="fas fa-paper-plane"></i> Send</span>
                    <span wire:loading wire:target="sendReply"><i class="fas fa-spinner fa-spin"></i> Sending…</span>
                </button>
            </div>
            @else
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px;color:#92400e;">
                <i class="fas fa-clock" style="font-size:18px;flex-shrink:0;"></i>
                <div>
                    <div style="font-size:13px;font-weight:700;">24-hour messaging window closed</div>
                    <div style="font-size:12px;margin-top:2px;opacity:.8;">Free-text replies are only allowed within 24 hours of the last inbound message from this contact. Please use a WhatsApp template message instead.</div>
                </div>
            </div>
            @endif
        </div>

        @else

        {{-- Empty state when no conversation selected --}}
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;padding:40px;">
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                <i class="fab fa-whatsapp" style="font-size:38px;color:#25d366;"></i>
            </div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:6px;">WhatsApp Inbox</div>
            <div style="font-size:13px;text-align:center;max-width:280px;line-height:1.6;">
                Select a conversation from the list to view messages and reply to hotel owners.
            </div>
            <div style="margin-top:20px;font-size:11px;text-align:center;background:#f1f5f9;border-radius:10px;padding:10px 16px;color:#64748b;">
                <i class="fas fa-sync-alt" style="margin-right:5px;"></i>
                Auto-refreshes every 4 seconds
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

    document.addEventListener('livewire:navigated', window.waInboxScrollToBottom);
</script>
@endscript
