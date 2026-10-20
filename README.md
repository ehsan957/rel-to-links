# Rel for Links

A WordPress plugin to add `rel` attributes (`nofollow`, `sponsored`, `ugc`, `noopener`, `noreferrer`, `external`, `noindex`) to navigation menu links, either per menu item or automatically for all external links.

## Features

- **Per-item checkboxes** in **Appearance → Menus**. Tick any combination of rel values for each menu item.
- **Automatic rel for external links**: in **Settings → Rel for Links**, choose which rel values to add to menu links that point to another domain.
- **Merges with core**: values are combined with WordPress's built-in "Link Relationship (XFN)" field, and duplicates are removed.
- **Clean uninstall**: deleting the plugin removes all of its data.

## Requirements

- WordPress 5.4+
- PHP 7.0+
- A classic theme using classic menus (the block Navigation block is not supported)

## Installation

1. Copy this folder to `wp-content/plugins/rel-for-links`, or download a release zip and upload it via **Plugins → Add New → Upload Plugin**.
2. Activate **Rel for Links**.
3. Edit a menu in **Appearance → Menus** and pick rel values per item.
4. Optionally configure external-link defaults in **Settings → Rel for Links**.

## How external links are detected

A link is external when it uses `http`/`https` and its host differs from `home_url()` (a leading `www.` is ignored). Relative URLs, `#anchors`, `mailto:` and `tel:` links are never external.

## Hooks

### `rel_for_links_options`

Filter the available rel values (`value => label`).

```php
add_filter( 'rel_for_links_options', function ( $options ) {
	$options['me'] = 'me';
	return $options;
} );
```

### `rel_for_links_is_external`

Override external-link detection.

```php
add_filter( 'rel_for_links_is_external', function ( $is_external, $url ) {
	// Treat a sister site as internal.
	if ( false !== strpos( $url, 'example-partner.com' ) ) {
		return false;
	}
	return $is_external;
}, 10, 2 );
```

## Data stored

| Where | Key | Contents |
| --- | --- | --- |
| Post meta (menu items) | `_rfl_menu_item_rel` | Array of rel values for that item |
| Options | `rfl_settings` | `external_enabled` (bool), `external_rel` (array) |

## Author

[Ehsan Shariaty](https://grizzly.ir)

## License

GPL-2.0-or-later. See <https://www.gnu.org/licenses/gpl-2.0.html>.
