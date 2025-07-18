<?php
/*
Plugin Name: Dev Essentials for SEO Implementation
Description: Manage SEO meta, header/footer scripts, internal linking, and index status (supports Yoast, AIOSEO, Rank Math, SEOPress, and Squirrly SEO).
Version: 2.2
Author: Rocket Devs
GitHub Plugin URI: https://github.com/jesieboybalongcas/dev-essentials-seo
Primary Branch: master
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

/*--------------------------------------------------------------
# Admin-menu structure
--------------------------------------------------------------*/
add_action( 'admin_menu', function () {
	// Top-level item
	// 1️⃣ Top-level menu (slug = dev-essential)
	add_menu_page(
		'Dev Essential',            // Page title (browser <title>)
		'Dev Essential',            // Menu label in sidebar
		'manage_options',           // Capability
		'dev-essential',            // Slug (parent slug)
		'dev_essential_meta_updater', // Default callback (Meta Updater)
		'dashicons-admin-generic',  // Icon
		81                          // Position
	);

	// 2️⃣ Sub-page #1 — Meta Updater
	add_submenu_page(
		'dev-essential',            // Parent slug → MUST match first arg above
		'Meta Updater',             // Page title
		'Meta Updater',             // Sub-menu label
		'manage_options',           // Capability
		'dev-essential',            // Sub-page slug (same as parent so it’s “default”)
		'dev_essential_meta_updater'// Callback function
	);

	// 3️⃣ Sub-page #2 — Internal Linking Updater
	add_submenu_page(
		'dev-essential',            // Parent slug
		'Internal Linking Updater', // Page title
		'Internal Linking Updater', // Sub-menu label
		'manage_options',           // Capability
		'dev-essential-internal-links', // Unique slug
		'dev_essential_internal_links'  // Callback
	);

	// 4️⃣ Sub-page #3 — Header and Footer Scripts
	add_submenu_page(
		'dev-essential',            // Parent slug
		'Header and Footer',        // Page title
		'Header and Footer',        // Sub-menu label
		'manage_options',           // Capability
		'dev-essential-header-footer',  // Unique slug
		'dev_essential_header_footer'   // Callback
	);

	// 5️⃣ Sub-page — Indexed Pages
	add_submenu_page(
		'dev-essential',               // Parent slug
		'Indexed Pages',               // Browser title
		'Indexed Pages',               // Sidebar label
		'manage_options',              // Capability
		'dev-essential-indexed-pages', // Unique slug
		'dev_essential_indexed_pages'  // Callback (see below)
	);

	// 5️⃣ Sub-page — Canonical URL
	add_submenu_page(
        'dev-essential',
        'Canonical URL',
        'Canonical URL',
        'manage_options',
        'dev-essential-canonical-url',
        'dev_essential_canonical_url'
    );
} );

/*--------------------------------------------------------------
# Modular code
--------------------------------------------------------------*/
require_once plugin_dir_path( __FILE__ ) . 'includes/meta-updater.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/internal-linking.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/header-footer.php';
require_once plugin_dir_path(__FILE__) . 'includes/indexed-pages.php';
require_once plugin_dir_path(__FILE__) . 'includes/canonical-url.php';
