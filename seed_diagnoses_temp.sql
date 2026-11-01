USE vfdsms;
INSERT INTO diagnoses (vehicle_id, receptionist_id, diagnosis_date, fault_code, title, description, category, severity, status) VALUES
(28, 19, '2025-06-15', 'P0300', 'Engine Misfire Detected', 'Random misfire on multiple cylinders', 'engine', 'high', 'open'),
(29, 19, '2025-06-18', 'P0720', 'Transmission Speed Sensor Fault', 'Erratic gear changes, slipping transmission', 'transmission', 'medium', 'in_progress'),
(30, 19, '2025-06-20', 'B1400', 'Electrical Short Circuit', 'Battery draining, dim lights', 'electrical', 'medium', 'resolved'),
(31, 19, '2025-06-21', NULL, 'Brake Pad Wear', 'Squealing brakes, reduced stopping power', 'brakes', 'critical', 'pending'),
(32, 19, '2025-06-19', 'P0171', 'Fuel System Lean Mixture', 'Poor fuel economy, rough idle', 'fuel', 'medium', 'open');
