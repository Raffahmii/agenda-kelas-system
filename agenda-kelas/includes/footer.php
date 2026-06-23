<?php
/**
 * Footer Component - Menutup tag HTML
 */
?>
    </div> <!-- /.content-container -->
</div> <!-- /.main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

<script>
    // Auto hide alerts after 3 seconds
    document.querySelectorAll('.alert-auto-hide').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
</script>
</body>
</html>