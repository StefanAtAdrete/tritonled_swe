<?php

declare(strict_types=1);

use Drupal\commerce_product\Entity\Product;

$canonicalId = 46;
$protectedVariationIds = [20928, 20929, 20930, 20931, 20932];
$titles = [
  'DB218 Linjär LED-armatur',
  'DB218 Linjar LED-armatur',
  'DB218 Linear LED luminaire',
];

$database = \Drupal::database();
$candidateIds = $database->select('commerce_product_field_data', 'p')
  ->fields('p', ['product_id'])
  ->condition('title', $titles, 'IN')
  ->distinct()
  ->execute()
  ->fetchCol();
$candidateIds = array_values(array_map('intval', $candidateIds));
$duplicateIds = array_values(array_diff($candidateIds, [$canonicalId]));
sort($duplicateIds, SORT_NUMERIC);

$idRanges = [];
foreach ($duplicateIds as $id) {
  $lastIndex = array_key_last($idRanges);
  if ($lastIndex === NULL || $id > $idRanges[$lastIndex][1] + 1) {
    $idRanges[] = [$id, $id];
  }
  else {
    $idRanges[$lastIndex][1] = $id;
  }
}
$idRanges = array_map(
  static fn(array $range): string => $range[0] === $range[1]
    ? (string) $range[0]
    : $range[0] . '-' . $range[1],
  $idRanges,
);

$canonical = Product::load($canonicalId);
$canonicalVariationIds = $canonical
  ? array_values(array_map('intval', $canonical->getVariationIds()))
  : [];
sort($canonicalVariationIds, SORT_NUMERIC);

$countReferences = static function (string $table, string $column) use ($database, $duplicateIds): int {
  if ($duplicateIds === []) {
    return 0;
  }
  return (int) $database->select($table, 't')
    ->condition($column, $duplicateIds, 'IN')
    ->countQuery()
    ->execute()
    ->fetchField();
};

$variationLinks = $countReferences('commerce_product__variations', 'entity_id');
$mediaLinks = $countReferences('commerce_product__field_product_media', 'entity_id');
$mediaFileLinks = $countReferences('commerce_product__field_product_media_files', 'entity_id');

$orderItemLinks = 0;
if ($duplicateIds !== []) {
  $orderQuery = $database->select('commerce_order_item', 'oi');
  $orderQuery->innerJoin('commerce_product_variation_field_data', 'p', 'p.variation_id = oi.purchased_entity');
  $orderItemLinks = (int) $orderQuery
    ->condition('p.product_id', $duplicateIds, 'IN')
    ->countQuery()
    ->execute()
    ->fetchField();
}

$aliasCount = 0;
if ($duplicateIds !== []) {
  $paths = array_map(static fn(int $id): string => '/product/' . $id, $duplicateIds);
  $aliasCount = (int) $database->select('path_alias', 'pa')
    ->condition('path', $paths, 'IN')
    ->countQuery()
    ->execute()
    ->fetchField();
}

$feed21 = \Drupal\feeds\Entity\Feed::load(21);
$feed20 = \Drupal\feeds\Entity\Feed::load(20);
$blockingReasons = [];
if (!$canonical) {
  $blockingReasons[] = 'Canonical product 46 is missing.';
}
if ($canonicalVariationIds !== $protectedVariationIds) {
  $blockingReasons[] = 'Canonical variation set does not match 20928-20932.';
}
if ($variationLinks !== 0) {
  $blockingReasons[] = 'At least one duplicate product owns a variation.';
}
if ($mediaLinks !== 0 || $mediaFileLinks !== 0) {
  $blockingReasons[] = 'At least one duplicate product references media.';
}
if ($orderItemLinks !== 0) {
  $blockingReasons[] = 'At least one order item references a duplicate product variation.';
}
if (!$feed21 || $feed21->isActive()) {
  $blockingReasons[] = 'Product feed 21 is not disabled.';
}
if (!$feed20 || !$feed20->isActive()) {
  $blockingReasons[] = 'Variation feed 20 is not active.';
}

$report = [
  'mode' => 'dry-run',
  'generated_at' => gmdate(DATE_ATOM),
  'canonical_product_id' => $canonicalId,
  'canonical_languages' => $canonical ? array_keys($canonical->getTranslationLanguages()) : [],
  'protected_variation_ids' => $protectedVariationIds,
  'canonical_variation_ids' => $canonicalVariationIds,
  'matched_product_count' => count($candidateIds),
  'duplicate_product_count' => count($duplicateIds),
  'duplicate_product_id_ranges' => $idRanges,
  'duplicate_product_ids_sha256' => hash('sha256', implode(',', $duplicateIds)),
  'duplicate_variation_links' => $variationLinks,
  'duplicate_media_links' => $mediaLinks,
  'duplicate_media_file_links' => $mediaFileLinks,
  'duplicate_order_item_links' => $orderItemLinks,
  'duplicate_path_aliases' => $aliasCount,
  'feed_20_active' => $feed20 ? $feed20->isActive() : NULL,
  'feed_21_active' => $feed21 ? $feed21->isActive() : NULL,
  'safe_to_prepare_cleanup' => $blockingReasons === [],
  'blocking_reasons' => $blockingReasons,
];

if (getenv('TRITONLED_INCLUDE_DUPLICATE_IDS') === '1') {
  $report['duplicate_product_ids'] = $duplicateIds;
}

print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

