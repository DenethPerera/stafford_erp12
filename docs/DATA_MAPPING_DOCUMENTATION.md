# Data Mapping & Architecture Documentation

This document provides a comprehensive reference for all data fields validated in `StaffordQualificationController::validateCamelCase()`, including:
1. **Source ERP Tables and Columns** (where data originates during auto-fill/lookup)
2. **Target Database Tables and Columns** (where data is stored in the application database)
3. **Field Transformations & Business Logic** (snake_case conversion, regex extraction, fallbacks)

---

## 1. High-Level Architecture & Database Connections

The system communicates between two distinct database connections:

| Connection Name | Configuration Key | Database Type | Primary Purpose |
| :--- | :--- | :--- | :--- |
| **`erav`** | `DB_ERAV_*` / `config/database.php` | ERP MySQL DB | Read-only ERP master data (Purchase Orders, Styles, Branches) |
| **`mysql`** *(default)* | `DB_*` / `config/database.php` | Local MySQL DB | Application records (Qualifications/Certificates, Lab Test Reports, Labs) |

```mermaid
flowchart TD
    subgraph ERAV_ERP ["ERAV ERP Database (connection: erav)"]
        porder_pdf_list["porder_pdf_list (PO Headers)"]
        porder_pdf_info["porder_pdf_info (PO Line Items)"]
        prodorder_list["prodorder_list (Production Orders)"]
        mbranchlist["mbranchlist (Factory/Branch Master)"]
        prodstyle_list["prodstyle_list (Product Style Master)"]
    end

    subgraph APP_DB ["Application Database (connection: mysql)"]
        stafford_qualifications["stafford_qualifications (Main Certificate Table)"]
        labs["labs (Testing Laboratories)"]
    end

    porder_pdf_list -->|eravPoLookup / ApiIntegrationService| FormPayload["Frontend Form (CamelCase Request)"]
    porder_pdf_info --> FormPayload
    prodorder_list --> FormPayload
    mbranchlist --> FormPayload
    prodstyle_list --> FormPayload

    FormPayload -->|validateCamelCase()| Validator["Laravel Request Validator"]
    Validator -->|mapToSnakeCase()| stafford_qualifications
    labs -.->|lab_id (Foreign Key)| stafford_qualifications
```

---

## 2. ERP Source Tables Reference

| Model | Table Name | Connection | Role in Data Flow |
| :--- | :--- | :--- | :--- |
| `App\Models\Erav\EravPurchaseOrder` | **`porder_pdf_list`** | `erav` | Main PO header: `pdf_po_refno`, `porder_date`, `customer_name`, `customer_addr`. |
| `App\Models\Erav\EravPurchaseOrderItem` | **`porder_pdf_info`** | `erav` | Line items: `item_styleno`, `item_description`, `item_colordesc`, `upc_refno`. Linked via `porder_pdf_list_id`. |
| `App\Models\Erav\EravProdOrder` | **`prodorder_list`** | `erav` | Production order: `prod_order_season`, `prodorder_ref`, `ex_factory_date`, `mbranchlist_id`. Linked via `porder_pdf_list_id`. |
| `App\Models\Erav\EravBranch` | **`mbranchlist`** | `erav` | Manufacturing branches: `br_name`, `br_addr`. Linked via `prodorder_list.mbranchlist_id`. |
| `App\Models\Erav\EravProductStyle` | **`prodstyle_list`** | `erav` | Style specs: `styleid`, `program_title`, `product_description`, `fabric_content`. Linked via `item_styleno = styleid`. |

---

## 3. Storage Tables Reference

| Model | Table Name | Connection | Role in Data Flow |
| :--- | :--- | :--- | :--- |
| `App\Models\StaffordQualification` | **`stafford_qualifications`** | `mysql` (default) | Master storage for certificates, PO data, factory info, POC, and test JSON. |
| `App\Models\Lab` | **`labs`** | `mysql` (default) | Stores laboratory identity: `id`, `name`, `lab_code`, `email`, `phone`, `address_line1`. |

---

## 4. Field-by-Field Mapping Matrix

The table below maps every parameter in `validateCamelCase()` to its database destination and its origin during ERP PO lookup:

### 4.1 Purchase Order & Product Identifiers

