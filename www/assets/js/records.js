document.addEventListener('DOMContentLoaded', function () {
  var select = document.getElementById('record-type-select');
  if (!select) return;

  function sync() {
    var type = String(select.value || '').toUpperCase();
    document.querySelectorAll('.record-type-fields').forEach(function (block) {
      var active = String(block.dataset.type || '').toUpperCase() === type;
      block.style.display = active ? '' : 'none';
      block.querySelectorAll('input, textarea, select').forEach(function (el) {
        el.disabled = !active;
      });
    });
  }

  select.addEventListener('change', sync);
  sync();
});
