<?php
/**
 * Database Migration/Setup Script
 * Run this script to ensure all required tables exist
 * Access it via: http://localhost/Vehicle%20Fault%20Diagnosis%20and%20Service%20Monitoring%20System/setup.php
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/Vehicles Fault Diagnosis and Service Monitoring System/config/database.php';
$conn = getDB();

$tables_created = [];
$errors = [];

// List of SQL statements to create missing tables
$sql_statements = [
    'vehicle_technician_assignments' => "
        CREATE TABLE IF NOT EXISTS vehicle_technician_assignments (
            vehicle_id INT PRIMARY KEY,
            technician_id INT,
            assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_technician (technician_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'invoices' => "
        CREATE TABLE IF NOT EXISTS invoices (
            id INT PRIMARY KEY AUTO_INCREMENT,
            diagnosis_id INT,
            client_id INT,
            receptionist_id INT,
            amount DECIMAL(10, 2),
            tax DECIMAL(10, 2),
            total DECIMAL(10, 2),
            status VARCHAR(20) DEFAULT 'unpaid',
            issued_date DATE,
            due_date DATE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (diagnosis_id) REFERENCES diagnoses(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receptionist_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_client (client_id),
            INDEX idx_diagnosis (diagnosis_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'diagnoses' => "
        CREATE TABLE IF NOT EXISTS diagnoses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            vehicle_id INT NOT NULL,
            receptionist_id INT,
            diagnosis_date DATE,
            fault_code VARCHAR(10),
            title VARCHAR(255),
            description TEXT,
            category VARCHAR(50),
            severity VARCHAR(20),
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            FOREIGN KEY (receptionist_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_vehicle (vehicle_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'comments' => "
        CREATE TABLE IF NOT EXISTS comments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            diagnosis_id INT NOT NULL,
            user_id INT,
            rating INT,
            comment_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (diagnosis_id) REFERENCES diagnoses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_diagnosis (diagnosis_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
];

// Execute each SQL statement
foreach ($sql_statements as $table_name => $sql) {
    if ($conn->query($sql)) {
        $tables_created[] = $table_name;
    } else {
        $errors[] = "$table_name: " . $conn->error;
    }
}

// Check if tables exist
$check_tables = $conn->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
$existing_tables = [];
while ($row = $check_tables->fetch_assoc()) {
    $existing_tables[] = $row['TABLE_NAME'];
}

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Database Setup & Migration</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-warning">
        <h5>⚠️ Errors Encountered:</h5>
        <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($tables_created)): ?>
    <div class="alert alert-success">
        <h5>✅ Successfully created/verified tables:</h5>
        <ul class="mb-0">
        <?php foreach ($tables_created as $table): ?>
            <li><strong><?= htmlspecialchars($table) ?></strong></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Existing Database Tables</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($existing_tables as $table): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($table) ?></code></td>
                            <td>
                                <?php
                                $required_tables = ['vehicle_technician_assignments', 'invoices', 'diagnoses', 'comments'];
                                if (in_array($table, $required_tables)) {
                                    echo '<span class="badge bg-success">Required - ✓</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Existing</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h5>Summary:</h5>
        <p><strong>Database:</strong> <?= DB_NAME ?></p>
        <p><strong>Total Tables:</strong> <?= count($existing_tables) ?></p>
        <p class="mb-0"><strong>Status:</strong> All required tables are ready! ✅</p>
    </div>

    <a href="index.php" class="btn btn-primary mt-3">← Back to System</a>
</div>
</body>
</html>
