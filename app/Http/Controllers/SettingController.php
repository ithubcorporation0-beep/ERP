<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\SettingKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-only app settings (company profile + invoice numbering/currency
 * defaults). Gated by the 'viewAdmin' Gate at the route level, matching
 * Users/Audit Log. The logo itself is viewable by any authenticated user
 * (it needs to show up on the invoice print view etc.), same as avatars.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'companyName' => Setting::get(SettingKeys::COMPANY_NAME, ''),
            'logo' => Setting::where('key', SettingKeys::COMPANY_LOGO)->first()?->getFirstMedia('logo'),
            'invoicePrefix' => Setting::get(SettingKeys::INVOICE_PREFIX, 'INV'),
            'numberingReset' => Setting::get(SettingKeys::INVOICE_NUMBERING_RESET, SettingKeys::NUMBERING_RESET_YEARLY),
            'numberingResetOptions' => SettingKeys::NUMBERING_RESET_OPTIONS,
            'defaultCurrency' => Setting::get(SettingKeys::DEFAULT_CURRENCY, 'USD'),
            'taxBehavior' => Setting::get(SettingKeys::DEFAULT_TAX_BEHAVIOR, SettingKeys::TAX_BEHAVIOR_PER_LINE),
            'taxBehaviorOptions' => SettingKeys::TAX_BEHAVIOR_OPTIONS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'invoice_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
            'invoice_numbering_reset' => ['required', Rule::in(SettingKeys::NUMBERING_RESET_OPTIONS)],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_tax_behavior' => ['required', Rule::in(SettingKeys::TAX_BEHAVIOR_OPTIONS)],
        ]);

        Setting::set(SettingKeys::COMPANY_NAME, $data['company_name'] ?? '');
        Setting::set(SettingKeys::INVOICE_PREFIX, strtoupper($data['invoice_prefix']));
        Setting::set(SettingKeys::INVOICE_NUMBERING_RESET, $data['invoice_numbering_reset']);
        Setting::set(SettingKeys::DEFAULT_CURRENCY, strtoupper($data['default_currency']));
        Setting::set(SettingKeys::DEFAULT_TAX_BEHAVIOR, $data['default_tax_behavior']);

        return back()->with('status', 'Settings updated.');
    }

    public function storeLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        Setting::firstOrCreate(['key' => SettingKeys::COMPANY_LOGO])
            ->addMedia($request->file('logo'))
            ->toMediaCollection('logo');

        return back()->with('status', 'Logo updated.');
    }

    public function destroyLogo(): RedirectResponse
    {
        Setting::where('key', SettingKeys::COMPANY_LOGO)->first()?->getFirstMedia('logo')?->delete();

        return back()->with('status', 'Logo removed.');
    }

    /**
     * Any authenticated user can view the company logo (it's not
     * sensitive, and shows up on invoice print views for everyone who
     * can see an invoice) — served through this route rather than a
     * public URL only because it lives on the same private disk as
     * every other upload in this app.
     */
    public function showLogo(): StreamedResponse
    {
        $logo = Setting::where('key', SettingKeys::COMPANY_LOGO)->first()?->getFirstMedia('logo');

        abort_unless($logo, 404);

        // Streams via Flysystem rather than assuming a real local path, so
        // this works the same whether the 'private' disk is local or s3.
        return Storage::disk($logo->disk)->response($logo->getPathRelativeToRoot());
    }
}
