---
paths:
  - 'app/Enums/Document*.php'
---

# Enums

## Invoices and quotations share one model, statuses do not
`Document` covers both types; `DocumentType` decides which `DocumentStatus` cases are legal (`DocumentStatus::appliesTo()`, `DocumentType::statuses()`). Accepted/Declined are quotation-only, Paid is invoice-only, Draft/Sent/Cancelled are shared.

Validation goes through `DocumentValidationRules::statusRules($type)`, which is `Rule::enum()->only($type->statuses())`, so a status from the wrong type is rejected rather than silently stored. The index controller drops a status filter that does not apply to the listed type for the same reason. When you add a status, put it in `appliesTo()` too, and add its label plus badge classes to `resources/js/lib/documents.ts`.
