<?php

if ( !defined( 'ABSPATH' ) )
	exit;


/* global constants */

# TODO move definitions of XA_DIR and XA_URL

$xa_tabs = [
	'xa_settings' => __( 'Settings', 'xa' ),
	'xa_users' => __( 'Users', 'xa' ),
];


/* backend printers */

function xa_header() {
	global $xa_tabs;
	$active = $_GET['page'];
	$url = admin_url( 'admin.php' );
	echo '<div class="wrap">' . "\n";
	echo sprintf( '<h1>%s :: %s</h1>', __( 'XA', 'xa' ), $xa_tabs[ $active ] ) . "\n";
	echo '<h2 class="nav-tab-wrapper">' . "\n";
	foreach ( $xa_tabs as $page => $title ) {
		$class = ['nav-tab'];
		if ( $page === $active )
			$class[] = 'nav-tab-active';
		echo sprintf( '<a class="%s" href="%s?page=%s">%s</a>', implode( ' ', $class ), $url, $page, $title ) . "\n";
	}
	echo '</h2>' . "\n";
}

function xa_hidden( string $name, string $value ) {
	echo sprintf( '<input type="hidden" name="%s" value="%s" />', $name, $value ) . "\n";
}

function xa_input_nonce( string $action ) {
	$nonce = wp_create_nonce( $action );
	xa_hidden( 'nonce', $nonce );
}

function xa_spinner() {
	echo '<span class="spinner" style="float: none;"></span>' . "\n";
}

function xa_description( string $description ) {
	echo sprintf( '<p class="description">%s</p>', $description ) . "\n";
}

function xa_footer() {
	echo '<hr />' . "\n";
	echo sprintf( '<p class="dashicons-before dashicons-info">%s</p>', __( 'Options are immediately saved.', 'xa' ) );
	echo '</div>' . "\n";
}
 
function xa_success( array $array = [] ) {
	header( 'content-type: application/json' );
	exit( json_encode( $array ) );
}


/* option handling */

function xa_option_nonce( string $key ): string {
	return $key;
}

add_action( 'wp_ajax_xa_option', function() {
	if ( !current_user_can( 'administrator' ) )
		exit( 'role' );
	$key = $_POST['key'];
	$action = xa_option_nonce( $key );
	if ( wp_verify_nonce( $_POST['nonce'], $action ) === FALSE )
		exit( 'nonce' );
	$value = $_POST['value'];
	if ( $value !== '' )
		update_option( $key, $value, FALSE );
	else
		delete_option( $key );
	xa_success();
} );


/* add menu and submenu pages */

add_action( 'admin_menu', function() {
	global $xa_tabs;
	$capability = 'administrator';
	$prefix = __( 'XA', 'xa' );
	if ( !current_user_can( $capability ) )
		return;
	$page_title = sprintf( '%s :: %s', $prefix, array_values( $xa_tabs )[0] );
	$menu_title = $prefix;
	$menu_slug = array_keys( $xa_tabs )[0];
	$function = $menu_slug . '_page';
	$icon_url = XA_URL . '/logo-square-16.png';
	$position = NULL;
	add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	$parent_slug = $menu_slug;
	foreach ( $xa_tabs as $menu_slug => $menu_title ) {
		$page_title = sprintf( '%s :: %s', $prefix, $menu_title );
		$function = $menu_slug . '_page';
		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}
} );

add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( !current_user_can( 'administrator' ) )
		return;
	if ( !in_array( $hook, ['toplevel_page_xa_settings'] ) )
		return;
	wp_enqueue_script( 'xa_main', XA_URL . '/main.js', ['jquery'] );
} );


/* additional files */

require_once( XA_DIR . '/settings.php' );
