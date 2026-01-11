# AI Agents Workflow

**Skapad**: 2025-01-11  
**Syfte**: Använda Drupal AI Agents via MCP för snabb content/struktur-skapande

---

## 🤖 Tillgängliga AI Agents

### **Content & Structure**

#### 1. Field Type Agent
**Tool**: `aif_field_type_agent`

**Vad den gör:**
- Lägga till fields på entity types
- Redigera field konfiguration
- Ta bort fields
- Ändra form display
- Ändra view display

**Exempel:**
```javascript
// Lägg till featured badge på Product
aif_field_type_agent({
  prompt: "Add boolean field 'field_featured_badge' to commerce_product luminaire bundle. Display as checkbox in form, display as badge icon in teaser view mode."
})

// Lägg till flera fields
aif_field_type_agent({
  prompt: "Add these fields to node Article:
  - field_reading_time (integer, display as '5 min read')
  - field_author_bio (text_long)
  - field_related_products (entity_reference to Product, multiple)"
})
```

---

#### 2. Content Type Agent
**Tool**: `aif_node_content_type_agent`

**Vad den gör:**
- Skapa nya content types
- Redigera befintliga content types
- Ändra settings (published by default, revisions, etc)

**Exempel:**
```javascript
// Skapa ny content type
aif_node_content_type_agent({
  prompt: "Create 'Case Study' content type with:
  - Published by default
  - Revisions enabled
  - Not promoted to front page
  - Display author and date"
})

// Editera befintlig
aif_node_content_type_agent({
  prompt: "Change Article content type to NOT display author and date by default"
})
```

**OBS:** Skapar INTE body field automatiskt - använd Field Agent för det!

---

#### 3. Taxonomy Agent
**Tool**: `aif_taxonomy_agent`

**Vad den gör:**
- Skapa vocabularies
- Lägga till taxonomy terms
- Redigera terms
- Reorder terms (viktigt för hierarkiska taxonomies)

**Exempel:**
```javascript
// Skapa vocabulary + terms
aif_taxonomy_agent({
  prompt: "Create 'Installation Type' vocabulary with terms:
  - Ceiling Mount
  - Wall Mount
  - Suspended
  - Track Mount
  - Recessed"
})

// Lägg till fler terms
aif_taxonomy_agent({
  prompt: "Add 'Surface Mount' term to Installation Type vocabulary"
})

// Hierarkisk taxonomy
aif_taxonomy_agent({
  prompt: "Create 'Product Categories' vocabulary with hierarchy:
  - Indoor Lighting
    - Downlights
    - Panel Lights
    - Track Lights
  - Outdoor Lighting
    - Floodlights
    - Street Lights
    - Facade Lights"
})
```

---

### **Commerce**

#### 4. Commerce Product Agent
**Tool**: `aif_commerce_product_agent`

**Vad den gör:**
- Skapa produkter med variations
- Hantera product attributes (watt, CCT)
- Sätta technical specifications
- Länka variations till product

**Exempel:**
```javascript
// Skapa produkt med 4 variations
aif_commerce_product_agent({
  prompt: "Create product 'LUNA Downlight LED' with variations:
  
  Variation 1: 15W, 3000K, CRI>80, 1200lm, SKU: LUNA-15-3K-80, Price: 2495 SEK
  Variation 2: 15W, 4000K, CRI>80, 1250lm, SKU: LUNA-15-4K-80, Price: 2495 SEK
  Variation 3: 20W, 3000K, CRI>90, 1800lm, SKU: LUNA-20-3K-90, Price: 2995 SEK
  Variation 4: 20W, 4000K, CRI>90, 1850lm, SKU: LUNA-20-4K-90, Price: 2995 SEK
  
  All: IP44, Dimensions 180x180x120mm, Weight 0.8kg, Mounting: Recessed
  Description: 'Elegant downlight för inomhusbruk med hög ljuskvalitet'"
})
```

**Helper Tools:**
```javascript
// Lista alla attribute values
aif_commerce_agent_list_attribute_values({ attribute: 'watt' })
aif_commerce_agent_list_attribute_values({ attribute: 'cct' })

// Lista produkter
aif_commerce_agent_list_products({ bundle: 'luminaire', limit: 50 })

// Lägg till variation till befintlig produkt
aif_commerce_agent_add_variation_to_product({
  product_id: 1,
  variation_ids: '5,6,7'
})
```

---

### **Triage Agents (Routers)**

**Content Type Triage** (`aif_content_type_agent_triage`)
- Avgör om du vill create/edit/delete/ask om content types
- Delegerar till rätt sub-agent

**Field Agent Triage** (`aif_field_agent_triage`)
- Router för field operations

**Taxonomy Config** (`aif_taxonomy_agent_config`)
- Router för taxonomy operations

**OBS:** Använd dessa när du är osäker vilken agent du behöver.

---

## 🔄 Workflow: AI Agents + Testing

### **Scenario 1: Skapa Content Type + Fields**

