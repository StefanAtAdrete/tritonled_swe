# Offcanvas Mobilmeny med Bootstrap Collapse

**Datum:** 2026-03-15
**Task:** TASK-016
**Status:** ✅ Löst

---

## Problem

Drupal/Radix renderar samma meny-block i både desktop navbar och offcanvas mobilmeny.
Samma HTML = samma `data-bs-toggle="dropdown"` på båda — dropdown-overlay på mobil är dålig UX.

**Önskat beteende:** Inline collapse-undermeny i offcanvas (ul → li nivå 1 → li nivå 2).

---

## Lösning

Separat meny-block för offcanvas med egen Twig-template och Bootstrap Collapse-struktur.

### Steg 1 — Ny region i tritonled_radix.info.yml

```yaml
regions:
  navbar_left: 'Navbar left'
  navbar_offcanvas: 'Navbar offcanvas'   # ← ny
  navbar_left_2: 'Navbar left 2'
```

### Steg 2 — Uppdatera page.html.twig + page--front.html.twig

Offcanvas-body använder `navbar_offcanvas` istället för `navbar_left`:

```twig
{# Offcanvas body — använd navbar_offcanvas (inte navbar_left) #}
{% if page.navbar_offcanvas %}
  {{ page.navbar_offcanvas }}
{% endif %}
```

### Steg 3 — Nytt meny-block i admin UI

Skapa ett nytt `System Menu Block → Main navigation` och placera i region `navbar_offcanvas`.
Block-ID blir t.ex. `tritonled_radix_mainnavigation`.

### Steg 4 — hook_theme_suggestions_menu_alter i tritonled_radix.theme

Lägger till block-ID som template-suggestion för menyer.
**OBS:** Drupal genererar INTE block-ID-baserade suggestions för menu-hook automatiskt — detta krävs.

```php
/**
 * Implements hook_theme_suggestions_menu_alter().
 */
function tritonled_radix_theme_suggestions_menu_alter(array &$suggestions, array $variables) {
  if (!empty($variables['attributes']['block_id'])) {
    $block_id = $variables['attributes']['block_id'];
    $menu_name = $variables['menu_name'];
    $suggestions[] = 'menu__' . str_replace('-', '_', $menu_name)
      . '__' . str_replace('-', '_', $block_id);
  }
}

/**
 * Implements hook_preprocess_block().
 * Skickar block_id till menu-elementets attributes.
 */
function tritonled_radix_preprocess_block(&$variables) {
  if (isset($variables['content']['#menu_name']) && isset($variables['elements']['#id'])) {
    $variables['content']['#attributes']['block_id'] = $variables['elements']['#id'];
  }
}
```

### Steg 5 — Template: menu--main--tritonled-radix-mainnavigation.html.twig

Placeras i `themes/custom/tritonled_radix/templates/navigation/`.

```twig
<ul class="navbar-nav flex-column w-100">
  {% for item in items %}
    {% set item_id = 'offcanvas-submenu-' ~ loop.index %}
    <li class="nav-item{% if item.below %} has-children{% endif %}{% if item.in_active_trail %} active{% endif %}">
      {% if item.below %}
        {# Förälder med undermeny — knapp med collapse, ingen sidnavigering #}
        <button
          class="nav-link w-100 text-start d-flex align-items-center justify-content-between offcanvas-submenu-toggle{% if item.in_active_trail %} active{% endif %}"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#{{ item_id }}"
          aria-expanded="false"
          aria-controls="{{ item_id }}">
          <span>{{ item.title }}</span>
          <span class="offcanvas-chevron">&#8250;</span>
        </button>
        <ul class="collapse list-unstyled offcanvas-submenu ps-3" id="{{ item_id }}">
          {% for child in item.below %}
            <li class="nav-item{% if child.in_active_trail %} active{% endif %}">
              {{ link(child.title, child.url, {
                'class': ['nav-link', 'py-2', child.in_active_trail ? 'active' : '']
              }) }}
            </li>
          {% endfor %}
        </ul>
      {% else %}
        {{ link(item.title, item.url, {
          'class': ['nav-link', item.in_active_trail ? 'active' : '']
        }) }}
      {% endif %}
    </li>
  {% endfor %}
</ul>
```

### Steg 6 — CSS: css/components/offcanvas-menu.css

```css
.offcanvas-submenu-toggle {
  background: none;
  border: none;
  color: inherit;
  font-weight: inherit;
}

.offcanvas-submenu-toggle:hover,
.offcanvas-submenu-toggle:focus {
  background: none;
  color: var(--bs-nav-link-hover-color);
}

.offcanvas-chevron {
  display: inline-block;
  transition: transform 0.2s ease;
  font-size: 1.2rem;
  line-height: 1;
  flex-shrink: 0;
}

.offcanvas-submenu-toggle[aria-expanded="true"] .offcanvas-chevron {
  transform: rotate(90deg);
}

.offcanvas-submenu {
  border-left: 2px solid var(--bs-border-color);
}
```

---

## Viktiga lärdomar

### SDC-komponenter kan inte overridas från child-theme
`radix:dropdown-menu` namespace löses alltid mot Radix — inte child-temat.
Lösning: Eget block eller menu-template istället för att försöka overrida SDC.

### Drupal genererar inte block-ID suggestions för menu-hook automatiskt
Till skillnad från `block`-hooken måste `menu`-hooken få block-ID via
`hook_theme_suggestions_menu_alter` + `preprocess_block`.

### Förälderlänk med undermeny = button, inte a
`<a href="">` med tom href orsakar sidladdning.
Lösning: `<button>` med `data-bs-toggle="collapse"` — ingen navigering, bara toggle.

### Samma block kan inte renderas två gånger
Desktop och offcanvas måste ha *separata* block-instanser.
Drupal renderar inte samma block ID två gånger på samma sida.

---

## Filer

```
web/themes/custom/tritonled_radix/
├── tritonled_radix.info.yml                    (ny region navbar_offcanvas)
├── tritonled_radix.theme                       (hook_theme_suggestions_menu_alter)
├── tritonled_radix.libraries.yml               (offcanvas-menu.css registrerad)
├── templates/
│   ├── navigation/
│   │   └── menu--main--tritonled-radix-mainnavigation.html.twig
│   └── page/
│       ├── page.html.twig                      (navbar_offcanvas i offcanvas-body)
│       └── page--front.html.twig               (samma)
└── css/components/
    └── offcanvas-menu.css
```
