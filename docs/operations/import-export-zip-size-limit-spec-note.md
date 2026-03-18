# Spec Note: Import/Export ZIP File Size Validation

**Date:** 2025-03-18  
**Decision:** [import-export-zip-size-limit-decision.md](import-export-zip-size-limit-decision.md)  
**Master spec:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §43.11, §43.12

---

## Purpose

§43.11 requires file-upload workflows to validate **file size**. This note states how that is satisfied for the Import/Export ZIP upload path.

---

## Spec alignment

- **§43.11 (File Upload Security Rules):** The Import/Export validate flow satisfies “file size” validation by enforcing a **plugin-defined maximum size** before the file is moved. The maximum is applied to `$_FILES['aio_ie_package_file']['size']` prior to `move_uploaded_file()`. Oversized uploads are rejected with a clear, admin-safe message that states the limit.
- **§43.12 (ZIP Import Security Rules):** The pre-move size check is part of “tightly controlled” import: it prevents large files from being moved and then failing later, and keeps behavior consistent across hosting environments with different PHP limits.

---

## Product note

- **Threshold:** 50 MB (decision record). Implement as a named constant (e.g. `Import_Export_Zip_Size_Limit::MAX_BYTES`) so the value is documented and adjustable.
- **Scope:** Applies only to the Import/Export package upload (validate step). Industry bundle JSON upload remains governed by its own validator and 10 MB limit.
