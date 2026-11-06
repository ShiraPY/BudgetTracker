</section></main>
<script>
document.querySelectorAll('[data-confirm]').forEach(function (b) {
  b.addEventListener('click', function (ev) { if (!confirm(b.dataset.confirm)) ev.preventDefault(); });
});
</script>
</body></html>
