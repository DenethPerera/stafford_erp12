# Customer PO upload deployment

No database schema changes are required. Existing POs remain unchanged and are skipped, including revised quantities and sizes. Compare/revision approval is a separate feature.

- AB-FTP uses the first active customer matching the AlphaBroder name or AB reference, as in the earlier lookup. It does not automatically distinguish US and Canada. Verify the customer in Preview before confirming. No customer-ID environment configuration is required.
- Deliver customer files to storage/app/ab_ftp/inbox through your server SFTP arrangement or a separately configured downloader. Automatic remote transfer is not implemented because no endpoint/credentials were provided.
- The application needs access to storage and bootstrap/cache. Keep inbox and import history private, outside public web directories. Back up storage/app/customer-pos/import-history along with application storage.
- AB CSV must use the standard 37 headers in their original order. Only empty trailing columns are accepted. Dates must be real YYYYMMDD dates. Quantity is a positive whole number; unit cost has up to four decimal places. UPC values are accepted as supplied text, including scientific notation; no numeric conversion or barcode reconstruction is performed.
- Refresh Customer PO Upload to see new files, sorted by modification time descending. Preview customer, totals and items before Confirm. Previews expire after 30 minutes.
- Confirm writes new POs through the existing erav models. Existing customer/PO pairs are checked again under a MySQL advisory lock, serialising imports through this service without adding an index/table. Other programs writing those tables must provide their own coordination.
- Each successful confirmation writes a JSON audit file containing filename, SHA-256, user ID, timestamp and saved/skipped counts. Source CSVs stay in the inbox for future Compare. Audit write failure is reported separately after a successful import; it does not roll back committed orders.
- Validate in a separate staging database before production: new PO, repeat file, mixed new/existing POs, revised existing PO (must remain unchanged), malformed CSV, concurrent confirmations and failed insert rollback.

Database-free parser checks: php vendor/phpunit/phpunit/phpunit tests/Unit/CustomerPoUploadParserTest.php --do-not-cache-result
