<?php

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Support\Roles;
use App\Support\SettingKeys;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Roles::ADMIN);
});

test('Setting::get/set round-trips strings, numbers, booleans, and arrays, including falsy values', function () {
    Setting::set('a_string', 'hello');
    Setting::set('an_int', 5);
    Setting::set('a_false_bool', false);
    Setting::set('a_zero', 0);
    Setting::set('an_empty_string', '');
    Setting::set('an_array', ['a', 'b']);

    expect(Setting::get('a_string'))->toBe('hello')
        ->and(Setting::get('an_int'))->toBe(5)
        ->and(Setting::get('a_false_bool'))->toBeFalse()
        ->and(Setting::get('a_zero'))->toBe(0)
        ->and(Setting::get('an_empty_string'))->toBe('')
        ->and(Setting::get('an_array'))->toBe(['a', 'b'])
        ->and(Setting::get('missing_key', 'fallback'))->toBe('fallback');
});

test('only ADMIN/SUPER_ADMIN can access the Settings page', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(route('settings.edit'));

    $expected ? $response->assertOk() : $response->assertForbidden();
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::ACCOUNTANT, false],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('an admin can update all settings in one submission', function () {
    $response = $this->actingAs($this->admin)->patch(route('settings.update'), [
        'company_name' => 'Acme Corp',
        'invoice_prefix' => 'acme',
        'invoice_numbering_reset' => SettingKeys::NUMBERING_RESET_CONTINUOUS,
        'default_currency' => 'eur',
        'default_tax_behavior' => SettingKeys::TAX_BEHAVIOR_INCLUSIVE,
    ]);

    $response->assertRedirect();

    expect(Setting::get(SettingKeys::COMPANY_NAME))->toBe('Acme Corp')
        ->and(Setting::get(SettingKeys::INVOICE_PREFIX))->toBe('ACME')
        ->and(Setting::get(SettingKeys::INVOICE_NUMBERING_RESET))->toBe(SettingKeys::NUMBERING_RESET_CONTINUOUS)
        ->and(Setting::get(SettingKeys::DEFAULT_CURRENCY))->toBe('EUR')
        ->and(Setting::get(SettingKeys::DEFAULT_TAX_BEHAVIOR))->toBe(SettingKeys::TAX_BEHAVIOR_INCLUSIVE);
});

test('an invalid numbering reset value is rejected', function () {
    $response = $this->actingAs($this->admin)->patch(route('settings.update'), [
        'invoice_prefix' => 'INV',
        'invoice_numbering_reset' => 'monthly',
        'default_currency' => 'USD',
        'default_tax_behavior' => SettingKeys::TAX_BEHAVIOR_PER_LINE,
    ]);

    $response->assertSessionHasErrors('invoice_numbering_reset');
});

test('a non-admin cannot update settings', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);

    $this->actingAs($manager)->patch(route('settings.update'), [
        'invoice_prefix' => 'HACK',
        'invoice_numbering_reset' => SettingKeys::NUMBERING_RESET_YEARLY,
        'default_currency' => 'USD',
        'default_tax_behavior' => SettingKeys::TAX_BEHAVIOR_PER_LINE,
    ])->assertForbidden();

    expect(Setting::get(SettingKeys::INVOICE_PREFIX))->toBeNull();
});

test('an admin can upload, view, and remove the company logo', function () {
    Storage::fake('private');

    $this->actingAs($this->admin)
        ->get(route('settings.logo.show'))
        ->assertNotFound();

    $this->actingAs($this->admin)
        ->post(route('settings.logo.store'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])
        ->assertRedirect();

    $logo = Setting::where('key', SettingKeys::COMPANY_LOGO)->first()->getFirstMedia('logo');
    expect($logo)->not->toBeNull()
        ->and($logo->disk)->toBe('private');

    $this->actingAs($this->admin)->get(route('settings.logo.show'))->assertOk();

    $this->actingAs($this->admin)->delete(route('settings.logo.destroy'))->assertRedirect();

    $this->actingAs($this->admin)->get(route('settings.logo.show'))->assertNotFound();
});

test('any authenticated user can view the company logo, but only an admin can upload one', function () {
    Storage::fake('private');

    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($employee)
        ->post(route('settings.logo.store'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])
        ->assertForbidden();

    $this->actingAs($this->admin)->post(route('settings.logo.store'), [
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ]);

    $this->actingAs($employee)->get(route('settings.logo.show'))->assertOk();
});

test('the configured default currency pre-fills new invoice, payment, and expense forms', function () {
    Setting::set(SettingKeys::DEFAULT_CURRENCY, 'EUR');

    // Payments' create form only renders its (currency-carrying) body when
    // at least one invoice is eligible for a payment.
    Invoice::factory()->create(['status' => InvoiceStatus::SENT, 'total' => 100, 'balance_due' => 100]);

    $this->actingAs($this->admin)->get(route('invoices.create'))->assertOk()->assertSee('EUR', false);
    $this->actingAs($this->admin)->get(route('payments.create'))->assertOk()->assertSee('EUR', false);
    $this->actingAs($this->admin)->get(route('expenses.create'))->assertOk()->assertSee('EUR', false);
});