| Request Field (`camelCase`) | Validation Rule | Storage Table & Column (`stafford_qualifications`) | ERP Source Table & Column | Details & Business Logic |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `nullable\|integer` | `id` *(Primary Key)* | `stafford_qualifications.id` | Set if editing an existing local qualification. |
| `poNumber` | `nullable\|string` | `po_number` | `porder_pdf_list.pdf_po_refno` | Purchase Order number. |
| `allPoNumbers` | `nullable\|array` | *(Handled iteratively in controller)* | User Selection / ERP PO list | Array of PO numbers when applying one certificate to multiple POs. |
| `season` | `nullable\|string\|max:50` | `season` | `prodorder_list.prod_order_season` | Season code (e.g., `FA25`, `SP26`). |
| `poDate` | `nullable\|string` | `po_date` | `porder_pdf_list.porder_date` | PO issue date. |
| `buyer` | `nullable\|string` | `buyer` | `porder_pdf_list.customer_name` | Name of the buyer/customer. Defaults to importer name if null. |
| `shipment` | `nullable\|string` | `shipment` | User Input / `stafford_qualifications.shipment` | Shipment terms / details. |
| `origin` | `nullable\|string` | `origin` | Parsed from `mbranchlist.br_addr` | Factory country of origin. Defaults to `factoryCountry`. |
| `styleNo` | `nullable\|string\|max:50` | `style_no` | `porder_pdf_info.item_styleno` | Style number from the first item of the PO. |
| `erpStyleDesc` | `nullable\|string` | `erp_style_desc` | `porder_pdf_info.item_description` | Raw style description from ERP item list. |
| `productName` | `required\|string\|max:250` | `product_name` | `porder_pdf_info.item_description` | Product title. Fallback: `prodstyle_list.product_description`. |
| `brandName` | `nullable\|string\|max:50` | `brand_name` | `prodstyle_list.program_title` | Program/Brand name. Fallback: `'Stafford'`. |
| `color` | `nullable\|string\|max:50` | `color` | `porder_pdf_info.item_colordesc` | Colorway description. |
| `productStyle` | `nullable\|string\|max:50` | `product_style` | `porder_pdf_info.item_styleno` | Defaults to `styleNo` if not explicitly specified. |
| `primaryProductId` | `required\|string\|max:50` | `primary_product_id` | `prodstyle_list.styleid` OR `porder_pdf_info.item_styleno` | Base model/item code without parenthesis, truncated to max 19 characters. |
| `productIdType` | `required\|string` | `product_id_type` | Derived based on `upc_refno` | Evaluates to `'UPC'` if item has UPC, otherwise `'Model #'`. |
| `gtin` | `nullable\|string` | `gtin` | User Input / `stafford_qualifications.gtin` | Global Trade Item Number. |
| `upc` | `nullable\|string` | `upc` | `porder_pdf_info.upc_refno` | UPC barcode number. |
| `description` | `nullable\|string\|max:250` | `description` | `porder_pdf_info.item_description` + `prodstyle_list.fabric_content` | Formatted as `[item_description] - [fabric_content]`. |
| `additionalIdentifiers`| `nullable\|array` | `additional_identifiers` *(JSON)* | User Input / `porder_pdf_info` | Array of additional SKU / style numbers. |

---

### 4.2 Certificate & Compliance Fields

| Request Field (`camelCase`) | Validation Rule | Storage Table & Column (`stafford_qualifications`) | Source Origin | Details & Business Logic |
| :--- | :--- | :--- | :--- | :--- |
| `certType` | `required\|string\|in:GCC,CPC` | `cert_type` | User Selection | Certificate standard: General (`GCC`) or Children's (`CPC`). |
| `intendedUse` | `nullable\|string` | `intended_use` | User Input | General wear / sleepwear / etc. |
| `childrenProduct` | `nullable\|string` | `children_product` | User Input | Flag or details regarding children's compliance. |
| `certLanguage` | `nullable\|string` | `cert_language` | Default: `'English'` | Language of certificate. |
| `certScope` | `nullable\|string` | `cert_scope` | Default: `'National - USA (CPSC)'` | Certification regulatory scope. |
| `certNo` | `nullable\|string` | `cert_no` | System Generated | Format: `[PREFIX]-[poNumber]-[RANDOM(1000,9999)]`. |
| `versionId` | `required\|string\|max:19` | `version_id` | System Generated / Form Input | Revision/version tracking key. |
| `status` | `nullable\|string` | `status` | Form default: `'Incomplete'` | Qualification status (`'Incomplete'`, `'Completed'`). |

---

### 4.3 Factory & Manufacturing Details

Factory data is extracted from the ERP relationship: `porder_pdf_list` $\rightarrow$ `prodorder_list` $\rightarrow$ `mbranchlist`:

