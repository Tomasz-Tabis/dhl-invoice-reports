<?php

namespace Tests\Feature;

use App\Exceptions\DhlInvoiceParserException;
use App\Models\FailedInvoiceUpload;
use App\Models\InvoiceUpload;
use App\Models\Report;
use App\Models\User;
use App\Services\DhlInvoiceParserService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_registration_is_not_public_and_admin_routes_require_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $managedUser = User::factory()->create(['role' => 'user']);

        $this->get('/register')->assertNotFound();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.users.destroy', $managedUser))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_admin_can_delete_user_and_their_stored_files(): void
    {
        Storage::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $invoiceUpload = $this->createInvoiceUpload($user);
        $report = $this->createReport($user, $invoiceUpload);
        $failedInvoiceUpload = $this->createFailedInvoiceUpload($user);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('invoice_uploads', ['id' => $invoiceUpload->id]);
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('failed_invoice_uploads', ['id' => $failedInvoiceUpload->id]);
        Storage::assertMissing($invoiceUpload->original_pdf_path);
        Storage::assertMissing($report->generated_pdf_path);
        Storage::assertMissing($failedInvoiceUpload->original_pdf_path);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_users_can_only_access_their_own_invoices_and_reports(): void
    {
        Storage::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $invoiceUpload = $this->createInvoiceUpload($owner);
        $report = $this->createReport($owner, $invoiceUpload);

        $this->actingAs($otherUser)
            ->get(route('invoices.show', $invoiceUpload))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->get(route('invoices.download', $invoiceUpload))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('invoices.reports.store', $invoiceUpload), [
                'selected_drivers' => ['STOPS|817893'],
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->get(route('reports.download', $report))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('invoices.destroy', $invoiceUpload))
            ->assertForbidden();
    }

    public function test_generating_multiple_reports_keeps_the_invoice_upload_and_original_pdf(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $invoiceUpload = $this->createInvoiceUpload($user);

        $this->actingAs($user)
            ->post(route('invoices.reports.store', $invoiceUpload), [
                'selected_drivers' => ['STOPS|817893'],
            ])
            ->assertRedirect(route('invoices.show', $invoiceUpload));

        $this->actingAs($user)
            ->post(route('invoices.reports.store', $invoiceUpload), [
                'selected_drivers' => ['STOPS|817893'],
            ])
            ->assertRedirect(route('invoices.show', $invoiceUpload));

        $this->assertDatabaseHas('invoice_uploads', ['id' => $invoiceUpload->id]);
        $this->assertSame(2, Report::where('invoice_upload_id', $invoiceUpload->id)->count());
        Storage::assertExists($invoiceUpload->original_pdf_path);
    }

    public function test_deleting_report_only_removes_generated_pdf_and_report_record(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $invoiceUpload = $this->createInvoiceUpload($user);
        $report = $this->createReport($user, $invoiceUpload);

        $this->actingAs($user)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect();

        $this->assertDatabaseHas('invoice_uploads', ['id' => $invoiceUpload->id]);
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        Storage::assertExists($invoiceUpload->original_pdf_path);
        Storage::assertMissing($report->generated_pdf_path);
    }

    public function test_deleting_invoice_removes_original_pdf_related_reports_and_generated_pdfs(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $invoiceUpload = $this->createInvoiceUpload($user);
        $firstReport = $this->createReport($user, $invoiceUpload, 'reports/first.pdf');
        $secondReport = $this->createReport($user, $invoiceUpload, 'reports/second.pdf');

        $this->actingAs($user)
            ->delete(route('invoices.destroy', $invoiceUpload))
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseMissing('invoice_uploads', ['id' => $invoiceUpload->id]);
        $this->assertDatabaseMissing('reports', ['id' => $firstReport->id]);
        $this->assertDatabaseMissing('reports', ['id' => $secondReport->id]);
        Storage::assertMissing($invoiceUpload->original_pdf_path);
        Storage::assertMissing($firstReport->generated_pdf_path);
        Storage::assertMissing($secondReport->generated_pdf_path);
    }

    public function test_report_generation_requires_selected_driver(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $invoiceUpload = $this->createInvoiceUpload($user);

        $this->actingAs($user)
            ->from(route('invoices.show', $invoiceUpload))
            ->post(route('invoices.reports.store', $invoiceUpload), [])
            ->assertRedirect(route('invoices.show', $invoiceUpload))
            ->assertSessionHasErrors('selected_drivers');
    }

    public function test_failed_invoice_upload_is_saved_when_parser_fails(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $this->mock(DhlInvoiceParserService::class)
            ->shouldReceive('parse')
            ->once()
            ->andThrow(DhlInvoiceParserException::unreadablePdf());

        $this->actingAs($user)
            ->from(route('invoices.upload'))
            ->post(route('invoices.store'), [
                'invoice_pdf' => UploadedFile::fake()->create('broken.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('invoices.upload'))
            ->assertSessionHasErrors([
                'invoice_pdf' => 'De factuur kon niet worden verwerkt, maar het bestand is opgeslagen in mislukte uploads.',
            ]);

        $this->assertDatabaseMissing('invoice_uploads', [
            'user_id' => $user->id,
            'original_pdf_filename' => 'broken.pdf',
        ]);

        $this->assertDatabaseHas('failed_invoice_uploads', [
            'user_id' => $user->id,
            'original_pdf_filename' => 'broken.pdf',
            'error_message' => 'PDF is onleesbaar of bevat geen tekst.',
        ]);

        $failedInvoiceUpload = FailedInvoiceUpload::firstOrFail();
        $this->assertStringStartsWith('failed/', $failedInvoiceUpload->original_pdf_path);
        Storage::assertExists($failedInvoiceUpload->original_pdf_path);
    }

    public function test_users_can_only_access_their_own_failed_invoice_uploads(): void
    {
        Storage::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $failedInvoiceUpload = $this->createFailedInvoiceUpload($owner);

        $this->actingAs($otherUser)
            ->get(route('failed-invoices.index'))
            ->assertOk()
            ->assertDontSee($failedInvoiceUpload->original_pdf_filename);

        $this->actingAs($otherUser)
            ->get(route('failed-invoices.download', $failedInvoiceUpload))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('failed-invoices.destroy', $failedInvoiceUpload))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('failed-invoices.download', $failedInvoiceUpload))
            ->assertOk();
    }

    public function test_deleting_failed_invoice_upload_removes_pdf_and_record(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $failedInvoiceUpload = $this->createFailedInvoiceUpload($user);

        $this->actingAs($user)
            ->delete(route('failed-invoices.destroy', $failedInvoiceUpload))
            ->assertRedirect(route('failed-invoices.index'));

        $this->assertDatabaseMissing('failed_invoice_uploads', ['id' => $failedInvoiceUpload->id]);
        Storage::assertMissing($failedInvoiceUpload->original_pdf_path);
    }

    private function createInvoiceUpload(User $user): InvoiceUpload
    {
        Storage::put('invoices/original.pdf', 'invoice');

        return InvoiceUpload::create([
            'user_id' => $user->id,
            'original_pdf_path' => 'invoices/original.pdf',
            'original_pdf_filename' => 'original.pdf',
            'parsed_data' => $this->parsedData(),
            'week_number' => 19,
            'year' => 2026,
        ]);
    }

    private function createReport(User $user, InvoiceUpload $invoiceUpload, string $path = 'reports/report.pdf'): Report
    {
        Storage::put($path, 'report');

        return Report::create([
            'user_id' => $user->id,
            'invoice_upload_id' => $invoiceUpload->id,
            'original_pdf_path' => $invoiceUpload->original_pdf_path,
            'original_pdf_filename' => $invoiceUpload->original_pdf_filename,
            'generated_pdf_path' => $path,
            'week_number' => $invoiceUpload->week_number,
            'year' => $invoiceUpload->year,
            'selected_drivers' => [
                [
                    'employee_number' => '817893',
                    'name' => 'Mosaab El Fallouchi',
                    'type' => 'STOPS',
                    'hub_code' => 'BRE',
                    'raw_type' => 'BREPAK',
                ],
            ],
        ]);
    }

    private function createFailedInvoiceUpload(User $user): FailedInvoiceUpload
    {
        Storage::put('failed/broken.pdf', 'broken invoice');

        return FailedInvoiceUpload::create([
            'user_id' => $user->id,
            'original_pdf_path' => 'failed/broken.pdf',
            'original_pdf_filename' => 'broken.pdf',
            'error_message' => 'Parser failed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parsedData(): array
    {
        return [
            'week_number' => 19,
            'year' => 2026,
            'drivers' => [
                [
                    'hub_code' => 'BRE',
                    'raw_type' => 'BREPAK',
                    'type' => 'STOPS',
                    'name' => 'Mosaab El Fallouchi',
                    'employee_number' => '817893',
                    'rows' => [
                        [
                            'date' => '05-05-2026',
                            'ma_vr' => 207,
                            'za' => 0,
                            'zo' => 0,
                        ],
                    ],
                    'totals' => [
                        'ma_vr' => 207,
                        'za' => 0,
                        'zo' => 0,
                        'total' => 207,
                    ],
                ],
            ],
            'grand_totals' => [
                'ma_vr' => 207,
                'za' => 0,
                'zo' => 0,
                'total' => 207,
            ],
            'grand_time_totals' => [
                'ma_vr' => '00:00',
                'za' => '00:00',
                'zo' => '00:00',
            ],
        ];
    }
}
