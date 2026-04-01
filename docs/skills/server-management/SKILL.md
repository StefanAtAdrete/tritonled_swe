---
name: server-management
description: >
  SSH, rsync och serveradministration för TritonLED på Hostinger VPS.
  Använd detta skill när Stefan behöver: SSH:a in på servern, synka media-filer,
  köra drush-kommandon på prod, felsöka serverproblem, eller hantera filer som
  inte följer med git. Trigga alltid när Stefan nämner "prod", "servern",
  "media-filer", "rsync", "SSH" eller "deploy".
---

# Server Management — TritonLED Prod

## Serverinfo

| | |
|---|---|
| **IP** | `168.231.108.87` |
| **SSH-port** | `2222` |
| **SSH-användare** | `tritonswe_ssh` |
| **SSH alias** | `ssh tritonled` (konfigurerat i `~/.ssh/config`) |
| **Webbroot** | `/home/tritonled/htdocs/tritonled.se` |
| **CloudPanel** | `https://168.231.108.87:8443` |
| **Drush** | Alltid `vendor/bin/drush` — aldrig bara `drush` |

## SSH-access

```bash
# Enklaste sättet (alias konfigurerat)
ssh tritonled

# Explicit om alias inte fungerar
ssh -p 2222 tritonswe_ssh@168.231.108.87
```

### Om SSH hänger sig eller timeout
1. Kontrollera att SSH-daemonen körs (via Hostinger webberminal som root):
   ```bash
   ss -tlnp | grep sshd
   systemctl start ssh
   systemctl enable ssh
   ```
2. Kontrollera UFW tillåter port 2222:
   ```bash
   ufw status
   ufw allow 2222/tcp
   ufw reload
   ```
3. SSH lyssnar på **port 2222** — inte 22. Hostinger brandvägg + UFW måste båda tillåta den.

### Om Hostinger webberminal hänger ("Press any key to wake your server")
- Klicka Reset i Hostinger VPS-panelen — normalt beteende
- Använd alltid SSH direkt istället för webterminalen

### Emergency mode (sista utväg)
- Aktiveras i Hostinger → VPS → Settings → Emergency mode
- Tar upp till 20 minuter
- Ger root-access via SSH: `ssh root@168.231.108.87`
- Disken mountas på `/mnt/sdb1/` — inte på `/`
- `tritonled`-användaren finns inte i rescue-OS — använd UID `1006`:
  ```bash
  chown -R 1006:1006 /mnt/sdb1/home/tritonled/htdocs/tritonled.se/private
  ```

---

## Rsync — Media-filer

Media-filer följer inte med git och måste synkas manuellt.

### Lokal → Prod
```bash
rsync -avz --progress \
  --exclude="php/" \
  --exclude="js/" \
  --exclude="css/" \
  --exclude="languages/" \
  --exclude="styles/" \
  -e "ssh -p 2222" \
  /Users/steffes/Projekt/tritonled/web/sites/default/files/ \
  tritonswe_ssh@168.231.108.87:/home/tritonled/htdocs/tritonled.se/web/sites/default/files/
```

### Prod → Lokal (backup)
```bash
rsync -avz --progress \
  -e "ssh -p 2222" \
  tritonswe_ssh@168.231.108.87:/home/tritonled/htdocs/tritonled.se/web/sites/default/files/ \
  /Users/steffes/Projekt/tritonled/web/sites/default/files/
```

---

## Drush på prod

```bash
# Enskilt kommando via SSH
ssh tritonled "cd /home/tritonled/htdocs/tritonled.se && vendor/bin/drush cr"

# Interaktiv session
ssh tritonled
cd /home/tritonled/htdocs/tritonled.se
vendor/bin/drush cim --partial -y
vendor/bin/drush cr
```

---

## Deploy-flöde

```bash
# 1. LOKALT
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Beskrivning"
git push origin main

# 2. PÅ PROD
ssh tritonled
cd /home/tritonled/htdocs/tritonled.se
git pull
vendor/bin/drush cim --partial -y
vendor/bin/drush cr
```

---

## Databasbackup från prod

```bash
ssh tritonled "cd /home/tritonled/htdocs/tritonled.se && vendor/bin/drush sql:dump --gzip" > ~/Desktop/prod_db_$(date +%Y%m%d).sql.gz
```

---

## settings.php på prod

Sökväg: `/home/tritonled/htdocs/tritonled.se/web/sites/default/settings.php`

Viktiga inställningar som MÅSTE finnas:
```php
$settings['config_sync_directory'] = $app_root . '/../config/sync';
$settings['file_private_path'] = $app_root . '/../private';
$settings['trusted_host_patterns'] = [
  '^tritonled\.se$',
  '^www\.tritonled\.se$',
  '^preview\.affarsfabriken\.se$',
];
```

### När tritonled.se-domänen pekas om
Uppdatera `trusted_host_patterns` i settings.php — lägg till/ta bort domäner efter behov:
```bash
ssh tritonled
nano /home/tritonled/htdocs/tritonled.se/web/sites/default/settings.php
```

---

## Vanliga felproblem

### "Entity/field definitions — Configuration Split setting"
```bash
ssh tritonled
cd /home/tritonled/htdocs/tritonled.se
vendor/bin/drush php:eval "
\$manager = \Drupal::entityDefinitionUpdateManager();
\$entity_type = \Drupal::entityTypeManager()->getDefinition('config_split');
\$manager->installEntityType(\$entity_type);
"
vendor/bin/drush php:eval "
\Drupal::keyValue('system.schema')->set('config_split', 8003);
"
vendor/bin/drush php:eval "
\Drupal::configFactory()->getEditable('config_split.config_split.local')
  ->set('status', false)
  ->save();
"
vendor/bin/drush cr
```

### "File system — private directory not writable"
Katalogen ägs av root. Fixa via Emergency mode (se ovan) eller som root:
```bash
chown -R tritonled:tritonled /home/tritonled/htdocs/tritonled.se/private
# eller med UID om tritonled-användaren saknas i rescue-OS:
chown -R 1006:1006 /home/tritonled/htdocs/tritonled.se/private
```

### "Trusted Host Settings not enabled"
Lägg till `trusted_host_patterns` i settings.php (se ovan).

---

## Noteringar

- SSH fungerar på **port 2222** — Hostinger brandvägg + UFW måste båda tillåta porten
- `tritonswe_ssh` har INTE sudo-access — root-operationer kräver Hostinger webberminal eller Emergency mode
- `tritonled`-användaren har UID **1006**
- SSH-nyckel: `~/.ssh/id_tritonled_new` (ed25519, ingen passphrase)
- SSH fungerar på IP `168.231.108.87` oavsett vilken domän som pekar dit
