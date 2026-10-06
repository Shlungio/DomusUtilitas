<?php
declare(strict_types=1);


// ========================================
// WRITE AUDIT LOG
// ========================================

function write_audit(
    PDO $pdo,
    string $action,
    string $entityType,
    ?int $entityId = null,
    ?string $details = null
): bool {

    $adminId =
        isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : null;


    $adminUsername =
        trim(
            (string)(
                $_SESSION['username']
                ?? 'Unknown Admin'
            )
        );


    if ($adminUsername === '') {
        $adminUsername =
            'Unknown Admin';
    }


    try {

        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs
            (
                admin_id,
                admin_username,
                action,
                entity_type,
                entity_id,
                details
            )
            VALUES
            (?, ?, ?, ?, ?, ?)'
        );


        $stmt->execute([
            $adminId,
            $adminUsername,
            $action,
            $entityType,
            $entityId,
            $details
        ]);


        return true;


    } catch (PDOException $e) {

        // Do not break the main admin action
        // just because audit logging failed.
        error_log(
            'Audit log error: ' .
            $e->getMessage()
        );

        return false;
    }
}