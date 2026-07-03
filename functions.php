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
    $db_version = '2.0.0';
    $installed_ver = get_option( 'inventory_management_db_version' );

    $table_products = $wpdb->prefix . 'products';
    $table_category = $wpdb->prefix . 'prod_category';
    $old_category   = $wpdb->prefix . 'Prod_Category';

    // Rename Prod_Category to prod_category if the old camelCase table exists but the lowercase one doesn't (Linux case sensitivity helper)
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$old_category'" ) && ! $wpdb->get_var( "SHOW TABLES LIKE '$table_category'" ) ) {
        $wpdb->query( "RENAME TABLE $old_category TO $table_category" );
    }

    if ( $installed_ver !== $db_version || ! $wpdb->get_var( "SHOW TABLES LIKE '$table_products'" ) || ! $wpdb->get_var( "SHOW TABLES LIKE '$table_category'" ) ) {
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
            produce_date date NOT NULL DEFAULT '0000-00-00',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            Last_updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_prod_log );

        // 3. Create wp_prod_category Table
        $table_category = $wpdb->prefix . 'prod_category';
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
            company_name varchar(255) NOT NULL DEFAULT '',
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

        // Safe check to add company_name column if it does not exist
        $existing_customers_columns = $wpdb->get_col( "DESCRIBE $table_customers" );
        if ( ! empty( $existing_customers_columns ) ) {
            if ( ! in_array( 'company_name', $existing_customers_columns ) ) {
                $wpdb->query( "ALTER TABLE $table_customers ADD COLUMN company_name varchar(255) NOT NULL DEFAULT '' AFTER id" );
            }
        }

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

        // 8. Create wp_raw_material Table
        $table_raw_material = $wpdb->prefix . 'raw_material';
        $sql_raw_material = "CREATE TABLE $table_raw_material (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_date date NOT NULL DEFAULT '0000-00-00',
            product_id bigint(20) NOT NULL,
            quantity decimal(10,2) NOT NULL DEFAULT '0.00',
            Created_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            Last_upd_dt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_by varchar(100) NOT NULL DEFAULT '',
            Last_updated_by varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_raw_material );

        update_option( 'inventory_management_db_version', $db_version );
    }
}
add_action( 'after_setup_theme', 'inventory_management_init_db' );

/**
 * Handle form submissions (inserts) and inline deletes
 */
