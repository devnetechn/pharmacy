<?php
// includes/stock.php

/**
 * Compute the new running balance after a transaction.
 * received_in adds quantity; given_out subtracts quantity.
 * Throws InvalidArgumentException on bad type/qty, RangeException on overdraw.
 */
function compute_new_balance(int $prevBalance, string $type, int $quantity): int {
    if ($quantity < 0) {
        throw new InvalidArgumentException('Quantity cannot be negative.');
    }
    if ($type === 'received_in') {
        return $prevBalance + $quantity;
    }
    if ($type === 'given_out') {
        $new = $prevBalance - $quantity;
        if ($new < 0) {
            throw new RangeException('Not enough stock: balance would go negative.');
        }
        return $new;
    }
    throw new InvalidArgumentException("Unknown transaction type: $type");
}

/** Get the latest balance for a drug (0 if no transactions yet). */
function current_balance(PDO $pdo, int $drugId): int {
    $stmt = $pdo->prepare(
        'SELECT balance FROM transactions WHERE drug_id = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$drugId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['balance'] : 0;
}
