#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
if command -v ddev >/dev/null 2>&1; then
  DRUSH="ddev drush"
elif [ -x "./vendor/bin/drush" ]; then
  DRUSH="./vendor/bin/drush"
else
  echo "ERROR: Neither ddev drush nor ./vendor/bin/drush is available"
  exit 1
fi

CATEGORY="${1:-wall_light}"
SKIP_MEDIA="${2:-}"

case "$CATEGORY" in
  wall_light)
    PRICE_TYPE="tritonled_wall_light_prices"
    PRICE_CSV="private://feeds/incoming/wall_lights_prices.csv"
    MEDIA_TYPE="tritonled_wl_media_assets"
    MEDIA_CSV="private://feeds/incoming/wall_lights_media.csv"
    PRODUCT_TYPE="wall_light"
    MEDIA_FIELD="field_product_media"
    MEDIA_DEST_DIR="public://import-images/wall-light/"
    ;;
  floodlight)
    PRICE_TYPE="tritonled_floodlight_variations"
    PRICE_CSV="private://feeds/incoming/floodlight_prices.csv"
    MEDIA_TYPE="tritonled_floodlight_media"
    MEDIA_CSV="private://feeds/incoming/floodlight_media.csv"
    PRODUCT_TYPE="floodlight"
    MEDIA_FIELD="field_product_media"
    MEDIA_DEST_DIR="public://import-images/floodlight/"
    ;;
  highbay)
    PRICE_TYPE="tritonled_highbay_variations"
    PRICE_CSV="private://feeds/incoming/highbay_prices.csv"
    MEDIA_TYPE="tritonled_highbay_media_assets"
    MEDIA_CSV="private://feeds/incoming/highbay_media.csv"
    PRODUCT_TYPE="highbay"
    MEDIA_FIELD="field_product_media"
    MEDIA_DEST_DIR="public://import-images/highbay/"
    ;;
  linear_led)
    PRICE_TYPE="tritonled_linear_led_prices"
    PRICE_CSV="private://feeds/incoming/linear_led_prices.csv"
    MEDIA_TYPE="tritonled_linear_led_media"
    MEDIA_CSV="private://feeds/incoming/linear_led_media.csv"
    PRODUCT_TYPE="linear_led"
    MEDIA_FIELD="field_product_media"
    MEDIA_DEST_DIR="public://import-images/linear-led/"
    ;;
  *)
    echo "ERROR: Unknown category '$CATEGORY'. Use: wall_light | floodlight | highbay | linear_led"
    exit 1
    ;;
esac

echo "Category: $CATEGORY"
echo "Price feed type: $PRICE_TYPE"

price_fid="$( $DRUSH sqlq "SELECT MAX(fid) FROM feeds_feed WHERE type='${PRICE_TYPE}';" | tr -d '\r' | awk 'NF{last=$0} END{print last}' )"
if [ -z "${price_fid}" ] || [ "${price_fid}" = "NULL" ]; then
  echo "No price feed instance found, creating one (${PRICE_TYPE})"
  price_fid="$( $DRUSH php:eval "\$s=\\Drupal::entityTypeManager()->getStorage('feeds_feed'); \$f=\$s->create(['type'=>'${PRICE_TYPE}','title'=>'Price Import','uid'=>1,'status'=>1]); \$f->setSource('${PRICE_CSV}'); \$f->save(); echo \$f->id();" | tr -d '\r' | tail -n1 )"
fi
if [ -z "${price_fid}" ] || [ "${price_fid}" = "NULL" ]; then
  echo "ERROR: Could not create/find feed instance for ${PRICE_TYPE}"
  exit 1
fi

echo "[1/5] Import prices via feed ${price_fid}"
$DRUSH feeds:import "${price_fid}" -y

if [ "$SKIP_MEDIA" = "--skip-media" ]; then
  echo "[2-4/5] Media steps skipped"
