<?php

use App\Models\Product;
use App\Models\User;
use App\Support\Roles;

test('admin can list, search, create, update and delete a product', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    Product::factory()->create(['name' => 'Widget A']);
    Product::factory()->create(['name' => 'Gadget B']);

    $this->actingAs($admin)
        ->get('/products?search=Widget')
        ->assertOk()
        ->assertSee('Widget A')
        ->assertDontSee('Gadget B');

    $response = $this->actingAs($admin)->post('/products', [
        'name' => 'Widget C',
        'sku' => 'SKU-100',
        'unit_price' => 29.99,
        'tax_rate' => 5,
    ]);
    $response->assertRedirect(route('products.index'));

    $product = Product::firstWhere('name', 'Widget C');
    expect($product)->not->toBeNull()
        ->and($product->sku)->toBe('SKU-100');

    $this->actingAs($admin)
        ->put(route('products.update', $product), [
            'name' => 'Widget C (Updated)',
            'unit_price' => 34.99,
        ])
        ->assertRedirect(route('products.index'));

    expect($product->fresh()->name)->toBe('Widget C (Updated)');

    $this->actingAs($admin)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect(Product::find($product->id))->toBeNull();
});

test('creating a product requires a unique name and sku', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);
    Product::factory()->create(['name' => 'Widget A', 'sku' => 'SKU-1']);

    $this->actingAs($admin)
        ->post('/products', ['name' => 'Widget A', 'unit_price' => 10])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->post('/products', ['name' => 'Widget New', 'sku' => 'SKU-1', 'unit_price' => 10])
        ->assertSessionHasErrors('sku');
});

test('an accountant can view products but cannot create, edit, or delete them', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole(Roles::ACCOUNTANT);
    $product = Product::factory()->create();

    $this->actingAs($accountant)->get('/products')->assertOk();
    $this->actingAs($accountant)->get(route('products.create'))->assertForbidden();
    $this->actingAs($accountant)->post('/products', ['name' => 'New Product', 'unit_price' => 10])->assertForbidden();
    $this->actingAs($accountant)->get(route('products.edit', $product))->assertForbidden();
    $this->actingAs($accountant)->put(route('products.update', $product), ['name' => 'x', 'unit_price' => 10])->assertForbidden();
    $this->actingAs($accountant)->delete(route('products.destroy', $product))->assertForbidden();
});

test('a client cannot access the products module at all', function () {
    $client = User::factory()->create();
    $client->assignRole(Roles::CLIENT);

    $this->actingAs($client)->get('/products')->assertForbidden();
});
