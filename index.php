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

// Clean the view parameter to prevent path traversal
$view = trim( $view, '/' );
$view = str_replace( array( '..', '\\' ), '', $view ); // Safety first

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

    // A. Fetch Total Products
    $total_products = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}products" );

    // B. Fetch Total Labour Cost
    $total_labor = (float) $wpdb->get_var( "SELECT SUM(total_labor_payout) FROM {$wpdb->prefix}fin_prod_log" );

    // C. Fetch Top Categories by production count
    $category_counts = $wpdb->get_results( "
        SELECT p.category, SUM(l.quantity_produced) AS total_qty
        FROM {$wpdb->prefix}fin_prod_log l
        INNER JOIN {$wpdb->prefix}products p ON l.product_id = p.id
        GROUP BY p.category
        ORDER BY total_qty DESC
        LIMIT 4
    " );

    // Fetch Category images for mapping
    $categories_info = $wpdb->get_results( "SELECT name, image FROM {$wpdb->prefix}Prod_Category" );
    $cat_image_map = array();
    if ( ! empty( $categories_info ) ) {
        foreach ( $categories_info as $c_info ) {
            $cat_image_map[ strtolower( trim( $c_info->name ) ) ] = $c_info->image;
        }
    }

    $bg_classes = array( 'bg-warning-light', 'bg-danger-light', 'bg-info-light', 'bg-success-light' );
    $categories_html = '';
    if ( ! empty( $category_counts ) ) {
        foreach ( $category_counts as $idx => $cat ) {
            $cat_name = trim( $cat->category );
            $cat_key  = strtolower( $cat_name );
            $cat_image = '';
            if ( isset( $cat_image_map[ $cat_key ] ) && ! empty( $cat_image_map[ $cat_key ] ) ) {
                $img_path = $cat_image_map[ $cat_key ];
                if ( strpos( $img_path, 'assets/' ) === 0 ) {
                    $cat_image = get_template_directory_uri() . '/' . $img_path;
                } else {
                    $cat_image = $img_path;
                }
            } else {
                $fallback_index = ( $idx % 3 ) + 1;
                $cat_image = get_template_directory_uri() . '/assets/images/product/0' . $fallback_index . '.png';
            }
            $bg_class = $bg_classes[ $idx % 4 ];

            $categories_html .= '
            <li class="col-lg-3">
                <div class="card card-block card-stretch card-height mb-0">
                    <div class="card-body">
                        <div class="' . esc_attr( $bg_class ) . ' rounded">
                            <img src="' . esc_url( $cat_image ) . '" class="style-img img-fluid m-auto p-3" alt="image">
                        </div>
                        <div class="style-text text-left mt-3">
                            <h5 class="mb-1">' . esc_html( $cat_name ) . '</h5>
                            <p class="mb-0">' . esc_html( $cat->total_qty ) . ' Item</p>
                        </div>
                    </div>
                </div>
            </li>';
        }
    } else {
        $categories_html = '<li class="col-12 text-center text-muted p-4">No produced items found.</li>';
    }

    $content = str_replace( '<!-- DASHBOARD_TOTAL_PRODUCTS -->', esc_html( $total_products ), $content );
    $content = str_replace( '<!-- DASHBOARD_TOTAL_LABOUR_COST -->', '&#8377; ' . number_format( $total_labor, 2 ), $content );
    $content = str_replace( '<!-- DASHBOARD_TOP_CATEGORIES -->', $categories_html, $content );
    $content = str_replace( '<!-- DASHBOARD_INCOME_PRODUCTS -->', esc_html( $total_products ), $content );
    $content = str_replace( '<!-- DASHBOARD_EXPENSE_LABOUR_COST -->', '&#8377; ' . number_format( $total_labor, 2 ), $content );
}

