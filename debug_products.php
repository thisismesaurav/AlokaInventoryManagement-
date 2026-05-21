<?php
require_once __DIR__ . '/../../../wp-load.php';
global $wpdb;
$products = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}products ORDER BY id DESC" );
print_r($products);
