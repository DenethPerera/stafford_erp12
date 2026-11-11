<?php

namespace Tests\Feature;

use App\Models\Forwarder;
use App\Models\ForwarderDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ForwarderDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Exercise the upload flow without using the application's database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');

        Schema::create('forwarders', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone');
            $table->string('cargo_security_certificate')->nullable();
            $table->string('container_inspection_checklist')->nullable();
            $table->string('consolidation_file')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_02_060000_create_forwarder_documents_table.php'))->up();
        (require database_path('migrations/2026_11_02_080000_add_shipment_id_to_forwarder_documents_table.php'))->up();
        Storage::fake('forwarders');
    }

    public function test_assigned_forwarder_can_upload_all_documents_and_admin_can_open_them(): void
    {
        $forwarder = $this->createForwarder();
        $other = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->get(route('forwarder.file-upload'))
            ->assertOk()
            ->assertSee('action="' . route('forwarder.file-upload.store') . '"', false)
            ->assertSee('name="shipment_id"', false)
            ->assertSee('No documents uploaded yet.')
            ->assertSee('enctype="multipart/form-data"', false);

        $this->post(route('forwarder.file-upload.store'), [
            // A submitted ID must never override the account's assignment.
            'forwarder_id' => $other->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => $this->pdf('cargo.pdf'),
            'container_inspection_checklist' => $this->pdf('inspection.pdf'),
            'consolidation_file' => $this->pdf('consolidation.pdf'),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('forwarder.file-upload'))
            ->assertSessionHas('success', 'Documents uploaded successfully.');

        $document = ForwarderDocument::sole();
        $this->assertEquals($forwarder->id, $document->forwarder_id);
        $this->assertSame('SHIP-0007', $document->shipment_id);
        $this->assertTrue($document->forwarder->is($forwarder));
        foreach (['cargo_security_certificate', 'container_inspection_checklist', 'consolidation_file'] as $field) {
            $this->assertStringStartsWith($forwarder->id . '/' . $field . '/', $document->$field);
            Storage::disk('forwarders')->assertExists($document->$field);
            $this->assertNull($forwarder->fresh()->$field);
        }
        $this->assertDatabaseMissing('forwarder_documents', ['forwarder_id' => $other->id]);

        $this->get(route('forwarder.file-upload'))
            ->assertOk()->assertSee('Uploaded Documents')->assertSee('SHIP-0007');

        foreach (['cargo_security_certificate', 'container_inspection_checklist', 'consolidation_file'] as $field) {
            $parameters = [
                'forwarder' => $forwarder->id,
                'document' => $field,
                'forwarder_document_id' => $document->id,
            ];
            $this->get(route('forwarder.documents.show', $parameters))
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $download = $this->get(route('forwarder.documents.show', $parameters + ['download' => 1]));
            $download->assertOk()->assertDownload(basename($document->$field));
            $this->assertSame("%PDF-1.4\nForwarder document\n%%EOF", $download->streamedContent());
        }

        $this->actingAs($this->userWithRole('super_admin'));
        $this->get(route('forwarders.index'))
            ->assertOk()
            ->assertSee('SHIP-0007')
            ->assertSee(route('forwarders.document', [
                'forwarder' => $forwarder->id,
                'document' => 'cargo_security_certificate',
            ]));

        $this->get(route('forwarders.document', [
            'forwarder' => $forwarder->id,
            'document' => 'cargo_security_certificate',
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $response = $this->get(route('forwarders.document', [
            'forwarder' => $forwarder->id,
            'document' => 'cargo_security_certificate',
            'download' => 1,
        ]));
        $response->assertOk()->assertDownload(basename($document->cargo_security_certificate));
        $this->assertSame("%PDF-1.4\nForwarder document\n%%EOF", $response->streamedContent());
    }

    public function test_partial_uploads_reuse_the_row_and_preserve_other_document_paths(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->post(route('forwarder.file-upload.store'), [
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => $this->pdf('cargo.pdf'),
        ])->assertSessionHasNoErrors();
        $original = ForwarderDocument::sole();

        $this->post(route('forwarder.file-upload.store'), [
            'shipment_id' => 'SHIP-0007',
            'consolidation_file' => $this->pdf('consolidation.pdf'),
        ])->assertSessionHasNoErrors();
        $updated = ForwarderDocument::sole();
        $this->assertSame($original->id, $updated->id);
        $this->assertSame($original->cargo_security_certificate, $updated->cargo_security_certificate);
        $this->assertNotNull($updated->consolidation_file);
        $this->assertNull($updated->container_inspection_checklist);

        $this->post(route('forwarder.file-upload.store'), [
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => $this->pdf('replacement.pdf'),
        ])->assertSessionHasNoErrors();
        $replacement = ForwarderDocument::sole();
        $this->assertSame($updated->consolidation_file, $replacement->consolidation_file);
        $this->assertNotSame($original->cargo_security_certificate, $replacement->cargo_security_certificate);
        Storage::disk('forwarders')->assertExists($original->cargo_security_certificate);
    }

    public function test_accounts_assigned_to_different_forwarders_save_separate_rows(): void
    {
        $first = $this->createForwarder();
        $second = $this->createForwarder();

        foreach ([$first, $second] as $forwarder) {
            $this->actingAs($this->userWithRole('forwarder', $forwarder->id));
            $this->post(route('forwarder.file-upload.store'), [
                'shipment_id' => 'SHIP-0007',
                'cargo_security_certificate' => $this->pdf('cargo.pdf'),
            ])->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('forwarder_documents', 2);
        $this->assertNotSame(
            $first->documentFiles->sole()->cargo_security_certificate,
            $second->documentFiles->sole()->cargo_security_certificate
        );
    }

    public function test_validation_rejects_invalid_files_before_storing_any_document(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->post(route('forwarder.file-upload.store'), [
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => $this->pdf('valid.pdf'),
            'container_inspection_checklist' => UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
        ])->assertSessionHasErrors('container_inspection_checklist');

        $this->post(route('forwarder.file-upload.store'), [
            'shipment_id' => 'SHIP-0007',
            'consolidation_file' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('consolidation_file');

        $this->assertDatabaseCount('forwarder_documents', 0);
        $this->assertSame([], Storage::disk('forwarders')->allFiles());
    }

    public function test_one_forwarder_can_keep_separate_documents_for_multiple_shipments(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        foreach (['SHIP-0007', 'SHIP-0008'] as $shipmentId) {
            $this->post(route('forwarder.file-upload.store'), [
                'shipment_id' => $shipmentId,
                'cargo_security_certificate' => $this->pdf('cargo.pdf'),
            ])->assertSessionHasNoErrors();
        }

        $first = ForwarderDocument::where('shipment_id', 'SHIP-0007')->sole();
        $second = ForwarderDocument::where('shipment_id', 'SHIP-0008')->sole();
        $this->assertNotSame($first->id, $second->id);

        $this->post(route('forwarder.file-upload.store'), [
            'shipment_id' => 'SHIP-0007',
            'consolidation_file' => $this->pdf('consolidation.pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('forwarder_documents', 2);
        $this->assertSame($first->cargo_security_certificate, $first->fresh()->cargo_security_certificate);
        $this->assertNotNull($first->fresh()->consolidation_file);
        $this->assertSame($second->cargo_security_certificate, $second->fresh()->cargo_security_certificate);
        $this->assertNull($second->fresh()->consolidation_file);
    }

    public function test_shipment_id_is_required_and_must_be_text_within_the_length_limit(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        foreach ([null, '', str_repeat('A', 256), ['SHIP-0007']] as $shipmentId) {
            $this->post(route('forwarder.file-upload.store'), [
                'shipment_id' => $shipmentId,
                'cargo_security_certificate' => $this->pdf('cargo.pdf'),
            ])->assertSessionHasErrors('shipment_id');
        }

        $this->assertDatabaseCount('forwarder_documents', 0);
        $this->assertSame([], Storage::disk('forwarders')->allFiles());
    }

    public function test_admin_links_open_the_selected_shipment_record_only_within_its_forwarder(): void
    {
        $forwarder = $this->createForwarder();
        $other = $this->createForwarder();
        $records = [];

        foreach (['SHIP-0007', 'SHIP-0008'] as $shipmentId) {
            $path = $forwarder->id . '/cargo_security_certificate/' . $shipmentId . '.pdf';
            $records[] = ForwarderDocument::create([
                'forwarder_id' => $forwarder->id,
                'shipment_id' => $shipmentId,
                'cargo_security_certificate' => $path,
            ]);
            Storage::disk('forwarders')->put($path, "%PDF-1.4\n" . $shipmentId . "\n%%EOF");
        }

        $this->actingAs($this->userWithRole('super_admin'));
        $response = $this->get(route('forwarders.index'));
        $response->assertOk()->assertSee('SHIP-0007')->assertSee('SHIP-0008');

        foreach ($records as $record) {
            $parameters = [
                'forwarder' => $forwarder->id,
                'document' => 'cargo_security_certificate',
                'forwarder_document_id' => $record->id,
            ];
            $response->assertSee(route('forwarders.document', $parameters));
            $download = $this->get(route('forwarders.document', $parameters + ['download' => 1]));
            $download->assertOk()->assertDownload(basename($record->cargo_security_certificate));
            $this->assertSame("%PDF-1.4\n" . $record->shipment_id . "\n%%EOF", $download->streamedContent());
        }

        $this->get(route('forwarders.document', [
            'forwarder' => $other->id,
            'document' => 'cargo_security_certificate',
            'forwarder_document_id' => $records[0]->id,
        ]))->assertNotFound();

        $this->get(route('forwarders.document', [
            'forwarder' => $forwarder->id,
            'document' => 'container_inspection_checklist',
            'forwarder_document_id' => $records[0]->id,
        ]))->assertNotFound();
    }

    public function test_migration_preserves_existing_paths_and_enforces_one_record_per_forwarder_and_shipment(): void
    {
        $migration = require database_path('migrations/2026_11_02_080000_add_shipment_id_to_forwarder_documents_table.php');
        $migration->down();
        $forwarder = $this->createForwarder();
        $legacy = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'cargo_security_certificate' => 'cargo_security_certificate/legacy.pdf',
        ]);

        $migration->up();

        $this->assertSame($legacy->cargo_security_certificate, $legacy->fresh()->cargo_security_certificate);
        $this->assertNull($legacy->fresh()->shipment_id);

        foreach (['SHIP-0007', 'SHIP-0008'] as $shipmentId) {
            ForwarderDocument::create([
                'forwarder_id' => $forwarder->id,
                'shipment_id' => $shipmentId,
            ]);
        }
        $this->assertDatabaseCount('forwarder_documents', 3);

        $this->expectException(\Illuminate\Database\QueryException::class);
        ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
        ]);
    }

    public function test_contact_details_and_shipment_documents_have_separate_lists(): void
    {
        $alpha = $this->createForwarder(['company_name' => 'Alpha Forwarding']);
        $beta = $this->createForwarder(['company_name' => 'Beta Forwarding']);
        $complete = ForwarderDocument::create([
            'forwarder_id' => $alpha->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'alpha/cargo.pdf',
            'container_inspection_checklist' => 'alpha/inspection.pdf',
            'consolidation_file' => 'alpha/consolidation.pdf',
        ]);
        ForwarderDocument::create([
            'forwarder_id' => $alpha->id,
            'shipment_id' => 'SHIP-0008',
            'cargo_security_certificate' => 'alpha/another-cargo.pdf',
        ]);
        ForwarderDocument::create([
            'forwarder_id' => $beta->id,
            'shipment_id' => 'SHIP-0007',
            'consolidation_file' => 'beta/consolidation.pdf',
        ]);
        $this->actingAs($this->userWithRole('super_admin'));

        $response = $this->get(route('forwarders.index'));
        $response->assertOk()->assertSee('Saved Forwarders')->assertSee('Forwarder Documents');

        $html = new \DOMDocument();
        $html->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($html);
        $details = $xpath->query('//table[@id="forwarder-details-table"]')->item(0);
        $this->assertCount(5, $details->getElementsByTagName('th'));
        $this->assertStringNotContainsString('Shipment ID', $details->textContent);
        $this->assertStringNotContainsString('Cargo Security Certificate', $details->textContent);
        $this->assertStringContainsString('Test Contact', $details->textContent);
        $this->assertCount(2, $xpath->query('//table[@id="forwarder-details-table"]/tbody/tr'));

        $documentRows = $xpath->query('//table[@id="forwarder-documents-table"]/tbody/tr');
        $this->assertCount(3, $documentRows);
        $expected = [
            ['Beta Forwarding', 'SHIP-0007'],
            ['Alpha Forwarding', 'SHIP-0008'],
            ['Alpha Forwarding', 'SHIP-0007'],
        ];
        foreach ($documentRows as $index => $row) {
            $cells = $row->getElementsByTagName('td');
            $this->assertCount(6, $cells);
            $this->assertSame($expected[$index][0], trim($cells->item(0)->textContent));
            $this->assertSame($expected[$index][1], trim($cells->item(1)->textContent));
        }
        $this->assertCount(6, $documentRows->item(2)->getElementsByTagName('a'));
        $this->assertStringContainsString('Not uploaded', $documentRows->item(1)->textContent);
        $response->assertSee(route('forwarders.document', [
            'forwarder' => $alpha->id,
            'document' => 'cargo_security_certificate',
            'forwarder_document_id' => $complete->id,
        ]));
    }

    public function test_forwarder_and_document_lists_paginate_independently(): void
    {
        $firstForwarderId = null;
        for ($number = 1; $number <= 11; $number++) {
            $forwarder = $this->createForwarder(['company_name' => 'Forwarder ' . $number]);
            $firstForwarderId = $firstForwarderId ?? $forwarder->id;
            ForwarderDocument::create([
                'forwarder_id' => $forwarder->id,
                'shipment_id' => 'SHIP-0007',
                'cargo_security_certificate' => $forwarder->id . '/cargo.pdf',
            ]);
        }
        ForwarderDocument::create([
            'forwarder_id' => $firstForwarderId,
            'shipment_id' => 'SHIP-0008',
            'cargo_security_certificate' => $firstForwarderId . '/new-cargo.pdf',
        ]);
        $this->actingAs($this->userWithRole('super_admin'));

        $firstPage = $this->get(route('forwarders.index'))->assertOk();
        $this->assertSame(11, $firstPage->viewData('forwarders')->total());
        $this->assertSame(12, $firstPage->viewData('documents')->total());
        $this->assertFalse($firstPage->viewData('forwarders')->contains('id', $firstForwarderId));
        $this->assertTrue($firstPage->viewData('documents')->contains('forwarder_id', $firstForwarderId));

        $secondPage = $this->get(route('forwarders.index', [
            'page' => 2,
            'documents_page' => 2,
        ]))->assertOk();
        $forwarders = $secondPage->viewData('forwarders');
        $documents = $secondPage->viewData('documents');
        $this->assertSame(2, $forwarders->currentPage());
        $this->assertSame(2, $documents->currentPage());
        $this->assertCount(1, $forwarders);
        $this->assertCount(2, $documents);

        parse_str(parse_url($forwarders->previousPageUrl(), PHP_URL_QUERY), $forwarderParameters);
        parse_str(parse_url($documents->previousPageUrl(), PHP_URL_QUERY), $documentParameters);
        $this->assertSame('2', $forwarderParameters['documents_page']);
        $this->assertSame('2', $documentParameters['page']);
    }

    public function test_document_search_matches_company_names_or_shipment_ids_without_filtering_contacts(): void
    {
        $dhl = $this->createForwarder(['company_name' => 'DHL Logistics']);
        $ups = $this->createForwarder(['company_name' => 'UPS Forwarding']);
        $first = ForwarderDocument::create(['forwarder_id' => $dhl->id, 'shipment_id' => 'AF-00004']);
        $second = ForwarderDocument::create(['forwarder_id' => $dhl->id, 'shipment_id' => 'AF-00005']);
        $third = ForwarderDocument::create(['forwarder_id' => $ups->id, 'shipment_id' => 'AF-00004']);
        $this->actingAs($this->userWithRole('super_admin'));

        $searches = [
            ['DHL', [$first->id, $second->id]],
            ['00004', [$first->id, $third->id]],
            ['  DHL  ', [$first->id, $second->id]],
        ];
        foreach ($searches as [$term, $expectedIds]) {
            $response = $this->get(route('forwarders.index', ['document_search' => $term]));
            $response->assertOk()->assertSee('Search documents');
            $this->assertEqualsCanonicalizing(
                $expectedIds,
                $response->viewData('documents')->pluck('id')->all()
            );
            $this->assertSame(2, $response->viewData('forwarders')->total());
            $this->assertSame(trim($term), $response->viewData('documentSearch'));
        }
    }

    public function test_empty_search_restores_all_documents_and_unmatched_search_shows_a_message(): void
    {
        $forwarder = $this->createForwarder();
        ForwarderDocument::create(['forwarder_id' => $forwarder->id, 'shipment_id' => 'AF-00004']);
        $this->actingAs($this->userWithRole('super_admin'));

        $notFound = $this->get(route('forwarders.index', ['document_search' => 'NoMatch']));
        $notFound->assertOk()->assertSee('No documents match your search.');
        $this->assertSame(0, $notFound->viewData('documents')->total());

        foreach ([[], ['document_search' => '   ']] as $parameters) {
            $response = $this->get(route('forwarders.index', $parameters))->assertOk();
            $this->assertSame(1, $response->viewData('documents')->total());
            $this->assertSame('', $response->viewData('documentSearch'));
        }
    }

    public function test_document_search_is_preserved_when_paging_through_matches(): void
    {
        $dhl = $this->createForwarder(['company_name' => 'DHL Logistics']);
        $other = $this->createForwarder(['company_name' => 'Other Forwarding']);
        for ($number = 1; $number <= 12; $number++) {
            ForwarderDocument::create([
                'forwarder_id' => $dhl->id,
                'shipment_id' => sprintf('AF-%05d', $number),
            ]);
        }
        ForwarderDocument::create(['forwarder_id' => $other->id, 'shipment_id' => 'OTHER-SHIPMENT']);
        $this->actingAs($this->userWithRole('super_admin'));

        $response = $this->get(route('forwarders.index', [
            'document_search' => 'DHL',
            'documents_page' => 2,
        ]));
        $response->assertOk()->assertDontSee('OTHER-SHIPMENT');
        $documents = $response->viewData('documents');
        $this->assertSame(12, $documents->total());
        $this->assertCount(2, $documents);
        $this->assertSame(2, $documents->currentPage());
        parse_str(parse_url($documents->previousPageUrl(), PHP_URL_QUERY), $parameters);
        $this->assertSame('DHL', $parameters['document_search']);
        $this->assertSame('1', $parameters['documents_page']);
    }

    public function test_document_search_rejects_arrays_and_overlong_input(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));

        foreach ([['DHL'], str_repeat('A', 256)] as $search) {
            $this->from(route('forwarders.index'))
                ->get(route('forwarders.index', ['document_search' => $search]))
                ->assertRedirect(route('forwarders.index'))
                ->assertSessionHasErrors('document_search');
        }
    }

    public function test_forwarder_upload_page_lists_only_its_assigned_company_shipments(): void
    {
        $forwarder = $this->createForwarder();
        $other = $this->createForwarder();
        $first = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'cargo.pdf',
            'container_inspection_checklist' => 'inspection.pdf',
            'consolidation_file' => 'consolidation.pdf',
        ]);
        $second = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0008',
            'cargo_security_certificate' => 'another-cargo.pdf',
        ]);
        $hidden = ForwarderDocument::create([
            'forwarder_id' => $other->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'other-company.pdf',
        ]);
        ForwarderDocument::create([
            'forwarder_id' => $other->id,
            'shipment_id' => 'OTHER-SHIPMENT',
        ]);
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        // A query parameter must not change which company is listed.
        $response = $this->get(route('forwarder.file-upload', ['forwarder_id' => $other->id]));
        $response->assertOk()->assertSee('SHIP-0007')->assertSee('SHIP-0008')
            ->assertDontSee('OTHER-SHIPMENT');
        $this->assertSame(2, $response->viewData('documents')->total());
        $this->assertFalse($response->viewData('documents')->contains('id', $hidden->id));

        foreach ([$first, $second] as $record) {
            $response->assertSee(route('forwarder.documents.show', [
                'forwarder' => $forwarder->id,
                'document' => 'cargo_security_certificate',
                'forwarder_document_id' => $record->id,
            ]));
        }

        $html = new \DOMDocument();
        $html->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $rows = (new \DOMXPath($html))->query('//table[@id="forwarder-uploaded-documents-table"]/tbody/tr');
        $this->assertCount(2, $rows);
        $this->assertCount(5, $rows->item(0)->getElementsByTagName('td'));
        $this->assertStringContainsString('SHIP-0008', $rows->item(0)->textContent);
        $this->assertStringContainsString('Not uploaded', $rows->item(0)->textContent);
        $this->assertCount(6, $rows->item(1)->getElementsByTagName('a'));
    }

    public function test_forwarder_document_links_enforce_assignment_and_record_ownership(): void
    {
        $forwarder = $this->createForwarder();
        $other = $this->createForwarder();
        $owned = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'cargo.pdf',
        ]);
        $hidden = ForwarderDocument::create([
            'forwarder_id' => $other->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'other.pdf',
        ]);
        Storage::disk('forwarders')->put('cargo.pdf', "%PDF-1.4\nOwned\n%%EOF");
        Storage::disk('forwarders')->put('other.pdf', "%PDF-1.4\nOther\n%%EOF");
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->get(route('forwarder.documents.show', [
            'forwarder' => $other->id,
            'document' => 'cargo_security_certificate',
            'forwarder_document_id' => $hidden->id,
            'download' => 1,
        ]))->assertForbidden();
        $this->get(route('forwarder.documents.show', [
            'forwarder' => $forwarder->id,
            'document' => 'cargo_security_certificate',
            'forwarder_document_id' => $hidden->id,
        ]))->assertNotFound();
        $this->get(route('forwarder.documents.show', [
            'forwarder' => $forwarder->id,
            'document' => 'company_name',
            'forwarder_document_id' => $owned->id,
        ]))->assertNotFound();
        $this->get(route('forwarder.documents.show', [
            'forwarder' => $forwarder->id,
            'document' => 'container_inspection_checklist',
            'forwarder_document_id' => $owned->id,
        ]))->assertNotFound();

        $this->actingAs($this->userWithRole('forwarder'));
        $this->get(route('forwarder.documents.show', [
            'forwarder' => $forwarder->id,
            'document' => 'cargo_security_certificate',
            'forwarder_document_id' => $owned->id,
        ]))->assertForbidden();

        foreach (['factory', 'lab', 'super_admin'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->get(route('forwarder.documents.show', [
                'forwarder' => $forwarder->id,
                'document' => 'cargo_security_certificate',
                'forwarder_document_id' => $owned->id,
            ]))->assertForbidden();
        }
    }

    public function test_forwarder_uploaded_documents_list_paginates_by_shipment(): void
    {
        $forwarder = $this->createForwarder();
        for ($number = 1; $number <= 11; $number++) {
            ForwarderDocument::create([
                'forwarder_id' => $forwarder->id,
                'shipment_id' => sprintf('SHIP-%04d', $number),
            ]);
        }
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $firstPage = $this->get(route('forwarder.file-upload'))->assertOk();
        $this->assertSame(11, $firstPage->viewData('documents')->total());
        $this->assertCount(10, $firstPage->viewData('documents'));
        $firstPage->assertDontSee('SHIP-0001');

        $secondPage = $this->get(route('forwarder.file-upload', ['page' => 2]))->assertOk();
        $this->assertCount(1, $secondPage->viewData('documents'));
        $secondPage->assertSee('SHIP-0001')->assertDontSee('SHIP-0002');
    }

    public function test_empty_upload_does_not_create_a_document_record(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->post(route('forwarder.file-upload.store'), ['shipment_id' => 'SHIP-0007'])
            ->assertRedirect(route('forwarder.file-upload'))
            ->assertSessionHas('error', 'Please select at least one document.');

        $this->assertDatabaseCount('forwarder_documents', 0);
    }

    public function test_unassigned_or_deleted_forwarder_assignments_cannot_upload(): void
    {
        foreach ([null, 999] as $forwarderId) {
            $this->actingAs($this->userWithRole('forwarder', $forwarderId));
            $this->get(route('forwarder.file-upload'))->assertForbidden();
            $this->post(route('forwarder.file-upload.store'), [
                'shipment_id' => 'SHIP-0007',
                'cargo_security_certificate' => $this->pdf('cargo.pdf'),
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('forwarder_documents', 0);
        $this->assertSame([], Storage::disk('forwarders')->allFiles());
    }

    public function test_guests_and_other_roles_cannot_use_the_upload_route(): void
    {
        $this->get(route('forwarder.file-upload'))
            ->assertRedirect(route('forwarder.login'));
        $this->post(route('forwarder.file-upload.store'))
            ->assertRedirect(route('forwarder.login'));

        foreach (['factory', 'lab', 'super_admin'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->get(route('forwarder.file-upload'))->assertForbidden();
            $this->post(route('forwarder.file-upload.store'))->assertForbidden();
        }

        $this->assertDatabaseCount('forwarder_documents', 0);
    }

    public function test_admin_can_still_open_legacy_paths_when_the_new_record_has_no_path(): void
    {
        $path = 'cargo_security_certificate/legacy.pdf';
        $forwarder = $this->createForwarder(['cargo_security_certificate' => $path]);
        ForwarderDocument::create(['forwarder_id' => $forwarder->id]);
        Storage::disk('forwarders')->put($path, "%PDF-1.4\nLegacy document\n%%EOF");
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('forwarders.document', [
            'forwarder' => $forwarder->id,
            'document' => 'cargo_security_certificate',
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('forwarders.document', [
            'forwarder' => $forwarder->id,
            'document' => 'company_name',
        ]))->assertNotFound();
    }

    public function test_newly_uploaded_files_are_removed_if_the_database_save_fails(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));
        Event::listen('eloquent.creating: ' . ForwarderDocument::class, function () {
            throw new \RuntimeException('Simulated document save failure.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->post(route('forwarder.file-upload.store'), [
                'shipment_id' => 'SHIP-0007',
                'cargo_security_certificate' => $this->pdf('cargo.pdf'),
            ]);
            $this->fail('The simulated database error should have been raised.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated document save failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('forwarder_documents', 0);
        $this->assertSame([], Storage::disk('forwarders')->allFiles());
    }

    private function createForwarder(array $attributes = []): Forwarder
    {
        return Forwarder::create(array_merge([
            'company_name' => 'Test Forwarder',
            'contact_person' => 'Test Contact',
            'email' => 'forwarder@example.com',
            'phone' => '0771234567',
        ], $attributes));
    }

    private function userWithRole(string $role, ?int $forwarderId = null): User
    {
        $user = new User(['name' => 'Test User', 'forwarder_id' => $forwarderId]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));

        return $user;
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\nForwarder document\n%%EOF");
    }
}
