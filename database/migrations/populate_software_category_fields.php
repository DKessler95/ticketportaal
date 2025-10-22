<?php
/**
 * Populate Software Category Fields
 * 
 * Task 5.2: Add fields for Software category:
 * - Applicatie naam (with dropdown options for common applications)
 * - Versie
 * - Licentie type
 * - Installatie locatie
 * 
 * Requirements: 2.1
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "Connected to database successfully.\n\n";
    
    // Get Software category ID
    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = 'Software' LIMIT 1");
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        die("ERROR: Software category not found. Please run seed_categories.sql first.\n");
    }
    
    $categoryId = $category['category_id'];
    echo "Software category ID: $categoryId\n\n";
    
    // Check if Software fields already exist
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM category_fields WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "Software category already has {$result['count']} fields.\n";
        echo "Checking if we need to add missing fields...\n\n";
    }
    
    // Define Software category fields
    $fields = [
        [
            'field_name' => 'software_name',
            'field_label' => 'Applicatie Naam',
            'field_type' => 'select',
            'field_options' => json_encode([
                'Microsoft Office',
                'Microsoft 365',
                'Microsoft Teams',
                'Outlook',
                'Excel',
                'Word',
                'PowerPoint',
                'Adobe Acrobat',
                'Adobe Photoshop',
                'Google Chrome',
                'Mozilla Firefox',
                'Zoom',
                'AutoCAD',
                'SAP',
                'ERP Systeem',
                'CRM Systeem',
                'Antivirus Software',
                'VPN Client',
                'Overig'
            ]),
            'is_required' => 1,
            'field_order' => 1,
            'placeholder' => null,
            'help_text' => 'Selecteer de software applicatie'
        ],
        [
            'field_name' => 'software_name_custom',
            'field_label' => 'Andere Applicatie',
            'field_type' => 'text',
            'field_options' => null,
            'is_required' => 0,
            'field_order' => 2,
            'placeholder' => 'Vul applicatie naam in',
            'help_text' => 'Alleen invullen als "Overig" geselecteerd'
        ],
        [
            'field_name' => 'software_version',
            'field_label' => 'Versie',
            'field_type' => 'text',
            'field_options' => null,
            'is_required' => 0,
            'field_order' => 3,
            'placeholder' => 'Bijv. 2021, 365, 11.0',
            'help_text' => 'Versie van de software indien bekend'
        ],
        [
            'field_name' => 'license_type',
            'field_label' => 'Licentie Type',
            'field_type' => 'select',
            'field_options' => json_encode([
                'Bedrijfslicentie',
                'Gebruikerslicentie',
                'Proefversie',
                'Gratis/Open Source',
                'Weet ik niet'
            ]),
            'is_required' => 0,
            'field_order' => 4,
            'placeholder' => null,
            'help_text' => 'Type licentie voor deze software'
        ],
        [
            'field_name' => 'installation_location',
            'field_label' => 'Installatie Locatie',
            'field_type' => 'select',
            'field_options' => json_encode([
                'Lokale Computer',
                'Netwerkschijf',
                'Cloud/Online',
                'Server',
                'Weet ik niet'
            ]),
            'is_required' => 1,
            'field_order' => 5,
            'placeholder' => null,
            'help_text' => 'Waar is de software geïnstalleerd?'
        ],
        [
            'field_name' => 'software_problem_type',
            'field_label' => 'Type Probleem',
            'field_type' => 'select',
            'field_options' => json_encode([
                'Installatie mislukt',
                'Start niet op',
                'Crasht regelmatig',
                'Foutmelding',
                'Licentieprobleem',
                'Update probleem',
                'Prestatieprobleem',
                'Functionaliteit werkt niet',
                'Compatibiliteitsprobleem',
                'Overig'
            ]),
            'is_required' => 1,
            'field_order' => 6,
            'placeholder' => null,
            'help_text' => 'Wat is het hoofdprobleem?'
        ],
        [
            'field_name' => 'error_message',
            'field_label' => 'Foutmelding',
            'field_type' => 'textarea',
            'field_options' => null,
            'is_required' => 0,
            'field_order' => 7,
            'placeholder' => 'Kopieer de exacte foutmelding hier',
            'help_text' => 'Indien er een foutmelding verschijnt, kopieer deze hier'
        ],
        [
            'field_name' => 'operating_system',
            'field_label' => 'Besturingssysteem',
            'field_type' => 'select',
            'field_options' => json_encode([
                'Windows 10',
                'Windows 11',
                'macOS',
                'Linux',
                'Weet ik niet'
            ]),
            'is_required' => 0,
            'field_order' => 8,
            'placeholder' => null,
            'help_text' => 'Op welk besturingssysteem draait de software?'
        ]
    ];
    
    // Insert fields
    $insertedCount = 0;
    $skippedCount = 0;
    
    foreach ($fields as $field) {
        // Check if field already exists
        $stmt = $pdo->prepare(
            "SELECT field_id FROM category_fields WHERE category_id = ? AND field_name = ?"
        );
        $stmt->execute([$categoryId, $field['field_name']]);
        
        if ($stmt->fetch()) {
            echo "⏭️  Skipped: {$field['field_label']} (already exists)\n";
            $skippedCount++;
            continue;
        }
        
        // Insert field
        $stmt = $pdo->prepare("
            INSERT INTO category_fields 
            (category_id, field_name, field_label, field_type, field_options, 
             is_required, field_order, placeholder, help_text, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $categoryId,
            $field['field_name'],
            $field['field_label'],
            $field['field_type'],
            $field['field_options'],
            $field['is_required'],
            $field['field_order'],
            $field['placeholder'],
            $field['help_text']
        ]);
        
        echo "✅ Created: {$field['field_label']} ({$field['field_type']})\n";
        $insertedCount++;
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SUMMARY:\n";
    echo "✅ Inserted: $insertedCount fields\n";
    echo "⏭️  Skipped: $skippedCount fields (already existed)\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Verify final count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM category_fields 
        WHERE category_id = ? AND is_active = 1
    ");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();
    
    echo "Total active Software category fields: {$result['count']}\n\n";
    
    // Display all Software fields
    echo "Current Software Category Fields:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT field_order, field_name, field_label, field_type, 
               CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status
        FROM category_fields 
        WHERE category_id = ? AND is_active = 1
        ORDER BY field_order
    ");
    $stmt->execute([$categoryId]);
    
    while ($row = $stmt->fetch()) {
        printf(
            "%2d. %-30s %-15s [%s]\n",
            $row['field_order'],
            $row['field_label'],
            "({$row['field_type']})",
            $row['required_status']
        );
    }
    
    echo "\n✅ Software category fields populated successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
