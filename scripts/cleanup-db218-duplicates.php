<?php

declare(strict_types=1);

use Drupal\commerce_product\Entity\Product;

$expectedSite = 'tritonled-security';
$confirmation = 'DELETE_LOCAL_DB218_DUPLICATES';
$canonicalId = 46;
$protectedVariationIds = [20928, 20929, 20930, 20931, 20932];
$titles = [
  'DB218 Linjär LED-armatur',
  'DB218 Linjar LED-armatur',
  'DB218 Linear LED luminaire',
];

if (getenv('DDEV_SITENAME') !== $expectedSite) {
  throw new RuntimeException('Cleanup is restricted to the tritonled-security DDEV site.');
}

$database = \Drupal::database();
$candidateIds = $database->select('commerce_product_field_data', 'p')
  ->fields('p', ['product_id'])
  ->condition('title', $titles, 'IN')
  ->distinct()
  ->execute()
  ->fetchCol();
$duplicateIds = array_values(array_diff(array_map('intval', $candidateIds), [$canonicalId]));
sort($duplicateIds, SORT_NUMERIC);

$canonical = Product::load($canonicalId);
if (!$canonical) {
  throw new RuntimeException('Canonical product 46 is missing.');
}
$canonicalVariationIds = array_values(array_map('intval', $canonical->getVariationIds()));
sort($canonicalVariationIds, SORT_NUMERIC);
if ($canonicalVariationIds !== $protectedVariationIds) {
  throw new RuntimeException('Canonical variation set does not match 20928-20932.');
}
if (array_keys($canonical->getTranslationLanguages()) !== ['sv', 'en']) {
  throw new RuntimeException('Canonical product languages are not exactly sv and en.');
}

$feed20 = \Drupal\feeds\Entity\Feed::load(20);
$feed21 = \Drupal\feeds\Entity\Feed::load(21);
if (!$feed20 || !$feed20->isActive()) {
  throw new RuntimeException('Variation feed 20 must remain active.');
}
if (!$feed21 || $feed21->isActive()) {
  throw new RuntimeException('Product feed 21 must be disabled.');
}

$referenceChecks = [
  'variations' => ['commerce_product__variations', 'entity_id'],
  'media' => ['commerce_product__field_product_media', 'entity_id'],
  'media_files' => ['commerce_product__field_product_media_files', 'entity_id'],
];
foreach ($referenceChecks as $label => [$table, $column]) {
  $count = $duplicateIds === [] ? 0 : (int) $database->select($table, 't')
    ->condition($column, $duplicateIds, 'IN')
    ->countQuery()
    ->execute()
    ->fetchField();
  if ($count !== 0) {
    throw new RuntimeException("Blocked: duplicate {$label} reference count is {$count}.");
  }
}

$orderItemCount = 0;
if ($duplicateIds !== []) {
  $orderQuery = $database->select('commerce_order_item', 'oi');
  $orderQuery->innerJoin('commerce_product_variation_field_data', 'p', 'p.variation_id = oi.purchased_entity');
  $orderItemCount = (int) $orderQuery
    ->condition('p.product_id', $duplicateIds, 'IN')
    ->countQuery()
    ->execute()
    ->fetchField();
}
if ($orderItemCount !== 0) {
  throw new RuntimeException("Blocked: {$orderItemCount} order items reference duplicate product variations.");
}

$summary = [
  'mode' => getenv('TRITONLED_DB218_CLEANUP_CONFIRM') === $confirmation ? 'execute' : 'dry-run',
  'canonical_product_id' => $canonicalId,
  'protected_variation_ids' => $protectedVariationIds,
  'duplicate_product_count' => count($duplicateIds),
  'first_duplicate_id' => $duplicateIds[0] ?? NULL,
  'last_duplicate_id' => $duplicateIds[array_key_last($duplicateIds)] ?? NULL,
  'duplicate_ids_sha256' => hash('sha256', implode(',', $duplicateIds)),
];

if ($summary['mode'] === 'dry-run') {
  $summary['next_step'] = "Set TRITONLED_DB218_CLEANUP_CONFIRM={$confirmation} to execute locally.";
  print json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  return;
}

$storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
$deleted = 0;
foreach (array_chunk($duplicateIds, 50) as $chunk) {
  $entities = $storage->loadMultiple($chunk);
  $storage->delete($entities);
  $deleted += count($entities);
}

$remainingQuery = $database->select('commerce_product_field_data', 'p')
  ->fields('p', ['product_id'])
  ->condition('title', $titles, 'IN')
  ->distinct();
$remaining = (int) $remainingQuery
  ->countQuery()
  ->execute()
  ->fetchField();
$remainingAliases = $duplicateIds === [] ? 0 : (int) $database->select('path_alias', 'pa')
  ->condition('path', array_map(static fn(int $id): string => '/product/' . $id, $duplicateIds), 'IN')
  ->countQuery()
  ->execute()
  ->fetchField();

$summary['deleted_product_count'] = $deleted;
$summary['remaining_matched_product_rows'] = $remaining;
$summary['remaining_duplicate_aliases'] = $remainingAliases;
print json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

