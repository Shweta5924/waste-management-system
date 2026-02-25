<?php
require_once 'config/db.php';

try {
    echo "🌱 Seeding Database with realistic demo data...\n\n";

    // 1. Create Users (Supervisors & Staff)
    $users = [
        ['Ravi Sharma', 'ravi@waste.com', 'password', 'supervisor', '9876543210'],
        ['Anita Desai', 'anita@waste.com', 'password', 'supervisor', '9876543211'],
        ['Suresh Patil', 'suresh@waste.com', 'password', 'staff', '9876543212'], // Staff
        ['Mahesh Babu', 'mahesh@waste.com', 'password', 'staff', '9876543213']   // Staff
    ];

    $user_ids = [];
    foreach ($users as $u) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
        $password = password_hash($u[2], PASSWORD_DEFAULT);
        $stmt->execute([$u[0], $u[1], $password, $u[3], $u[4]]);
        
        // Fetch ID
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$u[1]]);
        $user_ids[$u[1]] = $stmt->fetchColumn();
        echo "[User] Created {$u[3]}: {$u[0]}\n";
    }

    // 2. Create Wards
    $wards = [
        ['W-101', 'Indiranagar', 12000, $user_ids['ravi@waste.com']],
        ['W-102', 'Koramangala', 15000, $user_ids['anita@waste.com']],
        ['W-103', 'Whitefield', 20000, $user_ids['ravi@waste.com']],
        ['W-104', 'Jayanagar', 18000, $user_ids['anita@waste.com']]
    ];

    $ward_ids = [];
    foreach ($wards as $w) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO wards (ward_number, area_name, population, supervisor_id) VALUES (?, ?, ?, ?)");
        $stmt->execute($w);
        
        $stmt = $pdo->prepare("SELECT id FROM wards WHERE ward_number = ?");
        $stmt->execute([$w[0]]);
        $ward_ids[] = $stmt->fetchColumn();
        echo "[Ward] Created: {$w[1]}\n";
    }

    // 3. Create Vehicles
    $vehicles = [
        ['KA-01-HW-5566', 'Raju Driver', '9988776655', $ward_ids[0]],
        ['KA-03-MR-1122', 'Biju Driver', '8877665544', $ward_ids[1]],
        ['KA-51-ZZ-9900', 'Chotu Driver', '7766554433', $ward_ids[2]]
    ];

    foreach ($vehicles as $v) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO vehicles (vehicle_number, driver_name, driver_phone, ward_id) VALUES (?, ?, ?, ?)");
        $stmt->execute($v);
        echo "[Vehicle] Added: {$v[0]}\n";
    }

    // 4. Generate Daily Waste Entries (Last 30 Days)
    echo "\n[Data] Generating daily waste records for last 30 days...\n";
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO waste_entries (ward_id, date, wet_waste, dry_waste, mixed_waste) VALUES (?, ?, ?, ?, ?)");
    
    for ($i = 30; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        foreach ($ward_ids as $wid) {
            // Random realistic data
            // Wet: 400-800kg, Dry: 200-500kg, Mixed: 50-200kg (Improving over time)
            
            // Simulate trend: Less mixed waste over time (improvement)
            $factor = (30 - $i) / 30; // 0 to 1
            
            $wet = rand(400, 800);
            $dry = rand(200, 500);
            $mixed = rand(100, 300) * (1 - ($factor * 0.5)); // Mixed decreases by up to 50%
            
            $stmt->execute([$wid, $date, $wet, $dry, $mixed]);
        }
    }
    echo "   -> Added ~" . (count($ward_ids) * 30) . " waste entry records.\n";

    // 5. Create Complaints
    $complaints = [
        ['Anonymous', $ward_ids[0], 'Garbage bin overflowing near metro station.', 'Pending'],
        ['Priya K', $ward_ids[1], 'Collection truck did not come today.', 'Resolved'],
        ['Rahul D', $ward_ids[0], 'Illegal dumping in park corner.', 'Pending'],
        ['Amit S', $ward_ids[2], 'Street sweeping not done for 2 days.', 'Resolved']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO complaints (citizen_name, ward_id, complaint_text, status) VALUES (?, ?, ?, ?)");
    foreach ($complaints as $c) {
        $stmt->execute($c);
    }
    echo "[Complaints] Added sample complaints.\n";

    echo "\n✅ Success! Database populated with realistic data.\n";
    echo "   You can now login as Admin to see the charts and analytics.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
