<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Guest Check-In — {{ $settings->resort_name ?? $hotel->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; font-family: 'Inter', system-ui, sans-serif; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .step { display: none; }
        .step.active { display: block; }
        .progress-bar { height: 4px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 999px; transition: width .4s ease; }
        .input-field { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 15px; outline: none; transition: border-color .2s; background: #fafafa; }
        .input-field:focus { border-color: #6366f1; background: #fff; }
        .btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; border-radius: 14px; padding: 14px 28px; font-size: 16px; font-weight: 700; cursor: pointer; width: 100%; transition: opacity .2s; }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: none; border-radius: 14px; padding: 14px 28px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%; }
        .lang-btn { padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 700; cursor: pointer; border: 2px solid #6366f1; transition: all .2s; }
        .lang-btn.active { background: #6366f1; color: #fff; }
        .lang-btn:not(.active) { background: transparent; color: #6366f1; }
        #sigCanvas { border: 2px dashed #cbd5e1; border-radius: 14px; background: #fafafa; cursor: crosshair; touch-action: none; display: block; width: 100%; }
        .preview-img { max-height: 160px; border-radius: 10px; object-fit: contain; border: 1px solid #e2e8f0; }
        .lookup-msg { padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-top: 8px; }
        .lookup-success { background: #ecfdf5; color: #16a34a; }
        .lookup-info { background: #f0f9ff; color: #0284c7; }
        label.required::after { content: ' *'; color: #ef4444; }
    </style>
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;">
    <div class="card" style="width:100%;max-width:480px;padding:28px 24px;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                @if(($settings->logo_url ?? null))
                <img src="{{ $settings->logo_url }}" alt="Logo" style="height:44px;width:44px;object-fit:contain;border-radius:10px;">
                @else
                <div style="width:44px;height:44px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-hotel" style="color:#fff;font-size:18px;"></i>
                </div>
                @endif
                <div>
                    <div style="font-weight:800;font-size:15px;color:#1e293b;">{{ $settings->resort_name ?? $hotel->name }}</div>
                    <div style="font-size:12px;color:#94a3b8;" data-i18n="subtitle">Guest Check-In</div>
                </div>
            </div>
            <div style="display:flex;gap:6px;">
                <button class="lang-btn active" onclick="setLang('en')" id="btnEn">EN</button>
                <button class="lang-btn" onclick="setLang('hi')" id="btnHi">हि</button>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="progress-bar" style="margin-bottom:20px;">
            <div class="progress-fill" id="progressFill" style="width:16.6%;"></div>
        </div>
        <div style="font-size:12px;color:#94a3b8;text-align:right;margin-top:-14px;margin-bottom:14px;">
            <span id="stepLabel" data-i18n="step">Step</span> <span id="stepNum">1</span> / 6
        </div>

        <form id="checkinForm" action="{{ route('guest.checkin.store', $slug) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="booking_ref" value="{{ $bookingRef ?? '' }}">

            {{-- Step 1: Phone --}}
            <div class="step active" id="step1">
                <div style="text-align:center;margin-bottom:20px;">
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <i class="fas fa-phone" style="color:#fff;font-size:22px;"></i>
                    </div>
                    <h2 style="font-weight:800;font-size:20px;color:#1e293b;" data-i18n="step1_title">Enter Your Phone Number</h2>
                    <p style="font-size:13px;color:#64748b;margin-top:6px;" data-i18n="step1_sub">We'll check if you've stayed with us before</p>
                </div>
                <label class="required" style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:6px;" data-i18n="phone_label">Phone Number</label>
                <input type="tel" id="phoneInput" name="phone" class="input-field" placeholder="9876543210" maxlength="15" oninput="onPhoneInput(this.value)">
                <div id="lookupMsg" style="display:none;" class="lookup-msg"></div>
                <div style="margin-top:16px;">
                    <button type="button" class="btn-primary" onclick="nextStep()" id="step1Next" disabled data-i18n="continue">Continue</button>
                </div>
            </div>

            {{-- Step 2: Personal Details --}}
            <div class="step" id="step2">
                <div style="margin-bottom:18px;">
                    <h2 style="font-weight:800;font-size:18px;color:#1e293b;" data-i18n="step2_title">Personal Details</h2>
                    <p style="font-size:13px;color:#64748b;margin-top:4px;" data-i18n="step2_sub">Please verify or complete your information</p>
                </div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="required" style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="name_label">Full Name</label>
                        <input type="text" name="name" id="nameInput" class="input-field" placeholder="Rajan Kumar" required>
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="email_label">Email (optional)</label>
                        <input type="email" name="email" id="emailInput" class="input-field" placeholder="you@example.com">
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="dob_label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="dobInput" class="input-field">
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="address_label">Address</label>
                        <textarea name="address" id="addressInput" class="input-field" rows="2" placeholder="Street, City, State" style="resize:none;"></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:18px;">
                    <button type="button" class="btn-secondary" onclick="prevStep()"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span data-i18n="back">Back</span></button>
                    <button type="button" class="btn-primary" onclick="validateStep2()" data-i18n="continue">Continue</button>
                </div>
            </div>

            {{-- Step 3: ID Document Upload --}}
            <div class="step" id="step3">
                <div style="margin-bottom:18px;">
                    <h2 style="font-weight:800;font-size:18px;color:#1e293b;" data-i18n="step3_title">ID Verification</h2>
                    <p style="font-size:13px;color:#64748b;margin-top:4px;" data-i18n="step3_sub">Government ID required for all guests</p>
                </div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="id_type_label">ID Type</label>
                        <select name="id_type" id="idTypeInput" class="input-field">
                            <option value="">-- Select --</option>
                            <option value="Aadhaar Card" data-i18n-opt="aadhaar">Aadhaar Card</option>
                            <option value="PAN Card">PAN Card</option>
                            <option value="Passport" data-i18n-opt="passport">Passport</option>
                            <option value="Driving License" data-i18n-opt="driving">Driving License</option>
                            <option value="Voter ID" data-i18n-opt="voter">Voter ID</option>
                            <option value="Other" data-i18n-opt="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="id_number_label">ID Number</label>
                        <input type="text" name="id_number" id="idNumberInput" class="input-field" placeholder="e.g. XXXX-XXXX-XXXX">
                    </div>
                    <div>
                        {{-- Returning guest: existing doc on file --}}
                        <div id="existingDocBanner" style="display:none;background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:12px 14px;margin-bottom:10px;">
                            <div style="font-size:12px;font-weight:700;color:#16a34a;margin-bottom:4px;"><i class="fas fa-check-circle" style="margin-right:5px;"></i><span data-i18n="doc_on_file">ID document on file from your last visit</span></div>
                            <div style="font-size:12px;color:#15803d;" data-i18n="doc_reuse_hint">We'll reuse your document. Tap below only if you want to upload a new one.</div>
                        </div>

                        <label id="docUploadLabel" style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="doc_upload_label">Upload ID Document Photo</label>
                        <div id="uploadArea" onclick="document.getElementById('docFile').click()"
                            style="border:2px dashed #cbd5e1;border-radius:14px;padding:24px;text-align:center;cursor:pointer;background:#fafafa;transition:border-color .2s;"
                            onmouseenter="this.style.borderColor='#6366f1'" onmouseleave="this.style.borderColor='#cbd5e1'">
                            <div id="uploadPlaceholder">
                                <i class="fas fa-camera" style="font-size:28px;color:#94a3b8;margin-bottom:8px;display:block;"></i>
                                <div style="font-weight:700;font-size:14px;color:#475569;" data-i18n="tap_upload">Tap to upload or take photo</div>
                                <div style="font-size:12px;color:#94a3b8;margin-top:4px;" data-i18n="file_hint">JPG, PNG, PDF up to 5 MB</div>
                            </div>
                            <img id="docPreview" class="preview-img" style="display:none;margin:0 auto;">
                        </div>
                        <input type="file" name="id_document" id="docFile" accept="image/*,.pdf,.heic,.heif" capture="environment" style="display:none;" onchange="handleDocUpload(this)">
                        <input type="hidden" name="reuse_id_document" id="reuseDocInput" value="0">
                        <p id="docError" style="color:#ef4444;font-size:12px;margin-top:6px;display:none;" data-i18n="doc_required">Please upload your ID document to continue.</p>
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:18px;">
                    <button type="button" class="btn-secondary" onclick="prevStep()"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span data-i18n="back">Back</span></button>
                    <button type="button" class="btn-primary" onclick="validateStep3()" data-i18n="continue">Continue</button>
                </div>
            </div>

            {{-- Step 4: Digital Signature --}}
            <div class="step" id="step4">
                <div style="margin-bottom:18px;">
                    <h2 style="font-weight:800;font-size:18px;color:#1e293b;" data-i18n="step4_title">Digital Signature</h2>
                    <p style="font-size:13px;color:#64748b;margin-top:4px;" data-i18n="step4_sub">Sign in the box below using your finger or stylus</p>
                </div>
                {{-- Returning guest: existing signature on file --}}
                <div id="existingSigBanner" style="display:none;background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:12px 14px;margin-bottom:12px;">
                    <div style="font-size:12px;font-weight:700;color:#16a34a;margin-bottom:4px;"><i class="fas fa-check-circle" style="margin-right:5px;"></i><span data-i18n="sig_on_file">Signature on file from your last visit — will be reused</span></div>
                    <div style="font-size:12px;color:#15803d;" data-i18n="sig_reuse_hint">Sign below only if you want to provide a new signature, otherwise leave blank and continue.</div>
                </div>
                <canvas id="sigCanvas" width="432" height="180"></canvas>
                <input type="hidden" name="signature_data" id="sigData">
                <input type="hidden" name="reuse_signature" id="reuseSigInput" value="0">
                <p id="sigError" style="color:#ef4444;font-size:12px;margin-top:6px;display:none;" data-i18n="sig_required">Please provide your signature.</p>
                <div style="display:flex;gap:10px;margin-top:10px;">
                    <button type="button" onclick="clearSig()" style="flex:1;padding:10px;background:#fee2e2;color:#dc2626;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;"><i class="fas fa-eraser" style="margin-right:5px;"></i><span data-i18n="clear">Clear</span></button>
                </div>
                <div style="display:flex;gap:10px;margin-top:14px;">
                    <button type="button" class="btn-secondary" onclick="prevStep()"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span data-i18n="back">Back</span></button>
                    <button type="button" class="btn-primary" onclick="validateStep4()" data-i18n="continue">Continue</button>
                </div>
            </div>

            {{-- Step 5: Additional Guests --}}
            <div class="step" id="step5">
                <div style="margin-bottom:18px;">
                    <h2 style="font-weight:800;font-size:18px;color:#1e293b;" data-i18n="step5_title">Additional Guests</h2>
                    <p style="font-size:13px;color:#64748b;margin-top:4px;" data-i18n="step5_sub">Add other guests travelling with you (optional)</p>
                </div>
                <div id="additionalGuestsList" style="display:flex;flex-direction:column;gap:12px;"></div>
                <button type="button" onclick="addGuest()"
                    style="margin-top:10px;padding:10px 18px;background:#f0f9ff;color:#0284c7;border:1.5px dashed #7dd3fc;border-radius:12px;font-weight:700;font-size:13px;cursor:pointer;width:100%;">
                    <i class="fas fa-plus" style="margin-right:6px;"></i><span data-i18n="add_guest">Add Guest</span>
                </button>
                <div style="display:flex;gap:10px;margin-top:18px;">
                    <button type="button" class="btn-secondary" onclick="prevStep()"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span data-i18n="back">Back</span></button>
                    <button type="button" class="btn-primary" onclick="nextStep()" data-i18n="continue">Continue</button>
                </div>
            </div>

            {{-- Step 6: Dates --}}
            <div class="step" id="step6">
                <div style="margin-bottom:18px;">
                    <h2 style="font-weight:800;font-size:18px;color:#1e293b;" data-i18n="step6_title">Stay Details</h2>
                    <p style="font-size:13px;color:#64748b;margin-top:4px;" data-i18n="step6_sub">When are you planning to stay?</p>
                </div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="checkin_date_label">Check-In Date</label>
                        <input type="date" name="requested_check_in" id="ciDate" class="input-field">
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="checkout_date_label">Check-Out Date</label>
                        <input type="date" name="requested_check_out" id="coDate" class="input-field">
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="guests_count_label">Total Guests</label>
                        <input type="number" name="guests_count" class="input-field" value="1" min="1" max="20">
                    </div>
                </div>
                <div style="margin-top:20px;background:#f8fafc;border-radius:14px;padding:16px;font-size:13px;color:#64748b;" data-i18n="summary_note">
                    Your details have been captured. Our team will assign a room and confirm your booking.
                </div>
                <div style="display:flex;gap:10px;margin-top:18px;">
                    <button type="button" class="btn-secondary" onclick="prevStep()"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span data-i18n="back">Back</span></button>
                    <button type="submit" class="btn-primary" id="submitBtn"><i class="fas fa-check" style="margin-right:8px;"></i><span data-i18n="submit">Submit Request</span></button>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
// ── Translation Map ─────────────────────────────────────────────────────────
var TRANS = {
    en: {
        subtitle: 'Guest Check-In', step: 'Step', step1_title: 'Enter Your Phone Number',
        step1_sub: "We'll check if you've stayed with us before", phone_label: 'Phone Number',
        continue: 'Continue', back: 'Back', step2_title: 'Personal Details',
        step2_sub: 'Please verify or complete your information', name_label: 'Full Name',
        email_label: 'Email (optional)', dob_label: 'Date of Birth', address_label: 'Address',
        step3_title: 'ID Verification', step3_sub: 'Government ID required for all guests',
        id_type_label: 'ID Type', id_number_label: 'ID Number', doc_upload_label: 'Upload ID Document Photo',
        tap_upload: 'Tap to upload or take photo', file_hint: 'JPG, PNG, PDF up to 5 MB',
        doc_required: 'Please upload your ID document to continue.',
        step4_title: 'Digital Signature', step4_sub: 'Sign in the box below using your finger or stylus',
        sig_required: 'Please provide your signature.', clear: 'Clear',
        step5_title: 'Additional Guests', step5_sub: 'Add other guests travelling with you (optional)',
        add_guest: 'Add Guest', step6_title: 'Stay Details',
        step6_sub: 'When are you planning to stay?', checkin_date_label: 'Check-In Date',
        checkout_date_label: 'Check-Out Date', guests_count_label: 'Total Guests',
        summary_note: 'Your details have been captured. Our team will assign a room and confirm your booking.',
        submit: 'Submit Request', guest_name: 'Guest Name', aadhaar: 'Aadhaar Card',
        passport: 'Passport', driving: 'Driving License', voter: 'Voter ID', other: 'Other',
        name_required: 'Please enter your name.',
        doc_uploaded: 'Document uploaded.',
        doc_on_file: 'ID document on file from your last visit',
        doc_reuse_hint: "We'll reuse your document. Tap below only if you want to upload a new one.",
        doc_upload_optional: 'Upload New ID Document (optional)',
        sig_on_file: 'Signature on file from your last visit — will be reused',
        sig_reuse_hint: 'Sign below only if you want to provide a new signature, otherwise leave blank and continue.',
    },
    hi: {
        subtitle: 'अतिथि चेक-इन', step: 'चरण', step1_title: 'अपना फोन नंबर दर्ज करें',
        step1_sub: 'हम जाँचेंगे कि क्या आप पहले यहाँ रुके हैं', phone_label: 'फोन नंबर',
        continue: 'आगे बढ़ें', back: 'वापस', step2_title: 'व्यक्तिगत विवरण',
        step2_sub: 'कृपया अपनी जानकारी सत्यापित करें', name_label: 'पूरा नाम',
        email_label: 'ईमेल (वैकल्पिक)', dob_label: 'जन्म तिथि', address_label: 'पता',
        step3_title: 'पहचान सत्यापन', step3_sub: 'सभी अतिथियों के लिए सरकारी पहचान पत्र आवश्यक',
        id_type_label: 'पहचान पत्र प्रकार', id_number_label: 'पहचान पत्र संख्या',
        doc_upload_label: 'पहचान पत्र की फोटो अपलोड करें',
        tap_upload: 'फोटो अपलोड करने या खींचने के लिए टैप करें', file_hint: 'JPG, PNG, PDF — अधिकतम 5 MB',
        doc_required: 'कृपया आगे बढ़ने के लिए अपना पहचान पत्र अपलोड करें।',
        step4_title: 'डिजिटल हस्ताक्षर', step4_sub: 'नीचे बॉक्स में अपनी उंगली या स्टाइलस से हस्ताक्षर करें',
        sig_required: 'कृपया अपना हस्ताक्षर प्रदान करें।', clear: 'मिटाएं',
        step5_title: 'अतिरिक्त अतिथि', step5_sub: 'आपके साथ यात्रा करने वाले अन्य अतिथि जोड़ें (वैकल्पिक)',
        add_guest: 'अतिथि जोड़ें', step6_title: 'ठहरने का विवरण',
        step6_sub: 'आप कब ठहरने की योजना बना रहे हैं?', checkin_date_label: 'चेक-इन तिथि',
        checkout_date_label: 'चेक-आउट तिथि', guests_count_label: 'कुल अतिथि',
        summary_note: 'आपका विवरण दर्ज कर लिया गया है। हमारी टीम एक कमरा आवंटित करेगी और आपकी बुकिंग की पुष्टि करेगी।',
        submit: 'अनुरोध भेजें', guest_name: 'अतिथि का नाम', aadhaar: 'आधार कार्ड',
        passport: 'पासपोर्ट', driving: 'ड्राइविंग लाइसेंस', voter: 'मतदाता पहचान पत्र', other: 'अन्य',
        name_required: 'कृपया अपना नाम दर्ज करें।',
        doc_uploaded: 'दस्तावेज़ अपलोड किया गया।',
        doc_on_file: 'पिछली यात्रा से पहचान पत्र उपलब्ध है',
        doc_reuse_hint: 'हम आपका पुराना दस्तावेज़ उपयोग करेंगे। नया अपलोड करना हो तो नीचे टैप करें।',
        doc_upload_optional: 'नया पहचान पत्र अपलोड करें (वैकल्पिक)',
        sig_on_file: 'पिछली यात्रा का हस्ताक्षर उपलब्ध — पुनः उपयोग किया जाएगा',
        sig_reuse_hint: 'यदि नया हस्ताक्षर देना हो तो नीचे हस्ताक्षर करें, अन्यथा खाली छोड़कर आगे बढ़ें।',
    }
};

var currentLang = (navigator.language || 'en').startsWith('hi') ? 'hi' : 'en';
var currentStep = 1;
var totalSteps  = 6;
var docUploaded = false;
var sigDrawn    = false;
var guestCount  = 0;
var existingDocOnFile       = false;
var existingSignatureOnFile = false;

function t(key) { return TRANS[currentLang][key] || TRANS['en'][key] || key; }

function setLang(lang) {
    currentLang = lang;
    document.getElementById('btnEn').classList.toggle('active', lang === 'en');
    document.getElementById('btnHi').classList.toggle('active', lang === 'hi');
    document.querySelectorAll('[data-i18n]').forEach(function(el) {
        var key = el.getAttribute('data-i18n');
        if (key) el.textContent = t(key);
    });
    document.querySelectorAll('[data-i18n-opt]').forEach(function(el) {
        var key = el.getAttribute('data-i18n-opt');
        if (key && TRANS[lang][key]) el.textContent = TRANS[lang][key];
    });
}

function updateProgress() {
    document.getElementById('progressFill').style.width = ((currentStep / totalSteps) * 100) + '%';
    document.getElementById('stepNum').textContent = currentStep;
}

function showStep(n) {
    document.querySelectorAll('.step').forEach(function(s) { s.classList.remove('active'); });
    document.getElementById('step' + n).classList.add('active');
    currentStep = n;
    updateProgress();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep() { if (currentStep < totalSteps) showStep(currentStep + 1); }
function prevStep() { if (currentStep > 1) showStep(currentStep - 1); }

// ── Step 1: Phone lookup ──
var lookupTimer = null;
function onPhoneInput(val) {
    clearTimeout(lookupTimer);
    var btn = document.getElementById('step1Next');
    btn.disabled = val.trim().length < 5;
    if (val.trim().length < 5) { hideLookupMsg(); return; }
    lookupTimer = setTimeout(function() { doLookup(val.trim()); }, 600);
}

function doLookup(phone) {
    fetch('{{ route('guest.checkin.lookup', $slug) }}?phone=' + encodeURIComponent(phone), { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.found) {
                // Pre-fill returning guest details — endpoint is rate-limited server-side
                document.getElementById('nameInput').value    = d.name || '';
                document.getElementById('emailInput').value   = d.email || '';
                document.getElementById('addressInput').value = d.address || '';
                document.getElementById('idTypeInput').value  = d.id_type || '';
                document.getElementById('idNumberInput').value= d.id_number || '';
                document.getElementById('dobInput').value     = d.dob || '';

                // Returning guest — existing ID doc on file
                if (d.has_id_doc) {
                    existingDocOnFile = true;
                    document.getElementById('existingDocBanner').style.display = 'block';
                    document.getElementById('reuseDocInput').value = '1';
                    document.getElementById('docUploadLabel').classList.remove('required');
                    document.getElementById('docUploadLabel').setAttribute('data-i18n', 'doc_upload_optional');
                    document.getElementById('docUploadLabel').textContent = t('doc_upload_optional');
                } else {
                    existingDocOnFile = false;
                    document.getElementById('existingDocBanner').style.display = 'none';
                    document.getElementById('reuseDocInput').value = '0';
                    document.getElementById('docUploadLabel').classList.add('required');
                    document.getElementById('docUploadLabel').setAttribute('data-i18n', 'doc_upload_label');
                    document.getElementById('docUploadLabel').textContent = t('doc_upload_label');
                }

                // Returning guest — existing signature on file
                if (d.has_signature) {
                    existingSignatureOnFile = true;
                    document.getElementById('existingSigBanner').style.display = 'block';
                    document.getElementById('reuseSigInput').value = '1';
                } else {
                    existingSignatureOnFile = false;
                    document.getElementById('existingSigBanner').style.display = 'none';
                    document.getElementById('reuseSigInput').value = '0';
                }

                showLookupMsg(currentLang === 'hi' ? '👋 आपका स्वागत है! आपका विवरण भरा गया है।' : '👋 Welcome back! Your name has been filled in.', 'success');
            } else {
                // New guest — reset any returning-guest state
                existingDocOnFile = false;
                existingSignatureOnFile = false;
                document.getElementById('existingDocBanner').style.display = 'none';
                document.getElementById('existingSigBanner').style.display = 'none';
                document.getElementById('reuseDocInput').value = '0';
                document.getElementById('reuseSigInput').value = '0';
                document.getElementById('docUploadLabel').classList.add('required');
                showLookupMsg(currentLang === 'hi' ? '🙏 पहली बार आपका स्वागत है!' : '🙏 Welcome! First time with us.', 'info');
            }
        }).catch(function() {});
}

function showLookupMsg(msg, type) {
    var el = document.getElementById('lookupMsg');
    el.textContent = msg;
    el.className = 'lookup-msg lookup-' + type;
    el.style.display = 'block';
}
function hideLookupMsg() { document.getElementById('lookupMsg').style.display = 'none'; }

// ── Step 2 validation ──
function validateStep2() {
    var name = document.getElementById('nameInput').value.trim();
    if (!name) { alert(t('name_required')); return; }
    nextStep();
}

// ── Step 3: Doc upload ──
function handleDocUpload(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    docUploaded = true;
    var reader = new FileReader();
    reader.onload = function(e) {
        if (file.type.startsWith('image/')) {
            var img = document.getElementById('docPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('uploadPlaceholder').style.display = 'none';
        } else {
            document.getElementById('uploadPlaceholder').innerHTML =
                '<i class="fas fa-file-pdf" style="font-size:28px;color:#ef4444;margin-bottom:8px;display:block;"></i>' +
                '<div style="font-weight:700;font-size:14px;color:#475569;">' + file.name + '</div>';
        }
    };
    reader.readAsDataURL(file);
}

function validateStep3() {
    // Allow proceed if existing doc is on file (no new upload required)
    if (!docUploaded && !existingDocOnFile) {
        document.getElementById('docError').style.display = 'block';
        return;
    }
    document.getElementById('docError').style.display = 'none';
    // If a new file was uploaded, disable the reuse flag
    if (docUploaded) {
        document.getElementById('reuseDocInput').value = '0';
    }
    nextStep();
}

// ── Step 4: Signature canvas ──
(function() {
    var canvas, ctx, drawing = false, lastX = 0, lastY = 0;
    function init() {
        canvas = document.getElementById('sigCanvas');
        // Fit canvas to container width
        var w = canvas.parentElement.offsetWidth - 48;
        canvas.width  = Math.max(300, w);
        canvas.height = 180;
        ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth   = 2.5;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var src  = e.touches ? e.touches[0] : e;
            return { x: src.clientX - rect.left, y: src.clientY - rect.top };
        }
        function start(e) { e.preventDefault(); drawing = true; var p = getPos(e); lastX = p.x; lastY = p.y; sigDrawn = false; }
        function move(e)  { e.preventDefault(); if (!drawing) return; var p = getPos(e); ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke(); lastX = p.x; lastY = p.y; sigDrawn = true; }
        function end(e)   { e.preventDefault(); drawing = false; }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup',   end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove',  move,  { passive: false });
        canvas.addEventListener('touchend',   end,   { passive: false });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();

window.clearSig = function() {
    var c = document.getElementById('sigCanvas');
    var ctx2 = c.getContext('2d');
    ctx2.clearRect(0, 0, c.width, c.height);
    sigDrawn = false;
    document.getElementById('sigData').value = '';
};

function validateStep4() {
    // If guest drew a new signature, capture it
    if (sigDrawn) {
        var canvas = document.getElementById('sigCanvas');
        document.getElementById('sigData').value = canvas.toDataURL('image/png');
        document.getElementById('reuseSigInput').value = '0';
        document.getElementById('sigError').style.display = 'none';
        nextStep();
        return;
    }
    // Allow proceed if existing signature is on file
    if (existingSignatureOnFile) {
        document.getElementById('sigData').value = '';
        document.getElementById('reuseSigInput').value = '1';
        document.getElementById('sigError').style.display = 'none';
        nextStep();
        return;
    }
    document.getElementById('sigError').style.display = 'block';
}

// ── Step 5: Additional guests ──
var guestIdx = 0;
window.addGuest = function() {
    var i = guestIdx++;
    var div = document.createElement('div');
    div.style.cssText = 'background:#f8fafc;border-radius:14px;padding:14px;border:1px solid #e2e8f0;position:relative;';
    div.innerHTML =
        '<button type="button" onclick="this.parentElement.remove()" style="position:absolute;top:10px;right:10px;background:#fee2e2;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;color:#dc2626;font-size:12px;">✕</button>' +
        '<div style="font-weight:700;font-size:13px;color:#475569;margin-bottom:10px;" data-i18n="guest_name">' + t('guest_name') + ' ' + (i + 1) + '</div>' +
        '<input type="text" name="additional_guests[' + i + '][name]" placeholder="Full Name" class="input-field" style="margin-bottom:8px;" required>' +
        '<div style="display:flex;gap:8px;">' +
        '<select name="additional_guests[' + i + '][id_type]" class="input-field" style="flex:1;"><option value="">ID Type</option><option>Aadhaar Card</option><option>PAN Card</option><option>Passport</option><option>Driving License</option><option>Voter ID</option><option>Other</option></select>' +
        '<input type="text" name="additional_guests[' + i + '][id_number]" placeholder="ID Number" class="input-field" style="flex:1;">' +
        '</div>';
    document.getElementById('additionalGuestsList').appendChild(div);
};

// Set today's date as default for check-in
document.addEventListener('DOMContentLoaded', function() {
    var today = new Date().toISOString().split('T')[0];
    var tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];
    document.getElementById('ciDate').value = today;
    document.getElementById('coDate').value = tomorrow;
    setLang(currentLang);
});

// Form submit - disable button to prevent double submit
document.getElementById('checkinForm').addEventListener('submit', function() {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>Submitting…';
});
</script>
</body>
</html>