function inventory_management_handle_submissions() {
    global $wpdb;

    // C2: Authentication gate — abort for unauthenticated users
    if ( ! is_user_logged_in() ) {
        return;
    }

    // 1. Intercept DELETE actions
    if ( isset( $_GET['action'] ) ) {
        $action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        // C1: Verify nonce for all destructive GET actions
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'posdash_delete_' . $action . '_' . $id ) ) {
            wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'inventory-management' ) );
        }

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
            $wpdb->delete( $wpdb->prefix . 'prod_category', array( 'id' => $id ) );
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

    // C1: Verify nonce for all POST submissions
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'posdash_form_action' ) ) {
        wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'inventory-management' ) );
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
            // H3: Restrict MIME types
            $file_type = wp_check_filetype( basename( $_FILES['pic']['name'] ) );
            if ( strpos( $file_type['type'], 'image/' ) === 0 ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $attachment_id = media_handle_upload( 'pic', 0 );
                if ( ! is_wp_error( $attachment_id ) ) {
                    $image_path = wp_get_attachment_url( $attachment_id );
                }
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
        // M2/M3: Allowlist validation for status and gender
        $allowed_statuses = array( 'Active', 'Inactive' );
        $allowed_genders  = array( 'Male', 'Female' );
        $status = isset( $_POST['status'] ) && in_array( $_POST['status'], $allowed_statuses, true ) ? $_POST['status'] : 'Active';
        $gender = isset( $_POST['gender'] ) && in_array( $_POST['gender'], $allowed_genders, true ) ? $_POST['gender'] : 'Male';
        $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

        $image_path = '';
        if ( ! empty( $_POST['cropped_image'] ) ) {
            // H2: Validate that base64 string is actually a safe image before saving
            $base64_img = $_POST['cropped_image'];
            if ( preg_match( '/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64_img ) ) {
                $base64_str = substr( $base64_img, strpos( $base64_img, ',' ) + 1 );
                $image_data = base64_decode( $base64_str, true );
                if ( false !== $image_data ) {
                    $image_info = @getimagesizefromstring( $image_data );
                    if ( $image_info && isset( $image_info['mime'] ) && strpos( $image_info['mime'], 'image/' ) === 0 ) {
                        $upload_dir = wp_upload_dir();
                        $safe_emp_name = sanitize_title( $name );
                        $filename = 'emp_' . $safe_emp_name . '_' . uniqid() . '.png';
                        $file_path = $upload_dir['path'] . '/' . $filename;
                        file_put_contents( $file_path, $image_data );
                        $image_path = $upload_dir['url'] . '/' . $filename;
                    }
                }
            }
        }

        // H3: Restrict allowed MIME types for proof documents
        $allowed_proof_mimes = array(
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        );
        $id_proof_path = '';
        if ( ! empty( $_FILES['id_proof_document']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $ext = strtolower( pathinfo( $_FILES['id_proof_document']['name'], PATHINFO_EXTENSION ) );
            if ( array_key_exists( $ext, $allowed_proof_mimes ) ) {
                $safe_name = sanitize_title( $name );
                $new_filename = $safe_name . '_id_proof.' . $ext;
                $_FILES['id_proof_document']['name'] = $new_filename;
                $uploaded_file = wp_handle_upload( $_FILES['id_proof_document'], array( 'test_form' => false, 'mimes' => $allowed_proof_mimes ) );
                if ( ! isset( $uploaded_file['error'] ) ) {
                    $id_proof_path = $uploaded_file['url'];
                }
            }
        }

        $address_proof_path = '';
        if ( ! empty( $_FILES['address_proof_document']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $ext = strtolower( pathinfo( $_FILES['address_proof_document']['name'], PATHINFO_EXTENSION ) );
            if ( array_key_exists( $ext, $allowed_proof_mimes ) ) {
                $safe_name = sanitize_title( $name );
                $new_filename = $safe_name . '_address_proof.' . $ext;
                $_FILES['address_proof_document']['name'] = $new_filename;
                $uploaded_file = wp_handle_upload( $_FILES['address_proof_document'], array( 'test_form' => false, 'mimes' => $allowed_proof_mimes ) );
                if ( ! isset( $uploaded_file['error'] ) ) {
                    $address_proof_path = $uploaded_file['url'];
                }
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
                // M2/M3: Allowlist validation for status and gender in edit
                $allowed_statuses = array( 'Active', 'Inactive' );
                $allowed_genders  = array( 'Male', 'Female' );
                $gender = isset( $_POST['gender'] ) && in_array( $_POST['gender'], $allowed_genders, true ) ? $_POST['gender'] : 'Male';
                $company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
                $status = isset( $_POST['status'] ) && in_array( $_POST['status'], $allowed_statuses, true ) ? $_POST['status'] : 'Active';
                $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

                // Handle profile image update — H2: validate image before saving
                $image_path = $existing_emp->image;
                if ( ! empty( $_POST['cropped_image'] ) ) {
                    $base64_img = $_POST['cropped_image'];
                    if ( preg_match( '/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64_img ) ) {
                        $base64_str = substr( $base64_img, strpos( $base64_img, ',' ) + 1 );
                        $image_data = base64_decode( $base64_str, true );
                        if ( false !== $image_data ) {
                            $image_info = @getimagesizefromstring( $image_data );
                            if ( $image_info && isset( $image_info['mime'] ) && strpos( $image_info['mime'], 'image/' ) === 0 ) {
                                $upload_dir = wp_upload_dir();
                                $safe_emp_name = sanitize_title( $name );
                                $filename = 'emp_' . $safe_emp_name . '_' . uniqid() . '.png';
                                $file_path = $upload_dir['path'] . '/' . $filename;
                                file_put_contents( $file_path, $image_data );
                                $image_path = $upload_dir['url'] . '/' . $filename;
                            }
                        }
                    }
                }

                // Handle ID Proof — H3: restrict MIME types
                $allowed_proof_mimes_edit = array(
                    'pdf'  => 'application/pdf',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                );
                $id_proof_path = $existing_emp->id_proof_document;
                if ( ! empty( $_FILES['id_proof_document']['name'] ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $ext = strtolower( pathinfo( $_FILES['id_proof_document']['name'], PATHINFO_EXTENSION ) );
                    if ( array_key_exists( $ext, $allowed_proof_mimes_edit ) ) {
                        $safe_name = sanitize_title( $name );
                        $new_filename = $safe_name . '_id_proof.' . $ext;
                        $_FILES['id_proof_document']['name'] = $new_filename;
                        $uploaded_file = wp_handle_upload( $_FILES['id_proof_document'], array( 'test_form' => false, 'mimes' => $allowed_proof_mimes_edit ) );
                        if ( ! isset( $uploaded_file['error'] ) ) {
                            $id_proof_path = $uploaded_file['url'];
                        }
                    }
                }

                // Handle Address Proof — H3: restrict MIME types
                $address_proof_path = $existing_emp->address_proof_document;
                if ( ! empty( $_FILES['address_proof_document']['name'] ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $ext = strtolower( pathinfo( $_FILES['address_proof_document']['name'], PATHINFO_EXTENSION ) );
                    if ( array_key_exists( $ext, $allowed_proof_mimes_edit ) ) {
                        $safe_name = sanitize_title( $name );
                        $new_filename = $safe_name . '_address_proof.' . $ext;
                        $_FILES['address_proof_document']['name'] = $new_filename;
                        $uploaded_file = wp_handle_upload( $_FILES['address_proof_document'], array( 'test_form' => false, 'mimes' => $allowed_proof_mimes_edit ) );
                        if ( ! isset( $uploaded_file['error'] ) ) {
                            $address_proof_path = $uploaded_file['url'];
                        }
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
            $wpdb->prefix . 'prod_category',
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
        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
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
                'company_name'    => $company_name,
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

    if ( 'edit_profile' === $action_type ) {
        $current_user = wp_get_current_user();
        if ( ! $current_user->exists() ) {
            wp_redirect( home_url( '/auth-sign-in' ) );
            exit;
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $nickname = isset( $_POST['nickname'] ) ? sanitize_text_field( wp_unslash( $_POST['nickname'] ) ) : '';
        $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
        $website = isset( $_POST['website'] ) ? sanitize_url( wp_unslash( $_POST['website'] ) ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

        // Validation: Nickname is required
        if ( empty( $nickname ) ) {
            wp_redirect( home_url( '/user-profile?error=' . urlencode( 'Nickname cannot be empty.' ) ) );
            exit;
        }

        // Validation: Email is required
        if ( empty( $email ) ) {
            wp_redirect( home_url( '/user-profile?error=' . urlencode( 'Email address cannot be empty.' ) ) );
            exit;
        }

        // Prepare arguments for wp_update_user
        $userdata = array(
            'ID'           => $current_user->ID,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'nickname'     => $nickname,
            'display_name' => $display_name,
            'user_url'     => $website,
            'description'  => $description,
        );

        $result = wp_update_user( $userdata );

        if ( is_wp_error( $result ) ) {
            $error_message = urlencode( $result->get_error_message() );
            wp_redirect( home_url( '/user-profile?error=' . $error_message ) );
            exit;
        } else {
            wp_redirect( home_url( '/user-profile?success=profile_updated' ) );
            exit;
        }
    }
}
add_action( 'template_redirect', 'inventory_management_handle_submissions' );
// --- Daily Production Log AJAX Endpoints ---

add_action( 'wp_ajax_search_employees', 'posdash_search_employees' );
// H1: Removed nopriv - requires login
function posdash_search_employees() {
    // H1: Authentication required for all AJAX data endpoints
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
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
// H1: Removed nopriv - requires login
function posdash_search_products() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
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
// H1: Removed nopriv - requires login
function posdash_get_daily_logs() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
    global $wpdb;
    $emp_id = isset( $_GET['employee_id'] ) ? intval( wp_unslash( $_GET['employee_id'] ) ) : 0;
    $date   = isset( $_GET['produce_date'] ) ? sanitize_text_field( wp_unslash( $_GET['produce_date'] ) ) : '';
    // M1: Validate date format
    if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
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
// H1: Removed nopriv - requires login
function posdash_get_all_categories() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'prod_category';
    $results = $wpdb->get_results( "SELECT id, name FROM $table ORDER BY name ASC" );
    wp_send_json_success( $results );
}

add_action( 'wp_ajax_save_production_log', 'posdash_save_production_log' );
// H1: nopriv REMOVED - save actions require login

add_action( 'wp_ajax_get_products_by_category', 'posdash_get_products_by_category' );
// H1: Removed nopriv - requires login
function posdash_get_products_by_category() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
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
    // H1: Authentication + nonce check for write operations
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
    check_ajax_referer( 'posdash_ajax_action', 'nonce' );
    global $wpdb;
    $emp_id = isset( $_POST['employee_id'] ) ? intval( wp_unslash( $_POST['employee_id'] ) ) : 0;
    $prod_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
    $qty = isset( $_POST['quantity'] ) ? intval( wp_unslash( $_POST['quantity'] ) ) : 0;
    $date = isset( $_POST['produce_date'] ) ? sanitize_text_field( wp_unslash( $_POST['produce_date'] ) ) : '';
    // M1: Validate date format Y-m-d
    if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! strtotime( $date ) ) {
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
            var filterCurrentMonthOnly = true;

            var table = $('.custom-data-table').DataTable({
                "pageLength": 10,
                "order": [[1, 'desc']], // Sort by Produce Date descending by default (index 1 after removing checkbox)
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

                    // Total over all filtered/displayed pages for Quantity (column index 4 after removing checkbox)
                    var pageTotalQty = api
                        .column(4, { search: 'applied' })
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Total over all filtered/displayed pages for Total Cost (column index 6 after removing checkbox)
                    var pageTotalCost = api
                        .column(6, { search: 'applied' })
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Update footer cells
                    $(api.column(4).footer()).html(pageTotalQty);
                    $(api.column(6).footer()).html('₹' + pageTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
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

                    // Column index 1 is Produce Date (e.g. "May 19, 2026")
                    var cellDateStrRaw = data[1];
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
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();

                    var intVal = function (i) {
                        if (typeof i === 'string') {
                            var text = jQuery('<div>' + i + '</div>').text();
                            return parseFloat(text.replace(/[^0-9.]/g, '')) || 0;
                        }
                        return typeof i === 'number' ? i : 0;
                    };

                    var pageTotalQty = api.column(3, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    var pageTotalCost = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    $(api.column(3).footer()).html(pageTotalQty);
                    $(api.column(5).footer()).html('₹' + pageTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
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
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();
                    var intVal = function (i) {
                        if (typeof i === 'string') {
                            var text = jQuery('<div>' + i + '</div>').text();
                            return parseFloat(text.replace(/[^0-9.]/g, '')) || 0;
                        }
                        return typeof i === 'number' ? i : 0;
                    };
                    var pageTotalQty = api.column(3, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    var pageTotalCost = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    $(api.column(3).footer()).html(pageTotalQty);
                    $(api.column(5).footer()).html('₹' + pageTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
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
                            $('.salary-summary-table tbody').append('<tr id="salary-empty-row"><td colspan="4" class="text-center text-muted py-3">No salary data for ' + MONTH_NAMES[selMonth] + ' ' + selYear + '.</td></tr>');
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

/**
 * POS Dash - User Authentication, Redirects, and Idle Logout (3 Minutes)
 */

add_action( 'template_redirect', function() {
    // Determine view name (mimic index.php routing)
    $view = '';
    if ( isset( $_GET['view'] ) ) {
        $view = sanitize_text_field( wp_unslash( $_GET['view'] ) );
    } else {
        $request_uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $request_uri = preg_replace( '#/+#', '/', $request_uri );
        $request_path = trim( $request_uri, '/' );
        if ( empty( $request_path ) ) {
            $view = 'backend/index';
        } else {
            $view = $request_path;
        }
    }
    $view = trim( $view, '/' );
    $view = preg_replace( '/[^a-zA-Z0-9_\-\/]/', '', $view );

    // Check for manual logout action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'logout' ) {
        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie( 'posdash_last_activity', '', array( 'expires' => time() - 3600, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Strict' ) );
        } else {
            setcookie( 'posdash_last_activity', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
        wp_logout();
        $loggedout_reason = ( isset( $_GET['reason'] ) && $_GET['reason'] === 'idle' ) ? 'idle' : 'manual';
        wp_safe_redirect( add_query_arg( 'loggedout', $loggedout_reason, home_url( '/auth-sign-in' ) ) );
        exit;
    }

    $public_views = array( 'auth-sign-in', 'auth-sign-up', 'auth-recoverpw', 'auth-confirm-mail' );
    $clean_view_name = $view;
    if ( strpos( $clean_view_name, 'backend/' ) === 0 ) {
        $clean_view_name = substr( $clean_view_name, 8 );
    }

    if ( ! is_user_logged_in() ) {
        // Handle login POST
        if ( $clean_view_name === 'auth-sign-in' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
            $creds = array(
                'user_login'    => sanitize_text_field( wp_unslash( $_POST['username'] ) ),
                'user_password' => wp_unslash( $_POST['password'] ), // M4: Added wp_unslash
                'remember'      => isset( $_POST['remember'] ) ? true : false,
            );

            $user = wp_signon( $creds, is_ssl() );

            if ( is_wp_error( $user ) ) {
                global $posdash_login_error;
                $posdash_login_error = $user->get_error_message();
            } else {
                // L3: Hardened cookie
                if ( PHP_VERSION_ID >= 70300 ) {
                    setcookie( 'posdash_last_activity', time(), array( 'expires' => time() + 86400 * 30, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Strict' ) );
                } else {
                    setcookie( 'posdash_last_activity', time(), time() + 86400 * 30, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
                }
                wp_safe_redirect( home_url( '/' ) );
                exit;
            }
        }

        // Handle signup POST
        if ( $clean_view_name === 'auth-sign-up' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['signup_action'] ) ) {
            $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
            $last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
            $email      = sanitize_email( wp_unslash( $_POST['email'] ) );
            $password   = wp_unslash( $_POST['password'] ); // M4: Added wp_unslash
            $confirm_password = wp_unslash( $_POST['confirm_password'] );
            $invite_code = isset( $_POST['invite_code'] ) ? sanitize_text_field( wp_unslash( $_POST['invite_code'] ) ) : '';

            global $posdash_signup_error, $posdash_signup_success;
            $valid_invite_code = get_option( 'inventory_invite_token', 'SECRET-2026' ); // default fallback

            if ( empty( $email ) || ! is_email( $email ) ) {
                $posdash_signup_error = 'Please enter a valid email address.';
            } elseif ( $invite_code !== $valid_invite_code ) {
                // H4: Restrict registration to valid invite code
                $posdash_signup_error = 'Invalid invite code.';
            } elseif ( empty( $password ) || strlen( $password ) < 8 ) {
                // L2: Increased min password length to 8
                $posdash_signup_error = 'Password must be at least 8 characters long.';
            } elseif ( $password !== $confirm_password ) {
                $posdash_signup_error = 'Passwords do not match.';
            } elseif ( email_exists( $email ) ) {
                $posdash_signup_error = 'This email address is already registered.';
            } else {
                $base_username = sanitize_user( current( explode( '@', $email ) ) );
                $username = $base_username;
                $i = 1;
                while ( username_exists( $username ) ) {
                    $username = $base_username . $i;
                    $i++;
                }

                $user_id = wp_create_user( $username, $password, $email );
                if ( is_wp_error( $user_id ) ) {
                    $posdash_signup_error = $user_id->get_error_message();
                } else {
                    wp_update_user( array(
                        'ID'         => $user_id,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                    ) );

                    // Auto login
                    $creds = array(
                        'user_login'    => $username,
                        'user_password' => $password,
                        'remember'      => true,
                    );
                    $user = wp_signon( $creds, is_ssl() );

                    if ( ! is_wp_error( $user ) ) {
                        // L3: Hardened cookie
                        if ( PHP_VERSION_ID >= 70300 ) {
                            setcookie( 'posdash_last_activity', time(), array( 'expires' => time() + 86400 * 30, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Strict' ) );
                        } else {
                            setcookie( 'posdash_last_activity', time(), time() + 86400 * 30, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
                        }
                        wp_safe_redirect( home_url( '/' ) );
                        exit;
                    } else {
                        $posdash_signup_success = 'Account created successfully! Please sign in.';
                    }
                }
            }
        }

        // If guest and accessing non-public view, redirect to login page
        if ( ! in_array( $clean_view_name, $public_views, true ) ) {
            wp_safe_redirect( home_url( '/auth-sign-in' ) );
            exit;
        }
    } else {
        // If logged in and accessing public view, redirect to home page
        if ( in_array( $clean_view_name, $public_views, true ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }
        
        // Idle timeout server-side check
        $now = time();
        $timeout = 180; // 3 minutes = 180 seconds
        
        if ( isset( $_COOKIE['posdash_last_activity'] ) ) {
            $last_activity = intval( $_COOKIE['posdash_last_activity'] );
            if ( ( $now - $last_activity ) > $timeout ) {
                if ( PHP_VERSION_ID >= 70300 ) {
                    setcookie( 'posdash_last_activity', '', array( 'expires' => time() - 3600, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Strict' ) );
                } else {
                    setcookie( 'posdash_last_activity', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
                }
                wp_logout();
                wp_safe_redirect( add_query_arg( 'loggedout', 'idle', home_url( '/auth-sign-in' ) ) );
                exit;
            }
        }
        
        // Update activity cookie (L3: hardened)
        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie( 'posdash_last_activity', $now, array( 'expires' => $now + 86400 * 30, 'path' => COOKIEPATH, 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Strict' ) );
        } else {
            setcookie( 'posdash_last_activity', $now, $now + 86400 * 30, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
    }
} );

// Hook to wp_footer to inject client-side inactivity timer and AJAX nonce
add_action( 'wp_footer', function() {
    if ( is_user_logged_in() ) {
        $logout_url = esc_url( wp_logout_url( add_query_arg( 'reason', 'idle', home_url( '/auth-sign-in' ) ) ) );
        $ajax_nonce = wp_create_nonce( 'posdash_ajax_action' );
        ?>
        <script type="text/javascript">
        (function() {
            // Setup global AJAX nonce for authenticated requests
            if (typeof jQuery !== 'undefined') {
                jQuery.ajaxSetup({
                    data: { nonce: "<?php echo esc_js( $ajax_nonce ); ?>" }
                });
            }

            var idleTime = 0;
            var idleLimit = 180; // 3 minutes

            function resetIdleTimer() {
                idleTime = 0;
            }

            // Monitor events
            var events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
            events.forEach(function(name) {
                document.addEventListener(name, resetIdleTimer, true);
            });

            // Increment the idle counter every second
            var idleInterval = setInterval(timerIncrement, 1000);

            function timerIncrement() {
                idleTime = idleTime + 1;
                if (idleTime >= idleLimit) {
                    clearInterval(idleInterval);
                    // Redirect to logout URL
                    window.location.href = "<?php echo $logout_url; ?>";
                }
            }
        })();
        </script>
        <?php
    }
} );

// Disable admin bar for all users on the frontend
add_filter( 'show_admin_bar', '__return_false' );

/**
 * Add custom "Manager" user role.
 */
function inventory_management_add_manager_role() {
    if ( ! get_role( 'manager' ) ) {
        // Clone capabilities of the 'editor' role, or default to some basic ones if editor doesn't exist
        $editor = get_role( 'editor' );
        $caps = ! empty( $editor ) ? $editor->capabilities : array(
            'read' => true,
            'edit_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
        );
        add_role( 'manager', __( 'Manager', 'inventory-management' ), $caps );
    }
}
add_action( 'init', 'inventory_management_add_manager_role' );

/**
 * --- AJAX Endpoints for Raw Materials ---
 */

add_action( 'wp_ajax_search_raw_material_products', 'posdash_search_raw_material_products' );
// H1: Removed nopriv - requires login
function posdash_search_raw_material_products() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
    global $wpdb;
    $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
    $cat  = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
    
    $table = $wpdb->prefix . 'products';
    $cat_query = "";
    if ( ! empty( $cat ) ) {
        $cat_query = $wpdb->prepare( " AND category = %s", $cat );
    }
    
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT id, product_name as name, category, cost FROM $table WHERE product_type = 'Raw Material' AND (product_name LIKE %s OR id LIKE %s) $cat_query LIMIT 50", '%' . $wpdb->esc_like( $term ) . '%', '%' . $wpdb->esc_like( $term ) . '%' ) );
    
    wp_send_json_success( $results );
}

add_action( 'wp_ajax_save_raw_material_log', 'posdash_save_raw_material_log' );
// H1: nopriv REMOVED - save actions require login
function posdash_save_raw_material_log() {
    // H1: Authentication + nonce check for write operations
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
    check_ajax_referer( 'posdash_ajax_action', 'nonce' );
    global $wpdb;
    $prod_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
    $qty = isset( $_POST['quantity'] ) ? floatval( wp_unslash( $_POST['quantity'] ) ) : 0.00;
    $date = isset( $_POST['log_date'] ) ? sanitize_text_field( wp_unslash( $_POST['log_date'] ) ) : '';
    // M1: Validate date format Y-m-d
    if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! strtotime( $date ) ) {
        $date = current_time( 'Y-m-d' );
    }
    
    if ( $prod_id <= 0 || $qty <= 0 ) {
        wp_send_json_error( 'Invalid input data.' );
        return;
    }
    
    $current_user = wp_get_current_user();
    $username = $current_user->exists() ? $current_user->user_login : 'admin';
    
    $wpdb->insert(
        $wpdb->prefix . 'raw_material',
        array(
            'log_date'        => $date,
            'product_id'      => $prod_id,
            'quantity'        => $qty,
            'Created_dt'      => current_time( 'mysql' ),
            'Last_upd_dt'     => current_time( 'mysql' ),
            'created_by'      => $username,
            'Last_updated_by' => $username,
        )
    );
    
    wp_send_json_success( 'Raw material logged successfully.' );
}

add_action( 'wp_ajax_get_raw_material_logs', 'posdash_get_raw_material_logs' );
// H1: Removed nopriv - requires login
function posdash_get_raw_material_logs() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Authentication required.', 403 );
        return;
    }
    global $wpdb;
    $date = isset( $_GET['log_date'] ) ? sanitize_text_field( wp_unslash( $_GET['log_date'] ) ) : '';
    // M1: Validate date format
    if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        $date = current_time( 'Y-m-d' );
    }
    
    $raw_table = $wpdb->prefix . 'raw_material';
    $prod_table = $wpdb->prefix . 'products';
    
    $results = $wpdb->get_results( $wpdb->prepare( "
        SELECT r.id, r.product_id, p.product_name, p.category, r.quantity, r.log_date, r.created_by, r.Created_dt
        FROM $raw_table r
        LEFT JOIN $prod_table p ON r.product_id = p.id
        WHERE r.log_date = %s
        ORDER BY r.id DESC
    ", $date ) );
    
    wp_send_json_success( $results );
}

/**
 * --- ThemeSettings WordPress Admin Option Panel ---
 */

function inventory_management_add_settings_page() {
    add_theme_page(
        __( 'Theme Settings', 'inventory-management' ),
        __( 'ThemeSettings', 'inventory-management' ),
        'manage_options',
        'theme-settings',
        'inventory_management_render_settings_page'
    );
}
add_action( 'admin_menu', 'inventory_management_add_settings_page' );

function inventory_management_register_settings() {
    register_setting( 'inventory_theme_settings_group', 'inventory_brand_name' );
    register_setting( 'inventory_theme_settings_group', 'inventory_brand_logo' );
    register_setting( 'inventory_theme_settings_group', 'inventory_owner_email' );
    register_setting( 'inventory_theme_settings_group', 'inventory_work_logged_email_subject' );
    register_setting( 'inventory_theme_settings_group', 'inventory_work_logged_email_body' );
}
add_action( 'admin_init', 'inventory_management_register_settings' );

function inventory_management_settings_assets( $hook ) {
    if ( 'appearance_page_theme-settings' !== $hook ) {
        return;
    }
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'inventory_management_settings_assets' );

function inventory_management_render_settings_page() {
    // M5: Enforce strict allowlist for the 'tab' parameter
    $allowed_tabs = array( 'brand_details', 'email_templates' );
    $active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $allowed_tabs, true ) ? $_GET['tab'] : 'brand_details';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Theme Settings', 'inventory-management' ); ?></h1>
        <?php settings_errors(); ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=theme-settings&tab=brand_details" class="nav-tab <?php echo $active_tab === 'brand_details' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Brand details', 'inventory-management' ); ?>
            </a>
            <a href="?page=theme-settings&tab=email_templates" class="nav-tab <?php echo $active_tab === 'email_templates' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Email templates', 'inventory-management' ); ?>
            </a>
        </h2>

        <form method="post" action="options.php" style="margin-top: 20px;">
            <?php
            settings_fields( 'inventory_theme_settings_group' );
            
            if ( 'brand_details' === $active_tab ) {
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Brand Name', 'inventory-management' ); ?></th>
                        <td>
                            <input type="text" name="inventory_brand_name" value="<?php echo esc_attr( get_option( 'inventory_brand_name' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Brand Logo', 'inventory-management' ); ?></th>
                        <td>
                            <input type="text" id="inventory_brand_logo" name="inventory_brand_logo" value="<?php echo esc_url( get_option( 'inventory_brand_logo' ) ); ?>" class="regular-text" />
                            <input type="button" id="upload_logo_button" class="button" value="<?php esc_attr_e( 'Upload Logo', 'inventory-management' ); ?>" />
                            <p class="description"><?php esc_html_e( 'Choose an image from the Media Library or upload a new one.', 'inventory-management' ); ?></p>
                            <div style="margin-top: 10px;">
                                <img id="logo_preview" src="<?php echo esc_url( get_option( 'inventory_brand_logo' ) ); ?>" style="max-height: 80px; display: <?php echo get_option( 'inventory_brand_logo' ) ? 'block' : 'none'; ?>;" />
                            </div>
                        </td>
                    </tr>
                </table>
                <?php
            } elseif ( 'email_templates' === $active_tab ) {
                ?>
                <h3><?php esc_html_e( 'Work Logged Email Template', 'inventory-management' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Configure the email notification template sent to the owner daily when work is logged.', 'inventory-management' ); ?></p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Owner/Recipient Email', 'inventory-management' ); ?></th>
                        <td>
                            <input type="email" name="inventory_owner_email" value="<?php echo esc_attr( get_option( 'inventory_owner_email' ) ); ?>" class="regular-text" placeholder="owner@example.com" />
                            <p class="description"><?php esc_html_e( 'Email address where notifications will be sent.', 'inventory-management' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Email Subject', 'inventory-management' ); ?></th>
                        <td>
                            <input type="text" name="inventory_work_logged_email_subject" value="<?php echo esc_attr( get_option( 'inventory_work_logged_email_subject' ) ); ?>" class="regular-text" style="width: 100%; max-width: 600px;" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Email Body', 'inventory-management' ); ?></th>
                        <td>
                            <?php
                            $content = get_option( 'inventory_work_logged_email_body' );
                            wp_editor( $content, 'inventory_work_logged_email_body', array(
                                'textarea_name' => 'inventory_work_logged_email_body',
                                'media_buttons' => false,
                                'textarea_rows' => 12,
                                'teeny'         => true,
                            ) );
                            ?>
                        </td>
                    </tr>
                </table>
                <?php
            }

            submit_button();
            ?>
        </form>
    </div>

    <!-- Media Uploader Script -->
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#upload_logo_button').click(function(e) {
            e.preventDefault();
            var image = wp.media({ 
                title: 'Upload Brand Logo',
                multiple: false
            }).open()
            .on('select', function(e){
                var uploaded_image = image.state().get('selection').first();
                var image_url = uploaded_image.toJSON().url;
                $('#inventory_brand_logo').val(image_url);
                $('#logo_preview').attr('src', image_url).show();
            });
        });
    });
    </script>
    <?php
}