```javascript
// 1. Exportera logs först
"Stefan kör: ddev exec /var/www/html/scripts/export-drupal-status.sh"

// 2. Claude kollar status
Filesystem:read_text_file('web/sites/default/files/logs/summary.json')
// → System clean ✅

// 3. Skapa content type
aif_node_content_type_agent({
  prompt: "Create 'LED Product Review' content type, published by default, revisions enabled"
})

// 4. Lägg till fields
aif_field_type_agent({
  prompt: "Add to LED Product Review:
  - field_rating (integer, 1-5, required)
  - field_review_text (text_formatted, required)
  - field_product (entity_reference to Product, required)
  - field_reviewer_name (string)"
})

// 5. Stefan: ddev drush cr

// 6. Exportera logs igen
"Stefan kör: ddev exec /var/www/html/scripts/export-drupal-status.sh"

// 7. Claude verifierar
Filesystem:read_text_file('web/sites/default/files/logs/summary.json')
// → No new errors ✅

// 8. Visual test (om det påverkar frontend)
puppeteer_navigate('http://tritonled.ddev.site/node/add/led-product-review')
puppeteer_screenshot(1920, 1080, 'review-form')
```

---

### **Scenario 2: Skapa Produkter**

```javascript
// 1. Lista befintliga produkter
aif_commerce_agent_list_products({ bundle: 'luminaire', limit: 10 })

// 2. Kolla attributes
aif_commerce_agent_list_attribute_values({ attribute: 'watt' })
aif_commerce_agent_list_attribute_values({ attribute: 'cct' })

// 3. Skapa ny produkt
aif_commerce_product_agent({
  prompt: "Create TITAN Industrial LED High Bay with 6 variations:
  
  100W/3000K - 3495 SEK - SKU: TITAN-100-3K
  100W/4000K - 3495 SEK - SKU: TITAN-100-4K
  100W/5000K - 3495 SEK - SKU: TITAN-100-5K
  150W/3000K - 3995 SEK - SKU: TITAN-150-3K
  150W/4000K - 3995 SEK - SKU: TITAN-150-4K
  150W/5000K - 3995 SEK - SKU: TITAN-150-5K
  
  All: CRI>80, IP65, 400x200x150mm, 5kg, Suspended mount
  Description: 'Kraftfull industriarmatur för höga tak'"
})

// 4. Stefan: ddev drush cr

// 5. Visual test
puppeteer_navigate('http://tritonled.ddev.site/product/1')
puppeteer_screenshot(1920, 1080, 'product-page')
```

---

### **Scenario 3: Taxonomy för Produkter**

```javascript
// Skapa Application Areas taxonomy
aif_taxonomy_agent({
  prompt: "Create 'Application Areas' vocabulary with terms:
  - Warehouse & Logistics
  - Manufacturing
  - Retail & Commercial
  - Office Spaces
  - Healthcare Facilities
  - Education
  - Outdoor & Parking"
})

// Lägg till field på Product
aif_field_type_agent({
  prompt: "Add entity_reference field 'field_application_areas' to commerce_product luminaire, reference Application Areas taxonomy, allow multiple values"
})
```

---

## ✅ Best Practices

### **När ska AI Agents användas?**

**✅ Använd AI Agents för:**
- Skapa content types snabbt
- Lägga till flera fields samtidigt
- Skapa vocabularies + många terms
- Skapa produkter med många variations
- Repetitiva strukturändringar

**❌ Använd INTE AI Agents för:**
- Små justeringar (1 field) → Gör i UI
- Template-ändringar → Kod
- Views konfiguration → Använd Views UI
- Theme-ändringar → Kod i theme

---

### **Workflow: Alltid samma**

1. **Export logs** (`export-drupal-status.sh`)
2. **Claude kollar status** (inga errors?)
3. **Kör AI agent** (skapa content/struktur)
4. **Stefan: Cache clear** (`ddev drush cr`)
5. **Export logs igen**
6. **Claude verifierar** (inga nya errors?)
7. **Visual test** (om frontend påverkas)

---

### **Error Handling**

**Om AI Agent failar:**
```javascript
// Agent returnerar error
{
  "error": "Field field_rating already exists on bundle led_product_review"
}

// Claude rapporterar
"Error: Field redan exists. Ska jag:
1. Skippa detta field?
2. Editera befintlig field istället?
3. Ta bort och återskapa?"
```

**Om struktur blir fel:**
- Använd Drupal UI för att inspektera
- Fixa manuellt eller kör AI agent igen med korrigerad prompt
- Dokumentera issue för framtida undvikande

---

## 🎯 Integration med Test Framework

**Pre-Implementation Check (uppdaterad):**
```markdown
- [ ] Exportera Drupal logs
- [ ] Claude läser summary: Inga errors? ✅
- [ ] Använd AI agent för struktur (content types, fields, taxonomy)
- [ ] Stefan: Cache clear
- [ ] Exportera logs igen
- [ ] Claude verifierar: Inga nya errors? ✅
- [ ] Fortsätt med design implementation
```

**Post-Implementation:**
```markdown
- [ ] Visual test med Puppeteer
- [ ] Lighthouse audit
- [ ] Approve/reject
```

---

## 📚 Relaterad Dokumentation

- `/docs/04-workflows/design-testing.md` - Visual testing workflow
- `/scripts/README.md` - Log export script
- `/docs/SESSION-2025-01-11.md` - Setup dokumentation

---

## 🔮 Framtida Förbättringar

**Möjliga AI Agents att lägga till:**
- Views configuration agent
- Block placement agent
- Menu management agent
- User role & permissions agent
- Media library agent

**Automations:**
- Auto-cache clear efter AI agent operation
- Auto-log export efter changes
- Auto-visual test efter struktur-ändringar

---

**Version**: 1.0  
**Författare**: Stefan + Claude  
**Senast uppdaterad**: 2025-01-11
