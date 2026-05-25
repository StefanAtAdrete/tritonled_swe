#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
if [ -x "./vendor/bin/drush" ]; then
  DRUSH="./vendor/bin/drush"
elif command -v ddev >/dev/null 2>&1; then
  DRUSH="ddev drush"
else
  echo "ERROR: Neither ./vendor/bin/drush nor ddev drush is available"
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
    MEDIA_TYPE="tritonled_floodlight_media_assets"
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
  *)
    echo "ERROR: Unknown category '$CATEGORY'. Use: wall_light | floodlight | highbay"
    exit 1
    ;;
esac

echo "Category: $CATEGORY"
echo "Price feed type: $PRICE_TYPE"

price_fid="$(PRICE_TYPE="$PRICE_TYPE" $DRUSH php:eval '$t=getenv("PRICE_TYPE"); $fid=\Drupal::database()->query("SELECT MAX(fid) FROM {feeds_feed} WHERE type=:t",[":t"=>$t])->fetchField(); echo $fid ?: "";' | tr -d '\r' | tail -n1)"
if [ -z "${price_fid}" ] || [ "${price_fid}" = "NULL" ]; then
  echo "ERROR: No feed instance found for ${PRICE_TYPE}"
  exit 1
fi

echo "[1/5] Import prices via feed ${price_fid}"
$DRUSH feeds:import "${price_fid}" -y

if [ "$SKIP_MEDIA" = "--skip-media" ]; then
  echo "[2-4/5] Media steps skipped"
else
  echo "[2/5] Ensure media feed instance exists (${MEDIA_TYPE})"
  media_fid="$(MEDIA_TYPE="$MEDIA_TYPE" $DRUSH php:eval '$t=getenv("MEDIA_TYPE"); $fid=\Drupal::database()->query("SELECT MAX(fid) FROM {feeds_feed} WHERE type=:t",[":t"=>$t])->fetchField(); echo $fid ?: "";' | tr -d '\r' | tail -n1)"
  if [ -z "${media_fid}" ] || [ "${media_fid}" = "NULL" ]; then
    media_fid="$(MEDIA_TYPE="$MEDIA_TYPE" MEDIA_CSV="$MEDIA_CSV" $DRUSH php:eval '$s=\Drupal::entityTypeManager()->getStorage("feeds_feed"); $f=$s->create(["type"=>getenv("MEDIA_TYPE"),"title"=>"Media Assets","uid"=>1]); $f->setSource(getenv("MEDIA_CSV")); $f->save(); echo $f->id();' | tail -n1)"
  fi

  echo "[3/5] Create media entities from CSV local files"
  MEDIA_CSV="$MEDIA_CSV" MEDIA_DEST_DIR="$MEDIA_DEST_DIR" $DRUSH php:eval '$csv=getenv("MEDIA_CSV"); $destDir=getenv("MEDIA_DEST_DIR"); $fs=\Drupal::service("file_system"); $real=$fs->realpath($csv); if(!file_exists($real)){echo "csv missing\n"; return;} $rows=array_map("str_getcsv", file($real)); $header=array_shift($rows); $idx=array_flip($header); $created=0; foreach($rows as $r){$path=$r[$idx["image_url"]]??""; $name=$r[$idx["title_text"]]??""; $status=(int)($r[$idx["status"]]??1); $lang=$r[$idx["langcode"]]??"en"; if(str_starts_with($path,"http://")||str_starts_with($path,"https://")){ $u=parse_url($path); $p=$u["path"]??""; if(str_starts_with($p,"/sites/default/files/")){ $path="/var/www/html/web".$p; } } if(str_starts_with($path,"file://")){$path=substr($path,7);} if(str_starts_with($path,"public://")){$path=$fs->realpath($path);} if(!file_exists($path)){echo "missing: $path\n"; continue;} $data=file_get_contents($path); $dest=$destDir.basename($path); $file=\Drupal::service("file.repository")->writeData($data,$dest,\Drupal\Core\File\FileExists::Replace); $m=\Drupal\media\Entity\Media::create(["bundle"=>"image","name"=>$name?:basename($path),"status"=>$status,"langcode"=>$lang,"field_media_image"=>["target_id"=>$file->id(),"alt"=>$name?:basename($path),"title"=>$name?:basename($path)]]); $m->save(); $created++; } echo "created=$created\n";'

  echo "[4/5] Link media to products via SKU"
  MEDIA_CSV="$MEDIA_CSV" PRODUCT_TYPE="$PRODUCT_TYPE" MEDIA_FIELD="$MEDIA_FIELD" $DRUSH php:eval '$csv=getenv("MEDIA_CSV"); $ptype=getenv("PRODUCT_TYPE"); $mfield=getenv("MEDIA_FIELD"); $fs=\Drupal::service("file_system"); $real=$fs->realpath($csv); if(!file_exists($real)){echo "csv missing\n"; return;} $rows=array_map("str_getcsv", file($real)); $header=array_shift($rows); $idx=array_flip($header); $db=\Drupal::database(); $linked=0; foreach($rows as $r){$sku=$r[$idx["sku"]]??""; $lang=$r[$idx["langcode"]]??"en"; $title=$r[$idx["title_text"]]??""; if(!$sku||!$title) continue; $vid=$db->query("SELECT variation_id FROM commerce_product_variation_field_data WHERE sku=:sku LIMIT 1",[":sku"=>$sku])->fetchField(); if(!$vid) continue; $pid=$db->query("SELECT entity_id FROM commerce_product__variations WHERE variations_target_id=:vid LIMIT 1",[":vid"=>$vid])->fetchField(); if(!$pid) continue; $mids=$db->query("SELECT mfd.mid FROM media_field_data mfd WHERE mfd.name=:name AND mfd.langcode=:lang ORDER BY mfd.mid DESC",[":name"=>$title,":lang"=>$lang])->fetchCol(); if(!$mids) continue; $product=\Drupal\commerce_product\Entity\Product::load($pid); if(!$product || $product->bundle()!==$ptype || !$product->hasField($mfield)) continue; $existing=array_column($product->get($mfield)->getValue(),"target_id"); foreach($mids as $mid){ if(!in_array($mid,$existing)){ $product->get($mfield)->appendItem(["target_id"=>$mid]); $existing[]=$mid; $linked++; } } $product->save(); } echo "linked=$linked\n";'
fi

echo "[5/5] Verify summary"
$DRUSH sqlq "SELECT type, COUNT(*) AS products FROM commerce_product_field_data WHERE langcode='sv' GROUP BY type ORDER BY type;"
echo "DONE"
