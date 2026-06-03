</main><!-- /crcc-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const main = document.getElementById('mainContent');
  const bd = document.getElementById('backdrop');
  const isMobile = window.innerWidth <= 768;
  if (isMobile) {
    sb.classList.toggle('mobile-open');
    bd.classList.toggle('show');
  } else {
    sb.classList.toggle('collapsed');
    main.classList.toggle('expanded');
  }
}
</script>
</body>
</html>
