<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoiceBillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('Receptionist');
        $this->patient = $this->patientFor(Hospital::default());
    }

    public function test_receptionist_can_view_billing_and_create_an_invoice(): void
    {
        $this->actingAs($this->receptionist)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Index')
                ->has('invoices.data', 0)
                ->where('can.create', true));

        $this->actingAs($this->receptionist)
            ->get(route('billing.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Create')
                ->has('patients', 1));

        $response = $this->actingAs($this->receptionist)->post(route('billing.store'), [
            'patient_id' => $this->patient->id,
            'issued_at' => today()->toDateString(),
            'due_at' => today()->addDays(14)->toDateString(),
            'discount' => '5.00',
            'notes' => 'Please bring the insurance card.',
            'items' => [
                ['description' => 'Consultation', 'quantity' => 1, 'unit_price' => '50.00'],
                ['description' => 'Laboratory test', 'quantity' => 2, 'unit_price' => '12.50'],
            ],
        ]);

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('billing.show', $invoice))
            ->assertSessionHas('success');

        $this->assertSame($this->patient->hospital_id, $invoice->hospital_id);
        $this->assertSame('75.00', $invoice->subtotal);
        $this->assertSame('5.00', $invoice->discount);
        $this->assertSame('70.00', $invoice->total);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertCount(2, $invoice->items);
        $this->assertSame('25.00', $invoice->items->last()->amount);
    }

    public function test_invoice_discount_cannot_exceed_the_subtotal(): void
    {
        $this->actingAs($this->receptionist)
            ->from(route('billing.create'))
            ->post(route('billing.store'), [
                'patient_id' => $this->patient->id,
                'issued_at' => today()->toDateString(),
                'discount' => '20.00',
                'items' => [
                    ['description' => 'Consultation', 'quantity' => 1, 'unit_price' => '10.00'],
                ],
            ])
            ->assertSessionHasErrors('discount');

        $this->assertSame(0, Invoice::count());
    }

    public function test_payment_updates_invoice_balance_and_status(): void
    {
        $invoice = $this->invoiceFor($this->patient, '100.00');

        $this->actingAs($this->receptionist)
            ->post(route('billing.payments.store', $invoice), [
                'amount' => '40.00',
                'method' => 'card',
                'reference' => 'POS-123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('40.00', $invoice->amount_paid);
        $this->assertSame(60.0, $invoice->balance);
        $this->assertSame('partial', $invoice->status);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'recorded_by' => $this->receptionist->id,
            'amount' => '40.00',
            'method' => 'card',
            'reference' => 'POS-123',
        ]);

        $this->actingAs($this->receptionist)
            ->get(route('billing.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Show')
                ->where('invoice.balance', 60)
                ->has('invoice.payments', 1)
                ->where('invoice.payments.0.recorder.name', $this->receptionist->name));

        $this->actingAs($this->receptionist)
            ->post(route('billing.payments.store', $invoice), [
                'amount' => '60.00',
                'method' => 'cash',
            ]);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $invoice->fresh()->balance);
    }

    public function test_payment_cannot_exceed_the_outstanding_balance(): void
    {
        $invoice = $this->invoiceFor($this->patient, '25.00');

        $this->actingAs($this->receptionist)
            ->from(route('billing.show', $invoice))
            ->post(route('billing.payments.store', $invoice), [
                'amount' => '25.01',
                'method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('0.00', $invoice->fresh()->amount_paid);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_receptionist_can_void_an_unpaid_invoice_but_not_a_paid_one(): void
    {
        $unpaid = $this->invoiceFor($this->patient, '25.00');

        $this->actingAs($this->receptionist)
            ->post(route('billing.void', $unpaid))
            ->assertSessionHas('success');

        $this->assertSame('void', $unpaid->fresh()->status);

        $paid = $this->invoiceFor($this->patient, '25.00');
        $this->actingAs($this->receptionist)->post(route('billing.payments.store', $paid), [
            'amount' => '25.00',
            'method' => 'cash',
        ]);

        $this->actingAs($this->receptionist)
            ->from(route('billing.show', $paid))
            ->post(route('billing.void', $paid))
            ->assertSessionHasErrors('invoice');

        $this->assertSame('paid', $paid->fresh()->status);
    }

    public function test_payment_analytics_summarizes_collections_in_the_selected_date_range(): void
    {
        $invoice = $this->invoiceFor($this->patient, '100.00');
        $invoice->payments()->create([
            'recorded_by' => $this->receptionist->id,
            'amount' => '25.00',
            'method' => 'cash',
            'paid_at' => today()->setTime(10, 0),
        ]);
        $invoice->payments()->create([
            'recorded_by' => $this->receptionist->id,
            'amount' => '10.00',
            'method' => 'card',
            'paid_at' => today()->subDays(2)->setTime(10, 0),
        ]);

        $this->actingAs($this->receptionist)
            ->get(route('billing.analytics', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Analytics')
                ->where('summary.paymentCount', 1)
                ->where('summary.collected', 25)
                ->where('summary.averagePayment', 25)
                ->where('summary.invoiceCount', 1)
                ->where('summary.totalInvoiced', 100)
                ->where('summary.collectionRate', 0)
                ->where('invoiceStatuses.0.status', 'unpaid')
                ->where('invoiceStatuses.0.invoiceCount', 1)
                ->where('methods.0.method', 'cash')
                ->where('methods.0.total', 25)
                ->has('daily', 1)
                ->has('payments.data', 1)
                ->where('payments.data.0.invoice.patient.first_name', 'Ada'));
    }

    public function test_payment_analytics_can_filter_all_payment_metrics_by_method(): void
    {
        $invoice = $this->invoiceFor($this->patient, '100.00');
        $invoice->payments()->create([
            'recorded_by' => $this->receptionist->id,
            'amount' => '25.00',
            'method' => 'cash',
            'paid_at' => today()->setTime(10, 0),
        ]);
        $invoice->payments()->create([
            'recorded_by' => $this->receptionist->id,
            'amount' => '15.00',
            'method' => 'card',
            'paid_at' => today()->setTime(11, 0),
        ]);

        $this->actingAs($this->receptionist)
            ->get(route('billing.analytics', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
                'method' => 'cash',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Analytics')
                ->where('filters.method', 'cash')
                ->where('paymentMethods', ['cash', 'card', 'transfer', 'insurance'])
                ->where('summary.paymentCount', 1)
                ->where('summary.collected', 25)
                ->where('summary.averagePayment', 25)
                ->where('methods.0.method', 'cash')
                ->where('methods.0.total', 25)
                ->has('methods', 1)
                ->where('daily.0.total', 25)
                ->has('payments.data', 1)
                ->where('payments.data.0.method', 'cash')
                ->where('summary.totalInvoiced', 100));
    }

    public function test_payment_analytics_rejects_an_inverted_date_range(): void
    {
        $this->actingAs($this->receptionist)
            ->from(route('billing.analytics'))
            ->get(route('billing.analytics', [
                'from' => today()->toDateString(),
                'to' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('to');
    }

    public function test_payment_analytics_rejects_an_unknown_payment_method(): void
    {
        $this->actingAs($this->receptionist)
            ->from(route('billing.analytics'))
            ->get(route('billing.analytics', ['method' => 'crypto']))
            ->assertSessionHasErrors('method');
    }

    public function test_billing_is_scoped_to_the_active_hospital_and_manage_permission(): void
    {
        $otherHospital = Hospital::create(['name' => 'Other Hospital', 'is_active' => false]);
        $otherPatient = $this->patientFor($otherHospital, 'MRN-OTHER-00001');
        $foreignInvoice = $this->invoiceFor($otherPatient, '10.00');

        $this->actingAs($this->receptionist)
            ->get(route('billing.show', $foreignInvoice))
            ->assertNotFound();

        $doctor = User::factory()->create();
        $doctor->assignRole('Doctor');

        $this->actingAs($doctor)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Index')
                ->where('can.create', false));

        $invoice = $this->invoiceFor($this->patient, '10.00');
        $this->actingAs($doctor)
            ->post(route('billing.payments.store', $invoice), [
                'amount' => '1.00',
                'method' => 'cash',
            ])
            ->assertForbidden();
    }

    private function patientFor(Hospital $hospital, string $mrn = 'MRN-BILLING-00001'): Patient
    {
        return Patient::create([
            'hospital_id' => $hospital->id,
            'mrn' => $mrn,
            'first_name' => 'Ada',
            'last_name' => 'Okafor',
            'dob' => '1990-01-01',
            'gender' => 'Female',
            'phone' => '08030000000',
            'address' => '1 Clinic Road',
        ]);
    }

    private function invoiceFor(Patient $patient, string $total): Invoice
    {
        return Invoice::create([
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-TEST-'.strtoupper(fake()->unique()->bothify('??????')),
            'status' => 'unpaid',
            'subtotal' => $total,
            'discount' => '0.00',
            'total' => $total,
            'amount_paid' => '0.00',
            'issued_at' => today(),
        ]);
    }
}