| Request Field (`camelCase`) | Validation Rule | Storage Table & Column (`stafford_qualifications`) | ERP Source Table & Column | Extraction / Parsing Logic |
| :--- | :--- | :--- | :--- | :--- |
| `factoryName` | `required\|string\|max:100` | `factory_name` | `mbranchlist.br_name` | Branch/Factory name. Fallback: `'Stafford Branch'`. |
| `factoryAddress` | `nullable\|string\|max:255` | `factory_address` | `mbranchlist.br_addr` | Street address portion of branch address. |
| `factoryCity` | `nullable\|string\|max:50` | `factory_city` | `mbranchlist.br_addr` | Parsed city name from address block. |
| `factoryState` | `nullable\|string\|max:100` | `factory_state` | `mbranchlist.br_addr` | State/province regex match. |
| `factoryPostal` | `nullable\|string\|max:10` | `factory_postal` | `mbranchlist.br_addr` | Postal/ZIP code regex match. |
| `factoryCountry` | `nullable\|string\|max:50` | `factory_country` | `mbranchlist.br_addr` | Identified from standard country dictionary list. |
| `factoryGLN` | `nullable\|string\|max:50` | `factory_gln` | `mbranchlist.br_addr` / User Input | Global Location Number. |
| `factoryAltId` | `nullable\|string\|max:50` | `factory_alt_id` | `mbranchlist.br_addr` / User Input | Factory alternate ID. |
| `factoryContact` | `nullable\|string` | `factory_contact` | `mbranchlist.br_addr` | Parsed contact name. |
| `factoryEmail` | `nullable\|string\|max:50\|email` | `factory_email` | `mbranchlist.br_addr` | Regex-extracted email address. |
| `factoryPhone` | `nullable\|string\|max:20` | `factory_phone` | `mbranchlist.br_addr` | Regex-extracted phone number. |
| `manufactureDate` | `required\|string` | `manufacture_date` | `prodorder_list.ex_factory_date` | Date in `YYYY-MM` or full `YYYY-MM-DD` format. |
| `manufactureFullDate`| `nullable\|date` | *(Used to compute `manufacture_date`)*| `prodorder_list.ex_factory_date` | Complete ex-factory date (`YYYY-MM-DD`). |
| `manufactureStartDate`| `nullable\|date` | `manufacture_start_date` | Derived from `ex_factory_date` | Defaulted to 1 month prior to `ex_factory_date`. |
| `manufactureEndDate` | `nullable\|date` | `manufacture_end_date` | Derived from `ex_factory_date` | Defaulted to 5 days prior to `ex_factory_date`. |
| `lotNumber` | `nullable\|string\|max:100` | `lot_number` | `prodorder_list.prodorder_ref` OR `porder_pdf_list.pdf_po_refno` | Production order reference or PO number. |
| `lotAssignedBy` | `nullable\|string\|max:50` | `lot_assigned_by` | Form Default: `'Manufacturer'` | Entity assigning the lot. |

---

### 4.4 Importer & Point of Contact (POC)

Importer data is extracted from `porder_pdf_list.customer_addr`:

| Request Field (`camelCase`) | Validation Rule | Storage Table & Column (`stafford_qualifications`) | ERP Source Table & Column | Details & Business Logic |
| :--- | :--- | :--- | :--- | :--- |
| `importerName` | `nullable\|string\|max:100` | `importer_name` | `porder_pdf_list.customer_name` | Customer/Buyer name. |
| `importerAddress` | `nullable\|string\|max:100` | `importer_address` | `porder_pdf_list.customer_addr` | Raw customer address. |
| `importerCity` | `nullable\|string\|max:50` | `importer_city` | `porder_pdf_list.customer_addr` | Parsed city component. |
| `importerState` | `nullable\|string\|max:100` | `importer_state` | `porder_pdf_list.customer_addr` | Parsed state code (e.g. `NY`). |
| `importerPostal` | `nullable\|string\|max:10` | `importer_postal` | `porder_pdf_list.customer_addr` | Parsed 5-digit ZIP. |
| `importerCountry` | `nullable\|string\|max:50` | `importer_country` | `porder_pdf_list.customer_addr` | Country (defaults to `'USA'`). |
| `pointOfContact` | `nullable\|string` | `point_of_contact` | Form Selection | Chooses either `'Importer'` or `'Manufacturer'`. |
| `pocType` | `nullable\|string\|in:...` | `poc_type` | Form Selection | Importer, Manufacturer, Private Labeler, etc. |
| `pocName` | `nullable\|string\|max:100` | `poc_name` | Mapped from `importerName` or `factoryName` | Contact name based on `pointOfContact`. |
| `pocAddressLine1` | `nullable\|string\|max:100` | `poc_address_line1` | Mapped from `importerAddress` or `factoryAddress` | Contact street address. |
| `pocAddressLine2` | `nullable\|string\|max:100` | `poc_address_line2` | Form Input | Suite/Unit. |
| `pocAptNumber` | `nullable\|string\|max:10` | `poc_apt_number` | Form Input | Apartment number. |
| `pocCity` | `nullable\|string\|max:50` | `poc_city` | Mapped from `importerCity` or `factoryCity` | City. |
| `pocStateProvince` | `nullable\|string\|max:100` | `poc_state_province` | Mapped from `importerState` or `factoryState` | State / Province. |
| `pocCountry` | `nullable\|string\|max:50` | `poc_country` | Mapped from `importerCountry` or `factoryCountry` | Country. |
| `pocPostalCode` | `nullable\|string\|max:10` | `poc_postal_code` | Mapped from `importerPostal` or `factoryPostal` | Postal Code. |
| `pocPhone` | `nullable\|string\|max:20` | `poc_phone` | Mapped from `factoryPhone` / Form Input | Phone number. |
| `pocEmail` | `nullable\|string\|max:50\|email` | `poc_email` | Mapped from `factoryEmail` / Form Input | Email address. |
| `pocGln` | `nullable\|string\|max:50` | `poc_gln` | Form Input | GLN identifier. |
| `pocAlternateId` | `nullable\|string\|max:50` | `poc_alternate_id` | Form Input | Alternate identifier. |

