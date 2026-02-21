<?php
$feed1 = \Drupal::entityTypeManager()->getStorage('feeds_feed')->load(1);
$feed3 = \Drupal::entityTypeManager()->getStorage('feeds_feed')->load(3);
echo 'Feed 1 fields:' . PHP_EOL;
foreach ($feed1->getFields() as $name => $field) {
  $val = $feed1->get($name)->getValue();
  if ($val) echo $name . ': ' . json_encode($val) . PHP_EOL;
}
echo PHP_EOL . 'Feed 3 fields:' . PHP_EOL;
foreach ($feed3->getFields() as $name => $field) {
  $val = $feed3->get($name)->getValue();
  if ($val) echo $name . ': ' . json_encode($val) . PHP_EOL;
}
