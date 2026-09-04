<?php
/**
 * The main template file for Inventory Management POS Dash theme.
 *
 * This file serves as the dynamic router and template engine, rendering
 * the converted POS Dash HTML templates in a seamless WordPress environment.
 *
 * @package Inventory_Management
 */

// Silence is golden, but routing is platinum.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirect legacy 'page-' prefixed URLs to clean URLs if a matching template exists in ThemeHtml
$request_uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$request_uri = preg_replace( '#/+#', '/', $request_uri );
$request_path = trim( $request_uri, '/' );

if ( strpos( $request_path, 'page-' ) === 0 ) {
	$clean_path = substr( $request_path, 5 );
	$theme_html_dir = get_template_directory() . '/templates-html/ThemeHtml/';
	if ( file_exists( $theme_html_dir . $clean_path . '.html' ) ) {
		$query = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );
		$redirect_url = home_url( '/' . $clean_path );
		if ( ! empty( $query ) ) {
			$redirect_url .= '?' . $query;
		}
		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}
}

if ( isset( $_GET['view'] ) ) {
	$view_val = sanitize_text_field( wp_unslash( $_GET['view'] ) );
	if ( strpos( $view_val, 'page-' ) === 0 ) {
		$clean_view = substr( $view_val, 5 );
		$theme_html_dir = get_template_directory() . '/templates-html/ThemeHtml/';
		if ( file_exists( $theme_html_dir . $clean_view . '.html' ) ) {
			$query_args = $_GET;
			$query_args['view'] = $clean_view;
			wp_safe_redirect( add_query_arg( $query_args, home_url( '/' ) ), 301 );
			exit;
		}
	}
}

// 1. Get the requested view parameter from query param or clean Request URI
$view = '';
if ( isset( $_GET['view'] ) ) {
	$view = sanitize_text_field( wp_unslash( $_GET['view'] ) );
} else {
	$request_uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	// Standardize slashes and clean multiple slashes (e.g. //page-list-product -> /page-list-product)
	$request_uri = preg_replace( '#/+#', '/', $request_uri );
	$request_path = trim( $request_uri, '/' );

	if ( empty( $request_path ) ) {
		$view = 'backend/index';
	} else {
		$view = $request_path;
	}
}

// Clean the view parameter to prevent path traversal using strict regex whitelist
$view = trim( $view, '/' );
$view = preg_replace( '/[^a-zA-Z0-9_\-\/]/', '', $view ); // Safety first (only allow word characters, hyphens, and slashes)

// Override 404 if we are dynamically loading a valid template
if ( ! empty( $view ) && 'backend/index' !== $view ) {
	global $wp_query;
	if ( is_404() || ( isset( $wp_query ) && $wp_query->is_404 ) ) {
		status_header( 200 );
		$wp_query->is_404 = false;
	}
}

$current_dir = 'backend';
$file_path   = '';

// Helper function to resolve files in ThemeHtml with 'page-' prefix fallback
if ( ! function_exists( 'resolve_theme_html_file' ) ) {
	function resolve_theme_html_file( $view ) {
		$view_file = $view;
		if ( strpos( $view_file, 'ThemeHtml/' ) === 0 ) {
			$view_file = substr( $view_file, 10 );
		} elseif ( strpos( $view_file, 'backend/' ) === 0 ) {
			$view_file = substr( $view_file, 8 );
		}

		$theme_html_dir = get_template_directory() . '/templates-html/ThemeHtml/';

		// Try exact match (e.g. list-product)
		if ( file_exists( $theme_html_dir . $view_file . '.html' ) ) {
			return $theme_html_dir . $view_file . '.html';
		}

		// Try stripped 'page-' prefix match (e.g. page-list-product -> list-product)
		if ( strpos( $view_file, 'page-' ) === 0 ) {
			$stripped = substr( $view_file, 5 );
			if ( file_exists( $theme_html_dir . $stripped . '.html' ) ) {
				return $theme_html_dir . $stripped . '.html';
			}
		}

		// Try adding 'page-' prefix match (just in case)
		if ( strpos( $view_file, 'page-' ) !== 0 ) {
			$with_prefix = 'page-' . $view_file;
			if ( file_exists( $theme_html_dir . $with_prefix . '.html' ) ) {
				return $theme_html_dir . $with_prefix . '.html';
			}
		}

		return false;
	}
}

// 2. Resolve the target template file
$resolved_theme_file = resolve_theme_html_file( $view );
if ( $resolved_theme_file !== false ) {
	$current_dir = 'ThemeHtml';
	$file_path   = $resolved_theme_file;
} elseif ( strpos( $view, 'backend/' ) === 0 ) {
	$current_dir = 'backend';
	$view_file   = substr( $view, 8 );
	$file_path   = get_template_directory() . '/templates-html/backend/' . $view_file . '.html';
} elseif ( strpos( $view, 'app/' ) === 0 ) {
	$current_dir = 'app';
	$view_file   = substr( $view, 4 );
	$file_path   = get_template_directory() . '/templates-html/app/' . $view_file . '.html';
} else {
	// Search in backend first, then app as fallback
	if ( file_exists( get_template_directory() . '/templates-html/backend/' . $view . '.html' ) ) {
		$current_dir = 'backend';
		$file_path   = get_template_directory() . '/templates-html/backend/' . $view . '.html';
	} elseif ( file_exists( get_template_directory() . '/templates-html/app/' . $view . '.html' ) ) {
		$current_dir = 'app';
		$file_path   = get_template_directory() . '/templates-html/app/' . $view . '.html';
	}
}

// Fallback to ThemeHtml/index if file is missing
if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
	$resolved_index = resolve_theme_html_file( 'index' );
	if ( $resolved_index !== false ) {
		$current_dir = 'ThemeHtml';
		$file_path   = $resolved_index;
	} else {
		$current_dir = 'backend';
		$file_path   = get_template_directory() . '/templates-html/backend/index.html';
	}
}

// Global variable so child processes know which folder we are in
global $posdash_current_dir;
$posdash_current_dir = $current_dir;

// 3. Load and process HTML contents
$content = file_get_contents( $file_path );

// Helper function to rewrite URLs and paths dynamically
if ( ! function_exists( 'process_posdash_html_content' ) ) {
	function process_posdash_html_content( $content, $current_dir ) {
		$theme_uri = get_template_directory_uri();
		$home_url  = home_url( '/' );

		// A. Replace assets path (universal replacement for relative paths)
		$content = preg_replace( '/(\.\.\/)+assets\//i', $theme_uri . '/assets/', $content );

		// B. Replace index.html links in href/action/src attributes
		$content = preg_replace_callback( '/(href|action|src)=(["\'])([^"\'\s>]*?)index\.html\2/i', function( $matches ) use ( $home_url ) {
			return $matches[1] . '=' . $matches[2] . $home_url . $matches[2];
		}, $content );

		// C. Replace backend HTML links in href/action/src attributes
		$content = preg_replace_callback( '/(href|action|src)=(["\'])([^"\'\s>]*?)(\.\.\/)+backend\/([a-zA-Z0-9_-]+)\.html\2/i', function( $matches ) use ( $home_url ) {
			$slug = ( $matches[5] === 'index' ) ? '' : ( strpos( $matches[5], 'page-' ) === 0 ? substr( $matches[5], 5 ) : $matches[5] );
			return $matches[1] . '=' . $matches[2] . $home_url . $slug . $matches[2];
		}, $content );

		// C2. Replace ThemeHtml HTML links in href/action/src attributes
		$content = preg_replace_callback( '/(href|action|src)=(["\'])([^"\'\s>]*?)(\.\.\/)+ThemeHtml\/([a-zA-Z0-9_-]+)\.html\2/i', function( $matches ) use ( $home_url ) {
			$slug = ( $matches[5] === 'index' ) ? '' : ( strpos( $matches[5], 'page-' ) === 0 ? substr( $matches[5], 5 ) : $matches[5] );
			return $matches[1] . '=' . $matches[2] . $home_url . $slug . $matches[2];
		}, $content );

		// D. Replace app HTML links in href/action/src attributes
		$content = preg_replace_callback( '/(href|action|src)=(["\'])([^"\'\s>]*?)(\.\.\/)+app\/([a-zA-Z0-9_-]+)\.html\2/i', function( $matches ) use ( $home_url ) {
			$slug = ( $matches[5] === 'index' ) ? '' : ( strpos( $matches[5], 'page-' ) === 0 ? substr( $matches[5], 5 ) : $matches[5] );
			return $matches[1] . '=' . $matches[2] . $home_url . $slug . $matches[2];
		}, $content );

		// E. Handle sibling HTML links in href/action attributes (e.g. href="page-list-product.html#add-product-form" or href="list-product.html")
		$content = preg_replace_callback( '/(href|action)=(["\'])(?!\s*http|\s*#|\s*javascript:)([^"\'\s>#?]+)\.html([^"\'\s>]*)\2/i', function( $matches ) use ( $home_url ) {
			$attr_name = $matches[1];
			$quote     = $matches[2];
			$filename  = basename( $matches[3] );
			$suffix    = $matches[4]; // preserve #hash or ?query
			if ( $filename === 'index' ) {
				return $attr_name . '=' . $quote . $home_url . $suffix . $quote;
			}
			if ( in_array( $filename, array( 'min', 'css', 'js', 'html' ), true ) ) {
				return $matches[0];
			}
			$slug = ( strpos( $filename, 'page-' ) === 0 ) ? substr( $filename, 5 ) : $filename;
			return $attr_name . '=' . $quote . $home_url . $slug . $suffix . $quote;
		}, $content );

		return $content;
	}
}

