<?php
/**
 * The footer for our theme
 *
 * @package Inventory_Management
 */
$theme_uri = get_template_directory_uri();
?>
    </div>
    <!-- Wrapper End-->
    <footer class="iq-footer">
            <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item"><a href="#">Privacy Policy</a></li>
                                <li class="list-inline-item"><a href="#">Terms of Use</a></li>
                            </ul>
                        </div>
                        <div class="col-lg-6 text-right">
                            <span class="mr-1"><script>document.write(new Date().getFullYear())</script>©</span> <a href="#" class="">POS Dash</a>.
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
    
    <?php wp_footer(); ?>
  </body>
</html>
