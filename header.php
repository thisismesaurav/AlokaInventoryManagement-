<?php
/**
 * The header for our theme
 *
 * @package Inventory_Management
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$theme_uri = get_template_directory_uri();
$home_url  = home_url( '/' );

$brand_name = get_option( 'inventory_brand_name' );
if ( empty( $brand_name ) ) {
    $brand_name = 'POSDash';
}
$brand_logo = get_option( 'inventory_brand_logo' );
if ( empty( $brand_logo ) ) {
    $brand_logo = $theme_uri . '/assets/images/logo.png';
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo esc_url( $theme_uri ); ?>/assets/images/favicon.ico" />
    <link rel="stylesheet" href="<?php echo esc_url( $theme_uri ); ?>/assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( $theme_uri ); ?>/assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="<?php echo esc_url( $theme_uri ); ?>/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( $theme_uri ); ?>/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( $theme_uri ); ?>/assets/vendor/remixicon/fonts/remixicon.css">
    <?php wp_head(); ?>
    <script>
        window.posdashAjaxUrl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
        window.posdashNonce = "<?php echo esc_js( wp_create_nonce( 'posdash_ajax_action' ) ); ?>";
    </script>
</head>
<body <?php body_class(); ?>>
    <!-- loader Start -->
    <div id="loading">
          <div id="loading-center">
          </div>
    </div>
    <!-- loader END -->
    <!-- Wrapper Start -->
    <div class="wrapper">
      
      <div class="iq-sidebar  sidebar-default ">
          <div class="iq-sidebar-logo d-flex align-items-center justify-content-between">
              <a href="<?php echo esc_url( $home_url ); ?>" class="header-logo">
                  <img src="<?php echo esc_url( $brand_logo ); ?>" class="img-fluid rounded-normal light-logo" alt="logo"><h5 class="logo-title light-logo ml-3"><?php echo esc_html( $brand_name ); ?></h5>
              </a>
              <div class="iq-menu-bt-sidebar ml-0">
                  <i class="las la-bars wrapper-menu"></i>
              </div>
          </div>
          <div class="data-scrollbar" data-scroll="1">
              <nav class="iq-sidebar-menu">
                  <ul id="iq-sidebar-toggle" class="iq-menu">
                      <li class="">
                          <a href="<?php echo esc_url( $home_url ); ?>" class="svg-icon">                        
                              <svg  class="svg-icon" id="p-dash1" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
                              </svg>
                              <span class="ml-4">Dashboards</span>
                          </a>
                      </li>
                      <li class=" ">
                          <a href="#production" class="collapsed" data-toggle="collapse" aria-expanded="false">
                              <svg class="svg-icon" id="p-dash-production" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                  <polyline points="14 2 14 8 20 8"></polyline>
                                  <line x1="16" y1="13" x2="8" y2="13"></line>
                                  <line x1="16" y1="17" x2="8" y2="17"></line>
                                  <polyline points="10 9 9 9 8 9"></polyline>
                              </svg>
                              <span class="ml-4">Data Entry</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="production" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                              <li class="">
                                  <a href="<?php echo esc_url( $home_url . 'list-production-log' ); ?>">
                                      <i class="las la-minus"></i><span>Work</span>
                                  </a>
                              </li>
                              <li class="">
                                  <a href="<?php echo esc_url( $home_url . 'list-raw-material' ); ?>">
                                      <i class="las la-minus"></i><span>Raw Material</span>
                                  </a>
                              </li>
                          </ul>
                      <li class=" ">
                          <a href="#product" class="collapsed" data-toggle="collapse" aria-expanded="false">
                              <svg class="svg-icon" id="p-dash2" width="20" height="20"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                              </svg>
                              <span class="ml-4">Products</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="product" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                              <li class="">
                                  <a href="<?php echo esc_url( $home_url . 'list-product' ); ?>">
                                      <i class="las la-minus"></i><span>List Product</span>
                                  </a>
                              </li>
                              <li class="">
                                  <a href="<?php echo esc_url( $home_url . 'add-product' ); ?>">
                                      <i class="las la-minus"></i><span>Add Product</span>
                                  </a>
                              </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#category" class="collapsed" data-toggle="collapse" aria-expanded="false">
                              <svg class="svg-icon" id="p-dash3" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                              </svg>
                              <span class="ml-4">Categories</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="category" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'list-category' ); ?>">
                                              <i class="las la-minus"></i><span>List Category</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'add-category' ); ?>">
                                              <i class="las la-minus"></i><span>Add Category</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'list-type' ); ?>">
                                              <i class="las la-minus"></i><span>List Type</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'add-type' ); ?>">
                                              <i class="las la-minus"></i><span>Add Type</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#people" class="collapsed" data-toggle="collapse" aria-expanded="false">
                              <svg class="svg-icon" id="p-dash8" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                              </svg>
                              <span class="ml-4">People</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="people" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'list-customers' ); ?>">
                                              <i class="las la-minus"></i><span>Customers</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'add-customers' ); ?>">
                                              <i class="las la-minus"></i><span>Add Customers</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'list-users' ); ?>">
                                              <i class="las la-minus"></i><span>Users</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'add-users' ); ?>">
                                              <i class="las la-minus"></i><span>Add Users</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'list-suppliers' ); ?>">
                                              <i class="las la-minus"></i><span>Suppliers</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'add-supplier' ); ?>">
                                              <i class="las la-minus"></i><span>Add Suppliers</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#reports" class="collapsed" data-toggle="collapse" aria-expanded="false">
                              <svg class="svg-icon" id="p-dash9" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>
                              </svg>
                              <span class="ml-4">Reports</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="reports" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'report-employee' ); ?>">
                                              <i class="las la-minus"></i><span>Employee</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'report-finished-product' ); ?>">
                                              <i class="las la-minus"></i><span>Finished product</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="<?php echo esc_url( $home_url . 'report-salary' ); ?>">
                                              <i class="las la-minus"></i><span>Salary</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                  </ul>
              </nav>

              <div class="p-3"></div>
          </div>
          </div>      <div class="iq-top-navbar">
          <div class="iq-navbar-custom">
              <nav class="navbar navbar-expand-lg navbar-light p-0">
                  <div class="iq-navbar-logo d-flex align-items-center justify-content-between">
                      <i class="ri-menu-line wrapper-menu"></i>
                      <a href="<?php echo esc_url( $home_url ); ?>" class="header-logo">
                          <img src="<?php echo esc_url( $brand_logo ); ?>" class="img-fluid rounded-normal" alt="logo">
                          <h5 class="logo-title ml-3"><?php echo esc_html( $brand_name ); ?></h5>
      
                      </a>
                  </div>

                  <div class="header-brand-center">
                       <h4 class="mb-0 text-uppercase">
                           <?php echo esc_html( $brand_name ); ?>
                       </h4>
                   </div>

                  <div class="d-flex align-items-center ml-auto">
                      <button class="navbar-toggler" type="button" data-toggle="collapse"
                          data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                          aria-label="Toggle navigation">
                          <i class="ri-menu-3-line"></i>
                      </button>
                      <div class="collapse navbar-collapse" id="navbarSupportedContent">
                          <ul class="navbar-nav ml-auto navbar-list align-items-center">

                              <li class="nav-item nav-icon dropdown caption-content">
                                  <?php
                                  $current_user = wp_get_current_user();
                                  $avatar_url   = get_avatar_url( $current_user->ID );
                                  $registered_date = date_i18n( 'j F, Y', strtotime( $current_user->user_registered ) );
                                  ?>
                                  <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton4"
                                      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                      <img src="<?php echo esc_url( $avatar_url ); ?>" class="img-fluid rounded" alt="user">
                                  </a>
                                  <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
                                      <div class="card shadow-none m-0">
                                          <div class="card-body p-0 text-center">
                                              <div class="media-body profile-detail text-center">
                                                  <img src="<?php echo esc_url( $theme_uri ); ?>/assets/images/page-img/profile-bg.jpg" alt="profile-bg"
                                                      class="rounded-top img-fluid mb-4">
                                                  <img src="<?php echo esc_url( $avatar_url ); ?>" alt="profile-img"
                                                      class="rounded profile-img img-fluid avatar-70">
                                              </div>
                                              <div class="p-3">
                                                  <h5 class="mb-1"><?php echo esc_html( $current_user->display_name ); ?></h5>
                                                  <p class="mb-0"><?php echo esc_html( $current_user->user_email ); ?></p>
                                                  <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 5px;">Since <?php echo esc_html( $registered_date ); ?></p>
                                                  <div class="d-flex align-items-center justify-content-center mt-3">
                                                      <a href="<?php echo esc_url( home_url( '/user-profile' ) ); ?>" class="btn border mr-2">Profile</a>
                                                      <a href="<?php echo esc_url( wp_logout_url( home_url( '/auth-sign-in' ) ) ); ?>" class="btn border">Sign Out</a>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </li>
                          </ul>
                      </div>
                  </div>
              </nav>
          </div>
      </div>
