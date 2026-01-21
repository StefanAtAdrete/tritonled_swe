<?php

/**
 * Create taxonomy terms for TritonLED project.
 * Run with: ddev drush php:script create-taxonomy-terms.php
 */

use Drupal\taxonomy\Entity\Term;

$terms_data = [
  'product_series' => [
    ['name' => 'Titan Series', 'description' => 'High Bay LED fixtures for massive scale operations'],
    ['name' => 'Linear Pro Series', 'description' => 'Linear fixtures for continuous lighting runs'],
    ['name' => 'Vapor Guard Series', 'description' => 'Moisture and dust-resistant fixtures'],
    ['name' => 'ATEX Series', 'description' => 'Explosion-proof fixtures for hazardous locations'],
    ['name' => 'Strip Light Series', 'description' => 'Flexible LED strip lighting solutions'],
  ],
  'application_areas' => [
    ['name' => 'Warehousing', 'description' => 'High racks and narrow aisles'],
    ['name' => 'Cold Storage', 'description' => 'Temperature-controlled environments'],
    ['name' => 'Hazardous Locations', 'description' => 'ATEX/IECEx classified areas'],
    ['name' => 'Manufacturing', 'description' => 'Precision tasks and safety lighting'],
    ['name' => 'Sports Facilities', 'description' => 'Gymnasiums and indoor sports'],
    ['name' => 'Parking Garages', 'description' => 'Multi-level parking structures'],
    ['name' => 'Retail Spaces', 'description' => 'Commercial retail environments'],
    ['name' => 'Cleanrooms', 'description' => 'Controlled contamination environments'],
  ],
  'certifications' => [
    ['name' => 'DLC Premium Listed', 'description' => 'Qualifies for energy rebates'],
    ['name' => 'NSF Certified', 'description' => 'Food-safe environments'],
    ['name' => 'UL Listed', 'description' => 'Safety tested and certified'],
    ['name' => 'ATEX Certified', 'description' => 'European explosion protection'],
    ['name' => 'IECEx Certified', 'description' => 'International explosion protection'],
    ['name' => 'CE Marked', 'description' => 'European conformity'],
    ['name' => 'RoHS Compliant', 'description' => 'Restriction of hazardous substances'],
  ],
  'product_tags' => [
    ['name' => 'Best Seller'],
    ['name' => 'New Product'],
    ['name' => 'Featured'],
    ['name' => 'Limited Stock'],
    ['name' => 'Clearance'],
  ],
];

$created_count = 0;
$skipped_count = 0;

foreach ($terms_data as $vid => $terms) {
  echo "Creating terms for vocabulary: $vid\n";
  
  foreach ($terms as $term_data) {
    // Check if term already exists
    $existing = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => $vid,
        'name' => $term_data['name'],
      ]);
    
    if (!empty($existing)) {
      echo "  - Skipped (exists): {$term_data['name']}\n";
      $skipped_count++;
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
    $created_count++;
  }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "  Created: $created_count terms\n";
echo "  Skipped: $skipped_count terms\n";
echo "========================================\n";
