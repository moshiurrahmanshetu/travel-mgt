<?php
/**
 * Admin Layout Footer
 * Tour & Travel Booking Management System
 */
?>
        <footer id="admin-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    &copy; <?= date('Y'); ?> <strong><?= e(APP_NAME); ?></strong>. All rights reserved.
                </div>
                <div class="text-muted small">
                    Version <?= e(APP_VERSION); ?> &bull; Dhaka Time: <?= date('h:i A'); ?>
                </div>
            </div>
        </footer>
    </div><!-- /#admin-main -->
</div><!-- /#admin-wrapper -->

<!-- Bootstrap 5 Bundle JS (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Admin JS (Sidebar collapse, tooltips, alert auto-dismiss) -->
<script src="<?= asset('js/admin.js'); ?>"></script>
</body>
</html>
