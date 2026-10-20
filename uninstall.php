<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_post_meta_by_key( '_rfl_menu_item_rel' );
delete_option( 'rfl_settings' );