---

### 4.5 Certifier & Laboratory Assignment

| Request Field (`camelCase`) | Validation Rule | Storage Table & Column (`stafford_qualifications`) | Source Origin | Details & Business Logic |
| :--- | :--- | :--- | :--- | :--- |
| `certifierName` | `nullable\|string\|max:100` | `certifier_name` | Form Default: `'Stafford Textile Ltd'` | Compliance certifying entity. |
| `certifierTitle` | `nullable\|string` | `certifier_title` | Form Default: `'Compliance Manager'` | Title of certifier. |
| `certifierEmail` | `nullable\|string\|max:50\|email` | `certifier_email` | Form Default: `'compliance@staffordtextile.com'` | Certifier email. |
| `certifierPhone` | `nullable\|string\|max:20` | `certifier_phone` | Form Default: `'+1 416-555-0199'` | Certifier phone. |
| `labId` | `nullable\|exists:labs,id` | `lab_id` | `labs.id` | Foreign key referencing the authenticated or selected lab in `labs` table. |
| `lastTestDate` | `nullable\|date` | `last_test_date` | Computed from `tests.*.testDate` | Automatically calculated as the latest `testDate` in the tests array. |

---

### 4.6 Laboratory Tests (`tests` JSON Array)

* **Storage Column:** `stafford_qualifications.tests` (Cast as `array` / JSON)
* **Description:** Represents all laboratory test reports performed on the style/PO.

| Nested Field | Validation Rule | Description |
| :--- | :--- | :--- |
| `labType` | `nullable\|string\|max:50` | Laboratory type (`'In-house'`, `'Third Party'`, etc.). |
| `labName` | `required\|string\|max:100` | Testing laboratory company name. |
| `testReportId` | `nullable\|string\|max:400` | Test report identifier or certificate number. |
| `testDate` | `nullable\|date` | Date the laboratory conducted testing. |
| `labCpscId` | `nullable\|string` | CPSC-recognized laboratory identification number. |
| `gln` | `nullable\|string\|max:50` | Lab Global Location Number. |
| `alternateId` | `nullable\|string\|max:50` | Alternative lab ID. |
| `addressLine1` | `nullable\|string\|max:100` | Lab street address. |
| `addressLine2` | `nullable\|string\|max:100` | Lab secondary address line. |
| `aptNumber` | `nullable\|string\|max:10` | Suite / unit number. |
| `city` | `nullable\|string\|max:50` | Lab city. |
| `stateProvince` | `nullable\|string\|max:100` | Lab state or province. |
| `country` | `nullable\|string\|max:50` | Lab country. |
| `postalCode` | `nullable\|string\|max:10` | Lab postal / ZIP code. |
| `phone` | `nullable\|string\|max:20` | Lab phone number. |
| `email` | `nullable\|string\|max:50\|email` | Lab email address. |
| `isExclusion` | `nullable\|boolean` | Boolean flag if a test exclusion rule applies. |
| `exclusionReason` | `nullable\|string` | Explanation if testing was excluded. |
| `labNameOther` | `nullable\|string` | Free-text lab name if not in standard list. |
| `testReportFile` | `nullable\|string` | Relative path to uploaded PDF in `storage/app/public`. |
| `testReportFileUrl` | `nullable\|string` | Public URL to the uploaded test report (`asset('storage/...')`). |
| `componentTesting` | `nullable\|boolean` | Whether testing was conducted on individual components. |
| `componentDetails` | `nullable\|string` | Detailed notes on component testing. |
| `citations` | `nullable\|array` | Array of tested regulations/standards (e.g. `16 CFR 1610`). |
| `citations.*.code` | `required\|string` | Standard regulatory code (e.g. `16 CFR Part 1610`). |
| `citations.*.desc` | `nullable\|string` | Standard regulatory description (e.g. `Flammability of Clothing Textiles`). |
