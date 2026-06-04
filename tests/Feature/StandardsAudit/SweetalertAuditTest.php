<?php

it('does not use native confirm() / alert() in any module view', function () {
    // Match `confirm(` or `alert(` only when preceded by a JS-safe boundary
    // (start, whitespace, comparison, logical op, opening paren, or `!`).
    // This excludes erpConfirm, sweetAlert, .alert, etc.
    $regex = '/(?:^|[\s=&|!(])\b(confirm|alert)\s*\(/';

    $hits = grepModuleViews($regex);

    expect($hits)
        ->toBeEmpty(
            "Module views must use erpConfirm() / erpPrompt() / erpToast() instead of native\n".
            "confirm() / alert(). The native dialogs are not styled, are not localized, and are not\n".
            "consistent with the rest of the ERP UI.\n\n".
            "Offending lines:\n".formatHits($hits)
        );
});
