<?php // footer.php 
?>
</div><!-- /container-fluid (dibuka di menu.php) -->
</div><!-- /content -->
</div><!-- /app-wrap -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js untuk donut chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    // Toggle sidebar di mobile
    const sidebar = document.getElementById('sidebar');
    const btn = document.getElementById('toggleSidebar');
    if (btn) btn.addEventListener('click', () => sidebar.classList.toggle('show'));
</script>