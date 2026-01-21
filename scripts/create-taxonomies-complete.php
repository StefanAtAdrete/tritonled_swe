<?php

/**
 * Create taxonomies (vocabularies + terms) for TritonLED project.
 * Run with: ddev drush php:script create-taxonomies-complete.php
 */

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

$vocabularies = [
  'product_series' => [
    'name' => 'Product Series',
    'description' => 'Product lines and series for grouping LED products',
    'terms' => [
      ['name' => 'Titan Series', 'description' => 'High Bay LED fixtures for massive scale operations'],
      ['name' => 'Linear Pro Series', 'description' => 'Linear fixtures for continuous lighting runs'],
      ['name' => 'Vapor Guard Series', 'description' => 'Moisture and dust-resistant fixtures'],
      ['name' => 'ATEX Series', 'description' => 'Explosion-proof fixtures for hazardous locations'],
      ['name' => 'Strip Light Series', 'description' => 'Flexible LED strip lighting solutions'],
    ],
  ],
  'application_areas' => [
    'name' => 'Application Areas',
    'description' => 'Usage environments where LED products are suitable',
    'terms' => [
      ['name' => 'Warehousing', 'description' => 'High racks and narrow aisles'],
      ['name' => 'Cold Storage', 'description' => 'Temperature-controlled environments'],
      ['name' => 'Hazardous Locations', 'description' => 'ATEX/IECEx classified areas'],
      ['name' => 'Manufacturing', 'description' => 'Precision tasks and safety lighting'],
      ['name' => 'Sports Facilities', 'description' => 'Gymnasiums and indoor sports'],
      ['name' => 'Parking Garages', 'description' => 'Multi-level parking structures'],
      ['name' => 'Retail Spaces', 'description' => 'Commercial retail environments'],
      ['name' => 'Cleanrooms', 'description' => 'Controlled contamination environments'],
    ],
  ],
  'certifications' => [
    'name' => 'Certifications',
    'description' => 'Official certifications and standards',
    'terms' => [
      ['name' => 'DLC Premium Listed', 'description' => 'Qualifies for energy rebates'],
      ['name' => 'NSF Certified', 'description' => 'Food-safe environments'],
      ['name' => 'UL Listed', 'description' => 'Safety tested and certified'],
      ['name' => 'ATEX Certified', 'description' => 'European explosion protection'],
      ['name' => 'IECEx Certified', 'description' => 'International explosion protection'],
      ['name' => 'CE Marked', 'description' => 'European conformity'],
      ['name' => 'RoHS Compliant', 'description' => 'Restriction of hazardous substances'],
    ],
  ],
  'product_tags' => [
    'name' => 'Product Tags',
    'description' => 'Flexible markers for status and campaigns',
    'terms' => [
      ['name' => 'Best Seller'],
      ['name' => 'New Product'],
      ['name' => 'Featured'],
      ['name' => 'Limited Stock'],
      ['name' => 'Clearance'],
    ],
  ],
];

$vocab_created = 0;
$vocab_skipped = 0;
$terms_created = 0;
$terms_skipped = 0;

foreach ($vocabularies as $vid => $vocab_data) {
  echo "========================================\n";
  echo "Processing vocabulary: {$vocab_data['name']} ($vid)\n";
  echo "========================================\n";
  
  // Check if vocabulary exists
  $vocabulary = Vocabulary::load($vid);
  
  if (!$vocabulary) {
    // Create vocabulary
    $vocabulary = Vocabulary::create([
      'vid' => $vid,
      'name' => $vocab_data['name'],
      'description' => $vocab_data['description'],
      'weight' => 0,
    ]);
    $vocabulary->save();
    echo "✓ Created vocabulary: {$vocab_data['name']}\n\n";
    $vocab_created++;
  } else {
    echo "- Vocabulary already exists: {$vocab_data['name']}\n\n";
    $vocab_skipped++;
  }
  
  // Create terms
  echo "Creating terms:\n";
  foreach ($vocab_data['terms'] as $term_data) {
    // Check if term already exists
    $existing = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => $vid,
        'name' => $term_data['name'],
      ]);
    
    if (!empty($existing)) {
      echo "  - Skipped (exists): {$term_data['name']}\n";
      $terms_skipped++;
      continue;
    }
    
    // Create term
    $term = Term::create([
      'vid' => $vid,
      'name' => $term_data['name'],
      'description' => $term_data['description'] ?? '',
    ]);
    $term->save();
    
    echo "  ✓ Created: {$term_data['name']}\n";
    $terms_created++;
  }
  echo "\n";
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Vocabularies:\n";
echo "  Created: $vocab_created\n";
echo "  Skipped: $vocab_skipped\n";
echo "\nTerms:\n";
echo "  Created: $terms_created\n";
echo "  Skipped: $terms_skipped\n";
echo "========================================\n";
