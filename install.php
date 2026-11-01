<?php
require_once 'config/database.php';

echo "<h1>ATECAR GARAGE - VFDSMS Installation</h1>";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>✓ Database created successfully</p>";
} else {
    echo "<p style='color:red'>✗ Error creating database: " . $conn->error . "</p>";
}

$conn->select_db(DB_NAME);
$seed_file = 'seed.sql';
if (file_exists($seed_file)) {
    $sql = file_get_contents($seed_file);
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_result() && $conn->next_result());
        
        echo "<p style='color:green'>✓ Database tables and seed data imported successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error importing seed data: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ seed.sql file not found</p>";
}

$conn->close();

echo "<h2>Installation Complete!</h2>";
echo "<p>You can now <a href='login.php'>login to the system</a> using the following credentials:</p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@vfdsms.rw / password</li>";
echo "<li><strong>Staff:</strong> staff1@vfdsms.rw / password</li>";
echo "<li><strong>Receptionist:</strong> receptionist@vfdsms.rw / password</li>";
echo "<li><strong>Client:</strong> client1@vfdsms.rw / password</li>";
echo "</ul>";
echo "<p style='color:orange'><strong>Important:</strong> Delete this install.php file after installation for security.</p>";
?>