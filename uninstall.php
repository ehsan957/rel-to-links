<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_post_meta_by_key( '_rel_for_links_menu_item_rel' );
delete_option( 'rel_for_links_settings' );
