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
                    <div style="font-size:11px;color:#94a3b8;">{{ $conversations->count() }} hotel{{ $conversations->count() !== 1 ? 's' : '' }}</div>
                </div>
                @if($totalUnread > 0)
                <span style="margin-left:auto;background:#ef4444;color:#fff;border-radius:999px;font-size:11px;font-weight:700;padding:2px 8px;min-width:22px;text-align:center;">{{ $totalUnread }}</span>
                @endif
            </div>
        </div>

        {{-- List --}}
        <div style="flex:1;overflow-y:auto;">
            @forelse($conversations as $convo)
            @php $isSelected = $selectedHotel && $selectedHotel->hotel_id === $convo->hotel_id; @endphp
            <div wire:click="selectHotel({{ $convo->hotel_id }})"
                 style="padding:12px 14px;cursor:pointer;border-bottom:1px solid #f8fafc;transition:background .12s;{{ $isSelected ? 'background:#ede9fe;border-left:3px solid #7c3aed;' : 'border-left:3px solid transparent;' }}"
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
                        <div style="display:flex;align-items:center;gap:4px;margin-top:2px;">
                            @if($convo->preview_dir === 'outgoing')
                            <i class="fas fa-reply" style="font-size:9px;color:#7c3aed;flex-shrink:0;"></i>
                            @elseif($convo->preview_dir === 'incoming')
                            <i class="fas fa-arrow-left" style="font-size:9px;color:#25d366;flex-shrink:0;"></i>
                            @endif
                            <span style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;">{{ $convo->preview }}</span>
                            @if($convo->unread > 0)
                            <span style="background:#25d366;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;flex-shrink:0;">{{ $convo->unread }}</span>
                            @endif
                        </div>
                        <div style="margin-top:3px;display:flex;align-items:center;gap:5px;">
                            <span style="font-size:10px;color:#94a3b8;">{{ $convo->phone ?: 'No phone set' }}</span>
                            <span style="font-size:9px;background:#f1f5f9;color:#64748b;padding:1px 5px;border-radius:4px;font-weight:700;">{{ $convo->plan }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:40px 20px;text-align:center;color:#94a3b8;">
                <i class="fab fa-whatsapp" style="font-size:36px;opacity:.3;display:block;margin-bottom:10px;"></i>
                <div style="font-size:13px;font-weight:600;">No active hotels</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Thread / Empty State ────────────────────────────────────────── --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;">

        @if($selectedHotel)

        {{-- Thread header --}}
        <div style="padding:12px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;gap:10px;">
            <button wire:click="backToList" style="width:30px;height:30px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i>
            </button>
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;flex-shrink:0;">
                {{ mb_strtoupper(mb_substr($selectedHotel->hotel_name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $selectedHotel->hotel_name }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $selectedHotel->phone ?: 'No phone configured' }} &nbsp;·&nbsp; {{ $selectedHotel->plan }}</div>
            </div>
            <div>
                @if($within24h)
                <span style="display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">
                    <span style="width:6px;height:6px;background:#15803d;border-radius:50%;display:inline-block;"></span>
                    24h window open
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">
                    <i class="fas fa-exclamation-circle" style="font-size:10px;"></i>
                    No recent reply
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
                <div style="font-size:11px;margin-top:4px;">Send the first message to this hotel owner below.</div>
            </div>
            @endif

            @foreach($messages as $msg)
            @php $isOutgoing = $msg->direction === 'outgoing'; @endphp
            <div style="display:flex;flex-direction:column;align-items:{{ $isOutgoing ? 'flex-end' : 'flex-start' }};">
                <div style="font-size:10px;color:#94a3b8;margin-bottom:2px;padding:0 4px;">
                    {{ $msg->tag }} &nbsp;·&nbsp; {{ $msg->time }}
                </div>
                <div style="max-width:65%;background:{{ $isOutgoing ? 'linear-gradient(135deg,#7c3aed,#5b21b6)' : '#fff' }};color:{{ $isOutgoing ? '#fff' : '#1e293b' }};border-radius:{{ $isOutgoing ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};padding:10px 14px;box-shadow:0 1px 4px rgba(0,0,0,.1);">
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

            @if(!$within24h && !$messages->isEmpty())
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:7px 12px;font-size:11px;color:#92400e;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-clock"></i>
                Owner hasn't messaged recently — Meta may reject free-text. Use a template if blocked.
            </div>
            @endif

            @if($selectedHotel->phone)
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea wire:model="replyText"
                          placeholder="Type a message to {{ $selectedHotel->hotel_name }}…"
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
            <div style="font-size:10px;color:#94a3b8;margin-top:5px;padding-left:2px;">Ctrl+Enter to send</div>
            @else
            <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;font-size:12px;color:#b91c1c;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-exclamation-triangle"></i>
                This hotel has no phone number. <a href="{{ route('platform.hotels.edit', $selectedHotel->hotel_id) }}" style="color:#7c3aed;font-weight:700;text-decoration:none;">Add phone →</a>
            </div>
            @endif
        </div>

        @else

        {{-- Empty state --}}
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;padding:40px;">
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                <i class="fab fa-whatsapp" style="font-size:38px;color:#25d366;"></i>
            </div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:6px;">WhatsApp Inbox</div>
            <div style="font-size:13px;text-align:center;max-width:300px;line-height:1.6;color:#64748b;">
                Select a hotel from the left to view the conversation thread and send messages to the owner.
            </div>
            <div style="margin-top:16px;font-size:11px;text-align:center;background:#f1f5f9;border-radius:10px;padding:9px 14px;color:#64748b;">
                <i class="fas fa-sync-alt" style="margin-right:5px;"></i>
                Auto-refreshes every 4 seconds &nbsp;·&nbsp; Ctrl+Enter to send
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

    // Scroll to bottom on initial load
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(window.waInboxScrollToBottom, 300);
    });
</script>
@endscript
