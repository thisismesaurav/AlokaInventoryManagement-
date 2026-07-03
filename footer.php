<?php
/**
 * The footer for our theme
 *
 * @package Inventory_Management
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$theme_uri = get_template_directory_uri();
?>
    </div>
    <!-- Wrapper End-->
    <footer class="iq-footer">
        <div class="iq-footer-inner">
            <div class="card mb-0 border-0">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item"><a href="#">Privacy Policy</a></li>
                                <li class="list-inline-item"><a href="#">Terms of Use</a></li>
                            </ul>
                        </div>
                        <div class="col-6 text-right">
                            <span class="mr-1"><script>document.write(new Date().getFullYear())</script>©</span> <a href="#">POS Dash</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- Backend Bundle JavaScript -->
    <script src="<?php echo esc_url( $theme_uri ); ?>/assets/js/backend-bundle.min.js"></script>
    
    <!-- Table Treeview JavaScript -->
    <script src="<?php echo esc_url( $theme_uri ); ?>/assets/js/table-treeview.js"></script>
    
    <!-- Chart Custom JavaScript -->
    <script src="<?php echo esc_url( $theme_uri ); ?>/assets/js/customizer.js"></script>
    
    <!-- Chart Custom JavaScript -->
    <script async src="<?php echo esc_url( $theme_uri ); ?>/assets/js/chart-custom.js"></script>
    
    <!-- app JavaScript -->
    <script src="<?php echo esc_url( $theme_uri ); ?>/assets/js/app.js"></script>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var currentUrl = window.location.href.split('#')[0].split('?')[0];
        
        // Normalize trailing slashes
        if (currentUrl.endsWith('/')) {
            currentUrl = currentUrl.slice(0, -1);
        }

        $('.iq-sidebar-menu ul li a').each(function() {
            var rawHref = $(this).attr('href');
            if (rawHref && (rawHref.indexOf('#') === 0 || rawHref.startsWith('#'))) {
                return; // Skip collapse toggle links
            }

            var linkUrl = this.href.split('#')[0].split('?')[0];
            if (linkUrl.endsWith('/')) {
                linkUrl = linkUrl.slice(0, -1);
            }

            if (currentUrl === linkUrl) {
                // Apply active blue highlight class to the exact matching link
                $(this).addClass('current-page-active');
                $(this).closest('li').addClass('active');

                // Expand parent collapse container if item is in a submenu
                var parentUl = $(this).closest('ul.iq-submenu');
                if (parentUl.length) {
                    parentUl.addClass('show');
                    var parentLi = parentUl.closest('li');
                    parentLi.addClass('active');
                    parentLi.find('> a').attr('aria-expanded', 'true').removeClass('collapsed');
                }
            }
        });
    });
    </script>
    
    <?php wp_footer(); ?>
  </body>
</html>
