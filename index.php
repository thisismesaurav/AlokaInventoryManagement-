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

		// B. Replace index.html links to the WordPress homepage
		$content = preg_replace( '/(\.\.\/)+(backend|ThemeHtml)\/index\.html/i', $home_url, $content );
		$content = preg_replace( '/index\.html/i', $home_url, $content );

		// C. Replace backend HTML links to clean paths /XXX
		$content = preg_replace_callback( '/(\.\.\/)+backend\/([a-zA-Z0-9_-]+)\.html/i', function( $matches ) use ( $home_url ) {
			if ( $matches[2] === 'index' ) {
				return $home_url;
			}
			$slug = $matches[2];
			if ( strpos( $slug, 'page-' ) === 0 ) {
				$slug = substr( $slug, 5 );
			}
			return $home_url . $slug;
		}, $content );

		// C2. Replace ThemeHtml HTML links to clean paths /XXX
		$content = preg_replace_callback( '/(\.\.\/)+ThemeHtml\/([a-zA-Z0-9_-]+)\.html/i', function( $matches ) use ( $home_url ) {
			if ( $matches[2] === 'index' ) {
				return $home_url;
			}
			$slug = $matches[2];
			if ( strpos( $slug, 'page-' ) === 0 ) {
				$slug = substr( $slug, 5 );
			}
			return $home_url . $slug;
		}, $content );

		// D. Replace app HTML links to clean paths /XXX
		$content = preg_replace_callback( '/(\.\.\/)+app\/([a-zA-Z0-9_-]+)\.html/i', function( $matches ) use ( $home_url ) {
			$slug = $matches[2];
			if ( strpos( $slug, 'page-' ) === 0 ) {
				$slug = substr( $slug, 5 );
			}
			return $home_url . $slug;
		}, $content );

		// E. Handle sibling HTML links (e.g. "page-list-product.html" without relative path)
		if ( 'backend' === $current_dir || 'ThemeHtml' === $current_dir ) {
			$content = preg_replace_callback( '/(?<![\/a-zA-Z0-9_-])([a-zA-Z0-9_-]+)\.html/i', function( $matches ) use ( $home_url ) {
				if ( $matches[1] === 'index' ) {
					return $home_url;
				}
				// Skip if it matches asset/vendor stuff
				if ( in_array( $matches[1], array( 'min', 'css', 'js', 'html' ), true ) ) {
					return $matches[0];
				}
				$slug = $matches[1];
				if ( strpos( $slug, 'page-' ) === 0 ) {
					$slug = substr( $slug, 5 );
				}
				return $home_url . $slug;
			}, $content );
		} elseif ( 'app' === $current_dir ) {
			$content = preg_replace_callback( '/(?<![\/a-zA-Z0-9_-])([a-zA-Z0-9_-]+)\.html/i', function( $matches ) use ( $home_url ) {
				if ( in_array( $matches[1], array( 'min', 'css', 'js', 'html' ), true ) ) {
					return $matches[0];
				}
				$slug = $matches[1];
				if ( strpos( $slug, 'page-' ) === 0 ) {
					$slug = substr( $slug, 5 );
				}
				return $home_url . $slug;
			}, $content );
		}

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
    $products = $wpdb->get_results( "
        SELECT p.*, t.Type as type_name 
        FROM {$wpdb->prefix}products p
        LEFT JOIN {$wpdb->prefix}product_type t ON p.product_type = t.id
        ORDER BY p.id DESC
    " );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $products ) ) {
        foreach ( $products as $index => $product ) {
            $cost = number_format( (float) $product->cost, 2 );

            $tbody .= '<tr>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $product->id ) . '</td>';
            $tbody .= '<td>' . esc_html( ! empty( $product->type_name ) ? $product->type_name : $product->product_type ) . '</td>';
            $tbody .= '<td>' . esc_html( $product->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $product->product_name ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $cost ) . '</td>';
            $tbody .= '<td>' . esc_html( $product->product_code ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            
            // Edit Row Button
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#" onclick="window.openEditProductModal(' . intval( $product->id ) . '); return false;"><i class="ri-pencil-line mr-0"></i></a>';
            
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
        $tbody .= '<tr><td colspan="7" class="text-center">No products found.</td></tr>';
    }
    $tbody .= '</tbody>';

    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
    $content .= '<script>window.productList = ' . wp_json_encode( $products ) . '; window.currentIsAdmin = ' . ( current_user_can( 'administrator' ) ? 'true' : 'false' ) . ';</script>';

    // Fetch Categories for modal dropdown
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prod_category ORDER BY name ASC" );
    $cat_options = '';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $cat ) {
            $cat_options .= '<option value="' . esc_attr( $cat->name ) . '">' . esc_html( $cat->name ) . '</option>';
        }
    }
    $content = str_replace( '<!-- EDIT_CATEGORY_OPTIONS -->', $cat_options, $content );

    // Fetch Product Types for modal dropdown
    $types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}product_type ORDER BY Type ASC" );
    $type_options = '';
    if ( ! empty( $types ) ) {
        foreach ( $types as $t ) {
            $type_options .= '<option value="' . esc_attr( $t->id ) . '">' . esc_html( $t->Type ) . '</option>';
        }
    }
    $content = str_replace( '<!-- EDIT_TYPE_OPTIONS -->', $type_options, $content );
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
            $tbody .= '<td><div class="d-flex align-items-center"><img src="' . esc_url( $img_url ) . '" class="img-fluid rounded avatar-50 mr-3" alt="image"><div>' . esc_html( $cat->name ) . '</div></div></td>';
            $tbody .= '<td>' . esc_html( $cat->code ) . '</td>';
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
        $tbody .= '<tr><td colspan="4" class="text-center">No categories found.</td></tr>';
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

// Render Dynamic Real-Time Raw Material Logs from wp_raw_material
if ( strpos( $view, 'list-raw-material' ) !== false ) {
    global $wpdb;
    $raw_table = $wpdb->prefix . 'raw_material';
    $prod_table = $wpdb->prefix . 'products';

    $logs = $wpdb->get_results( "
        SELECT r.id, r.product_id, p.product_name, p.category, r.quantity, r.log_date, r.created_by, r.Created_dt
        FROM $raw_table r
        LEFT JOIN $prod_table p ON r.product_id = p.id
        ORDER BY r.log_date DESC, r.id DESC
    " );

    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $logs ) ) {
        foreach ( $logs as $index => $log ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $log_date = ! empty( $log->log_date ) ? date( 'M d, Y', strtotime( $log->log_date ) ) : 'N/A';
            
            $tbody .= '<tr>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $log->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $log_date ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->created_by ) . '</td>';
            $tbody .= '<td>' . esc_html( date( 'M d, Y h:i A', strtotime( $log->Created_dt ) ) ) . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="7" class="text-center">No raw material logs found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
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
    
    // Fetch Categories
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prod_category ORDER BY name ASC" );
    $cat_options = '';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $cat ) {
            $cat_options .= '<option value="' . esc_attr( $cat->name ) . '">' . esc_html( $cat->name ) . '</option>';
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
