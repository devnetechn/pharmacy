<?php
// tests/test_stock.php
require __DIR__ . '/../includes/stock.php';

$failures = 0;
function check($label, $got, $want) {
    global $failures;
    if ($got === $want) {
        echo "PASS: $label\n";
    } else {
        echo "FAIL: $label (got " . var_export($got, true) . ", want " . var_export($want, true) . ")\n";
        $failures++;
    }
}

// received_in adds
check('received adds to 0', compute_new_balance(0, 'received_in', 50), 50);
check('received adds to existing', compute_new_balance(50, 'received_in', 20), 70);
// given_out subtracts
check('given subtracts', compute_new_balance(70, 'given_out', 30), 40);
// given_out exactly to zero
check('given to zero', compute_new_balance(40, 'given_out', 40), 0);
// invalid type throws
try { compute_new_balance(10, 'bogus', 1); check('invalid type throws', false, true); }
catch (InvalidArgumentException $e) { check('invalid type throws', true, true); }
// negative result rejected
try { compute_new_balance(10, 'given_out', 11); check('overdraw throws', false, true); }
catch (RangeException $e) { check('overdraw throws', true, true); }
// negative quantity rejected
try { compute_new_balance(10, 'received_in', -5); check('negative qty throws', false, true); }
catch (InvalidArgumentException $e) { check('negative qty throws', true, true); }

echo $failures === 0 ? "\nALL PASSED\n" : "\n$failures FAILED\n";
exit($failures === 0 ? 0 : 1);