// Apply the rewriter
$content = process_posdash_html_content( $content, $current_dir );

// Dynamic User -> Employee renaming as requested by USER
$content = str_ireplace( '<span>Users</span>', '<span>Employee</span>', $content );
$content = str_ireplace( '<span>Add Users</span>', '<span>Add Employee</span>', $content );
$content = str_ireplace( '<h4 class="mb-3">User List</h4>', '<h4 class="mb-3">Employee List</h4>', $content );
$content = str_ireplace( '<h4 class="card-title">Add Users</h4>', '<h4 class="card-title">Add Employee</h4>', $content );
$content = str_ireplace( '<i class="las la-plus mr-3"></i>Add User</a>', '<i class="las la-plus mr-3"></i>Add Employee</a>', $content );
$content = str_ireplace( 'overview of user list', 'overview of employee list', $content );

// Render Dynamic Dashboard Statistics
if ( strpos( $view, 'index' ) !== false ) {
    global $wpdb;

    // 1. Fetch Stats Values
    $total_products = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}products" );
    $total_users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}employee WHERE status = 'Active'" );
    $today_date = current_time( 'Y-m-d' );
    
    $logs_today = (int) $wpdb->get_var( $wpdb->prepare( "
        SELECT 
            (SELECT COUNT(*) FROM {$wpdb->prefix}fin_prod_log WHERE DATE(Created_dt) = %s) + 
            (SELECT COUNT(*) FROM {$wpdb->prefix}raw_material WHERE DATE(Created_dt) = %s)
    ", $today_date, $today_date ) );
    
    $labour_today = (float) $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(total_labor_payout) FROM {$wpdb->prefix}fin_prod_log WHERE DATE(Created_dt) = %s
    ", $today_date ) );

    // 2. Generate Stats Row HTML
    $stats_html = '
    <div class="row mt-4">
        <!-- Stat 1: Total Products -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm stat-card-products" style="border-radius: 16px; background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%) !important; transition: all 0.3s ease;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-uppercase font-weight-bold" style="color: rgba(255,255,255,0.85) !important; letter-spacing: 0.5px;">Total Products</p>
                            <h3 class="mb-0 font-weight-bold" style="color: #ffffff !important;">' . esc_html( $total_products ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2) !important;">
                            <i class="las la-boxes" style="font-size: 24px; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stat 2: Active Users -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm stat-card-staff" style="border-radius: 16px; background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important; transition: all 0.3s ease;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-uppercase font-weight-bold" style="color: rgba(255,255,255,0.85) !important; letter-spacing: 0.5px;">Active Staff</p>
                            <h3 class="mb-0 font-weight-bold" style="color: #ffffff !important;">' . esc_html( $total_users ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2) !important;">
                            <i class="las la-users-cog" style="font-size: 24px; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat 3: Logs Today -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm stat-card-logs" style="border-radius: 16px; background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%) !important; transition: all 0.3s ease;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-uppercase font-weight-bold" style="color: rgba(255,255,255,0.85) !important; letter-spacing: 0.5px;">Logs Today</p>
                            <h3 class="mb-0 font-weight-bold" style="color: #ffffff !important;">' . esc_html( $logs_today ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2) !important;">
                            <i class="las la-clipboard-list" style="font-size: 24px; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat 4: Today\'s Labour Payout -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm stat-card-payout" style="border-radius: 16px; background: linear-gradient(135deg, #db2777 0%, #9333ea 100%) !important; transition: all 0.3s ease;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-uppercase font-weight-bold" style="color: rgba(255,255,255,0.85) !important; letter-spacing: 0.5px;">Today\'s Payout</p>
                            <h3 class="mb-0 font-weight-bold" style="color: #ffffff !important;">&#8377; ' . number_format( $labour_today, 2 ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2) !important;">
                            <i class="las la-rupee-sign" style="font-size: 24px; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    // 3. Fetch Activity Logs
    $recent_work = $wpdb->get_results( $wpdb->prepare( "
        SELECT 'work' AS log_type, l.Created_dt, l.quantity_produced AS qty, e.name AS emp_name, p.product_name AS prod_name, l.total_labor_payout AS cost
        FROM {$wpdb->prefix}fin_prod_log l
        LEFT JOIN {$wpdb->prefix}employee e ON l.employee_id = e.id
        LEFT JOIN {$wpdb->prefix}products p ON l.product_id = p.id
        WHERE DATE(l.Created_dt) = %s
        ORDER BY l.id DESC
        LIMIT 5
    ", $today_date ) );

    $recent_raw = $wpdb->get_results( $wpdb->prepare( "
        SELECT 'raw' AS log_type, r.Created_dt, r.quantity AS qty, r.created_by AS emp_name, p.product_name AS prod_name, 0.00 AS cost
        FROM {$wpdb->prefix}raw_material r
        LEFT JOIN {$wpdb->prefix}products p ON r.product_id = p.id
        WHERE DATE(r.Created_dt) = %s
        ORDER BY r.id DESC
        LIMIT 5
    ", $today_date ) );

    $recent_logs = array_merge( $recent_work, $recent_raw );
    usort( $recent_logs, function( $a, $b ) {
        return strcmp( $b->Created_dt, $a->Created_dt );
    } );
    $recent_logs = array_slice( $recent_logs, 0, 5 );

    // 4. Generate Activity Feed HTML
    $feed_html = '';
    if ( ! empty( $recent_logs ) ) {
        foreach ( $recent_logs as $log ) {
            $time_str = date( 'h:i A', strtotime( $log->Created_dt ) );
            $staff_name = ! empty( $log->emp_name ) ? esc_html( $log->emp_name ) : 'admin';
            
            if ( 'work' === $log->log_type ) {
                $type_badge = '<span class="badge bg-success-light text-success py-1 px-2 font-weight-bold" style="border-radius:4px;">Finished Goods</span>';
                $payout_str = '&#8377; ' . number_format( $log->cost, 2 );
            } else {
                $type_badge = '<span class="badge bg-warning-light text-warning py-1 px-2 font-weight-bold" style="border-radius:4px;">Raw Material</span>';
                $payout_str = '-';
            }

            $feed_html .= '
            <tr>
                <td style="padding: 12px 20px !important; font-weight: 500;">' . esc_html( $time_str ) . '</td>
                <td style="padding: 12px 20px !important;">' . esc_html( $staff_name ) . '</td>
                <td style="padding: 12px 20px !important;">' . $type_badge . '</td>
                <td style="padding: 12px 20px !important;">' . esc_html( $log->prod_name ) . '</td>
                <td class="text-center font-weight-bold" style="padding: 12px 20px !important;">' . esc_html( $log->qty ) . '</td>
                <td class="text-right font-weight-bold text-dark" style="padding: 12px 20px !important;">' . $payout_str . '</td>
            </tr>';
        }
    } else {
        $feed_html = '
        <tr>
            <td colspan="6" class="text-center text-muted p-4">
                <i class="las la-info-circle mr-1" style="font-size: 16px;"></i> No activity logged yet today.
            </td>
        </tr>';
    }

    // 4.5 Extra Dashboard Widgets
    $total_suppliers = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}supplier" );
    $total_categories = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}prod_category" );
    $total_customers = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}customers" );
    $all_time_payout = (float) $wpdb->get_var( "SELECT SUM(total_labor_payout) FROM {$wpdb->prefix}fin_prod_log" );

    $extra_widgets_html = '
    <div class="row mt-4">
        <div class="col-lg-12 mb-3">
            <h5 class="font-weight-bold">System Overview</h5>
        </div>
        
        <!-- Widget 1 -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm" style="border-radius: 12px; border-left: 5px solid #ec4899; background: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-muted font-weight-bold text-uppercase" style="font-size: 11px;">Total Suppliers</p>
                            <h3 class="mb-0 font-weight-bold text-dark">' . esc_html( $total_suppliers ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-pink-light text-pink" style="width: 50px; height: 50px; background: #fdf2f8; color: #ec4899;">
                            <i class="las la-truck" style="font-size: 26px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 2 -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm" style="border-radius: 12px; border-left: 5px solid #14b8a6; background: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-muted font-weight-bold text-uppercase" style="font-size: 11px;">Total Categories</p>
                            <h3 class="mb-0 font-weight-bold text-dark">' . esc_html( $total_categories ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-teal-light text-teal" style="width: 50px; height: 50px; background: #f0fdfa; color: #14b8a6;">
                            <i class="las la-tags" style="font-size: 26px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 3 -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm" style="border-radius: 12px; border-left: 5px solid #f59e0b; background: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-muted font-weight-bold text-uppercase" style="font-size: 11px;">Total Customers</p>
                            <h3 class="mb-0 font-weight-bold text-dark">' . esc_html( $total_customers ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-orange-light text-orange" style="width: 50px; height: 50px; background: #fffbeb; color: #f59e0b;">
                            <i class="las la-user-tie" style="font-size: 26px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 4 -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-block card-stretch card-height border-none shadow-sm" style="border-radius: 12px; border-left: 5px solid #8b5cf6; background: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-muted font-weight-bold text-uppercase" style="font-size: 11px;">All-Time Payout</p>
                            <h3 class="mb-0 font-weight-bold text-dark">&#8377; ' . number_format( $all_time_payout, 2 ) . '</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-purple-light text-purple" style="width: 50px; height: 50px; background: #f5f3ff; color: #8b5cf6;">
                            <i class="las la-wallet" style="font-size: 26px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    // 5. Replace placeholders
    $content = str_replace( '<!-- DASHBOARD_STATS_ROW -->', $stats_html, $content );
    $content = str_replace( '<!-- DASHBOARD_ACTIVITY_FEED -->', $feed_html, $content );
    $content = str_replace( '<!-- DASHBOARD_EXTRA_WIDGETS -->', $extra_widgets_html, $content );

    // Dynamic greeting/salutation for dashboard index
    $current_user = wp_get_current_user();
    $content = str_replace( 'Hi Graham', 'Hi ' . esc_html( $current_user->display_name ), $content );
}

// Render Dynamic User Profile Details
if ( strpos( $view, 'user-profile' ) !== false ) {
    $current_user = wp_get_current_user();
    $avatar_url   = get_avatar_url( $current_user->ID );
    $user_roles   = $current_user->roles;
    $role_name    = ! empty( $user_roles ) ? ucfirst( $user_roles[0] ) : 'Subscriber';

    // Fetch all user properties from the database
    $user_login   = $current_user->user_login;
    $display_name = $current_user->display_name;
    $first_name   = get_user_meta( $current_user->ID, 'first_name', true );
    $last_name    = get_user_meta( $current_user->ID, 'last_name', true );
    $nickname     = get_user_meta( $current_user->ID, 'nickname', true );
    $user_email   = $current_user->user_email;
    $user_url     = $current_user->user_url;
    $registered   = date_i18n( get_option( 'date_format' ), strtotime( $current_user->user_registered ) );
    $description  = get_user_meta( $current_user->ID, 'description', true );

    // Handle success or error alert messages
    $alert_html = '';
    if ( isset( $_GET['success'] ) && $_GET['success'] === 'profile_updated' ) {
        $alert_html = '
        <div class="col-lg-12 mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="iq-alert-text">Profile updated successfully.</div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>';
    } elseif ( isset( $_GET['error'] ) ) {
        $error_msg = sanitize_text_field( wp_unslash( $_GET['error'] ) );
        $alert_html = '
        <div class="col-lg-12 mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="iq-alert-text">Error: ' . esc_html( $error_msg ) . '</div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>';
    }
    $content = str_replace( '<!-- PROFILE_ALERT -->', $alert_html, $content );

    // Replace the placeholders in content
    $content = str_replace( '<!-- USER_AVATAR -->', esc_url( $avatar_url ), $content );
    $content = str_replace( '<!-- DISPLAY_NAME -->', esc_html( $display_name ), $content );
    $content = str_replace( '<!-- USER_ROLE -->', esc_html( $role_name ), $content );
    $content = str_replace( '<!-- USER_DESCRIPTION_SHORT -->', esc_html( wp_trim_words( $description, 15, '...' ) ), $content );
    $content = str_replace( '<!-- USER_DESCRIPTION -->', esc_html( $description ), $content );

    $content = str_replace( '<!-- USER_LOGIN -->', esc_html( $user_login ), $content );
    $content = str_replace( '<!-- FIRST_NAME -->', esc_html( $first_name ), $content );
    $content = str_replace( '<!-- LAST_NAME -->', esc_html( $last_name ), $content );
    $content = str_replace( '<!-- NICKNAME -->', esc_html( $nickname ), $content );
    $content = str_replace( '<!-- USER_EMAIL -->', esc_html( $user_email ), $content );
    $content = str_replace( '<!-- USER_URL -->', esc_html( $user_url ), $content );
    $content = str_replace( '<!-- USER_REGISTERED -->', esc_html( $registered ), $content );
}


// Render Dynamic Real-Time Products from wp_products
if ( strpos( $view, 'list-product' ) !== false ) {
    global $wpdb;
    inventory_management_migrate_product_categories();
    $products = $wpdb->get_results( "
        SELECT p.*, t.Type as type_name, c.name as category_name
        FROM {$wpdb->prefix}products p
        LEFT JOIN {$wpdb->prefix}product_type t ON p.product_type = t.id
        LEFT JOIN {$wpdb->prefix}prod_category c ON (p.category = c.id OR p.category = c.name)
        ORDER BY p.id DESC
    " );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $products ) ) {
        foreach ( $products as $index => $product ) {
            $cost = number_format( (float) $product->cost, 2 );
            $cat_display = ! empty( $product->category_name ) ? $product->category_name : $product->category;

            $tbody .= '<tr>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $product->id ) . '</td>';
            $tbody .= '<td>' . esc_html( ! empty( $product->type_name ) ? $product->type_name : $product->product_type ) . '</td>';
            $tbody .= '<td>' . esc_html( $cat_display ) . '</td>';
            $tbody .= '<td>' . esc_html( $product->product_name ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $cost ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            
            // Edit Row Button
            $tbody .= '<a class="badge bg-success mr-2 btn-edit-product" data-toggle="tooltip" data-placement="top" title="Edit" href="#" data-id="' . intval( $product->id ) . '" onclick="window.openEditProductModal(' . intval( $product->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            
            // Quick Edit Price Button
            $tbody .= '<a class="badge bg-info mr-2 btn-edit-price" data-toggle="tooltip" data-placement="top" title="Edit Price" href="#" data-id="' . intval( $product->id ) . '" data-name="' . esc_attr( $product->product_name ) . '" data-category="' . esc_attr( $cat_display ) . '" data-cost="' . esc_attr( $product->cost ) . '" onclick="window.openQuickEditPriceModal(this); return false;"><i class="ri-price-tag-3-line mr-0"></i></a>';
            
            // Delete Row Button (Secure with Nonce)
            $delete_url = wp_nonce_url(
                home_url( '/?action=delete_product&id=' . $product->id ),
                'posdash_delete_delete_product_' . $product->id
            );
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( $delete_url ) . '" onclick="if(!window.currentIsAdmin){ alert(\'This action is only allowed for administrator.\'); return false; } return confirm(\'Are you sure you want to delete this product?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="6" class="text-center">No products found.</td></tr>';
    }
    $tbody .= '</tbody>';

    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
    $content = '<script>window.productList = ' . wp_json_encode( $products ) . '; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>' . $content;

    // Fetch Categories for modal dropdown and filters
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prod_category ORDER BY name ASC" );
    $edit_cat_options = '';
    $filter_cat_options = '';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $cat ) {
            $edit_cat_options .= '<option value="' . esc_attr( $cat->id ) . '">' . esc_html( $cat->name ) . '</option>';
            $filter_cat_options .= '<option value="' . esc_attr( $cat->name ) . '">' . esc_html( $cat->name ) . '</option>';
        }
    }
    $content = str_replace( '<!-- EDIT_CATEGORY_OPTIONS -->', $edit_cat_options, $content );
    $content = str_replace( '<!-- DYNAMIC_CATEGORY_OPTIONS -->', $filter_cat_options, $content );

    // Fetch Product Types for modal dropdown and filters
    $types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}product_type ORDER BY Type ASC" );
    $type_options = '';
    $filter_type_options = '';
    if ( ! empty( $types ) ) {
        foreach ( $types as $t ) {
            $type_options .= '<option value="' . esc_attr( $t->id ) . '">' . esc_html( $t->Type ) . '</option>';
            $filter_type_options .= '<option value="' . esc_attr( $t->Type ) . '">' . esc_html( $t->Type ) . '</option>';
        }
    }
    $content = str_replace( '<!-- EDIT_TYPE_OPTIONS -->', $type_options, $content );
    $content = str_replace( '<!-- DYNAMIC_TYPE_OPTIONS -->', $filter_type_options, $content );
}

// Render Dynamic Real-Time Employees from wp_employee
if ( strpos( $view, 'list-users' ) !== false ) {
    global $wpdb;
    $employees = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}employee ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $employees ) ) {
        foreach ( $employees as $index => $emp ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $tbody .= '<tr>';
            $default_img = ( $emp->gender === 'Female' ) ? '/assets/images/user/11.png' : '/assets/images/user/1.jpg';
            $img_url = !empty( $emp->image ) ? $emp->image : get_template_directory_uri() . $default_img;
            $tbody .= '<td><div class="d-flex align-items-center"><img src="' . esc_url( $img_url ) . '" class="img-fluid rounded avatar-50 mr-3" alt="image"><div>' . esc_html( $emp->name ) . '</div></div></td>';
            $tbody .= '<td>' . esc_html( $emp->email ) . '</td>';
            $tbody .= '<td>' . esc_html( $emp->company ) . '</td>';
            $tbody .= '<td>' . esc_html( $emp->address ) . '</td>';
            $tbody .= '<td>' . esc_html( $emp->status ) . '</td>';
            $edit_btn = '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#" onclick="window.openEditUserModal(' . intval( $emp->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            $delete_url = wp_nonce_url(
                home_url( '/?action=delete_employee&id=' . $emp->id ),
                'posdash_delete_delete_employee_' . $emp->id
            );
            $delete_btn = '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( $delete_url ) . '" onclick="if(!window.currentIsAdmin){ alert(\'This action is only allowed for administrator.\'); return false; } return confirm(\'Are you sure you want to delete this user?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            $tbody .= '<td><div class="d-flex align-items-center list-action">' . $edit_btn . $delete_btn . '</div></td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="6" class="text-center">No employees found.</td></tr>';
    }
    $tbody .= '</tbody>';

    $content = preg_replace( '/<tbody class="ligth-body">.*?<\/tbody>/s', $tbody, $content );
    $content .= '<script>window.employeeList = ' . wp_json_encode( $employees ) . '; window.themeUri = "' . esc_url( get_template_directory_uri() ) . '"; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>';
}

// Extend Categories sidebar dropdown dynamically across all page templates
$content = preg_replace(
    '/<ul id="category"[^>]*>.*?<\/ul>/is',
    '<ul id="category" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
        <li class="' . ( strpos( $_SERVER['REQUEST_URI'], 'list-category' ) !== false ? 'active' : '' ) . '">
            <a href="' . esc_url( home_url( '/list-category' ) ) . '">
                <i class="las la-minus"></i><span>Category</span>
            </a>
        </li>
        <li class="' . ( strpos( $_SERVER['REQUEST_URI'], 'list-type' ) !== false ? 'active' : '' ) . '">
            <a href="' . esc_url( home_url( '/list-type' ) ) . '">
                <i class="las la-minus"></i><span>Type of Product</span>
            </a>
        </li>
    </ul>',
    $content
);

// Render Dynamic Real-Time Categories from wp_prod_category
if ( strpos( $view, 'list-category' ) !== false ) {
    global $wpdb;
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prod_category ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $index => $cat ) {
            $img_url = $cat->image;
            if ( strpos( $img_url, 'assets/' ) === 0 ) {
                $img_url = get_template_directory_uri() . '/' . $img_url;
            }
            $tbody .= '<tr>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $cat->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $cat->name ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            
            // Edit Button
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#" onclick="window.openEditCategoryModal(' . intval( $cat->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            
            // Delete Button (Secure with Nonce)
            $delete_url = wp_nonce_url(
                home_url( '/?action=delete_category&id=' . $cat->id ),
                'posdash_delete_delete_category_' . $cat->id
            );
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( $delete_url ) . '" onclick="if(!window.currentIsAdmin){ alert(\'This action is only allowed for administrator.\'); return false; } return confirm(\'Are you sure you want to delete this category?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="3" class="text-center">No categories found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace( '/<tbody class="ligth-body">.*?<\/tbody>/s', $tbody, $content );
    $content .= '<script>window.categoryList = ' . wp_json_encode( $categories ) . '; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>';
}

// Render Dynamic Real-Time Product Types from wp_product_type
if ( strpos( $view, 'list-type' ) !== false ) {
    global $wpdb;
    $types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}product_type ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $types ) ) {
        foreach ( $types as $index => $t ) {
            $tbody .= '<tr>';
            $tbody .= '<td>' . esc_html( $t->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $t->Type ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            
            // Edit Button
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#" onclick="window.openEditTypeModal(' . intval( $t->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            
            // Delete Button (Secure with Nonce)
            $delete_url = wp_nonce_url(
                home_url( '/?action=delete_type&id=' . $t->id ),
                'posdash_delete_delete_type_' . $t->id
            );
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( $delete_url ) . '" onclick="if(!window.currentIsAdmin){ alert(\'This action is only allowed for administrator.\'); return false; } return confirm(\'Are you sure you want to delete this product type?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="3" class="text-center">No product types found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace( '/<tbody class="ligth-body">.*?<\/tbody>/s', $tbody, $content );
    $content .= '<script>window.typeList = ' . wp_json_encode( $types ) . '; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>';
}

// Render Dynamic Real-Time Customers from wp_customers
if ( strpos( $view, 'list-customers' ) !== false ) {
    global $wpdb;
    $customers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}customers ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $customers ) ) {
        foreach ( $customers as $index => $cust ) {
            $tbody .= '<tr>';
            $tbody .= '<td>' . esc_html( isset( $cust->company_name ) ? $cust->company_name : '' ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->name ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->email ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->phone_number ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->country ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->order_count ) . '</td>';
            $tbody .= '<td><div class="badge badge-warning">' . esc_html( $cust->status ) . '</div></td>';
            $tbody .= '<td>' . esc_html( $cust->last_order ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            
            // Edit Button
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#" onclick="window.openEditCustomerModal(' . intval( $cust->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            
            // Delete Button (Secure with Nonce)
            $delete_url = wp_nonce_url(
                home_url( '/?action=delete_customer&id=' . $cust->id ),
                'posdash_delete_delete_customer_' . $cust->id
            );
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( $delete_url ) . '" onclick="if(!window.currentIsAdmin){ alert(\'This action is only allowed for administrator.\'); return false; } return confirm(\'Are you sure you want to delete this customer?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="9" class="text-center">No customers found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
    $content .= '<script>window.customerList = ' . wp_json_encode( $customers ) . '; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>';
}

// Render Dynamic Real-Time Suppliers from wp_suppliers
if ( strpos( $view, 'list-suppliers' ) !== false ) {
    global $wpdb;
    $suppliers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}suppliers ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $suppliers ) ) {
        foreach ( $suppliers as $index => $supp ) {
            $tbody .= '<tr>';
            $tbody .= '<td>' . esc_html( $supp->company_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->name ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->email ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->phone_number ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->city ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->country ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->gst_number ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            
            // Edit Button
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#" onclick="window.openEditSupplierModal(' . intval( $supp->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            
            // Delete Button (Secure with Nonce)
            $delete_url = wp_nonce_url(
                home_url( '/?action=delete_supplier&id=' . $supp->id ),
                'posdash_delete_delete_supplier_' . $supp->id
            );
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( $delete_url ) . '" onclick="if(!window.currentIsAdmin){ alert(\'This action is only allowed for administrator.\'); return false; } return confirm(\'Are you sure you want to delete this supplier?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="8" class="text-center">No suppliers found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
    $content .= '<script>window.supplierList = ' . wp_json_encode( $suppliers ) . '; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>';
}

// Helper function to render color badge with dynamic background based on color name
if ( ! function_exists( 'get_color_badge_html' ) ) {
    function get_color_badge_html( $color ) {
        if ( empty( $color ) ) {
            return '<span class="badge badge-light border text-muted px-2 py-1">-</span>';
        }
        $c      = strtolower( trim( $color ) );
        $bg     = '#6c757d';
        $text   = '#ffffff';
        $border = 'transparent';

        if ( strpos( $c, 'red' ) !== false ) {
            $bg = '#dc3545'; $text = '#ffffff';
        } elseif ( strpos( $c, 'green' ) !== false ) {
            $bg = '#28a745'; $text = '#ffffff';
        } elseif ( strpos( $c, 'navy' ) !== false ) {
            $bg = '#0a192f'; $text = '#ffffff';
        } elseif ( strpos( $c, 'blue' ) !== false ) {
            $bg = '#007bff'; $text = '#ffffff';
        } elseif ( strpos( $c, 'yellow' ) !== false ) {
            $bg = '#ffc107'; $text = '#212529';
        } elseif ( strpos( $c, 'black' ) !== false ) {
            $bg = '#1a1a1a'; $text = '#ffffff';
        } elseif ( strpos( $c, 'white' ) !== false ) {
            $bg = '#ffffff'; $text = '#212529'; $border = '#ced4da';
        } elseif ( strpos( $c, 'grey' ) !== false || strpos( $c, 'gray' ) !== false ) {
            $bg = '#6c757d'; $text = '#ffffff';
        } elseif ( strpos( $c, 'orange' ) !== false ) {
            $bg = '#fd7e14'; $text = '#ffffff';
        } elseif ( strpos( $c, 'pink' ) !== false ) {
            $bg = '#e83e8c'; $text = '#ffffff';
        } elseif ( strpos( $c, 'purple' ) !== false ) {
            $bg = '#6f42c1'; $text = '#ffffff';
        } elseif ( strpos( $c, 'maroon' ) !== false ) {
            $bg = '#800000'; $text = '#ffffff';
        } else {
            $clean_bg = strtolower( preg_replace( '/[^a-z0-9#]/', '', $color ) );
            if ( ! empty( $clean_bg ) ) {
                $bg = $clean_bg;
            }
            $text = '#ffffff';
        }

        return sprintf(
            '<span class="badge px-3 py-1 font-weight-bold" style="background-color: %s; color: %s; border: 1px solid %s; font-size: 12px; border-radius: 12px;">%s</span>',
            esc_attr( $bg ),
            esc_attr( $text ),
            esc_attr( $border ),
            esc_html( $color )
        );
    }
}

// Render Dynamic Real-Time Raw Material Logs & Total Stock Audit
if ( strpos( $view, 'list-raw-material' ) !== false ) {
    global $wpdb;
    $raw_table   = $wpdb->prefix . 'raw_material';
    $prod_table  = $wpdb->prefix . 'products';
    $audit_table = $wpdb->prefix . 'raw_mat_audit';

    // 1. Raw Material Log List table body
    $logs = $wpdb->get_results( "
        SELECT r.id, r.product_id, p.product_name, r.color, r.quantity, r.log_date, r.created_by, r.Created_dt
        FROM $raw_table r
        LEFT JOIN $prod_table p ON r.product_id = p.id
        ORDER BY r.log_date DESC, r.id DESC
    " );

    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $logs ) ) {
        foreach ( $logs as $index => $log ) {
            $log_date = ! empty( $log->log_date ) ? date( 'M d, Y', strtotime( $log->log_date ) ) : 'N/A';
            
            $tbody .= '<tr>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $log->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $log_date ) . '</td>';
            $tbody .= '<td class="font-weight-bold text-dark">' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . get_color_badge_html( $log->color ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->created_by ) . '</td>';
            $tbody .= '<td>' . esc_html( date( 'M d, Y h:i A', strtotime( $log->Created_dt ) ) ) . '</td>';
            $tbody .= '<td class="text-right pr-4">';
            $tbody .= '<div class="d-flex align-items-center justify-content-end list-action">';
            $tbody .= '<button type="button" class="btn btn-sm btn-outline-primary mr-2 btn-edit-raw-log" ';
            $tbody .= 'data-id="' . esc_attr( $log->id ) . '" ';
            $tbody .= 'data-product-name="' . esc_attr( $log->product_name ) . '" ';
            $tbody .= 'data-color="' . esc_attr( $log->color ) . '" ';
            $tbody .= 'data-quantity="' . esc_attr( $log->quantity ) . '" ';
            $tbody .= 'data-log-date="' . esc_attr( $log->log_date ) . '" title="Edit Log">';
            $tbody .= '<i class="ri-pencil-line mr-0"></i>';
            $tbody .= '</button>';
            $tbody .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-raw-log" ';
            $tbody .= 'data-id="' . esc_attr( $log->id ) . '" ';
            $tbody .= 'data-product-name="' . esc_attr( $log->product_name ) . '" title="Delete Log">';
            $tbody .= '<i class="ri-delete-bin-line mr-0"></i>';
            $tbody .= '</button>';
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="8" class="text-center">No raw material logs found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );

    // 2. Total Stock & Accordion Audit Trail table body
    $stock_summaries = $wpdb->get_results( "
        SELECT 
            a.product_id, 
            COALESCE(p.product_name, 'Unknown Product') AS product_name, 
            COALESCE(a.color, '') AS color, 
            SUM(CASE 
                WHEN COALESCE(a.action_type, 'Added') = 'Deleted' THEN 0
                WHEN a.txn_type LIKE 'IN%%' THEN a.quantity 
                ELSE -a.quantity 
            END) AS total_stock
        FROM $audit_table a
        LEFT JOIN $prod_table p ON a.product_id = p.id
        GROUP BY a.product_id, COALESCE(a.color, '')
        ORDER BY p.product_name ASC, a.color ASC
    " );

    $stock_tbody = '<tbody class="total-stock-body">';
    if ( ! empty( $stock_summaries ) ) {
        foreach ( $stock_summaries as $s_idx => $stock_item ) {
            $row_id = 'audit-collapse-' . esc_attr( $stock_item->product_id ) . '-' . esc_attr( sanitize_title( $stock_item->color ) );
            $color_badge_html = get_color_badge_html( $stock_item->color );
            $formatted_stock = number_format( (float) $stock_item->total_stock, 2 );

            // Fetch audit records for this product + color
            $audits = $wpdb->get_results( $wpdb->prepare( "
                SELECT id, parent_id, txn_type, COALESCE(action_type, 'Added') AS action_type, quantity, old_quantity, entry_date, old_entry_date, color, old_color, created_at, created_by
                FROM $audit_table
                WHERE product_id = %d AND (color = %s OR (color IS NULL AND %s = ''))
                ORDER BY entry_date DESC, id DESC
            ", $stock_item->product_id, $stock_item->color, $stock_item->color ) );

            $stock_tbody .= '<tr>';
            $stock_tbody .= '<td class="text-center">';
            $stock_tbody .= '<button type="button" class="btn btn-sm btn-outline-primary btn-accordion-toggle" data-target="#' . esc_attr( $row_id ) . '" title="View Audit History">';
            $stock_tbody .= '<i class="las la-angle-down accordion-icon"></i>';
            $stock_tbody .= '</button>';
            $stock_tbody .= '</td>';
            $stock_tbody .= '<td class="font-weight-bold text-dark">' . esc_html( $stock_item->product_name ) . '</td>';
            $stock_tbody .= '<td>' . $color_badge_html . '</td>';
            $stock_tbody .= '<td>' . esc_html( $formatted_stock ) . '</td>';
            $stock_tbody .= '<td class="text-right pr-4">';
            $stock_tbody .= '<button type="button" class="btn btn-sm btn-warning text-white font-weight-bold px-3 py-1 btn-release-stock" ';
            $stock_tbody .= 'data-product-id="' . esc_attr( $stock_item->product_id ) . '" ';
            $stock_tbody .= 'data-product-name="' . esc_attr( $stock_item->product_name ) . '" ';
            $stock_tbody .= 'data-color="' . esc_attr( $stock_item->color ) . '" ';
            $stock_tbody .= 'data-color-html="' . esc_attr( $color_badge_html ) . '" ';
            $stock_tbody .= 'data-stock="' . esc_attr( $formatted_stock ) . '">';
            $stock_tbody .= '<i class="las la-minus-circle mr-1"></i>Release Stock';
            $stock_tbody .= '</button>';
            $stock_tbody .= '</td>';
            $stock_tbody .= '</tr>';

            // Accordion Row
            $stock_tbody .= '<tr id="' . esc_attr( $row_id ) . '" class="audit-collapse-row" style="display: none;">';
            $stock_tbody .= '<td colspan="5" class="bg-light p-3">';
            $stock_tbody .= '<div class="table-responsive rounded mb-0">';
            $stock_tbody .= '<table class="data-table table mb-0 tbl-server-info">';
            $stock_tbody .= '<thead class="bg-white text-uppercase">';
            $stock_tbody .= '<tr class="ligth ligth-data">';
            $stock_tbody .= '<th>Audit ID</th>';
            $stock_tbody .= '<th>Entry Date</th>';
            $stock_tbody .= '<th>Logged Date & Time</th>';
            $stock_tbody .= '<th>Type</th>';
            $stock_tbody .= '<th>Operation</th>';
            $stock_tbody .= '<th>Quantity</th>';
            $stock_tbody .= '<th>Logged By</th>';
            $stock_tbody .= '</tr>';
            $stock_tbody .= '</thead>';
            $stock_tbody .= '<tbody>';

            if ( ! empty( $audits ) ) {
                foreach ( $audits as $audit ) {
                    $entry_dt   = ! empty( $audit->entry_date ) ? date( 'M d, Y', strtotime( $audit->entry_date ) ) : 'N/A';
                    $created_dt = ! empty( $audit->created_at ) ? date( 'M d, Y h:i A', strtotime( $audit->created_at ) ) : 'N/A';
                    
                    $txn_raw = trim( $audit->txn_type );
                    $is_in   = strpos( strtoupper( $txn_raw ), 'IN' ) !== false;
                    $type_badge = $is_in ? '<span class="badge badge-success px-2 py-1"><i class="las la-arrow-down mr-1"></i>IN</span>' : '<span class="badge badge-danger px-2 py-1"><i class="las la-arrow-up mr-1"></i>OUT</span>';

                    // Operation badge
                    $act_raw   = ! empty( $audit->action_type ) ? trim( $audit->action_type ) : ( $is_in ? 'Added' : 'Released' );
                    $act_upper = strtoupper( $act_raw );

                    if ( $act_upper === 'DELETED' ) {
                        $op_badge = '<span class="badge badge-secondary px-2 py-1"><i class="las la-trash-alt mr-1"></i>Deleted</span>';
                        $qty_formatted = '<span class="text-muted font-weight-bold" style="text-decoration: line-through;">' . ( $is_in ? '+' : '-' ) . esc_html( number_format( (float) $audit->quantity, 2 ) ) . '</span>';
                        $entry_date_display = esc_html( $entry_dt );
                    } elseif ( $act_upper === 'EDITED' ) {
                        $op_badge = '<span class="badge badge-info px-2 py-1"><i class="las la-edit mr-1"></i>Edited</span>';
                        
                        // Show old color if changed
                        if ( ! empty( $audit->old_color ) && strtolower( trim( $audit->old_color ) ) !== strtolower( trim( $audit->color ) ) ) {
                            $op_badge .= '<small class="text-muted" style="font-size:11px; display:block; margin-top:2px;">Color: ' . esc_html( $audit->color ) . ' (Was: <span style="text-decoration:line-through;">' . esc_html( $audit->old_color ) . '</span>)</small>';
                        }

                        // Show new quantity with old quantity below if changed
                        $qty_formatted = '<div><span class="text-success font-weight-bold">+' . esc_html( number_format( (float) $audit->quantity, 2 ) ) . '</span></div>';
                        if ( ! is_null( $audit->old_quantity ) && abs( (float) $audit->old_quantity - (float) $audit->quantity ) > 0.0001 ) {
                            $old_q_val = number_format( (float) $audit->old_quantity, 2 );
                            $qty_formatted .= '<small class="text-muted" style="font-size:11px; display:block;">(Was: <span style="text-decoration:line-through;">+' . esc_html( $old_q_val ) . '</span>)</small>';
                        }

                        // Show new entry date with old entry date below if changed
                        $entry_date_display = '<div>' . esc_html( $entry_dt ) . '</div>';
                        if ( ! empty( $audit->old_entry_date ) && $audit->old_entry_date !== '0000-00-00' && $audit->old_entry_date !== $audit->entry_date ) {
                            $old_dt = date( 'M d, Y', strtotime( $audit->old_entry_date ) );
                            $entry_date_display .= '<small class="text-muted" style="font-size:11px; display:block;">(Was: <span style="text-decoration:line-through;">' . esc_html( $old_dt ) . '</span>)</small>';
                        }
                    } elseif ( $act_upper === 'RELEASED' ) {
                        $op_badge = '<span class="badge badge-warning text-white px-2 py-1"><i class="las la-minus-circle mr-1"></i>Released</span>';
                        $qty_formatted = '<span class="text-danger font-weight-bold">-' . esc_html( number_format( (float) $audit->quantity, 2 ) ) . '</span>';
                        $entry_date_display = esc_html( $entry_dt );
                    } else {
                        $op_badge = '<span class="badge badge-success px-2 py-1"><i class="las la-plus-circle mr-1"></i>Added</span>';
                        $qty_formatted = '<span class="text-success font-weight-bold">+' . esc_html( number_format( (float) $audit->quantity, 2 ) ) . '</span>';
                        $entry_date_display = esc_html( $entry_dt );
                    }

                    $stock_tbody .= '<tr>';
                    $stock_tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $audit->id ) . '</td>';
                    $stock_tbody .= '<td>' . $entry_date_display . '</td>';
                    $stock_tbody .= '<td>' . esc_html( $created_dt ) . '</td>';
                    $stock_tbody .= '<td>' . $type_badge . '</td>';
                    $stock_tbody .= '<td>' . $op_badge . '</td>';
                    $stock_tbody .= '<td>' . $qty_formatted . '</td>';
                    $stock_tbody .= '<td>' . esc_html( $audit->created_by ) . '</td>';
                    $stock_tbody .= '</tr>';
                }
            } else {
                $stock_tbody .= '<tr><td colspan="7" class="text-center py-3">No audit records found.</td></tr>';
            }

            $stock_tbody .= '</tbody>';
            $stock_tbody .= '</table>';
            $stock_tbody .= '</div>';
            $stock_tbody .= '</td>';
            $stock_tbody .= '</tr>';
        }
    } else {
        $stock_tbody .= '<tr><td colspan="5" class="text-center py-4 text-muted">No raw material stock entries available yet.</td></tr>';
    }
    $stock_tbody .= '</tbody>';

    $content = preg_replace_callback( '/<tbody class="total-stock-body">.*?<\/tbody>/s', function() use ($stock_tbody) { return $stock_tbody; }, $content );
}

// Render Dynamic Real-Time Production Logs from wp_fin_prod_log
if ( strpos( $view, 'list-production-log' ) !== false ) {
    global $wpdb;
    $log_table  = $wpdb->prefix . 'fin_prod_log';
    $emp_table  = $wpdb->prefix . 'employee';
    $prod_table = $wpdb->prefix . 'products';

    $logs = $wpdb->get_results( "
        SELECT l.id, l.employee_id, e.name as employee_name, e.image as employee_image,
               l.product_id, p.product_name, p.category, l.quantity_produced,
               l.unit_labor_cost_snapshot, l.total_labor_payout, l.Created_dt, l.produce_date, l.created_by,
               u.user_login as logged_by_name
        FROM $log_table l
        LEFT JOIN $emp_table e ON l.employee_id = e.id
        LEFT JOIN $prod_table p ON l.product_id = p.id
        LEFT JOIN {$wpdb->prefix}users u ON l.created_by = u.ID
        ORDER BY l.produce_date DESC, l.id DESC
    " );

    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $logs ) ) {
        foreach ( $logs as $index => $log ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $emp_img = ! empty( $log->employee_image ) ? $log->employee_image : get_template_directory_uri() . '/assets/images/user/1.jpg';
            $unit_cost = number_format( (float) $log->unit_labor_cost_snapshot, 2 );
            $total_payout = number_format( (float) $log->total_labor_payout, 2 );
            $produce_date = ! empty( $log->produce_date ) ? date( 'M d, Y', strtotime( $log->produce_date ) ) : 'N/A';
            
            $tbody .= '<tr>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $log->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $produce_date ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity_produced ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $unit_cost ) . '</td>';
            $tbody .= '<td><strong class="text-success">₹' . esc_html( $total_payout ) . '</strong></td>';
            $tbody .= '<td>' . esc_html( $log->employee_name ) . '</td>';
            $logged_by = ! empty( $log->logged_by_name ) ? $log->logged_by_name : ( ! empty( $log->created_by ) ? $log->created_by : 'System' );
            $tbody .= '<td>' . esc_html( $logged_by ) . '</td>';
            $tbody .= '<td>' . esc_html( date( 'M d, Y h:i A', strtotime( $log->Created_dt ) ) ) . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="10" class="text-center">No production records found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );

    // Fetch unique employee names to populate the datalist
    $employees = $wpdb->get_results( "SELECT DISTINCT name FROM $emp_table ORDER BY name ASC" );
    $datalist_options = '';
    if ( ! empty( $employees ) ) {
        foreach ( $employees as $emp ) {
            if ( ! empty( $emp->name ) ) {
                $datalist_options .= '<option value="' . esc_attr( $emp->name ) . '">';
            }
        }
    }
    $content = str_replace( '<!-- EMPLOYEE_DATALIST_OPTIONS -->', $datalist_options, $content );
}

// Render Dynamic Employee Report
if ( strpos( $view, 'report-employee' ) !== false ) {
    global $wpdb;
    $log_table  = $wpdb->prefix . 'fin_prod_log';
    $emp_table  = $wpdb->prefix . 'employee';
    $prod_table = $wpdb->prefix . 'products';

    $logs = $wpdb->get_results( "
        SELECT l.id, l.employee_id, e.name as employee_name, e.id as emp_real_id,
               l.product_id, p.product_name, p.category, l.quantity_produced,
               l.unit_labor_cost_snapshot, l.total_labor_payout, l.Created_dt, l.produce_date
        FROM $log_table l
        LEFT JOIN $emp_table e ON l.employee_id = e.id
        LEFT JOIN $prod_table p ON l.product_id = p.id
        ORDER BY l.id DESC
    " );

    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $logs ) ) {
        foreach ( $logs as $index => $log ) {
            $cb_id = 'checkbox_emp_' . ( $index + 1 );
            $unit_cost = number_format( (float) $log->unit_labor_cost_snapshot, 2 );
            $total_payout = number_format( (float) $log->total_labor_payout, 2 );
            $produce_date = ! empty( $log->produce_date ) ? date( 'M d, Y', strtotime( $log->produce_date ) ) : 'N/A';
            $tbody .= '<tr data-raw-date="' . esc_attr( $log->produce_date ) . '" data-emp-id="' . esc_attr( $log->emp_real_id ) . '" data-emp-name="' . esc_attr( strtolower($log->employee_name) ) . '">';
            $tbody .= '<td>' . esc_html( $produce_date ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity_produced ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $unit_cost ) . '</td>';
            $tbody .= '<td><strong class="text-success">₹' . esc_html( $total_payout ) . '</strong></td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="6" class="text-center">No records found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );

    $employees = $wpdb->get_results( "SELECT id, name FROM $emp_table ORDER BY name ASC" );
    $datalist_options = '';
    if ( ! empty( $employees ) ) {
        foreach ( $employees as $emp ) {
            if ( ! empty( $emp->name ) ) {
                $datalist_options .= '<option value="' . esc_attr( $emp->name ) . '">ID: ' . esc_html($emp->id) . '</option>';
            }
        }
    }
    $content = str_replace( '<!-- EMPLOYEE_DATALIST_OPTIONS -->', $datalist_options, $content );
}

// Render Dynamic Finished Product Report
if ( strpos( $view, 'report-finished-product' ) !== false ) {
    global $wpdb;
    $log_table  = $wpdb->prefix . 'fin_prod_log';
    $emp_table  = $wpdb->prefix . 'employee';
    $prod_table = $wpdb->prefix . 'products';
    $cat_table  = $wpdb->prefix . 'prod_category';

    $logs = $wpdb->get_results( "
        SELECT l.id, l.employee_id, e.name as employee_name,
               l.product_id, p.product_name, p.category, l.quantity_produced,
               l.unit_labor_cost_snapshot, l.total_labor_payout, l.Created_dt, l.produce_date
        FROM $log_table l
        LEFT JOIN $emp_table e ON l.employee_id = e.id
        LEFT JOIN $prod_table p ON l.product_id = p.id
        ORDER BY l.id DESC
    " );

    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $logs ) ) {
        foreach ( $logs as $index => $log ) {
            $cb_id = 'checkbox_prod_' . ( $index + 1 );
            $unit_cost = number_format( (float) $log->unit_labor_cost_snapshot, 2 );
            $total_payout = number_format( (float) $log->total_labor_payout, 2 );
            $produce_date = ! empty( $log->produce_date ) ? date( 'M d, Y', strtotime( $log->produce_date ) ) : 'N/A';
            
            $tbody .= '<tr data-raw-date="' . esc_attr( $log->produce_date ) . '" data-category="' . esc_attr( strtolower($log->category) ) . '" data-product="' . esc_attr( strtolower($log->product_name) ) . '">';
            $tbody .= '<td>' . esc_html( $produce_date ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity_produced ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $unit_cost ) . '</td>';
            $tbody .= '<td><strong class="text-success">₹' . esc_html( $total_payout ) . '</strong></td>';
            $tbody .= '<td>' . esc_html( $log->employee_name ) . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="7" class="text-center">No records found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );

    $categories = $wpdb->get_results( "SELECT * FROM $cat_table ORDER BY name ASC" );
    $cat_options = '';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $cat ) {
            $cat_options .= '<option value="' . esc_attr( strtolower($cat->name) ) . '">' . esc_html( $cat->name ) . '</option>';
        }
    }
    $content = str_replace( '<!-- CATEGORY_OPTIONS -->', $cat_options, $content );

    $products = $wpdb->get_results( "SELECT DISTINCT id, product_name, category FROM $prod_table ORDER BY product_name ASC" );
    $prod_options = '';
    if ( ! empty( $products ) ) {
        foreach ( $products as $prod ) {
            if ( ! empty( $prod->product_name ) ) {
                $prod_options .= '<option value="' . esc_attr( strtolower($prod->product_name) ) . '" data-category="' . esc_attr( strtolower($prod->category) ) . '">' . esc_html( $prod->product_name ) . '</option>';
            }
        }
    }
    $content = str_replace( '<!-- PRODUCT_DROPDOWN_OPTIONS -->', $prod_options, $content );
}


// Render Dynamic Salary Report
if ( strpos( $view, 'report-salary' ) !== false ) {
    global $wpdb;
    $log_table  = $wpdb->prefix . 'fin_prod_log';
    $emp_table  = $wpdb->prefix . 'employee';
    $prod_table = $wpdb->prefix . 'products';

    // Get list of selectable years
    $years_query = $wpdb->get_results( "SELECT DISTINCT YEAR(produce_date) AS yr FROM $log_table ORDER BY yr DESC" );
    $current_year = intval( date( 'Y' ) );
    $years_list = array();
    $has_current  = false;
    if ( ! empty( $years_query ) ) {
        foreach ( $years_query as $y ) {
            if ( $y->yr ) {
                $yr = intval( $y->yr );
                if ( $yr === $current_year ) $has_current = true;
                $years_list[] = $yr;
            }
        }
    }
    if ( ! $has_current ) {
        $years_list[] = $current_year;
    }
    rsort( $years_list );

    // Fetch all active/registered employees ordered alphabetically
    $employees = $wpdb->get_results( "SELECT id, name, status FROM $emp_table ORDER BY name ASC" );

    // Aggregated salary per employee per year+month
    $salary_rows = $wpdb->get_results( "
        SELECT employee_id AS emp_id,
               YEAR(produce_date)  AS yr,
               MONTH(produce_date) AS mo,
               SUM(total_labor_payout)  AS total_salary
        FROM $log_table
        GROUP BY employee_id, YEAR(produce_date), MONTH(produce_date)
    " );

    $salary_map = array();
    if ( ! empty( $salary_rows ) ) {
        foreach ( $salary_rows as $row ) {
            $key = $row->emp_id . '_' . intval( $row->yr ) . '_' . intval( $row->mo );
            $salary_map[ $key ] = floatval( $row->total_salary );
        }
    }

    // All individual logs for modal use (output as JS variable)
    $all_logs = $wpdb->get_results( "
        SELECT l.id, l.employee_id, e.name AS emp_name,
               l.product_id, p.product_name, p.category,
               l.quantity_produced, l.unit_labor_cost_snapshot,
               l.total_labor_payout, l.produce_date,
               YEAR(l.produce_date)  AS yr,
               MONTH(l.produce_date) AS mo
        FROM $log_table l
        INNER JOIN $emp_table e  ON l.employee_id = e.id
        INNER JOIN $prod_table p ON l.product_id  = p.id
        ORDER BY l.produce_date ASC
    " );

    // Build tbody rows
    $tbody = '<tbody class="ligth-body salary-rows">';
    if ( ! empty( $employees ) ) {
        $months = array( 1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                         5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                         9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December' );
        $idx = 0;
        foreach ( $years_list as $yr ) {
            foreach ( range( 1, 12 ) as $mo ) {
                $mo_name = isset( $months[ $mo ] ) ? $months[ $mo ] : $mo;
                foreach ( $employees as $emp ) {
                    $key = $emp->id . '_' . $yr . '_' . $mo;
                    $total_salary = isset( $salary_map[ $key ] ) ? $salary_map[ $key ] : 0.0;

                    // If employee is Inactive and has 0 salary for this period, do not display them
                    if ( $emp->status === 'Inactive' && $total_salary == 0.0 ) {
                        continue;
                    }

                    $cb_id   = 'salary_cb_' . $idx;
                    $salary  = number_format( (float) $total_salary, 2 );
                    $status_badge = ( $emp->status === 'Inactive' ) ? '<span class="badge badge-warning">Inactive</span>' : '<span class="badge badge-success">Active</span>';

                    $tbody .= '<tr data-year="' . esc_attr( $yr ) . '" data-month="' . esc_attr( $mo ) . '" data-emp-id="' . esc_attr( $emp->id ) . '" data-salary="' . esc_attr( $total_salary ) . '" style="display:none;">';
                    $tbody .= '<td>' . esc_html( $emp->name ) . '</td>';
                    $tbody .= '<td>' . $status_badge . '</td>';
                    $tbody .= '<td class="font-weight-bold text-success">&#8377;' . esc_html( $salary ) . '</td>';
                    $tbody .= '<td>';
                    $tbody .= '<a class="badge badge-info view-salary-detail" style="cursor:pointer;" data-emp-id="' . esc_attr( $emp->id ) . '" data-emp-name="' . esc_attr( $emp->name ) . '" data-year="' . esc_attr( $yr ) . '" data-month="' . esc_attr( $mo ) . '" data-month-name="' . esc_attr( $mo_name ) . '" title="View Work Detail"><i class="ri-eye-line mr-0"></i></a>';
                    $tbody .= '</td>';
                    $tbody .= '</tr>';
                    $idx++;
                }
            }
        }
    } else {
        $tbody .= '<tr id="salary-no-data-row"><td colspan="4" class="text-center text-muted">No employees found.</td></tr>';
    }
    $tbody .= '</tbody>';

    $content = preg_replace( '/<tbody class="ligth-body salary-rows">.*?<\/tbody>/s', $tbody, $content );

    // Distinct years for the year dropdown
    $year_options = '';
    foreach ( $years_list as $yr ) {
        $year_options .= '<option value="' . esc_attr( $yr ) . '">' . esc_html( $yr ) . '</option>';
    }
    $content = str_replace( '<!-- SALARY_YEAR_OPTIONS -->', $year_options, $content );

    // Build JS variable for individual logs (used by modal)
    $logs_json = array();
    foreach ( $all_logs as $log ) {
        $logs_json[] = array(
            'emp_id'    => intval( $log->employee_id ),
            'emp_name'  => $log->emp_name,
            'year'      => intval( $log->yr ),
            'month'     => intval( $log->mo ),
            'date'      => $log->produce_date,
            'category'  => $log->category,
            'product'   => $log->product_name,
            'qty'       => floatval( $log->quantity_produced ),
            'unit_cost' => floatval( $log->unit_labor_cost_snapshot ),
            'payout'    => floatval( $log->total_labor_payout ),
        );
    }
    $content .= '<script>window.salaryLogs = ' . wp_json_encode( $logs_json ) . ';</script>';
}



if ( strpos( $view, 'add-product' ) !== false ) {
    global $wpdb;
    
    // Check for success banner or error banner
    if ( isset( $_GET['success'] ) && '1' === $_GET['success'] ) {
        $alert_html = '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="font-size: 15px;"><i class="ri-checkbox-circle-line mr-2" style="font-size: 18px; vertical-align: middle;"></i><strong>Success:</strong> Product added successfully! You can add another product below.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        $content = str_replace( '<!-- ADD_PRODUCT_ALERT -->', $alert_html, $content );
    } elseif ( isset( $_GET['error'] ) && 'duplicate' === $_GET['error'] ) {
        $alert_html = '<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="font-size: 15px; border-left: 5px solid #dc3545; background-color: #f8d7da; color: #721c24;"><i class="ri-error-warning-fill mr-2" style="font-size: 20px; vertical-align: middle;"></i><strong>Validation Failed:</strong> A product with the same Product Type, Category, and Name already exists in the inventory.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        $content = str_replace( '<!-- ADD_PRODUCT_ALERT -->', $alert_html, $content );
    } else {
        $content = str_replace( '<!-- ADD_PRODUCT_ALERT -->', '', $content );
    }

    // Fetch Categories
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prod_category ORDER BY name ASC" );
    $cat_options = '';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $cat ) {
            $cat_options .= '<option value="' . esc_attr( $cat->id ) . '">' . esc_html( $cat->name ) . '</option>';
        }
    } else {
        $cat_options .= '<option>Beauty</option><option>Grocery</option><option>Food</option>';
    }

    // Replace the selectpicker block for category
    $content = preg_replace_callback(
        '/<select name="category" class="selectpicker form-control" data-style="py-0">.*?<\/select>/s',
        function() use ($cat_options) { return '<select name="category" class="selectpicker form-control" data-style="py-0">' . $cat_options . '</select>'; },
        $content
    );

    // Fetch Product Types
    $types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}product_type ORDER BY Type ASC" );
    $type_options = '';
    if ( ! empty( $types ) ) {
        foreach ( $types as $t ) {
            $type_options .= '<option value="' . esc_attr( $t->id ) . '">' . esc_html( $t->Type ) . '</option>';
        }
    } else {
        $type_options .= '<option>Raw Material</option><option>Finished Product</option>';
    }

    // Replace the selectpicker block for product type
    $content = preg_replace_callback(
        '/<select name="product_type" class="selectpicker form-control" data-style="py-0">.*?<\/select>/s',
        function() use ($type_options) { return '<select name="product_type" class="selectpicker form-control" data-style="py-0">' . $type_options . '</select>'; },
        $content
    );

    // Fetch Products Added Today to display in table list on top of form
    $today = current_time( 'Y-m-d' );
    $recent_products = $wpdb->get_results( $wpdb->prepare( "
        SELECT p.*, t.Type as type_name, c.name as category_name
        FROM {$wpdb->prefix}products p
        LEFT JOIN {$wpdb->prefix}product_type t ON p.product_type = t.id
        LEFT JOIN {$wpdb->prefix}prod_category c ON (p.category = c.id OR p.category = c.name)
        WHERE DATE(p.Created_dt) = %s
        ORDER BY p.id DESC
    ", $today ) );
    $recent_tbody = '';
    if ( ! empty( $recent_products ) ) {
        foreach ( $recent_products as $rp ) {
            $cost = number_format( (float) $rp->cost, 2 );
            $cat_display = ! empty( $rp->category_name ) ? $rp->category_name : $rp->category;
            $type_display = ! empty( $rp->type_name ) ? $rp->type_name : $rp->product_type;

            $recent_tbody .= '<tr>';
            $recent_tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $rp->id ) . '</td>';
            $recent_tbody .= '<td>' . esc_html( $type_display ) . '</td>';
            $recent_tbody .= '<td>' . esc_html( $cat_display ) . '</td>';
            $recent_tbody .= '<td>' . esc_html( $rp->product_name ) . '</td>';
            $recent_tbody .= '<td>₹' . esc_html( $cost ) . '</td>';
            $recent_tbody .= '</tr>';
        }
    } else {
        $recent_tbody .= '<tr><td colspan="5" class="text-center text-muted">No products added today yet.</td></tr>';
    }
    $content = str_replace( '<!-- ADDED_PRODUCTS_TBODY -->', $recent_tbody, $content );
}

// 3.5 Inject Login and Registration alerts/messages
if ( strpos( $view, 'auth-sign-in' ) !== false ) {
    global $posdash_login_error;
    $login_info = '';
    if ( isset( $_GET['loggedout'] ) ) {
        if ( $_GET['loggedout'] === 'idle' ) {
            $login_info = 'You have been logged out due to 3 minutes of inactivity.';
        } elseif ( $_GET['loggedout'] === 'manual' ) {
            $login_info = 'You have successfully logged out.';
        }
    }

    if ( ! empty( $posdash_login_error ) ) {
        $error_html = '<div class="alert alert-danger mb-3" role="alert">' . esc_html( $posdash_login_error ) . '</div>';
        $content = str_replace( '<form method="POST" action="">', '<form method="POST" action="">' . $error_html, $content );
        $content = str_replace( '<form>', '<form>' . $error_html, $content ); // fallback
    } elseif ( ! empty( $login_info ) ) {
        $info_html = '<div class="alert alert-info mb-3" role="alert">' . esc_html( $login_info ) . '</div>';
        $content = str_replace( '<form method="POST" action="">', '<form method="POST" action="">' . $info_html, $content );
        $content = str_replace( '<form>', '<form>' . $info_html, $content ); // fallback
    }
}

if ( strpos( $view, 'auth-sign-up' ) !== false ) {
    global $posdash_signup_error, $posdash_signup_success;
    if ( ! empty( $posdash_signup_error ) ) {
        $error_html = '<div class="alert alert-danger mb-3" role="alert">' . esc_html( $posdash_signup_error ) . '</div>';
        $content = str_replace( '<form method="POST" action="">', '<form method="POST" action="">' . $error_html, $content );
        $content = str_replace( '<form>', '<form>' . $error_html, $content ); // fallback
    }
    if ( ! empty( $posdash_signup_success ) ) {
        $success_html = '<div class="alert alert-success mb-3" role="alert">' . esc_html( $posdash_signup_success ) . '</div>';
        $content = str_replace( '<form method="POST" action="">', '<form method="POST" action="">' . $success_html, $content );
        $content = str_replace( '<form>', '<form>' . $success_html, $content ); // fallback
    }
}

// C1: Inject CSRF nonce into all forms
$nonce_field = wp_nonce_field( 'posdash_form_action', '_wpnonce', true, false );
$content = preg_replace( '/(<form[^>]*>)/i', '$1' . $nonce_field, $content );

// 4. Output the page content, using get_header() and get_footer() if it's a standard page
if ( stripos( $content, '<head>' ) !== false ) {
    // Standalone page (like auth pages) - inject head and footer manually
    ob_start();
    wp_head();
    $wp_head_content = ob_get_clean();

    ob_start();
    wp_footer();
    $wp_footer_content = ob_get_clean();

    $content = str_ireplace( '</head>', $wp_head_content . '</head>', $content );
    $content = str_ireplace( '</body>', $wp_footer_content . '</body>', $content );
    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
    // Standard page with stripped header and footer
    get_header();
    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    get_footer();
}
