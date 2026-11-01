<?php
/**
 * Script to assign vehicles to all clients
 * Run this once to assign every client a vehicle from the database templates
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/Vehicles Fault Diagnosis and Service Monitoring System/config/database.php';

$conn = getDB();

// Get all clients without vehicles
$no_vehicles = $conn->query("
  SELECT u.id, u.name 
  FROM users u 
  WHERE u.role='client' 
    AND u.id NOT IN (SELECT DISTINCT client_id FROM vehicles)
  ORDER BY u.id
")->fetch_all(MYSQLI_ASSOC);

// Get vehicle templates to cycle through
$templates = $conn->query("
  SELECT DISTINCT make, model, year, color, fuel_type, mileage 
  FROM vehicles 
  ORDER BY id
")->fetch_all(MYSQLI_ASSOC);

if (empty($templates)) {
    echo "No vehicle templates found in database.";
    exit;
}

$template_count = count($templates);
$assigned = 0;

// Assign vehicles to clients by cycling through templates
foreach ($no_vehicles as $index => $client) {
    $template = $templates[$index % $template_count];
    
    // Create unique plate for each client
    $base_plate = str_replace(' ', '', strtoupper(substr($template['make'], 0, 2) . substr($template['model'], 0, 2)));
    $plate = $base_plate . str_pad($client['id'], 3, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("
        INSERT INTO vehicles (client_id, make, model, year, plate, color, fuel_type, mileage, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'operational')
    ");
    
    $stmt->bind_param(
        "issssssi",
        $client['id'],
        $template['make'],
        $template['model'],
        $template['year'],
        $plate,
        $template['color'],
        $template['fuel_type'],
        $template['mileage']
    );
    
    if ($stmt->execute()) {
        $assigned++;
        echo "✓ Assigned vehicle to {$client['name']} (ID: {$client['id']})<br>";
    } else {
        echo "✗ Failed to assign vehicle to {$client['name']}: " . $conn->error . "<br>";
    }
}

// Update all vehicle statuses to 'operational'
$update = $conn->query("UPDATE vehicles SET status='operational'");
echo "<hr><strong>Summary:</strong><br>";
echo "✓ Assigned $assigned vehicles to clients without cars<br>";
echo "✓ Updated all vehicle statuses to 'operational'<br>";
echo "<br><a href='index.php'>Return to Dashboard</a>";
?>
