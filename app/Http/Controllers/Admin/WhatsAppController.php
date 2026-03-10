<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function config()
    {
        $config = WhatsAppConfig::first() ?? new WhatsAppConfig();
        return view('admin.whatsapp.config', compact('config'));
    }

    public function configSave(Request $request)
    {
        $data = $request->validate([
            'provider'              => 'required|in:meta,wati,interakt,gupshup,twilio',
            'api_key'               => 'nullable|string',
            'phone_number_id'       => 'nullable|string',
            'webhook_verify_token'  => 'nullable|string',
            'business_account_id'   => 'nullable|string',
            'test_phone'            => 'nullable|string',
            'is_active'             => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        WhatsAppConfig::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'WhatsApp configuration saved successfully.');
    }

    public function templates()
    {
        $templates  = WhatsAppTemplate::orderBy('id')->get()->keyBy('trigger_event');
        $allEvents  = WhatsAppTemplate::allEvents();
        return view('admin.whatsapp.templates', compact('templates', 'allEvents'));
    }

    public function templateEdit(WhatsAppTemplate $template)
    {
        return view('admin.whatsapp.template-edit', compact('template'));
    }

    public function templateSave(Request $request, WhatsAppTemplate $template)
    {
        $data = $request->validate([
            'template_name' => 'required|string|max:120',
            'message_body'  => 'required|string',
            'is_active'     => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $template->update($data);
        return redirect()->route('whatsapp.templates')->with('success', 'Template saved.');
    }

    public function testSend(Request $request)
    {
        $request->validate(['phone' => 'required|string', 'message' => 'required|string']);

        $sent = WhatsAppService::sendRaw($request->phone, $request->message);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'Test message sent successfully!' : 'Failed to send. Check your API credentials and logs.'
        );
    }
}
