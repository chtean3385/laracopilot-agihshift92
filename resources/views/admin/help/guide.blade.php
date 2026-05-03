@extends('layouts.admin')

@section('title', 'User Guide / उपयोगकर्ता गाइड')

@section('content')
<div style="max-width:1100px;margin:0 auto;">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#0891b2,#7c3aed);border-radius:18px;padding:28px 28px 24px;color:#fff;margin-bottom:22px;box-shadow:0 12px 30px rgba(124,58,237,.25);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap;">
            <div style="min-width:0;flex:1;">
                <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.18);padding:5px 12px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;margin-bottom:10px;">
                    <i class="fas fa-book-open"></i>
                    <span>Hotel CRM · User Manual</span>
                </div>
                <h1 style="font-size:26px;font-weight:800;margin:0 0 6px;line-height:1.2;">
                    <span data-lang="en">Complete Guide for Hotel Owners</span>
                    <span data-lang="hi" style="display:none;">होटल मालिकों के लिए पूरी गाइड</span>
                </h1>
                <p style="font-size:14px;opacity:.9;margin:0;max-width:680px;">
                    <span data-lang="en">Learn how to use every part of your CRM — guests, rooms, bookings, billing, restaurant, WhatsApp and more. Step by step, in simple language.</span>
                    <span data-lang="hi" style="display:none;">अपने CRM का हर हिस्सा कैसे इस्तेमाल करें सीखें — गेस्ट, रूम, बुकिंग, बिलिंग, रेस्टोरेंट, व्हाट्सएप और बहुत कुछ। आसान भाषा में, स्टेप बाई स्टेप।</span>
                </p>
            </div>

            {{-- Language toggle --}}
            <div style="display:flex;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:3px;flex-shrink:0;">
                <button type="button" onclick="setGuideLang('en')" id="lang-btn-en" style="padding:8px 14px;border:none;border-radius:8px;background:#fff;color:#0891b2;font-weight:700;font-size:12px;cursor:pointer;letter-spacing:.04em;">English</button>
                <button type="button" onclick="setGuideLang('hi')" id="lang-btn-hi" style="padding:8px 14px;border:none;border-radius:8px;background:transparent;color:#fff;font-weight:700;font-size:12px;cursor:pointer;letter-spacing:.04em;">हिन्दी</button>
            </div>
        </div>
    </div>

    {{-- ── Layout: TOC + content ───────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:1fr;gap:18px;">

        {{-- Quick navigation cards --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
            @php
                $tocCards = [
                    ['icon'=>'fa-rocket','color'=>'#0891b2','id'=>'getting-started','en'=>'Getting Started','hi'=>'शुरुआत कैसे करें'],
                    ['icon'=>'fa-users','color'=>'#10b981','id'=>'guests','en'=>'Manage Guests','hi'=>'गेस्ट मैनेज करें'],
                    ['icon'=>'fa-door-open','color'=>'#f59e0b','id'=>'rooms','en'=>'Rooms Setup','hi'=>'रूम सेटअप'],
                    ['icon'=>'fa-calendar-check','color'=>'#7c3aed','id'=>'bookings','en'=>'Bookings & Check-In','hi'=>'बुकिंग और चेक-इन'],
                    ['icon'=>'fa-wallet','color'=>'#ef4444','id'=>'billing','en'=>'Payments & Invoices','hi'=>'पेमेंट और इनवॉइस'],
                    ['icon'=>'fa-concierge-bell','color'=>'#ec4899','id'=>'restaurant','en'=>'Restaurant','hi'=>'रेस्टोरेंट'],
                    ['icon'=>'fa-boxes','color'=>'#3b82f6','id'=>'inventory','en'=>'Inventory','hi'=>'इन्वेंटरी'],
                    ['icon'=>'fa-chart-bar','color'=>'#06b6d4','id'=>'reports','en'=>'Reports','hi'=>'रिपोर्ट्स'],
                    ['icon'=>'fa-bolt','color'=>'#8b5cf6','id'=>'integrations','en'=>'WhatsApp & OTAs','hi'=>'व्हाट्सएप और OTA'],
                    ['icon'=>'fa-shield-halved','color'=>'#475569','id'=>'admin','en'=>'Users & Roles','hi'=>'यूज़र और रोल्स'],
                    ['icon'=>'fa-cog','color'=>'#64748b','id'=>'settings','en'=>'Settings','hi'=>'सेटिंग्स'],
                    ['icon'=>'fa-question-circle','color'=>'#15803d','id'=>'faq','en'=>'FAQ & Tips','hi'=>'अक्सर पूछे जाने वाले सवाल'],
                ];
            @endphp
            @foreach($tocCards as $c)
            <a href="#{{ $c['id'] }}" style="display:flex;align-items:center;gap:12px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 14px;text-decoration:none;color:#1e293b;transition:all .15s;" onmouseover="this.style.borderColor='{{ $c['color'] }}';this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 14px rgba(15,23,42,.08)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.transform='';this.style.boxShadow=''">
                <div style="width:38px;height:38px;border-radius:10px;background:{{ $c['color'] }}18;color:{{ $c['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas {{ $c['icon'] }}"></i>
                </div>
                <div style="font-weight:700;font-size:13px;line-height:1.25;">
                    <span data-lang="en">{{ $c['en'] }}</span>
                    <span data-lang="hi" style="display:none;">{{ $c['hi'] }}</span>
                </div>
            </a>
            @endforeach
        </div>

        {{-- ── Content sections ─────────────────────────────────────── --}}

        @php
            $sections = [
                [
                    'id'=>'getting-started','icon'=>'fa-rocket','color'=>'#0891b2',
                    'title_en'=>'1. Getting Started','title_hi'=>'1. शुरुआत कैसे करें',
                    'intro_en'=>'Welcome! Before you take your first booking, set up these basics in this exact order.',
                    'intro_hi'=>'स्वागत है! पहली बुकिंग लेने से पहले, ये बेसिक चीज़ें इसी क्रम में सेट कर लें।',
                    'steps'=>[
                        ['en'=>'Open Settings → fill your hotel name, address, GST number, logo and currency. This info shows on every invoice.','hi'=>'सेटिंग्स खोलें → होटल का नाम, पता, GST नंबर, लोगो और मुद्रा (currency) भरें। यह जानकारी हर इनवॉइस पर दिखती है।'],
                        ['en'=>'Go to Front Desk → Rooms and add every room with type and price.','hi'=>'फ्रंट डेस्क → रूम्स में जाकर हर कमरा उसके टाइप और रेट के साथ जोड़ें।'],
                        ['en'=>'Go to Administration → Users and create login accounts for your manager and receptionists.','hi'=>'एडमिनिस्ट्रेशन → यूज़र्स में जाकर अपने मैनेजर और रिसेप्शनिस्ट के लिए लॉगिन अकाउंट बनाएं।'],
                        ['en'=>'(Optional) Turn ON modules you need (Restaurant, Inventory, WhatsApp etc.) from Administration → Modules.','hi'=>'(वैकल्पिक) ज़रूरी मॉड्यूल (रेस्टोरेंट, इन्वेंटरी, व्हाट्सएप आदि) एडमिनिस्ट्रेशन → मॉड्यूल्स से चालू करें।'],
                        ['en'=>'You are ready! Start taking bookings from Front Desk → Bookings → New Booking.','hi'=>'अब आप तैयार हैं! फ्रंट डेस्क → बुकिंग्स → नई बुकिंग से बुकिंग लेना शुरू करें।'],
                    ],
                    'tip_en'=>'Tip: Anything marked with a small lock icon needs permission. Ask your Admin to enable it for you.',
                    'tip_hi'=>'टिप: जिस चीज़ पर ताले का छोटा निशान है उसके लिए अनुमति चाहिए। अपने एडमिन से चालू करने को कहें।',
                ],
                [
                    'id'=>'guests','icon'=>'fa-users','color'=>'#10b981',
                    'title_en'=>'2. Manage Guests','title_hi'=>'2. गेस्ट मैनेज करें',
                    'intro_en'=>'Every person who stays at your hotel is saved as a Guest. You can search past guests, see their stay history and store their ID proof.',
                    'intro_hi'=>'हर व्यक्ति जो आपके होटल में रुकता है वो "गेस्ट" के रूप में सेव होता है। आप पुराने गेस्ट खोज सकते हैं, उनकी ठहरने की हिस्ट्री देख सकते हैं और ID प्रूफ रख सकते हैं।',
                    'steps'=>[
                        ['en'=>'Click Guests in the sidebar to see the full list.','hi'=>'पूरी लिस्ट देखने के लिए साइडबार में "Guests" पर क्लिक करें।'],
                        ['en'=>'Use the search box on top to find by name, phone or ID number.','hi'=>'ऊपर के सर्च बॉक्स से नाम, फ़ोन या ID नंबर से खोजें।'],
                        ['en'=>'Click + Add Guest to register a new guest manually. Mostly this happens automatically when you create a booking.','hi'=>'+ Add Guest पर क्लिक करके नया गेस्ट जोड़ें। ज़्यादातर यह बुकिंग बनाते समय अपने आप हो जाता है।'],
                        ['en'=>'Open any guest to upload ID document, see their bookings, payments and WhatsApp messages.','hi'=>'किसी भी गेस्ट को खोल कर ID डॉक्युमेंट अपलोड करें, उनकी बुकिंग्स, पेमेंट और व्हाट्सएप मैसेज देखें।'],
                    ],
                    'tip_en'=>'Tip: Always ask for guest WhatsApp consent — only consented guests will receive booking confirmations and offers.',
                    'tip_hi'=>'टिप: गेस्ट से व्हाट्सएप के लिए अनुमति ज़रूर लें — सिर्फ़ अनुमति देने वाले गेस्ट को ही बुकिंग कन्फ़र्मेशन और ऑफ़र भेजे जाते हैं।',
                ],
                [
                    'id'=>'rooms','icon'=>'fa-door-open','color'=>'#f59e0b',
                    'title_en'=>'3. Rooms Setup','title_hi'=>'3. रूम सेटअप',
                    'intro_en'=>'Set up every room your hotel has, with its category (Deluxe, Suite etc.) and price per night.',
                    'intro_hi'=>'अपने होटल का हर कमरा, उसके टाइप (Deluxe, Suite आदि) और प्रति रात की कीमत के साथ सेट करें।',
                    'steps'=>[
                        ['en'=>'Open Front Desk → Rooms.','hi'=>'फ्रंट डेस्क → रूम्स खोलें।'],
                        ['en'=>'Click + Add Room. Fill room number, room type, max guests, AC/Non-AC and base price.','hi'=>'+ Add Room पर क्लिक करें। रूम नंबर, रूम टाइप, अधिकतम मेहमान, AC/Non-AC और बेस प्राइस भरें।'],
                        ['en'=>'Use the colored badge to mark a room as Available, Occupied, Cleaning or Maintenance.','hi'=>'रूम को Available, Occupied, Cleaning या Maintenance में से कोई एक स्थिति रंगीन बैज से सेट करें।'],
                        ['en'=>'For hourly hotels, enable Time Slots in Administration → Modules and add slot prices.','hi'=>'घंटे के हिसाब से चलने वाले होटल के लिए एडमिनिस्ट्रेशन → मॉड्यूल्स में Time Slots चालू करें और स्लॉट रेट जोड़ें।'],
                    ],
                    'tip_en'=>'Tip: Mark rooms as Maintenance instead of deleting them — old bookings stay linked to the room number.',
                    'tip_hi'=>'टिप: कमरे को डिलीट करने की जगह "Maintenance" में डालें — पुरानी बुकिंग्स रूम नंबर से जुड़ी रहेंगी।',
                ],
                [
                    'id'=>'bookings','icon'=>'fa-calendar-check','color'=>'#7c3aed',
                    'title_en'=>'4. Bookings, Check-In and Check-Out','title_hi'=>'4. बुकिंग, चेक-इन और चेक-आउट',
                    'intro_en'=>'This is your most-used screen. Create new bookings, check guests in, and check them out with one click.',
                    'intro_hi'=>'यह सबसे ज़्यादा इस्तेमाल होने वाली स्क्रीन है। नई बुकिंग बनाएं, गेस्ट को चेक-इन करें और एक क्लिक में चेक-आउट करें।',
                    'steps'=>[
                        ['en'=>'Front Desk → Bookings → + New Booking. Pick guest (or add new), pick dates, pick room. System shows only available rooms.','hi'=>'फ्रंट डेस्क → बुकिंग्स → + New Booking। गेस्ट चुनें (या नया जोड़ें), तारीख चुनें, रूम चुनें। सिस्टम सिर्फ़ खाली कमरे दिखाएगा।'],
                        ['en'=>'When the guest arrives, open Check-In, find the booking and click "Check In". Status turns green.','hi'=>'जब गेस्ट आ जाए, "Check-In" खोलें, बुकिंग ढूँढें और "Check In" पर क्लिक करें। स्टेटस हरा हो जाएगा।'],
                        ['en'=>'On departure, open Check-Out, click the booking, settle remaining payment, and click "Check Out". Invoice is generated automatically.','hi'=>'जाने के दिन Check-Out खोलें, बुकिंग पर क्लिक करें, बची हुई पेमेंट लें और "Check Out" दबाएं। इनवॉइस अपने आप बन जाता है।'],
                        ['en'=>'To cancel a booking, open it and click Cancel. The room becomes free again.','hi'=>'बुकिंग कैंसल करने के लिए उसे खोलें और Cancel पर क्लिक करें। कमरा फिर से खाली हो जाएगा।'],
                    ],
                    'tip_en'=>'Tip: System will not allow two overlapping bookings on the same room — you will get a clear error message.',
                    'tip_hi'=>'टिप: सिस्टम एक ही कमरे पर ओवरलैप होने वाली दो बुकिंग नहीं होने देगा — आपको साफ़ एरर मैसेज मिलेगा।',
                ],
                [
                    'id'=>'billing','icon'=>'fa-wallet','color'=>'#ef4444',
                    'title_en'=>'5. Payments and Invoices','title_hi'=>'5. पेमेंट और इनवॉइस',
                    'intro_en'=>'Every payment you receive (cash, card, UPI, online) is recorded against a booking. Invoices are auto-numbered.',
                    'intro_hi'=>'हर मिलने वाली पेमेंट (कैश, कार्ड, UPI, ऑनलाइन) बुकिंग के साथ दर्ज होती है। इनवॉइस अपने आप नंबर पाते हैं।',
                    'steps'=>[
                        ['en'=>'Open Billing → Payments to see all received payments. Filter by date or guest.','hi'=>'Billing → Payments खोलें — सारी पेमेंट दिखेंगी। तारीख या गेस्ट से फ़िल्टर करें।'],
                        ['en'=>'To record a payment, open the booking and click + Add Payment. Choose method (Cash, UPI, Card etc.).','hi'=>'पेमेंट दर्ज करने के लिए बुकिंग खोलें और + Add Payment पर क्लिक करें। मेथड (Cash, UPI, Card आदि) चुनें।'],
                        ['en'=>'Open Billing → Invoices to download or WhatsApp the invoice PDF to your guest.','hi'=>'Billing → Invoices खोल कर इनवॉइस PDF डाउनलोड करें या व्हाट्सएप करें।'],
                    ],
                    'tip_en'=>'Tip: Enable Payment Links module to send a secure online-pay link to guests who haven\'t arrived yet.',
                    'tip_hi'=>'टिप: Payment Links मॉड्यूल चालू करें — जो गेस्ट अभी नहीं आए, उन्हें ऑनलाइन पेमेंट का सुरक्षित लिंक भेज सकते हैं।',
                ],
                [
                    'id'=>'restaurant','icon'=>'fa-concierge-bell','color'=>'#ec4899',
                    'title_en'=>'6. Restaurant Module','title_hi'=>'6. रेस्टोरेंट मॉड्यूल',
                    'intro_en'=>'If your hotel has a restaurant, enable this module to manage tables, menu, KOT printing and bills.',
                    'intro_hi'=>'अगर आपके होटल में रेस्टोरेंट है, तो टेबल, मेन्यू, KOT प्रिंट और बिल मैनेज करने के लिए यह मॉड्यूल चालू करें।',
                    'steps'=>[
                        ['en'=>'Enable: Administration → Modules → switch ON "Restaurant Management".','hi'=>'चालू करें: एडमिनिस्ट्रेशन → मॉड्यूल्स → "Restaurant Management" को ON करें।'],
                        ['en'=>'Restaurant → Menu & Categories: add categories (Starter, Main, Dessert) and dishes with price.','hi'=>'Restaurant → Menu & Categories में कैटेगरी (Starter, Main, Dessert) और रेट के साथ डिश जोड़ें।'],
                        ['en'=>'Restaurant → Table Map: add your tables with seating capacity.','hi'=>'Restaurant → Table Map में बैठने की क्षमता के साथ अपनी टेबल जोड़ें।'],
                        ['en'=>'For an order, click a table → Add Items → Send to Kitchen (KOT prints).','hi'=>'ऑर्डर के लिए टेबल पर क्लिक करें → आइटम जोड़ें → Send to Kitchen (KOT प्रिंट होगा)।'],
                        ['en'=>'When done, click Generate Bill — pay direct or add to a guest\'s room bill.','hi'=>'पूरा होने पर Generate Bill दबाएं — सीधे पेमेंट लें या किसी गेस्ट के रूम बिल में जोड़ें।'],
                        ['en'=>'Restaurant → QR Codes: print a QR for each table — guests can scan and order from their phone.','hi'=>'Restaurant → QR Codes: हर टेबल के लिए QR प्रिंट करें — गेस्ट स्कैन करके अपने फ़ोन से ऑर्डर कर सकते हैं।'],
                    ],
                    'tip_en'=>'Tip: Guest QR-orders need staff approval before they enter the kitchen — protects you from prank orders.',
                    'tip_hi'=>'टिप: गेस्ट के QR ऑर्डर रसोई में जाने से पहले स्टाफ की मंज़ूरी मांगते हैं — मज़ाक के ऑर्डर से बचाव होता है।',
                ],
                [
                    'id'=>'inventory','icon'=>'fa-boxes','color'=>'#3b82f6',
                    'title_en'=>'7. Inventory','title_hi'=>'7. इन्वेंटरी',
                    'intro_en'=>'Track consumables, food ingredients and hotel supplies. Get an alert when stock is low.',
                    'intro_hi'=>'खाने का सामान, सप्लाई और रोज़मर्रा के सामान का स्टॉक रखें। कम होने पर अलर्ट मिलेगा।',
                    'steps'=>[
                        ['en'=>'Enable: Administration → Modules → switch ON "Inventory Management".','hi'=>'चालू करें: एडमिनिस्ट्रेशन → मॉड्यूल्स → "Inventory Management" को ON करें।'],
                        ['en'=>'Inventory → Add Item: name, unit (kg/litre/pcs), opening stock and low-stock alert level.','hi'=>'Inventory → Add Item: नाम, यूनिट (kg/litre/pcs), शुरुआती स्टॉक और लो-स्टॉक अलर्ट लेवल भरें।'],
                        ['en'=>'Use Adjust Stock to record purchases, usage or wastage.','hi'=>'खरीद, उपयोग या ख़राब हुआ सामान दर्ज करने के लिए Adjust Stock इस्तेमाल करें।'],
                    ],
                    'tip_en'=>'Tip: Items below their alert level appear in red on the dashboard so you never run out.','hi_tip'=>'',
                    'tip_hi'=>'टिप: अलर्ट लेवल से नीचे आए आइटम डैशबोर्ड पर लाल रंग में दिखते हैं ताकि कभी कमी न हो।',
                ],
                [
                    'id'=>'reports','icon'=>'fa-chart-bar','color'=>'#06b6d4',
                    'title_en'=>'8. Reports','title_hi'=>'8. रिपोर्ट्स',
                    'intro_en'=>'See how your business is doing — daily/monthly revenue, occupancy, top guests and more.',
                    'intro_hi'=>'अपने बिज़नेस का हाल देखें — रोज़ाना/महीने की कमाई, ऑक्यूपेंसी, टॉप गेस्ट आदि।',
                    'steps'=>[
                        ['en'=>'Open Reports from the sidebar.','hi'=>'साइडबार से Reports खोलें।'],
                        ['en'=>'Pick a date range (today, this week, this month, custom).','hi'=>'तारीख रेंज चुनें (आज, इस हफ़्ते, इस महीने, कस्टम)।'],
                        ['en'=>'Click Export → Excel or PDF for printing or sharing with your accountant.','hi'=>'Export → Excel या PDF दबा कर प्रिंट या अकाउंटेंट को भेजें।'],
                    ],
                    'tip_en'=>'Tip: Check Occupancy report weekly to see which room types are most popular and adjust prices.',
                    'tip_hi'=>'टिप: हर हफ़्ते Occupancy रिपोर्ट देखें — कौन से रूम टाइप ज़्यादा बिक रहे हैं समझें और रेट सेट करें।',
                ],
                [
                    'id'=>'integrations','icon'=>'fa-bolt','color'=>'#8b5cf6',
                    'title_en'=>'9. WhatsApp, OTAs & Booking Widget','title_hi'=>'9. व्हाट्सएप, OTA और बुकिंग विजेट',
                    'intro_en'=>'Automate guest communication and bring bookings from MakeMyTrip, Booking.com, Goibibo and your own website.',
                    'intro_hi'=>'गेस्ट से बातचीत ऑटोमैटिक करें और MakeMyTrip, Booking.com, Goibibo और अपनी वेबसाइट से बुकिंग लें।',
                    'steps'=>[
                        ['en'=>'Integrations → WhatsApp: connect your WhatsApp number (your Platform Admin will help). Then guests get auto confirmation, check-in reminders, invoices and offers.','hi'=>'Integrations → WhatsApp: अपना व्हाट्सएप नंबर जोड़ें (आपका प्लेटफ़ॉर्म एडमिन मदद करेगा)। फिर गेस्ट को अपने आप कन्फ़र्मेशन, चेक-इन रिमाइंडर, इनवॉइस और ऑफ़र मिलेंगे।'],
                        ['en'=>'Integrations → OTA Import Queue: bookings emailed by MakeMyTrip / Booking.com / Goibibo appear here. Approve and assign a room in one click.','hi'=>'Integrations → OTA Import Queue: MakeMyTrip / Booking.com / Goibibo से ईमेल आई बुकिंग यहाँ आती हैं। एक क्लिक में रूम असाइन करके मंज़ूर करें।'],
                        ['en'=>'Integrations → Booking Widget: copy the code and paste on your website — guests can book directly from your site.','hi'=>'Integrations → Booking Widget: कोड कॉपी करके अपनी वेबसाइट पर लगाएं — गेस्ट सीधे आपकी साइट से बुकिंग कर पाएंगे।'],
                        ['en'=>'Integrations → Payment Links: generate a secure UPI/Card payment link and send by WhatsApp or SMS.','hi'=>'Integrations → Payment Links: सुरक्षित UPI/Card लिंक बनाकर व्हाट्सएप या SMS से भेजें।'],
                    ],
                    'tip_en'=>'Tip: A 5-minute cool-down stops accidentally double-sending the same WhatsApp message to the same guest.',
                    'tip_hi'=>'टिप: 5 मिनट का कूल-डाउन एक ही गेस्ट को गलती से दो बार व्हाट्सएप मैसेज जाने से रोकता है।',
                ],
                [
                    'id'=>'admin','icon'=>'fa-shield-halved','color'=>'#475569',
                    'title_en'=>'10. Users, Roles & Permissions','title_hi'=>'10. यूज़र, रोल और अनुमतियाँ',
                    'intro_en'=>'There are 3 default roles: Admin (you), Manager and Receptionist. Each sees only what they are allowed to.',
                    'intro_hi'=>'3 डिफ़ॉल्ट रोल हैं: Admin (आप), Manager और Receptionist। हर एक को सिर्फ़ उतना ही दिखता है जितनी उसे अनुमति है।',
                    'steps'=>[
                        ['en'=>'Administration → Users: + Add User → fill name, email, password and pick a role.','hi'=>'एडमिनिस्ट्रेशन → Users: + Add User → नाम, ईमेल, पासवर्ड भरें और रोल चुनें।'],
                        ['en'=>'Administration → Roles & Permissions: tick / untick what each role can see and do. Receptionist usually cannot delete or see reports.','hi'=>'एडमिनिस्ट्रेशन → Roles & Permissions: हर रोल के लिए क्या दिखे/क्या न दिखे यहाँ टिक करें। Receptionist आमतौर पर डिलीट नहीं कर सकता और रिपोर्ट्स नहीं देख सकता।'],
                        ['en'=>'Delete permissions are OFF for all roles by default — only the Admin can grant them.','hi'=>'डिलीट की अनुमतियाँ सब रोल के लिए डिफ़ॉल्ट से बंद हैं — सिर्फ़ Admin ही चालू कर सकता है।'],
                    ],
                    'tip_en'=>'Tip: Always create a separate login for each staff member — never share the Admin password.',
                    'tip_hi'=>'टिप: हर स्टाफ़ के लिए अलग लॉगिन बनाएं — Admin का पासवर्ड कभी शेयर न करें।',
                ],
                [
                    'id'=>'settings','icon'=>'fa-cog','color'=>'#64748b',
                    'title_en'=>'11. Settings','title_hi'=>'11. सेटिंग्स',
                    'intro_en'=>'Update hotel info, GST/tax rates, invoice format, and turn modules on/off.',
                    'intro_hi'=>'होटल की जानकारी, GST/टैक्स रेट, इनवॉइस फ़ॉर्मैट अपडेट करें और मॉड्यूल चालू/बंद करें।',
                    'steps'=>[
                        ['en'=>'Administration → Settings: hotel name, address, GSTIN, logo, currency, tax %.','hi'=>'एडमिनिस्ट्रेशन → Settings: होटल का नाम, पता, GSTIN, लोगो, करेंसी, टैक्स %।'],
                        ['en'=>'Administration → Modules: turn ON only the features you actually use — keeps the sidebar clean.','hi'=>'एडमिनिस्ट्रेशन → Modules: सिर्फ़ ज़रूरी फ़ीचर चालू रखें — साइडबार साफ़ रहेगा।'],
                        ['en'=>'Administration → Activity Log: see who did what (every change is recorded with date/time/user).','hi'=>'एडमिनिस्ट्रेशन → Activity Log: कौन ने क्या किया देखें (हर बदलाव तारीख/समय/यूज़र के साथ दर्ज होता है)।'],
                    ],
                    'tip_en'=>'Tip: Upload a clear PNG logo (transparent background) so it looks sharp on invoices.',
                    'tip_hi'=>'टिप: साफ़ PNG लोगो (पारदर्शी बैकग्राउंड) अपलोड करें — इनवॉइस पर अच्छा दिखेगा।',
                ],
                [
                    'id'=>'faq','icon'=>'fa-question-circle','color'=>'#15803d',
                    'title_en'=>'12. Frequently Asked Questions','title_hi'=>'12. अक्सर पूछे जाने वाले सवाल',
                    'intro_en'=>'Quick answers to questions hotel owners ask most.',
                    'intro_hi'=>'होटल मालिक जो सवाल सबसे ज़्यादा पूछते हैं, उनके छोटे जवाब।',
                    'steps'=>[
                        ['en'=>'Q: I forgot my password. → On the login page, click "Forgot Password" and enter your email — a reset link is sent.','hi'=>'प्र: पासवर्ड भूल गए? → लॉगिन पेज पर "Forgot Password" दबाएं, ईमेल डालें — रीसेट लिंक मिल जाएगा।'],
                        ['en'=>'Q: A button is missing or grey for me. → You don\'t have permission for it. Ask your Admin to enable it from Roles & Permissions.','hi'=>'प्र: कोई बटन नहीं दिख रहा या ग्रे है? → आपको उसकी अनुमति नहीं है। एडमिन से Roles & Permissions में चालू कराएं।'],
                        ['en'=>'Q: My data is missing! → Check the hotel switcher at top-left. You may be looking at a different hotel.','hi'=>'प्र: मेरा डेटा गायब है! → ऊपर बाईं ओर होटल स्विचर देखें। शायद आप दूसरे होटल का डेटा देख रहे हैं।'],
                        ['en'=>'Q: WhatsApp not sending. → Check Integrations → WhatsApp page. The number must be approved by Platform Admin first.','hi'=>'प्र: व्हाट्सएप नहीं जा रहा? → Integrations → WhatsApp देखें। नंबर को पहले प्लेटफ़ॉर्म एडमिन से अप्रूव कराना ज़रूरी है।'],
                        ['en'=>'Q: How do I take a backup? → Backups happen automatically. Platform Admin can restore on request.','hi'=>'प्र: बैकअप कैसे लें? → बैकअप अपने आप होता है। ज़रूरत पर प्लेटफ़ॉर्म एडमिन रिस्टोर कर देगा।'],
                    ],
                    'tip_en'=>'Still stuck? Click WhatsApp on the topbar to message the support team.',
                    'tip_hi'=>'अभी भी अटके हैं? टॉपबार पर व्हाट्सएप दबा कर सपोर्ट टीम को मैसेज करें।',
                ],
            ];
        @endphp

        @foreach($sections as $s)
        <section id="{{ $s['id'] }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px 24px;scroll-margin-top:80px;">

            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                <div style="width:46px;height:46px;border-radius:12px;background:{{ $s['color'] }}18;color:{{ $s['color'] }};display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                    <i class="fas {{ $s['icon'] }}"></i>
                </div>
                <h2 style="font-size:19px;font-weight:800;color:#1e293b;margin:0;">
                    <span data-lang="en">{{ $s['title_en'] }}</span>
                    <span data-lang="hi" style="display:none;">{{ $s['title_hi'] }}</span>
                </h2>
            </div>

            <p style="font-size:14px;color:#475569;margin:0 0 14px;line-height:1.6;">
                <span data-lang="en">{{ $s['intro_en'] }}</span>
                <span data-lang="hi" style="display:none;">{{ $s['intro_hi'] }}</span>
            </p>

            <ol style="margin:0 0 14px;padding-left:22px;color:#1e293b;font-size:14px;line-height:1.7;">
                @foreach($s['steps'] as $step)
                <li style="margin-bottom:7px;">
                    <span data-lang="en">{{ $step['en'] }}</span>
                    <span data-lang="hi" style="display:none;">{{ $step['hi'] }}</span>
                </li>
                @endforeach
            </ol>

            <div style="background:linear-gradient(90deg,{{ $s['color'] }}12,{{ $s['color'] }}05);border-left:3px solid {{ $s['color'] }};border-radius:8px;padding:10px 14px;font-size:13px;color:#334155;display:flex;gap:10px;align-items:flex-start;">
                <i class="fas fa-lightbulb" style="color:{{ $s['color'] }};margin-top:3px;flex-shrink:0;"></i>
                <div>
                    <span data-lang="en">{{ $s['tip_en'] }}</span>
                    <span data-lang="hi" style="display:none;">{{ $s['tip_hi'] }}</span>
                </div>
            </div>
        </section>
        @endforeach

        {{-- ── Bottom CTA ───────────────────────────────────────────── --}}
        <div style="background:#0f172a;color:#fff;border-radius:14px;padding:24px 26px;display:flex;flex-wrap:wrap;align-items:center;gap:18px;justify-content:space-between;">
            <div style="min-width:0;flex:1;">
                <div style="font-size:16px;font-weight:800;margin-bottom:4px;">
                    <span data-lang="en">Need more help?</span>
                    <span data-lang="hi" style="display:none;">और मदद चाहिए?</span>
                </div>
                <div style="font-size:13px;color:#cbd5e1;">
                    <span data-lang="en">Our support team replies on WhatsApp within working hours.</span>
                    <span data-lang="hi" style="display:none;">हमारी सपोर्ट टीम कामकाजी घंटों में व्हाट्सएप पर जवाब देती है।</span>
                </div>
            </div>
            <a href="https://wa.me/919999999999" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:10px;background:#25d366;color:#fff;padding:12px 22px;border-radius:10px;font-weight:700;text-decoration:none;font-size:14px;">
                <i class="fab fa-whatsapp" style="font-size:18px;"></i>
                <span data-lang="en">Chat on WhatsApp</span>
                <span data-lang="hi" style="display:none;">व्हाट्सएप पर चैट करें</span>
            </a>
        </div>

    </div>
</div>

<script>
(function() {
    function setGuideLang(lang) {
        if (lang !== 'en' && lang !== 'hi') return;
        document.querySelectorAll('[data-lang="en"]').forEach(function(el){ el.style.display = (lang === 'en') ? '' : 'none'; });
        document.querySelectorAll('[data-lang="hi"]').forEach(function(el){ el.style.display = (lang === 'hi') ? '' : 'none'; });
        var en = document.getElementById('lang-btn-en');
        var hi = document.getElementById('lang-btn-hi');
        if (en && hi) {
            if (lang === 'en') {
                en.style.background = '#fff'; en.style.color = '#0891b2';
                hi.style.background = 'transparent'; hi.style.color = '#fff';
            } else {
                hi.style.background = '#fff'; hi.style.color = '#0891b2';
                en.style.background = 'transparent'; en.style.color = '#fff';
            }
        }
        try { localStorage.setItem('crm_guide_lang', lang); } catch (e) {}
    }
    window.setGuideLang = setGuideLang;

    // Restore saved language
    try {
        var saved = localStorage.getItem('crm_guide_lang');
        if (saved === 'hi') setGuideLang('hi');
    } catch (e) {}

    // Smooth scroll on TOC card click
    document.querySelectorAll('a[href^="#"]').forEach(function(a){
        a.addEventListener('click', function(e){
            var target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({behavior:'smooth', block:'start'});
            }
        });
    });
})();
</script>
@endsection