// Render Dynamic Real-Time Products from wp_products
if ( strpos( $view, 'list-product' ) !== false ) {
    global $wpdb;
    $products = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}products ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $products ) ) {
        foreach ( $products as $index => $product ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $cost = number_format( (float) $product->cost, 2 );

            $tbody .= '<tr>';
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td>' . esc_html( $product->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $product->product_code ) . '</td>';
            $tbody .= '<td>' . esc_html( $product->category ) . '</td>';
            $tbody .= '<td>$' . esc_html( $cost ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            $tbody .= '<a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="View" href="#"><i class="ri-eye-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( home_url( '/?action=delete_product&id=' . $product->id ) ) . '" onclick="return confirm(\'Are you sure you want to delete this product?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="6" class="text-center">No products found.</td></tr>';
    }
    $tbody .= '</tbody>';

    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
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
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input emp-checkbox" data-id="' . esc_attr( $emp->id ) . '" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $default_img = ( $emp->gender === 'Female' ) ? '/assets/images/user/11.png' : '/assets/images/user/1.jpg';
            $img_url = !empty( $emp->image ) ? $emp->image : get_template_directory_uri() . $default_img;
            $tbody .= '<td><div class="d-flex align-items-center"><img src="' . esc_url( $img_url ) . '" class="img-fluid rounded avatar-50 mr-3" alt="image"><div>' . esc_html( $emp->name ) . '</div></div></td>';
            $tbody .= '<td>' . esc_html( $emp->email ) . '</td>';
            $tbody .= '<td>' . esc_html( $emp->company ) . '</td>';
            $tbody .= '<td>' . esc_html( $emp->address ) . '</td>';
            $tbody .= '<td>' . esc_html( $emp->status ) . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="6" class="text-center">No employees found.</td></tr>';
    }
    $tbody .= '</tbody>';

    $content = preg_replace( '/<tbody class="ligth-body">.*?<\/tbody>/s', $tbody, $content );
    $content .= '<script>window.employeeList = ' . wp_json_encode( $employees ) . '; window.themeUri = "' . esc_url( get_template_directory_uri() ) . '";</script>';
}

// Extend Categories sidebar dropdown dynamically across all page templates
$content = preg_replace(
    '/<ul id="category"[^>]*>.*?<\/ul>/is',
    '<ul id="category" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
        <li class="' . ( strpos( $_SERVER['REQUEST_URI'], 'list-category' ) !== false ? 'active' : '' ) . '">
            <a href="' . esc_url( home_url( '/list-category' ) ) . '">
                <i class="las la-minus"></i><span>List Category</span>
            </a>
        </li>
        <li class="' . ( strpos( $_SERVER['REQUEST_URI'], 'add-category' ) !== false ? 'active' : '' ) . '">
            <a href="' . esc_url( home_url( '/add-category' ) ) . '">
                <i class="las la-minus"></i><span>Add Category</span>
            </a>
        </li>
        <li class="' . ( strpos( $_SERVER['REQUEST_URI'], 'list-type' ) !== false ? 'active' : '' ) . '">
            <a href="' . esc_url( home_url( '/list-type' ) ) . '">
                <i class="las la-minus"></i><span>List Type</span>
            </a>
        </li>
        <li class="' . ( strpos( $_SERVER['REQUEST_URI'], 'add-type' ) !== false ? 'active' : '' ) . '">
            <a href="' . esc_url( home_url( '/add-type' ) ) . '">
                <i class="las la-minus"></i><span>Add Type</span>
            </a>
        </li>
    </ul>',
    $content
);

// Render Dynamic Real-Time Categories from wp_Prod_Category
if ( strpos( $view, 'list-category' ) !== false ) {
    global $wpdb;
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}Prod_Category ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $index => $cat ) {
            $img_url = $cat->image;
            if ( strpos( $img_url, 'assets/' ) === 0 ) {
                $img_url = get_template_directory_uri() . '/' . $img_url;
            }
            $cb_id = 'checkbox' . ( $index + 2 );
            $tbody .= '<tr>';
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td><div class="d-flex align-items-center"><img src="' . esc_url( $img_url ) . '" class="img-fluid rounded avatar-50 mr-3" alt="image"><div>' . esc_html( $cat->name ) . '</div></div></td>';
            $tbody .= '<td>' . esc_html( $cat->code ) . '</td>';
            $tbody .= '<td>' . esc_html( $cat->name ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            $tbody .= '<a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="View" href="#"><i class="ri-eye-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( home_url( '/?action=delete_category&id=' . $cat->id ) ) . '" onclick="return confirm(\'Are you sure you want to delete this category?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="5" class="text-center">No categories found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace( '/<tbody class="ligth-body">.*?<\/tbody>/s', $tbody, $content );
}

// Render Dynamic Real-Time Product Types from wp_product_type
if ( strpos( $view, 'list-type' ) !== false ) {
    global $wpdb;
    $types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}product_type ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $types ) ) {
        foreach ( $types as $index => $t ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $tbody .= '<tr>';
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td>' . esc_html( $t->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $t->Type ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            $tbody .= '<a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="View" href="#"><i class="ri-eye-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( home_url( '/?action=delete_type&id=' . $t->id ) ) . '" onclick="return confirm(\'Are you sure you want to delete this product type?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="4" class="text-center">No product types found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
}

// Render Dynamic Real-Time Customers from wp_customers
if ( strpos( $view, 'list-customers' ) !== false ) {
    global $wpdb;
    $customers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}customers ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $customers ) ) {
        foreach ( $customers as $index => $cust ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $tbody .= '<tr>';
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td>' . esc_html( $cust->name ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->email ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->phone_number ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->country ) . '</td>';
            $tbody .= '<td>' . esc_html( $cust->order_count ) . '</td>';
            $tbody .= '<td><div class="badge badge-warning">' . esc_html( $cust->status ) . '</div></td>';
            $tbody .= '<td>' . esc_html( $cust->last_order ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            $tbody .= '<a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="View" href="#"><i class="ri-eye-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( home_url( '/?action=delete_customer&id=' . $cust->id ) ) . '" onclick="return confirm(\'Are you sure you want to delete this customer?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="9" class="text-center">No customers found.</td></tr>';
    }
    $tbody .= '</tbody>';
    $content = preg_replace_callback( '/<tbody class="ligth-body">.*?<\/tbody>/s', function() use ($tbody) { return $tbody; }, $content );
}

// Render Dynamic Real-Time Suppliers from wp_suppliers
if ( strpos( $view, 'list-suppliers' ) !== false ) {
    global $wpdb;
    $suppliers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}suppliers ORDER BY id DESC" );
    $tbody = '<tbody class="ligth-body">';
    if ( ! empty( $suppliers ) ) {
        foreach ( $suppliers as $index => $supp ) {
            $cb_id = 'checkbox' . ( $index + 2 );
            $tbody .= '<tr>';
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td>' . esc_html( $supp->company_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->name ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->email ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->phone_number ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->city ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->country ) . '</td>';
            $tbody .= '<td>' . esc_html( $supp->gst_number ) . '</td>';
            $tbody .= '<td>';
            $tbody .= '<div class="d-flex align-items-center list-action">';
            $tbody .= '<a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="View" href="#"><i class="ri-eye-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>';
            $tbody .= '<a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="Delete" href="' . esc_url( home_url( '/?action=delete_supplier&id=' . $supp->id ) ) . '" onclick="return confirm(\'Are you sure you want to delete this supplier?\');"><i class="ri-delete-bin-line mr-0"></i></a>';
            $tbody .= '</div>';
            $tbody .= '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="9" class="text-center">No suppliers found.</td></tr>';
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
               l.unit_labor_cost_snapshot, l.total_labor_payout, l.Created_dt, l.produce_date, l.created_by
        FROM $log_table l
        LEFT JOIN $emp_table e ON l.employee_id = e.id
        LEFT JOIN $prod_table p ON l.product_id = p.id
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
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td class="text-muted" style="font-size:12px;">#' . esc_html( $log->id ) . '</td>';
            $tbody .= '<td>' . esc_html( $produce_date ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity_produced ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $unit_cost ) . '</td>';
            $tbody .= '<td><strong class="text-success">₹' . esc_html( $total_payout ) . '</strong></td>';
            $tbody .= '<td>' . esc_html( $log->employee_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->created_by ) . '</td>';
            $tbody .= '<td>' . esc_html( date( 'M d, Y h:i A', strtotime( $log->Created_dt ) ) ) . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="11" class="text-center">No production records found.</td></tr>';
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
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
            $tbody .= '<td>' . esc_html( $produce_date ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->category ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->product_name ) . '</td>';
            $tbody .= '<td>' . esc_html( $log->quantity_produced ) . '</td>';
            $tbody .= '<td>₹' . esc_html( $unit_cost ) . '</td>';
            $tbody .= '<td><strong class="text-success">₹' . esc_html( $total_payout ) . '</strong></td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody .= '<tr><td colspan="7" class="text-center">No records found.</td></tr>';
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
    $cat_table  = $wpdb->prefix . 'Prod_Category';

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
            $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
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
        $tbody .= '<tr><td colspan="8" class="text-center">No records found.</td></tr>';
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
                    $tbody .= '<td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="' . esc_attr( $cb_id ) . '"><label for="' . esc_attr( $cb_id ) . '" class="mb-0"></label></div></td>';
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
        $tbody .= '<tr id="salary-no-data-row"><td colspan="5" class="text-center text-muted">No employees found.</td></tr>';
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
    $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}Prod_Category ORDER BY name ASC" );
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
            $type_options .= '<option value="' . esc_attr( $t->Type ) . '">' . esc_html( $t->Type ) . '</option>';
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
