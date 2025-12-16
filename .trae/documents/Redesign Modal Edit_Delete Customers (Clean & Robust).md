## Goals
- Single, clean edit modal with consistent open/close and submit.
- Lightweight delete confirm modal.
- Centralized event delegation; no duplicate listeners; predictable state.

## UI/HTML
- Replace current edit modal markup with a minimal modal: header (title + X), body (form fields), footer (Cancel + Save).
- Replace current delete modal with a minimal confirm: title, message, Cancel + Delete.
- IDs: `#customerModal`, `#deleteConfirmModal`, buttons `#customerCancelBtn`, `#customerSaveBtn`, `#customerCloseBtn`, `#deleteCancelBtn`, `#deleteConfirmBtn`.

## JS API
- `openCustomerModal(mode, data)` handles both add/edit; sets form values, title, shows modal.
- `closeCustomerModal()` hides modal and resets.
- `submitCustomerForm()` validates and posts to API; disables Save while submitting; refreshes list/stats; closes modal.
- `openDeleteConfirm(id, name)`, `closeDeleteConfirm()`, `performDelete()` implement delete flow.
- Event delegation: one click listener on table body for `[data-action="edit"]` / `[data-action="delete"]`.
- Sentinel flags to avoid duplicate binding.

## Integration
- Render customers generates action buttons with `data-id`/`data-name`.
- Remove inline `onclick` uses.
- Ensure export dropdown or overlays are closed on modal open.

## Verification
- Edit: open, change, save, modal closes, list updates.
- Delete: confirm modal blocks accidental delete; Cancel works; Delete deactivates.
- No duplicate listeners after re-render.

## Delivery
- Update `public/dashboard/customers.php` (markup + JS).