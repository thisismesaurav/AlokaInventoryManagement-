<?php
/**
 * Inventory Management functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Inventory_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'inventory_management_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 */
	function inventory_management_setup() {
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and WordPress will
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// Register navigation menus.
		register_nav_menus(
			array(
				'menu-1' => esc_html__( 'Primary Sidebar Menu', 'inventory-management' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );
	}
endif;
add_action( 'after_setup_theme', 'inventory_management_setup' );

/**
 * Enqueue styles and scripts.
 */
function inventory_management_scripts() {
	// Register the main WordPress stylesheet.
	wp_enqueue_style( 'inventory-management-style', get_stylesheet_uri(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'inventory_management_scripts' );

/**
 * Custom table setup and initial seed data
 */
function inventory_management_init_db() {
    global $wpdb;
    $db_version = '1.8.0';
    $installed_ver = get_option( 'inventory_management_db_version' );

    if ( $installed_ver !== $db_version ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Create wp_products Table
        $table_products = $wpdb->prefix . 'products';
        $sql_products = "CREATE TABLE $table_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_type varchar(100) NOT NULL DEFAULT '',
            product_name varchar(255) NOT NULL DEFAULT '',
            product_code varchar(100) NOT NULL DEFAULT '',
            category varchar(100) NOT NULL DEFAULT '',
            cost decimal(10,2) NOT NULL DEFAULT '0.00',
            quantity decimal(10,2) NOT NULL DEFAULT '0.00',
            image varchar(255) NOT NULL DEFAULT '',
            description text NOT NULL,
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_products );

        // Safe column drops if they exist (to fulfill: "remove the repective columns as well from its table")
        $existing_columns = $wpdb->get_col( "DESCRIBE $table_products" );
        if ( ! empty( $existing_columns ) ) {
            if ( in_array( 'barcode_symbology', $existing_columns ) ) {
                $wpdb->query( "ALTER TABLE $table_products DROP COLUMN barcode_symbology" );
            }
            if ( in_array( 'brand_name', $existing_columns ) ) {
                $wpdb->query( "ALTER TABLE $table_products DROP COLUMN brand_name" );
            }
            if ( in_array( 'price', $existing_columns ) ) {
                $wpdb->query( "ALTER TABLE $table_products DROP COLUMN price" );
            }
            if ( in_array( 'tax_method', $existing_columns ) ) {
                $wpdb->query( "ALTER TABLE $table_products DROP COLUMN tax_method" );
            }
        }

        // 2. Create wp_employee Table
        $table_employee = $wpdb->prefix . 'employee';
        $sql_employee = "CREATE TABLE $table_employee (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            phone varchar(100) NOT NULL DEFAULT '',
            email varchar(255) NOT NULL DEFAULT '',
            gender varchar(50) NOT NULL DEFAULT '',
            username varchar(100) NOT NULL DEFAULT '',
            password varchar(255) NOT NULL DEFAULT '',
            company varchar(255) NOT NULL DEFAULT '',
            status varchar(50) NOT NULL DEFAULT 'Active',
            address text NOT NULL,
            image varchar(255) NOT NULL DEFAULT '',
            id_proof_document varchar(255) NOT NULL DEFAULT '',
            address_proof_document varchar(255) NOT NULL DEFAULT '',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_employee );

        // 3. Create wp_fin_prod_log Table
        $table_prod_log = $wpdb->prefix . 'fin_prod_log';
        $sql_prod_log = "CREATE TABLE $table_prod_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            employee_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            quantity_produced int(11) NOT NULL,
            unit_labor_cost_snapshot decimal(10,2) NOT NULL,
            total_labor_payout decimal(10,2) NOT NULL,
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            Last_updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_prod_log );

        // 3. Create wp_Prod_Category Table
        $table_category = $wpdb->prefix . 'Prod_Category';
        $sql_category = "CREATE TABLE $table_category (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            code varchar(100) NOT NULL DEFAULT '',
            image varchar(255) NOT NULL DEFAULT '',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_category );

        // 4. Create wp_product_type Table
        $table_type = $wpdb->prefix . 'product_type';
        $sql_type = "CREATE TABLE $table_type (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            Type varchar(100) NOT NULL DEFAULT '',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_type );

        // 5. Create wp_customers Table
        $table_customers = $wpdb->prefix . 'customers';
        $sql_customers = "CREATE TABLE $table_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            email varchar(255) NOT NULL DEFAULT '',
            phone_number varchar(100) NOT NULL DEFAULT '',
            country varchar(255) NOT NULL DEFAULT '',
            address text NOT NULL,
            city varchar(255) NOT NULL DEFAULT '',
            state varchar(255) NOT NULL DEFAULT '',
            customer_group varchar(255) NOT NULL DEFAULT '',
            order_count int(11) NOT NULL DEFAULT '0',
            status varchar(50) NOT NULL DEFAULT 'Active',
            last_order varchar(100) NOT NULL DEFAULT '',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_customers );

        // 6. Create wp_suppliers Table
        $table_suppliers = $wpdb->prefix . 'suppliers';
        $sql_suppliers = "CREATE TABLE $table_suppliers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            company_name varchar(255) NOT NULL DEFAULT '',
            name varchar(255) NOT NULL DEFAULT '',
            email varchar(255) NOT NULL DEFAULT '',
            phone_number varchar(100) NOT NULL DEFAULT '',
            gst_number varchar(100) NOT NULL DEFAULT '',
            address text NOT NULL,
            city varchar(255) NOT NULL DEFAULT '',
            state varchar(255) NOT NULL DEFAULT '',
            country varchar(255) NOT NULL DEFAULT '',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_suppliers );

        // 7. Seed products table if empty
        $products_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_products" );
        if ( 0 == $products_count ) {
            $initial_products = array(
                array( 'product_type' => 'Standard', 'product_name' => 'Organic Cream', 'product_code' => 'CREM01', 'category' => 'Beauty', 'cost' => 10.00, 'quantity' => 10.0, 'image' => 'assets/images/table/product/01.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Rain Umbrella', 'product_code' => 'UM01', 'category' => 'Grocery', 'cost' => 20.00, 'quantity' => 15.0, 'image' => 'assets/images/table/product/02.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Serum Bottle', 'product_code' => 'SEM01', 'category' => 'Beauty', 'cost' => 25.00, 'quantity' => 50.0, 'image' => 'assets/images/table/product/03.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Coffee Beans', 'product_code' => 'COF01', 'category' => 'Food', 'cost' => 20.00, 'quantity' => 50.0, 'image' => 'assets/images/table/product/04.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Book Shelves', 'product_code' => 'FUN01', 'category' => 'Furniture', 'cost' => 30.00, 'quantity' => 25.0, 'image' => 'assets/images/table/product/05.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Dinner Set', 'product_code' => 'DIS01', 'category' => 'Grocery', 'cost' => 20.00, 'quantity' => 50.0, 'image' => 'assets/images/table/product/06.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Nike Shoes', 'product_code' => 'NIS01', 'category' => 'Shoes', 'cost' => 50.00, 'quantity' => 100.0, 'image' => 'assets/images/table/product/07.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Computer Glasses', 'product_code' => 'COG01', 'category' => 'Frames', 'cost' => 20.00, 'quantity' => 30.0, 'image' => 'assets/images/table/product/08.jpg', 'description' => 'This is test Product' ),
                array( 'product_type' => 'Standard', 'product_name' => 'Alloy Jewel Set', 'product_code' => 'AJS01', 'category' => 'Jewellery', 'cost' => 50.00, 'quantity' => 200.0, 'image' => 'assets/images/table/product/09.jpg', 'description' => 'This is test Product' ),
            );

            foreach ( $initial_products as $p ) {
                $wpdb->insert(
                    $table_products,
                    array_merge( $p, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 8. Seed employee table if empty
        $employee_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_employee" );
        if ( 0 == $employee_count ) {
            $initial_employees = array(
                array( 'name' => 'Cliff Hanger', 'email' => 'cliff@gmail.com', 'phone' => '1234567890', 'gender' => 'Male', 'username' => 'Cliff', 'password' => wp_hash_password('cliff123'), 'company' => 'Product Manager', 'status' => 'Active' ),
                array( 'name' => 'Terry Aki', 'email' => 'terry@gmail.com', 'phone' => '1234567891', 'gender' => 'Male', 'username' => 'Terry', 'password' => wp_hash_password('terry123'), 'company' => 'Stock CEO', 'status' => 'Active' ),
                array( 'name' => 'Ira Membrit', 'email' => 'ira@gmail.com', 'phone' => '1234567892', 'gender' => 'Female', 'username' => 'Ira', 'password' => wp_hash_password('ira123'), 'company' => 'Stock Manager', 'status' => 'Active' ),
                array( 'name' => 'Barb Ackue', 'email' => 'barb@gmail.com', 'phone' => '1234567893', 'gender' => 'Female', 'username' => 'Barb', 'password' => wp_hash_password('barb123'), 'company' => 'Stock Developer', 'status' => 'Active' ),
                array( 'name' => 'Max Conversion', 'email' => 'max@gmail.com', 'phone' => '1234567894', 'gender' => 'Male', 'username' => 'Max', 'password' => wp_hash_password('max123'), 'company' => 'Stock Manager', 'status' => 'Active' ),
                array( 'name' => 'Alex john', 'email' => 'alex@gmail.com', 'phone' => '1234567895', 'gender' => 'Male', 'username' => 'Alex', 'password' => wp_hash_password('alex123'), 'company' => 'Product Manager', 'status' => 'Active' ),
                array( 'name' => 'Paige Turner', 'email' => 'paige@gmail.com', 'phone' => '1234567896', 'gender' => 'Female', 'username' => 'Paige', 'password' => wp_hash_password('paige123'), 'company' => 'Stock Developer', 'status' => 'Active' ),
                array( 'name' => 'Greta Life', 'email' => 'greta@gmail.com', 'phone' => '1234567897', 'gender' => 'Female', 'username' => 'Greta', 'password' => wp_hash_password('greta123'), 'company' => 'Product Manager', 'status' => 'Active' ),
                array( 'name' => 'Anna Mull', 'email' => 'anna@gmail.com', 'phone' => '1234567898', 'gender' => 'Female', 'username' => 'Anna', 'password' => wp_hash_password('anna123'), 'company' => 'Stock Manager', 'status' => 'Active' ),
            );

            foreach ( $initial_employees as $emp ) {
                $wpdb->insert(
                    $table_employee,
                    array_merge( $emp, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 9. Seed wp_Prod_Category table if empty
        $category_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_category" );
        if ( 0 == $category_count ) {
            $initial_categories = array(
                array( 'name' => 'Beauty', 'code' => 'CREM01', 'image' => 'assets/images/table/product/01.jpg' ),
                array( 'name' => 'Grocery', 'code' => 'UM01', 'image' => 'assets/images/table/product/02.jpg' ),
                array( 'name' => 'Beauty', 'code' => 'SEM01', 'image' => 'assets/images/table/product/03.jpg' ),
                array( 'name' => 'Food', 'code' => 'COF01', 'image' => 'assets/images/table/product/04.jpg' ),
                array( 'name' => 'Furniture', 'code' => 'FUN01', 'image' => 'assets/images/table/product/05.jpg' ),
                array( 'name' => 'Grocery', 'code' => 'DIS01', 'image' => 'assets/images/table/product/06.jpg' ),
                array( 'name' => 'Shoes', 'code' => 'NIS01', 'image' => 'assets/images/table/product/07.jpg' ),
                array( 'name' => 'Frames', 'code' => 'COG01', 'image' => 'assets/images/table/product/08.jpg' ),
                array( 'name' => 'Jewellery', 'code' => 'AJS01', 'image' => 'assets/images/table/product/09.jpg' ),
            );
            foreach ( $initial_categories as $cat ) {
                $wpdb->insert(
                    $table_category,
                    array_merge( $cat, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 10. Seed wp_product_type table if empty
        $type_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_type" );
        if ( 0 == $type_count ) {
            $initial_types = array(
                array( 'Type' => 'Raw Material' ),
                array( 'Type' => 'Finished Product' ),
            );
            foreach ( $initial_types as $t ) {
                $wpdb->insert(
                    $table_type,
                    array_merge( $t, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 11. Seed wp_customers table if empty
        $customers_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_customers" );
        if ( 0 == $customers_count ) {
            $initial_customers = array(
                array( 'name' => 'Max Conversion', 'email' => 'max@gmail.com', 'phone_number' => '0123456789', 'country' => 'USA', 'address' => '123 Pine St', 'city' => 'Petaling', 'state' => 'Selangor', 'customer_group' => 'Retail', 'order_count' => 2, 'status' => 'Pending', 'last_order' => '1' ),
                array( 'name' => 'Alex john', 'email' => 'alex@gmail.com', 'phone_number' => '0123456123', 'country' => 'USA', 'address' => '456 Oak St', 'city' => 'Nanjing', 'state' => 'Jiangsu', 'customer_group' => 'Retail', 'order_count' => 3, 'status' => 'Pending', 'last_order' => '2' ),
                array( 'name' => 'Cliff Hanger', 'email' => 'cliff@gmail.com', 'phone_number' => '0189556789', 'country' => 'UK', 'address' => '789 Maple Rd', 'city' => 'Guilin', 'state' => 'Guangxi', 'customer_group' => 'Wholesale', 'order_count' => 3, 'status' => 'Pending', 'last_order' => '3' ),
                array( 'name' => 'Terry Aki', 'email' => 'terry@gmail.com', 'phone_number' => '0123205789', 'country' => 'USA', 'address' => '321 Elm Ave', 'city' => 'Suzhou', 'state' => 'Jiangsu', 'customer_group' => 'Retail', 'order_count' => 2, 'status' => 'Pending', 'last_order' => '2' ),
                array( 'name' => 'Rock lai', 'email' => 'rock@gmail.com', 'phone_number' => '0123452289', 'country' => 'UK', 'address' => '654 Birch Dr', 'city' => 'whopping', 'state' => 'London', 'customer_group' => 'Retail', 'order_count' => 3, 'status' => 'Pending', 'last_order' => '1' ),
                array( 'name' => 'Pete Sariya', 'email' => 'pete@gmail.com', 'phone_number' => '0111456789', 'country' => 'USA', 'address' => '987 Cedar Ln', 'city' => 'Petaling', 'state' => 'Selangor', 'customer_group' => 'Wholesale', 'order_count' => 5, 'status' => 'Pending', 'last_order' => '4' ),
                array( 'name' => 'Ira Membrit', 'email' => 'ira@gmail.com', 'phone_number' => '0123458719', 'country' => 'UK', 'address' => '159 Walnut Ave', 'city' => 'Francisco', 'state' => 'California', 'customer_group' => 'Retail', 'order_count' => 4, 'status' => 'Pending', 'last_order' => '2' ),
                array( 'name' => 'Barb Ackue', 'email' => 'barb@gmail.com', 'phone_number' => '0123246789', 'country' => 'USA', 'address' => '753 Chestnut St', 'city' => 'Miami', 'state' => 'Florida', 'customer_group' => 'Retail', 'order_count' => 2, 'status' => 'Pending', 'last_order' => '2' ),
                array( 'name' => 'Paige Turner', 'email' => 'paige@gmail.com', 'phone_number' => '0125856789', 'country' => 'UK', 'address' => '852 Cherry Pl', 'city' => 'Orlando', 'state' => 'Florida', 'customer_group' => 'Wholesale', 'order_count' => 9, 'status' => 'Pending', 'last_order' => '7' ),
            );
            foreach ( $initial_customers as $cust ) {
                $wpdb->insert(
                    $table_customers,
                    array_merge( $cust, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 12. Seed wp_suppliers table if empty
        $suppliers_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_suppliers" );
        if ( 0 == $suppliers_count ) {
            $initial_suppliers = array(
                array( 'company_name' => 'Fruits Supply', 'name' => 'Max Conversion', 'email' => 'max@gmail.com', 'phone_number' => '0123456789', 'gst_number' => '1234', 'address' => '123 Pine St', 'city' => 'Petaling', 'state' => 'Selangor', 'country' => 'USA' ),
                array( 'company_name' => 'Footwear Supply', 'name' => 'Paige Turner', 'email' => 'paige@gmail.com', 'phone_number' => '0125856789', 'gst_number' => '1235', 'address' => '852 Cherry Pl', 'city' => 'Orlando', 'state' => 'Florida', 'country' => 'USA' ),
                array( 'company_name' => 'Furniture Supply', 'name' => 'Barb Ackue', 'email' => 'barb@gmail.com', 'phone_number' => '0123246789', 'gst_number' => '1236', 'address' => '753 Chestnut St', 'city' => 'Miami', 'state' => 'Florida', 'country' => 'USA' ),
                array( 'company_name' => 'Food Supply', 'name' => 'Ira Membrit', 'email' => 'ira@gmail.com', 'phone_number' => '0123458719', 'gst_number' => '1237', 'address' => '159 Walnut Ave', 'city' => 'Francisco', 'state' => 'California', 'country' => 'UK' ),
                array( 'company_name' => 'Grocery Supply', 'name' => 'Pete Sariya', 'email' => 'pete@gmail.com', 'phone_number' => '0111456789', 'gst_number' => '1238', 'address' => '987 Cedar Ln', 'city' => 'Petaling', 'state' => 'Selangor', 'country' => 'USA' ),
                array( 'company_name' => 'Packing Supply', 'name' => 'Rock lai', 'email' => 'rock@gmail.com', 'phone_number' => '0123452289', 'gst_number' => '1239', 'address' => '654 Birch Dr', 'city' => 'whopping', 'state' => 'London', 'country' => 'UK' ),
                array( 'company_name' => 'Fish Supply', 'name' => 'Terry Aki', 'email' => 'terry@gmail.com', 'phone_number' => '0123205789', 'gst_number' => '1240', 'address' => '321 Elm Ave', 'city' => 'Suzhou', 'state' => 'Jiangsu', 'country' => 'USA' ),
                array( 'company_name' => 'Cloth Supply', 'name' => 'Cliff Hanger', 'email' => 'cliff@gmail.com', 'phone_number' => '0189556789', 'gst_number' => '1241', 'address' => '789 Maple Rd', 'city' => 'Guilin', 'state' => 'Guangxi', 'country' => 'UK' ),
                array( 'company_name' => 'Toy Supply', 'name' => 'Alex john', 'email' => 'alex@gmail.com', 'phone_number' => '0123456123', 'gst_number' => '1242', 'address' => '456 Oak St', 'city' => 'Nanjing', 'state' => 'Jiangsu', 'country' => 'USA' ),
            );
            foreach ( $initial_suppliers as $supp ) {
                $wpdb->insert(
                    $table_suppliers,
                    array_merge( $supp, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 13. Custom migration to insert categories from catalog
        $new_categories = array(
            array( 'name' => 'T-SHIRT', 'code' => 'TS01', 'image' => 'assets/images/table/product/01.jpg' ),
            array( 'name' => 'LOWER', 'code' => 'LW01', 'image' => 'assets/images/table/product/02.jpg' ),
            array( 'name' => 'UPPER', 'code' => 'UP01', 'image' => 'assets/images/table/product/03.jpg' ),
            array( 'name' => 'SHORTS', 'code' => 'SH01', 'image' => 'assets/images/table/product/04.jpg' ),
            array( 'name' => 'KEPRY', 'code' => 'KP01', 'image' => 'assets/images/table/product/05.jpg' ),
        );

        foreach ( $new_categories as $cat ) {
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_category WHERE name = %s", $cat['name'] ) );
            if ( ! $exists ) {
                $wpdb->insert(
                    $table_category,
                    array_merge( $cat, array(
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    ) )
                );
            }
        }

        // 14. Custom migration to insert products from catalog with cost=0, qty=1, generated codes
        $catalog_products = array(
            // T-SHIRT
            array( 'category' => 'T-SHIRT', 'name' => 'Plain', 'code' => 'TS-P' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Pattern', 'code' => 'TS-PT' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Pattern 2', 'code' => 'TS-PT2' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Plain Pocket', 'code' => 'TS-PP' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Pattern Pocket', 'code' => 'TS-PTP' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Round Neck Plain', 'code' => 'TS-RNP' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Round Neck Pattern', 'code' => 'TS-RNPT' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Round Neck Pattern 2', 'code' => 'TS-RNPT2' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Chinese color Plain', 'code' => 'TS-CCP' ),
            array( 'category' => 'T-SHIRT', 'name' => 'Chinese color Pattern', 'code' => 'TS-CCPT' ),
            
            // LOWER
            array( 'category' => 'LOWER', 'name' => 'Plain', 'code' => 'LW-P' ),
            array( 'category' => 'LOWER', 'name' => 'Plain 1 Pc chain', 'code' => 'LW-P1PC' ),
            array( 'category' => 'LOWER', 'name' => 'Plain 2 Pc chain', 'code' => 'LW-P2PC' ),
            array( 'category' => 'LOWER', 'name' => 'Plain 3 Pc chain', 'code' => 'LW-P3PC' ),
            array( 'category' => 'LOWER', 'name' => 'Plain Back Pc chain', 'code' => 'LW-PBPC' ),
            array( 'category' => 'LOWER', 'name' => 'Pattern', 'code' => 'LW-PT' ),
            array( 'category' => 'LOWER', 'name' => 'Pattern 1 Pc chain', 'code' => 'LW-PT1PC' ),
            array( 'category' => 'LOWER', 'name' => 'Pattern 2 Pc chain', 'code' => 'LW-PT2PC' ),
            array( 'category' => 'LOWER', 'name' => 'Pattern 3 Pc chain', 'code' => 'LW-PT3PC' ),
            array( 'category' => 'LOWER', 'name' => 'Pattern Back Pc chain', 'code' => 'LW-PTBPC' ),
            
            // UPPER
            array( 'category' => 'UPPER', 'name' => 'Plain', 'code' => 'UP-P' ),
            array( 'category' => 'UPPER', 'name' => 'Pattern', 'code' => 'UP-PT' ),
            array( 'category' => 'UPPER', 'name' => 'Double Pattern', 'code' => 'UP-DP' ),
            array( 'category' => 'UPPER', 'name' => '2 Pc Chain Plain', 'code' => 'UP-2PCP' ),
            array( 'category' => 'UPPER', 'name' => '2 Pc Chain Pattern', 'code' => 'UP-2PCPT' ),
            array( 'category' => 'UPPER', 'name' => '2 Pc chain DB pattern', 'code' => 'UP-2PCDBP' ),
            
            // SHORTS
            array( 'category' => 'SHORTS', 'name' => 'Plain', 'code' => 'SH-P' ),
            array( 'category' => 'SHORTS', 'name' => 'Single Piping', 'code' => 'SH-SP' ),
            array( 'category' => 'SHORTS', 'name' => 'Double Piping', 'code' => 'SH-DP' ),
            array( 'category' => 'SHORTS', 'name' => 'Pocket Plain', 'code' => 'SH-PP' ),
            array( 'category' => 'SHORTS', 'name' => 'Pocket Pattern', 'code' => 'SH-PPT' ),
            
            // KEPRY
            array( 'category' => 'KEPRY', 'name' => 'Plain', 'code' => 'KP-P' ),
            array( 'category' => 'KEPRY', 'name' => 'Pattern', 'code' => 'KP-PT' ),
        );

        foreach ( $catalog_products as $cp ) {
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_products WHERE category = %s AND product_name = %s", $cp['category'], $cp['name'] ) );
            if ( ! $exists ) {
                $wpdb->insert(
                    $table_products,
                    array(
                        'product_type'    => 'Standard',
                        'product_name'    => $cp['name'],
                        'product_code'    => $cp['code'],
                        'category'        => $cp['category'],
                        'cost'            => 0.00,
                        'quantity'        => 1.00,
                        'image'           => 'assets/images/table/product/01.jpg',
                        'description'     => 'Real product data from catalog',
                        'Created_dt'      => current_time( 'mysql' ),
                        'Last_upd_dt'     => current_time( 'mysql' ),
                        'created_by'      => 'admin',
                        'Last_updated_by' => 'admin',
                    )
                );
            }
        }

        update_option( 'inventory_management_db_version', $db_version );
    }
}
add_action( 'after_setup_theme', 'inventory_management_init_db' );

/**
 * Handle form submissions (inserts) and inline deletes
 */
function inventory_management_handle_submissions() {
    global $wpdb;

    // 1. Intercept DELETE actions
    if ( isset( $_GET['action'] ) ) {
        $action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        if ( 'delete_product' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'products', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-product' ) );
            exit;
        }

        if ( 'delete_employee' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'employee', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-users' ) );
            exit;
        }

        if ( 'delete_category' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'Prod_Category', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-category' ) );
            exit;
        }

        if ( 'delete_type' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'product_type', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-type' ) );
            exit;
        }

        if ( 'delete_customer' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'customers', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-customers' ) );
            exit;
        }

        if ( 'delete_supplier' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'suppliers', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-suppliers' ) );
            exit;
        }

        if ( 'delete_production_log' === $action && $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'fin_prod_log', array( 'id' => $id ) );
            wp_redirect( home_url( '/list-production-log' ) );
            exit;
        }
    }

    // 2. Intercept POST submissions
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset( $_POST['action_type'] ) ) {
        return;
    }

    $current_user = wp_get_current_user();
    $username = ! empty( $current_user->user_login ) ? $current_user->user_login : 'admin';
    $action_type = sanitize_text_field( wp_unslash( $_POST['action_type'] ) );

    if ( 'add_product' === $action_type ) {
        $product_type = isset( $_POST['product_type'] ) ? sanitize_text_field( wp_unslash( $_POST['product_type'] ) ) : 'Standard';
        $product_name = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
        $product_code = isset( $_POST['product_code'] ) ? sanitize_text_field( wp_unslash( $_POST['product_code'] ) ) : '';
        $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
        $cost = isset( $_POST['cost'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['cost'] ) ) ) : 0.00;
        $quantity = 1.00;
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

        // Handle File Upload
        $image_path = 'assets/images/table/product/01.jpg'; // default
        if ( ! empty( $_FILES['pic']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload( 'pic', 0 );
            if ( ! is_wp_error( $attachment_id ) ) {
                $image_path = wp_get_attachment_url( $attachment_id );
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'products',
            array(
                'product_type'      => $product_type,
                'product_name'      => $product_name,
                'product_code'      => $product_code,
                'category'          => $category,
                'cost'              => $cost,
                'quantity'          => $quantity,
                'image'             => $image_path,
                'description'       => $description,
                'Created_dt'        => current_time( 'mysql' ),
                'Last_upd_dt'       => current_time( 'mysql' ),
                'created_by'        => $username,
                'Last_updated_by'   => $username,
            )
        );

        wp_redirect( home_url( '/list-product' ) );
        exit;
    }

    if ( 'add_employee' === $action_type ) {
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $gender = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : 'Male';

        $company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Active';
        $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

        $image_path = '';
        if ( ! empty( $_POST['cropped_image'] ) ) {
            $base64_img = $_POST['cropped_image'];
            $base64_str = substr( $base64_img, strpos( $base64_img, ',' ) + 1 );
            $image_data = base64_decode( $base64_str );
            $upload_dir = wp_upload_dir();
            $safe_emp_name = sanitize_title( $name );
            $filename = 'emp_' . $safe_emp_name . '_' . uniqid() . '.png';
            $file_path = $upload_dir['path'] . '/' . $filename;
            file_put_contents( $file_path, $image_data );
            $image_path = $upload_dir['url'] . '/' . $filename;
        }

        $id_proof_path = '';
        if ( ! empty( $_FILES['id_proof_document']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $ext = pathinfo( $_FILES['id_proof_document']['name'], PATHINFO_EXTENSION );
            $safe_name = sanitize_title( $name );
            $new_filename = $safe_name . '_id_proof.' . $ext;
            $_FILES['id_proof_document']['name'] = $new_filename;
            $uploaded_file = wp_handle_upload( $_FILES['id_proof_document'], array( 'test_form' => false ) );
            if ( ! isset( $uploaded_file['error'] ) ) {
                $id_proof_path = $uploaded_file['url'];
            }
        }

        $address_proof_path = '';
        if ( ! empty( $_FILES['address_proof_document']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $ext = pathinfo( $_FILES['address_proof_document']['name'], PATHINFO_EXTENSION );
            $safe_name = sanitize_title( $name );
            $new_filename = $safe_name . '_address_proof.' . $ext;
            $_FILES['address_proof_document']['name'] = $new_filename;
            $uploaded_file = wp_handle_upload( $_FILES['address_proof_document'], array( 'test_form' => false ) );
            if ( ! isset( $uploaded_file['error'] ) ) {
                $address_proof_path = $uploaded_file['url'];
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'employee',
            array(
                'name'            => $name,
                'phone'           => $phone,
                'email'           => $email,
                'gender'          => $gender,
                'company'         => $company,
                'status'          => $status,
                'address'         => $address,
                'image'           => $image_path,
                'id_proof_document'      => $id_proof_path,
                'address_proof_document' => $address_proof_path,
                'Created_dt'      => current_time( 'mysql' ),
                'Last_upd_dt'     => current_time( 'mysql' ),
                'created_by'      => $username,
                'Last_updated_by' => $username,
            )
        );

        wp_redirect( home_url( '/list-users' ) );
        exit;
    }

    if ( 'edit_employee' === $action_type ) {
        $emp_id = isset( $_POST['employee_id'] ) ? intval( $_POST['employee_id'] ) : 0;
        if ( $emp_id > 0 ) {
            $existing_emp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}employee WHERE id = %d", $emp_id ) );
            if ( $existing_emp ) {
                $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
                $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
                $gender = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : 'Male';
                $company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
                $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Active';
                $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

                // Handle profile image update
                $image_path = $existing_emp->image;
                if ( ! empty( $_POST['cropped_image'] ) ) {
                    $base64_img = $_POST['cropped_image'];
                    $base64_str = substr( $base64_img, strpos( $base64_img, ',' ) + 1 );
                    $image_data = base64_decode( $base64_str );
                    $upload_dir = wp_upload_dir();
                    $safe_emp_name = sanitize_title( $name );
                    $filename = 'emp_' . $safe_emp_name . '_' . uniqid() . '.png';
                    $file_path = $upload_dir['path'] . '/' . $filename;
                    file_put_contents( $file_path, $image_data );
                    $image_path = $upload_dir['url'] . '/' . $filename;
                }

                // Handle ID Proof
                $id_proof_path = $existing_emp->id_proof_document;
                if ( ! empty( $_FILES['id_proof_document']['name'] ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $ext = pathinfo( $_FILES['id_proof_document']['name'], PATHINFO_EXTENSION );
                    $safe_name = sanitize_title( $name );
                    $new_filename = $safe_name . '_id_proof.' . $ext;
                    $_FILES['id_proof_document']['name'] = $new_filename;
                    $uploaded_file = wp_handle_upload( $_FILES['id_proof_document'], array( 'test_form' => false ) );
                    if ( ! isset( $uploaded_file['error'] ) ) {
                        $id_proof_path = $uploaded_file['url'];
                    }
                }

                // Handle Address Proof
                $address_proof_path = $existing_emp->address_proof_document;
                if ( ! empty( $_FILES['address_proof_document']['name'] ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $ext = pathinfo( $_FILES['address_proof_document']['name'], PATHINFO_EXTENSION );
                    $safe_name = sanitize_title( $name );
                    $new_filename = $safe_name . '_address_proof.' . $ext;
                    $_FILES['address_proof_document']['name'] = $new_filename;
                    $uploaded_file = wp_handle_upload( $_FILES['address_proof_document'], array( 'test_form' => false ) );
                    if ( ! isset( $uploaded_file['error'] ) ) {
                        $address_proof_path = $uploaded_file['url'];
                    }
                }

                $wpdb->update(
                    $wpdb->prefix . 'employee',
                    array(
                        'name'                   => $name,
                        'phone'                  => $phone,
                        'email'                  => $email,
                        'gender'                 => $gender,
                        'company'                => $company,
                        'status'                 => $status,
                        'address'                => $address,
                        'image'                  => $image_path,
                        'id_proof_document'      => $id_proof_path,
                        'address_proof_document' => $address_proof_path,
                        'Last_upd_dt'            => current_time( 'mysql' ),
                        'Last_updated_by'        => $username,
                    ),
                    array( 'id' => $emp_id )
                );
            }
        }

        wp_redirect( home_url( '/list-users' ) );
        exit;
    }

    if ( 'add_category' === $action_type ) {
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

        // Handle File Upload
        $image_path = 'assets/images/table/product/01.jpg'; // default
        if ( ! empty( $_FILES['pic']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload( 'pic', 0 );
            if ( ! is_wp_error( $attachment_id ) ) {
                $image_path = wp_get_attachment_url( $attachment_id );
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'Prod_Category',
            array(
                'name'            => $name,
                'code'            => $code,
                'image'           => $image_path,
                'Created_dt'      => current_time( 'mysql' ),
                'Last_upd_dt'     => current_time( 'mysql' ),
                'created_by'      => $username,
                'Last_updated_by' => $username,
            )
        );

        wp_redirect( home_url( '/list-category' ) );
        exit;
    }

    if ( 'add_type' === $action_type ) {
        $type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        $wpdb->insert(
            $wpdb->prefix . 'product_type',
            array(
                'Type'            => $type,
                'Created_dt'      => current_time( 'mysql' ),
                'Last_upd_dt'     => current_time( 'mysql' ),
                'created_by'      => $username,
                'Last_updated_by' => $username,
            )
        );

        wp_redirect( home_url( '/list-type' ) );
        exit;
    }

    if ( 'add_customer' === $action_type ) {
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone_number = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';
        $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
        $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
        $city = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
        $state = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
        $customer_group = isset( $_POST['customer_group'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_group'] ) ) : 'Retail';

        $wpdb->insert(
            $wpdb->prefix . 'customers',
            array(
                'name'            => $name,
                'email'           => $email,
                'phone_number'    => $phone_number,
                'country'         => $country,
                'address'         => $address,
                'city'            => $city,
                'state'           => $state,
                'customer_group'  => $customer_group,
                'order_count'     => 0,
                'status'          => 'Active',
                'last_order'      => '0',
                'Created_dt'      => current_time( 'mysql' ),
                'Last_upd_dt'     => current_time( 'mysql' ),
                'created_by'      => $username,
                'Last_updated_by' => $username,
            )
        );

        wp_redirect( home_url( '/list-customers' ) );
        exit;
    }

    if ( 'add_supplier' === $action_type ) {
        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone_number = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';
        $gst_number = isset( $_POST['gst_number'] ) ? sanitize_text_field( wp_unslash( $_POST['gst_number'] ) ) : '';
        $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
        $city = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
        $state = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
        $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';

        if ( empty( $company_name ) ) {
            $company_name = $name . ' Supply';
        }
        $wpdb->insert(
            $wpdb->prefix . 'suppliers',
            array(
                'company_name'    => $company_name,
                'name'            => $name,
                'email'           => $email,
                'phone_number'    => $phone_number,
                'gst_number'      => $gst_number,
                'address'         => $address,
                'city'            => $city,
                'state'           => $state,
                'country'         => $country,
                'Created_dt'      => current_time( 'mysql' ),
                'Last_upd_dt'     => current_time( 'mysql' ),
                'created_by'      => $username,
                'Last_updated_by' => $username,
            )
        );

        wp_redirect( home_url( '/list-suppliers' ) );
        exit;
    }
}
add_action( 'template_redirect', 'inventory_management_handle_submissions' );
// --- Daily Production Log AJAX Endpoints ---

add_action( 'wp_ajax_search_employees', 'posdash_search_employees' );
add_action( 'wp_ajax_nopriv_search_employees', 'posdash_search_employees' );
function posdash_search_employees() {
    global $wpdb;
    $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
    $table = $wpdb->prefix . 'employee';
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT id, name, image FROM $table WHERE (name LIKE %s OR id LIKE %s) AND status != 'Inactive' LIMIT 10", '%' . $wpdb->esc_like( $term ) . '%', '%' . $wpdb->esc_like( $term ) . '%' ) );
    
    $employees = array();
    foreach ( $results as $row ) {
        $img = !empty($row->image) ? $row->image : get_template_directory_uri() . '/assets/images/user/1.jpg';
        $employees[] = array( 'id' => $row->id, 'name' => $row->name, 'image' => $img );
    }
    wp_send_json_success( $employees );
}

add_action( 'wp_ajax_search_products', 'posdash_search_products' );
add_action( 'wp_ajax_nopriv_search_products', 'posdash_search_products' );
function posdash_search_products() {
    global $wpdb;
    $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
    $cat  = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
    
    $table = $wpdb->prefix . 'products';
    $cat_query = "";
    if ( ! empty( $cat ) ) {
        $cat_query = $wpdb->prepare( " AND category = %s", $cat );
    }
    
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT id, product_name as name, category, cost FROM $table WHERE (product_name LIKE %s OR id LIKE %s) $cat_query LIMIT 50", '%' . $wpdb->esc_like( $term ) . '%', '%' . $wpdb->esc_like( $term ) . '%' ) );
    
    wp_send_json_success( $results );
}

add_action( 'wp_ajax_get_daily_logs', 'posdash_get_daily_logs' );
add_action( 'wp_ajax_nopriv_get_daily_logs', 'posdash_get_daily_logs' );
function posdash_get_daily_logs() {
    global $wpdb;
    $emp_id = isset( $_GET['employee_id'] ) ? intval( wp_unslash( $_GET['employee_id'] ) ) : 0;
    $date   = isset( $_GET['produce_date'] ) ? sanitize_text_field( wp_unslash( $_GET['produce_date'] ) ) : '';
    if ( empty( $date ) ) {
        $date = current_time( 'Y-m-d' );
    }
    
    $log_table  = $wpdb->prefix . 'fin_prod_log';
    $prod_table = $wpdb->prefix . 'products';
    
    $results = $wpdb->get_results( $wpdb->prepare( "
        SELECT l.id, p.product_name, p.category, l.product_id, l.quantity_produced, l.unit_labor_cost_snapshot, l.total_labor_payout, l.Created_dt, l.produce_date
        FROM $log_table l
        LEFT JOIN $prod_table p ON l.product_id = p.id
        WHERE l.employee_id = %d AND l.produce_date = %s
        ORDER BY l.id DESC
    ", $emp_id, $date ) );
    
    wp_send_json_success( $results );
}

add_action( 'wp_ajax_get_all_categories', 'posdash_get_all_categories' );
add_action( 'wp_ajax_nopriv_get_all_categories', 'posdash_get_all_categories' );
function posdash_get_all_categories() {
    global $wpdb;
    $table = $wpdb->prefix . 'Prod_Category';
    $results = $wpdb->get_results( "SELECT id, name FROM $table ORDER BY name ASC" );
    wp_send_json_success( $results );
}

add_action( 'wp_ajax_save_production_log', 'posdash_save_production_log' );
add_action( 'wp_ajax_nopriv_save_production_log', 'posdash_save_production_log' );

add_action( 'wp_ajax_get_products_by_category', 'posdash_get_products_by_category' );
add_action( 'wp_ajax_nopriv_get_products_by_category', 'posdash_get_products_by_category' );
function posdash_get_products_by_category() {
    global $wpdb;
    $category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
    $table = $wpdb->prefix . 'products';
    if ( ! empty( $category ) ) {
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT id, product_name FROM $table WHERE category = %s ORDER BY product_name ASC", $category ) );
    } else {
        $results = $wpdb->get_results( "SELECT id, product_name FROM $table ORDER BY product_name ASC" );
    }
    wp_send_json_success( $results );
}

function posdash_save_production_log() {
    global $wpdb;
    $emp_id = isset( $_POST['employee_id'] ) ? intval( wp_unslash( $_POST['employee_id'] ) ) : 0;
    $prod_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
    $qty = isset( $_POST['quantity'] ) ? intval( wp_unslash( $_POST['quantity'] ) ) : 0;
    $date = isset( $_POST['produce_date'] ) ? sanitize_text_field( wp_unslash( $_POST['produce_date'] ) ) : '';
    if ( empty( $date ) ) {
        $date = current_time( 'Y-m-d' );
    }
    
    if ( $emp_id <= 0 || $prod_id <= 0 || $qty <= 0 ) {
        wp_send_json_error( 'Invalid input data.' );
    }
    
    // Fetch product to get unit cost (using cost column as labor cost for this context)
    $prod_table = $wpdb->prefix . 'products';
    $product = $wpdb->get_row( $wpdb->prepare( "SELECT cost FROM $prod_table WHERE id = %d", $prod_id ) );
    
    if ( ! $product ) {
        wp_send_json_error( 'Product not found.' );
    }
    
    $unit_cost = floatval( $product->cost );
    $total_payout = $unit_cost * $qty;
    $current_user = wp_get_current_user();
    $user_id = $current_user->exists() ? $current_user->ID : 1;
    
    $wpdb->insert(
        $wpdb->prefix . 'fin_prod_log',
        array(
            'employee_id'              => $emp_id,
            'product_id'               => $prod_id,
            'quantity_produced'        => $qty,
            'unit_labor_cost_snapshot' => $unit_cost,
            'total_labor_payout'       => $total_payout,
            'produce_date'             => $date,
            'Created_dt'               => current_time( 'mysql' ),
            'Last_upd_dt'              => current_time( 'mysql' ),
            'created_by'               => $user_id,
            'Last_updated_by'          => $user_id,
        )
    );
    
    wp_send_json_success( 'Log saved successfully.' );
}

// Custom DataTable initialization for the Production Work Log page
add_action( 'wp_footer', function() {
    if ( ( isset( $_GET['view'] ) && strpos( $_GET['view'], 'list-production-log' ) !== false ) || ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'list-production-log' ) !== false ) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Global state to track current month filter
            var filterCurrentMonthOnly = true;

            var table = $('.custom-data-table').DataTable({
                "pageLength": 10,
                "order": [[2, 'desc']], // Sort by Produce Date descending by default
                "columnDefs": [
                    { "orderable": false, "targets": 0 }
                ],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();

                    // helper to parse numbers
                    var intVal = function (i) {
                        if (typeof i === 'string') {
                            var text = jQuery('<div>' + i + '</div>').text();
                            return parseFloat(text.replace(/[^0-9.]/g, '')) || 0;
                        }
                        return typeof i === 'number' ? i : 0;
                    };

                    // Total over all filtered/displayed pages for Quantity (column index 5)
                    var pageTotalQty = api
                        .column(5, { search: 'applied' })
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Total over all filtered/displayed pages for Total Cost (column index 7)
                    var pageTotalCost = api
                        .column(7, { search: 'applied' })
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Update footer cells
                    $(api.column(5).footer()).html(pageTotalQty);
                    $(api.column(7).footer()).html('₹' + pageTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }
            });

            // Register custom filter logic with DataTables to filter by current month
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.className.indexOf('custom-data-table') === -1) {
                        return true;
                    }

                    if (!filterCurrentMonthOnly) {
                        return true; // Show all rows
                    }

                    // Column index 2 is Produce Date (e.g. "May 19, 2026")
                    var cellDateStrRaw = data[2];
                    if (!cellDateStrRaw) return false;

                    var cellDateObj = new Date(cellDateStrRaw);
                    if (isNaN(cellDateObj.getTime())) return false;

                    var today = new Date();
                    return (cellDateObj.getMonth() === today.getMonth() && cellDateObj.getFullYear() === today.getFullYear());
                }
            );

            // Redraw table to apply initial filter
            table.draw();

            // Handle the Show All / Show Current Month toggle button
            $(document).on('click', '#btn-toggle-view', function(e) {
                e.preventDefault();
                filterCurrentMonthOnly = !filterCurrentMonthOnly;
                
                if (filterCurrentMonthOnly) {
                    $(this).html('<i class="las la-eye mr-1"></i>Show All');
                    $(this).removeClass('btn-primary').addClass('btn-outline-primary');
                } else {
                    $(this).html('<i class="las la-calendar mr-1"></i>Show Current Month');
                    $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                }
                
                table.draw();
            });
        });
        </script>
        <?php
    }
}, 100 );

add_action( 'wp_footer', function() {
    if ( ( isset( $_GET['view'] ) && strpos( $_GET['view'], 'report-employee' ) !== false ) || ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'report-employee' ) !== false ) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function formatDate(d) {
                if (!d || isNaN(d.getTime())) return '';
                var month = '' + (d.getMonth() + 1),
                    day = '' + d.getDate(),
                    year = d.getFullYear();
                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;
                return [year, month, day].join('-');
            }
            
            // Default current month filter
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            $('#report-filter-start-date').val(formatDate(firstDay));
            $('#report-filter-end-date').val(formatDate(lastDay));
            
            var table = $('.report-employee-table').DataTable({
                "pageLength": 10,
                "order": [], 
                "columnDefs": [
                    { "orderable": false, "targets": 0 }
                ],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();

                    var intVal = function (i) {
                        if (typeof i === 'string') {
                            var text = jQuery('<div>' + i + '</div>').text();
                            return parseFloat(text.replace(/[^0-9.]/g, '')) || 0;
                        }
                        return typeof i === 'number' ? i : 0;
                    };

                    var pageTotalQty = api.column(4, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    var pageTotalCost = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    $(api.column(4).footer()).html(pageTotalQty);
                    $(api.column(6).footer()).html('â‚¹' + pageTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }
            });

            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex, rowData, counter) {
                    if (settings.nTable.className.indexOf('report-employee-table') === -1) {
                        return true;
                    }
                    
                    var filterEmployee = $('#report-filter-employee').val().trim().toLowerCase();
                    var filterEmployeeId = $('#report-filter-employee-id').val() || '';
                    var filterStartDate = $('#report-filter-start-date').val();
                    var filterEndDate = $('#report-filter-end-date').val();
                    
                    var rowNode = table.row(dataIndex).node();
                    var cellEmpName = $(rowNode).attr('data-emp-name') || '';
                    var cellEmpId = $(rowNode).attr('data-emp-id') || '';
                    var cellDateStr = $(rowNode).attr('data-raw-date') || '';
                    
                    var cellDateObj = new Date(cellDateStr);
                    var cellDateFormatted = formatDate(cellDateObj);
                    
                    if (filterEmployeeId !== '') {
                        if (cellEmpId !== filterEmployeeId) return false;
                    } else if (filterEmployee !== '') {
                        if (cellEmpName.indexOf(filterEmployee) === -1 && cellEmpId.indexOf(filterEmployee) === -1) {
                            return false;
                        }
                    }
                    
                    if (filterStartDate && cellDateFormatted < filterStartDate) {
                        return false;
                    }
                    if (filterEndDate && cellDateFormatted > filterEndDate) {
                        return false;
                    }
                    
                    return true;
                }
            );

            $('#report-filter-start-date, #report-filter-end-date').on('input change', function() { table.draw(); });
            
            var searchTimer;
            $('#report-filter-employee').on('input', function() {
                var term = $(this).val();
                $('#report-filter-employee-id').val('');
                table.draw();
                
                clearTimeout(searchTimer);
                if (term.length < 1) {
                    $('#employee-autocomplete-results').hide().empty();
                    return;
                }
                
                searchTimer = setTimeout(function() {
                    var ajaxUrl = (typeof window.posdashAjaxUrl !== 'undefined') ? window.posdashAjaxUrl : '/wp-admin/admin-ajax.php';
                    $.ajax({
                        url: ajaxUrl,
                        type: 'GET',
                        data: {
                            action: 'search_employees',
                            term: term
                        },
                        success: function(response) {
                            if (response.success && response.data.length > 0) {
                                var html = '';
                                $.each(response.data, function(index, emp) {
                                    html += '<a href="javascript:void(0);" class="dropdown-item d-flex align-items-center justify-content-between p-2 select-employee-item" data-name="' + emp.name + '" data-id="' + emp.id + '" style="border-bottom: 1px solid #f0f0f0; background: #fff; cursor: pointer;">' +
                                                '<div class="d-flex align-items-center">' +
                                                    '<img src="' + emp.image + '" class="rounded-circle mr-3" style="width: 35px; height: 35px; object-fit: cover;" alt="">' +
                                                    '<span class="font-weight-bold" style="color: #555; font-size: 14px;">' + emp.name + '</span>' +
                                                '</div>' +
                                                '<span class="text-muted" style="font-size: 13px;">ID: ' + emp.id + '</span>' +
                                            '</a>';
                                });
                                $('#employee-autocomplete-results').html(html).show();
                            } else {
                                $('#employee-autocomplete-results').hide();
                            }
                        }
                    });
                }, 300);
            });
            
            $(document).on('click', '.select-employee-item', function(e) {
                e.preventDefault();
                var selectedName = $(this).attr('data-name');
                var selectedId = $(this).attr('data-id');
                $('#report-filter-employee').val(selectedName);
                $('#report-filter-employee-id').val(selectedId);
                $('#employee-autocomplete-results').hide().empty();
                table.draw();
            });
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#report-filter-employee, #employee-autocomplete-results').length) {
                    $('#employee-autocomplete-results').hide();
                }
            });
            $('#btn-clear-report-filter').on('click', function() {
                $('#report-filter-employee').val('');
                $('#report-filter-start-date').val('');
                $('#report-filter-end-date').val('');
                table.draw();
            });
            
            table.draw();
        });
        </script>
        <?php
    }

    if ( ( isset( $_GET['view'] ) && strpos( $_GET['view'], 'report-finished-product' ) !== false ) || ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'report-finished-product' ) !== false ) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function formatDate(d) {
                if (!d || isNaN(d.getTime())) return '';
                var month = '' + (d.getMonth() + 1),
                    day = '' + d.getDate(),
                    year = d.getFullYear();
                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;
                return [year, month, day].join('-');
            }

            var table = $('.report-product-table').DataTable({
                "pageLength": 10,
                "order": [],
                "columnDefs": [
                    { "orderable": false, "targets": 0 }
                ],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();
                    var intVal = function (i) {
                        if (typeof i === 'string') {
                            var text = jQuery('<div>' + i + '</div>').text();
                            return parseFloat(text.replace(/[^0-9.]/g, '')) || 0;
                        }
                        return typeof i === 'number' ? i : 0;
                    };
                    var pageTotalQty = api.column(4, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    var pageTotalCost = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    $(api.column(4).footer()).html(pageTotalQty);
                    $(api.column(6).footer()).html('₹' + pageTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }
            });

            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex, rowData, counter) {
                    if (settings.nTable.className.indexOf('report-product-table') === -1) {
                        return true;
                    }

                    var filterCategory = $('#report-filter-category').val().toLowerCase();
                    var filterProduct  = $('#report-filter-product').val().toLowerCase();
                    var filterStart    = $('#report-filter-start-date').val();
                    var filterEnd      = $('#report-filter-end-date').val();

                    var rowNode      = table.row(dataIndex).node();
                    var rowCategory  = ($(rowNode).attr('data-category') || '').toLowerCase();
                    var rowProduct   = ($(rowNode).attr('data-product')  || '').toLowerCase();
                    var rowDate      = $(rowNode).attr('data-raw-date') || '';

                    if (filterCategory !== '' && rowCategory !== filterCategory) return false;
                    if (filterProduct  !== '' && rowProduct  !== filterProduct)  return false;
                    if (filterStart && rowDate < filterStart) return false;
                    if (filterEnd   && rowDate > filterEnd)   return false;

                    return true;
                }
            );

            // Redraw on any filter change
            $('#report-filter-category, #report-filter-product, #report-filter-start-date, #report-filter-end-date').on('change input', function() {
                table.draw();
            });

            // When category changes, filter the product dropdown
            $('#report-filter-category').on('change', function() {
                var selectedCat = $(this).val().toLowerCase();
                $('#report-filter-product option').each(function() {
                    var optVal = $(this).val();
                    if (optVal === '') { $(this).show(); return; }
                    // show all if no category selected, otherwise show only matching
                    var optCat = $(this).attr('data-category') || '';
                    $(this).toggle(selectedCat === '' || optCat.toLowerCase() === selectedCat);
                });
                $('#report-filter-product').val('');
                table.draw();
            });

            $('#btn-clear-product-filter').on('click', function() {
                $('#report-filter-category').val('');
                $('#report-filter-product').val('');
                $('#report-filter-start-date').val('');
                $('#report-filter-end-date').val('');
                $('#report-filter-product option').show();
                table.draw();
            });

            table.draw();
        });
        </script>
        <?php
    }
}, 100 );


add_action( 'wp_footer', function() {
    if ( ( isset( $_GET['view'] ) && strpos( $_GET['view'], 'report-salary' ) !== false ) || ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'report-salary' ) !== false ) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {

            var MONTH_NAMES = ['', 'January','February','March','April','May','June',
                                    'July','August','September','October','November','December'];

            // ── Defaults: current year & month ────────────────────────────────
            var today        = new Date();
            var defaultYear  = today.getFullYear();
            var defaultMonth = today.getMonth() + 1;

            $('#salary-filter-year').val(defaultYear);
            $('#salary-filter-month').val(defaultMonth);

            // ── Core: show rows matching the selected year+month ──────────────
            function updateTable() {
                var selYear  = parseInt($('#salary-filter-year').val());
                var selMonth = parseInt($('#salary-filter-month').val());
                var total    = 0;
                var visible  = 0;

                $('.salary-summary-table tbody tr[data-year]').each(function() {
                    var rowYear  = parseInt($(this).data('year'));
                    var rowMonth = parseInt($(this).data('month'));
                    if (rowYear === selYear && rowMonth === selMonth) {
                        $(this).show();
                        total += parseFloat($(this).data('salary')) || 0;
                        visible++;
                    } else {
                        $(this).hide();
                    }
                });

                // Update footer total
                $('#salary-footer-total').html('&#8377;' + total.toLocaleString('en-IN', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
                }));

                // Show "no data" row if nothing visible
                if ($('#salary-no-data-row').length === 0) {
                    if (visible === 0) {
                        if ($('#salary-empty-row').length === 0) {
                            $('.salary-summary-table tbody').append('<tr id="salary-empty-row"><td colspan="5" class="text-center text-muted py-3">No salary data for ' + MONTH_NAMES[selMonth] + ' ' + selYear + '.</td></tr>');
                        }
                    } else {
                        $('#salary-empty-row').remove();
                    }
                }
            }

            // Run on load
            updateTable();

            // ── Filters ───────────────────────────────────────────────────────
            $('#btn-apply-salary-filter').on('click', function() {
                updateTable();
            });

            $('#salary-filter-year, #salary-filter-month').on('change', function() {
                updateTable();
            });

            $('#btn-clear-salary-filter').on('click', function() {
                $('#salary-filter-year').val(defaultYear);
                $('#salary-filter-month').val(defaultMonth);
                updateTable();
            });

            // ── View button → modal ───────────────────────────────────────────
            $(document).on('click', '.view-salary-detail', function() {
                var empId    = parseInt($(this).data('emp-id'));
                var empName  = $(this).data('emp-name');
                var year     = parseInt($(this).data('year'));
                var month    = parseInt($(this).data('month'));
                var moName   = $(this).data('month-name') || MONTH_NAMES[month] || month;

                $('#modal-emp-name').text(empName);
                $('#modal-period-label').text(moName + ' ' + year);

                var logs = (window.salaryLogs || []).filter(function(l) {
                    return l.emp_id === empId && l.year === year && l.month === month;
                });

                if (logs.length === 0) {
                    $('#salary-detail-tbody').html('');
                    $('#salary-modal-no-data').show();
                    $('#salary-detail-table thead, #salary-detail-table tfoot').hide();
                    $('#modal-footer-qty').text('0');
                    $('#modal-footer-payout').text('&#8377;0.00');
                } else {
                    $('#salary-modal-no-data').hide();
                    $('#salary-detail-table thead, #salary-detail-table tfoot').show();

                    var html      = '';
                    var totalQty  = 0;
                    var totalPay  = 0;

                    logs.forEach(function(l) {
                        totalQty += l.qty;
                        totalPay += l.payout;
                        // Format date nicely
                        var d       = new Date(l.date);
                        var dateStr = isNaN(d.getTime()) ? l.date :
                                      d.toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
                        html += '<tr>' +
                            '<td>' + dateStr + '</td>' +
                            '<td>' + (l.category || '—') + '</td>' +
                            '<td>' + (l.product  || '—') + '</td>' +
                            '<td>' + l.qty + '</td>' +
                            '<td>&#8377;' + parseFloat(l.unit_cost).toFixed(2) + '</td>' +
                            '<td class="font-weight-bold text-success">&#8377;' + parseFloat(l.payout).toFixed(2) + '</td>' +
                        '</tr>';
                    });

                    $('#salary-detail-tbody').html(html);
                    $('#modal-footer-qty').text(totalQty);
                    $('#modal-footer-payout').html('&#8377;' + totalPay.toLocaleString('en-IN', {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                    }));
                }

                $('#salary-detail-modal').modal('show');
            });
        });
        </script>
        <?php
    }
}, 100 );


add_action( 'init', function() {
    global $wpdb;
    if ( ! get_option( 'posdash_fin_prod_log_fk_updated_v3' ) ) {
        // 1. created_by / Last_updated_by constraints
        $wpdb->query("UPDATE {$wpdb->prefix}fin_prod_log SET created_by = 1 WHERE created_by NOT REGEXP '^[0-9]+$' OR created_by = 0");
        $wpdb->query("UPDATE {$wpdb->prefix}fin_prod_log SET Last_updated_by = 1 WHERE Last_updated_by NOT REGEXP '^[0-9]+$' OR Last_updated_by = 0");
        
        // Ensure all created_by / Last_updated_by refer to existing users, fallback to 1 (admin/first user)
        $user_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}users");
        if ( ! empty( $user_ids ) ) {
            $user_ids_str = implode(',', array_map('intval', $user_ids));
            $wpdb->query("UPDATE {$wpdb->prefix}fin_prod_log SET created_by = 1 WHERE created_by NOT IN ($user_ids_str)");
            $wpdb->query("UPDATE {$wpdb->prefix}fin_prod_log SET Last_updated_by = 1 WHERE Last_updated_by NOT IN ($user_ids_str)");
        }
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log MODIFY created_by bigint(20) unsigned NOT NULL DEFAULT 1");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log MODIFY Last_updated_by bigint(20) unsigned NOT NULL DEFAULT 1");

        // Check and add fk_prod_log_created_by
        $fk_created = $wpdb->get_row("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->prefix}fin_prod_log' AND CONSTRAINT_NAME = 'fk_prod_log_created_by'");
        if ( ! $fk_created ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log ADD CONSTRAINT fk_prod_log_created_by FOREIGN KEY (created_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE");
        }

        // 2. employee_id constraints
        $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log MODIFY employee_id bigint(20) NOT NULL");
        
        // Delete orphaned logs where employee_id doesn't exist in employee table
        $wpdb->query("DELETE FROM {$wpdb->prefix}fin_prod_log WHERE employee_id NOT IN (SELECT id FROM {$wpdb->prefix}employee)");
        
        // Check and add fk_prod_log_emp_id
        $fk_emp = $wpdb->get_row("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->prefix}fin_prod_log' AND CONSTRAINT_NAME = 'fk_prod_log_emp_id'");
        if ( ! $fk_emp ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log ADD CONSTRAINT fk_prod_log_emp_id FOREIGN KEY (employee_id) REFERENCES {$wpdb->prefix}employee(id) ON DELETE CASCADE");
        }

        // 3. product_id constraints
        $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log MODIFY product_id bigint(20) NOT NULL");
        
        // Delete orphaned logs where product_id doesn't exist in products table
        $wpdb->query("DELETE FROM {$wpdb->prefix}fin_prod_log WHERE product_id NOT IN (SELECT id FROM {$wpdb->prefix}products)");
        
        // Check and add fk_prod_log_product_id
        $fk_prod = $wpdb->get_row("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->prefix}fin_prod_log' AND CONSTRAINT_NAME = 'fk_prod_log_product_id'");
        if ( ! $fk_prod ) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}fin_prod_log ADD CONSTRAINT fk_prod_log_product_id FOREIGN KEY (product_id) REFERENCES {$wpdb->prefix}products(id) ON DELETE CASCADE");
        }

        update_option( 'posdash_fin_prod_log_fk_updated_v3', true );
    }
});
