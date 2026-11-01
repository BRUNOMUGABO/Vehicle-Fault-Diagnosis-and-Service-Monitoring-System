-- Assign vehicles to technicians using round-robin distribution
USE vfdsms;

SET @tech_id = 20;
SET @tech_count = 14;
SET @counter = 0;

INSERT INTO vehicle_technician_assignments (vehicle_id, technician_id)
SELECT 
    v.id,
    CASE 
        WHEN (@counter := @counter + 1) <= @tech_count THEN @tech_id + @counter - 1
        ELSE @tech_id + ((@counter - 1) % @tech_count)
    END as technician_id
FROM vehicles v
WHERE v.id NOT IN (SELECT vehicle_id FROM vehicle_technician_assignments)
ORDER BY v.id
ON DUPLICATE KEY UPDATE technician_id = VALUES(technician_id);

-- Show assignment summary
SELECT 
    u.name as technician,
    COUNT(vta.vehicle_id) as assigned_vehicles
FROM users u
LEFT JOIN vehicle_technician_assignments vta ON u.id = vta.technician_id
WHERE u.role = 'staff'
GROUP BY u.id, u.name
ORDER BY u.id;
