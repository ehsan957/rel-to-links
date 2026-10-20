=== Rel for Links ===
Contributors: ehsan957
Tags: nofollow, menu, rel, seo, sponsored
Requires at least: 5.4
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add nofollow, sponsored, ugc, noopener, noreferrer and other rel attributes to menu links — per item or automatically for external links.

== Description ==

Rel for Links lets you control the `rel` attribute of your navigation menu links without touching code.

**Per menu item**

In Appearance → Menus, every menu item gets a "Link rel attributes" section with checkboxes. Tick any combination of:

* nofollow
* sponsored
* ugc
* noopener
* noreferrer
* external
* noindex

**Automatic for external links**

In Settings → Rel for Links, enable automatic rel attributes for menu links that point to another domain, and choose which values to add (defaults: nofollow, noopener, noreferrer).

**Plays nicely with core**

Values are merged with anything already entered in WordPress's built-in "Link Relationship (XFN)" field, and duplicates are removed.

**Developer friendly**

* `rel_for_links_options` — filter the list of available rel values.
* `rel_for_links_is_external` — filter whether a given URL is treated as external.

== Installation ==

1. Upload the `rel-for-links` folder to `/wp-content/plugins/`, or install it from Plugins → Add New.
2. Activate the plugin through the Plugins screen.
3. Edit a menu in Appearance → Menus and choose rel values for each item.
4. Optionally, configure external link defaults in Settings → Rel for Links.

== Frequently Asked Questions ==

= Does it work with block themes and the Navigation block? =

No. The plugin works with classic menus (Appearance → Menus), which are used by classic themes.

= Can I set the values in the Customizer? =

The checkboxes are shown on the Appearance → Menus screen. Values saved there are applied everywhere the menu is displayed, including Customizer previews.

= How is an "external" link detected? =

A link is external when it uses http or https and its host differs from your site's host (a leading "www." is ignored). Relative links, anchors, mailto: and tel: links are never treated as external. Use the `rel_for_links_is_external` filter to customise this.

= How do I add my own rel value? =

`
add_filter( 'rel_for_links_options', function ( $options ) {
	$options['me'] = 'me';
	return $options;
} );
`

= What happens when I delete the plugin? =

All per-item values and plugin settings are removed from the database.

== Screenshots ==

1. Rel checkboxes on a menu item.
2. Settings page for external links.

== Changelog ==

= 1.0.0 =
* Initial release.
* Per-item rel checkboxes in Appearance → Menus.
* Automatic rel attributes for external menu links.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
