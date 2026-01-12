<?php
/**
 * ACTIO - Layout Footer
 * 
 * Closes the HTML structure and includes JavaScript.
 * Based on dashio-template/src/index.html
 * 
 * @package Actio\Views\Layout
 */
?>
</div><!-- /.wrapper -->

<script src="<?= asset('js/dashio.js') ?>"></script>

<?php if (hasFlash('success')): ?>
    <script>
        // Show success toast
        document.addEventListener('DOMContentLoaded', function () {
            const message = <?= json_encode(flash('success')) ?>;
            if (typeof showToast === 'function') {
                showToast('success', message);
            } else {
                alert(message);
            }
        });
    </script>
<?php endif; ?>

<?php if (hasFlash('error')): ?>
    <script>
        // Show error toast
        document.addEventListener('DOMContentLoaded', function () {
            const message = <?= json_encode(flash('error')) ?>;
            if (typeof showToast === 'function') {
                showToast('error', message);
            } else {
                alert(message);
            }
        });
    </script>
<?php endif; ?>
</body>

</html>