else
  if ! $DRUSH cget "feeds.feed_type.${MEDIA_TYPE}" id >/dev/null 2>&1; then
    echo "[2-4/5] Media feed type ${MEDIA_TYPE} saknas i aktiv config, hoppar över media-steg"
  else
    echo "[2/5] Ensure media feed instance exists (${MEDIA_TYPE})"
    media_fid="$( $DRUSH sqlq "SELECT MAX(fid) FROM feeds_feed WHERE type='${MEDIA_TYPE}';" | tr -d '\r' | awk 'NF{last=$0} END{print last}' )"
    if [ -z "${media_fid}" ] || [ "${media_fid}" = "NULL" ]; then
      media_fid="$( $DRUSH php:eval "\$s=\\Drupal::entityTypeManager()->getStorage('feeds_feed'); \$f=\$s->create(['type'=>'${MEDIA_TYPE}','title'=>'Media Assets','uid'=>1,'status'=>1]); \$f->setSource('${MEDIA_CSV}'); \$f->save(); echo \$f->id();" | tr -d '\r' | tail -n1 )"
    fi

    echo "[3/5] Create media entities from CSV local files (dedupe by image file)"
    $DRUSH php:eval "\$csv='${MEDIA_CSV}'; \$destDir='${MEDIA_DEST_DIR}'; \$fs=\\Drupal::service('file_system'); \$real=\$fs->realpath(\$csv); if(!\$real || !file_exists(\$real)){echo 'csv missing\\n'; return;} \$raw=file(\$real); if(!\$raw){echo 'csv empty\\n'; return;} \$rows=array_map('str_getcsv', \$raw); \$header=array_shift(\$rows); if(!is_array(\$header)){echo 'csv header missing\\n'; return;} \$idx=array_flip(\$header); if(!\$fs->prepareDirectory(\$destDir, 3)){echo 'dest dir not writable: '.\$destDir.'\\n'; return;} \$db=\\Drupal::database(); \$created=0; \$reused=0; foreach(\$rows as \$r){\$path=\$r[\$idx['image_url']]??''; \$alt=\$r[\$idx['alt_text']]??''; \$title=\$r[\$idx['title_text']]??''; \$status=(int)(\$r[\$idx['status']]??1); \$lang=\$r[\$idx['langcode']]??'en'; if(str_starts_with(\$path,'http://')||str_starts_with(\$path,'https://')){ \$u=parse_url(\$path); \$p=\$u['path']??''; if(str_starts_with(\$p,'/sites/default/files/')){ \$path='/var/www/html/web'.\$p; } } if(str_starts_with(\$path,'file://')){\$path=substr(\$path,7);} if(str_starts_with(\$path,'public://')){\$path=\$fs->realpath(\$path);} if(!\$path){continue;} if(!file_exists(\$path)){echo 'missing: '.\$path.'\\n'; continue;} \$data=file_get_contents(\$path); \$dest=\$destDir.basename(\$path); \$file=\\Drupal::service('file.repository')->writeData(\$data,\$dest,\\Drupal\\Core\\File\\FileExists::Replace); \$fid=(int)\$file->id(); \$mid=\$db->query('SELECT mfd.mid FROM media_field_data mfd INNER JOIN media__field_media_image mfi ON mfi.entity_id=mfd.mid WHERE mfi.field_media_image_target_id=:fid ORDER BY mfd.mid ASC LIMIT 1',[':fid'=>\$fid])->fetchField(); if(\$mid){\$reused++; continue;} \$m=\\Drupal\\media\\Entity\\Media::create(['bundle'=>'image','name'=>\$title?:basename(\$path),'status'=>\$status,'langcode'=>\$lang,'field_media_image'=>['target_id'=>\$fid,'alt'=>\$alt?:\$title?:basename(\$path),'title'=>\$title?:basename(\$path)]]); \$m->save(); \$created++; } echo 'created='.\$created.' reused='.\$reused.'\\n';"

    echo "[4/5] Link media to products via SKU"
    $DRUSH php:eval "\$csv='${MEDIA_CSV}'; \$ptype='${PRODUCT_TYPE}'; \$mfield='${MEDIA_FIELD}'; \$destDir='${MEDIA_DEST_DIR}'; \$fs=\\Drupal::service('file_system'); \$real=\$fs->realpath(\$csv); if(!\$real || !file_exists(\$real)){echo 'csv missing\\n'; return;} \$raw=file(\$real); if(!\$raw){echo 'csv empty\\n'; return;} \$rows=array_map('str_getcsv', \$raw); \$header=array_shift(\$rows); if(!is_array(\$header)){echo 'csv header missing\\n'; return;} \$idx=array_flip(\$header); \$db=\\Drupal::database(); \$linked=0; foreach(\$rows as \$r){ \$sku=\$r[\$idx['sku']]??''; \$lang=\$r[\$idx['langcode']]??'en'; \$path=\$r[\$idx['image_url']]??''; if(!\$sku || !\$path) continue; if(str_starts_with(\$path,'http://')||str_starts_with(\$path,'https://')){ \$u=parse_url(\$path); \$p=\$u['path']??''; if(str_starts_with(\$p,'/sites/default/files/')){ \$path='public://'.substr(\$p,21); } } elseif(str_starts_with(\$path,'/var/www/html/web/sites/default/files/')){ \$path='public://'.substr(\$path,33); } elseif(str_starts_with(\$path,'public://')) { } else { \$path=\$destDir.basename(\$path); } \$fid=\$db->query('SELECT fid FROM file_managed WHERE uri=:uri ORDER BY fid DESC LIMIT 1',[':uri'=>\$path])->fetchField(); if(!\$fid) continue; \$mid=\$db->query('SELECT mfd.mid FROM media_field_data mfd INNER JOIN media__field_media_image mfi ON mfi.entity_id=mfd.mid WHERE mfi.field_media_image_target_id=:fid ORDER BY mfd.mid ASC LIMIT 1',[':fid'=>\$fid])->fetchField(); if(!\$mid) continue; \$vid=\$db->query('SELECT variation_id FROM commerce_product_variation_field_data WHERE sku=:sku LIMIT 1',[':sku'=>\$sku])->fetchField(); if(!\$vid) continue; \$pid=\$db->query('SELECT entity_id FROM commerce_product__variations WHERE variations_target_id=:vid LIMIT 1',[':vid'=>\$vid])->fetchField(); if(!\$pid) continue; \$product=\\Drupal\\commerce_product\\Entity\\Product::load(\$pid); if(!\$product || \$product->bundle()!==\$ptype || !\$product->hasField(\$mfield)) continue; \$existing=array_column(\$product->get(\$mfield)->getValue(),'target_id'); if(!in_array((int)\$mid,\$existing)){ \$product->get(\$mfield)->appendItem(['target_id'=>(int)\$mid]); \$linked++; \$product->save(); } } echo 'linked='.\$linked.'\\n';"
  fi
fi

echo "[5/5] Verify summary"
$DRUSH sqlq "SELECT type, COUNT(*) AS products FROM commerce_product_field_data WHERE langcode='sv' GROUP BY type ORDER BY type;"
echo "DONE"
