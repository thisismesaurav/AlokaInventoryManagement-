<?php
require_once __DIR__ . '/../../../wp-load.php';
global $wpdb;
$products = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}products ORDER BY id DESC LIMIT 15" );
echo "<pre>";
print_r($products);
echo "</pre>";
