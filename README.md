# GKD Gallery

A WordPress Gutenberg block plugin for displaying photo galleries with multiple layout options, lightbox support, and EXIF metadata captions.

## Features

- **4 Layout Options:** Tiled (justified rows), Grid (uniform), Masonry (waterfall), Collage (metro)
- **2 Image Sources:** Gallery taxonomy terms, or post categories (featured images)
- **Lightbox:** Full-size image viewer with keyboard navigation (← → Esc)
- **EXIF Captions:** Template-based caption system using image metadata
- **Auto-Detection:** Automatically detects gallery taxonomy archives (no manual configuration needed)
- **Click Menu:** Optional "Image" and "Post" links per photo

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Node.js 18+ (for building)
- A `gkd_gallery` custom taxonomy (see [Custom Taxonomy](#custom-taxonomy) below)

## Custom Taxonomy

This plugin displays images from posts assigned to a custom taxonomy called `gkd_gallery`. You must register this taxonomy before activating the plugin — either in your theme's `functions.php` or in a separate plugin.

### Minimal Example

Add this to your theme's `functions.php` or a custom plugin:

```php
add_action( 'init', function() {
    register_taxonomy( 'gkd_gallery', [ 'post' ], [
        'label'        => 'Galleries',
        'labels'       => [
            'name'          => 'Galleries',
            'singular_name' => 'Gallery',
            'add_new_item'  => 'Add New Gallery',
            'edit_item'     => 'Edit Gallery',
            'search_items'  => 'Search Galleries',
        ],
        'public'       => true,
        'hierarchical' => false,
        'show_ui'      => true,
        'show_in_rest' => true,   // Required for Gutenberg block editor
        'rewrite'      => [ 'slug' => 'gkd_gallery' ],
    ] );
} );
```

### Key Requirements

- **Taxonomy ID must be `gkd_gallery`** — the plugin looks for this exact ID
- **`show_in_rest: true` is required** — without this the block editor cannot fetch gallery terms for the dropdown
- **`rewrite slug`** — controls the URL of gallery archive pages (e.g. `/gkd_gallery/infrared/`)

### Adding Images to a Gallery

1. Create or edit any **post**
2. In the right sidebar, find the **Galleries** panel
3. Assign the post to one or more gallery terms (e.g. "Infrared", "Landscape")
4. Set a **Featured Image** on the post
5. The featured image will appear in that gallery

### Creating Gallery Archive Pages

Once your taxonomy is registered, WordPress automatically creates archive URLs for each term:

```
/gkd_gallery/infrared/    → shows all images tagged "Infrared"
/gkd_gallery/landscape/   → shows all images tagged "Landscape"
```

To display these archives using the gallery block:

1. Go to **Appearance → Editor → Templates**
2. Click **Add New Template → Gallery Archives**
3. Add the **Category Gallery** block
4. Leave **Select Galleries** blank (auto-detection handles it)
5. Save the template

## Installation

1. Clone this repository into `wp-content/plugins/gkd_gallery`
2. Run `npm install`
3. Run `npm run build`
4. Activate the plugin in WordPress Admin → Plugins

## Build

```bash
npm run build    # Production build
npm run start    # Development mode with live reloading
```

Build outputs to `blocks/Gallery/build/`:
- `index.js` — Block editor interface
- `view.js` — Frontend JavaScript (lightbox, layouts)
- `style.css` — Frontend styles
- `style-rtl.css` — RTL styles

## Block Usage

### On Any Page or Post

1. Add the **Category Gallery** block
2. Choose **Image Source:** Gallery Taxonomy or Category
3. Select galleries or category
4. Choose layout and configure options
5. Save

### On Taxonomy Archive Pages (Auto-Detection)

1. Go to **Appearance → Editor → Templates**
2. Select or create the **Gallery Archives** template
3. Add the **Category Gallery** block
4. Leave **Select Galleries** blank
5. The block automatically shows images for whichever gallery is being viewed

## Architecture

```
gkd_gallery.php                     # Plugin entry point - block registration
blocks/Gallery/
  ├── block.json                    # Block metadata (Gutenberg Block API)
  ├── block_init.php               # PHP class autoloader
  ├── index.jsx                     # Block editor UI (React)
  ├── view.jsx                      # Frontend JS (lightbox, layouts)
  ├── style.scss                    # Frontend styles
  ├── Renderer.php                  # WordPress render callback
  ├── Engine.php                    # Thin adapter
  └── src/
      ├── main/Main.php             # Core orchestration
      ├── impl/Queries.php          # Database queries
      ├── impl/HtmlBuilder.php      # HTML generation
      └── library/Exif.php          # EXIF metadata utilities
```

### Rendering Flow

```
WordPress → Renderer::render()
         → Engine::run()
         → Main::run()
              → normalize attributes
              → detect context (taxonomy/category archive)
              → validate
              → Queries (fetch images)
              → HtmlBuilder (generate HTML)
```

## EXIF Caption Template

In block settings, enter a template string using these placeholders:

| Placeholder | Description |
|---|---|
| `{FileName}` | Image filename |
| `{Copyright}` | Copyright string |
| `{CameraMake}` | Camera manufacturer |
| `{CameraModel}` | Camera model |
| `{ISOSpeedRatings}` | ISO value |
| `{FocalLength}` | Focal length (mm) |
| `{ShutterSpeedValue}` | Shutter speed |
| `{FNumber}` | Aperture (f-stop) |

**Conditional text:** Use `{'prefix text', Placeholder}` to show text only when EXIF data exists.

Example: `{CameraMake} {CameraModel} | {ISOSpeedRatings} | {ShutterSpeedValue} | {'f/', FNumber} | {FocalLength}`

## License

GPL-2.0-or-later
