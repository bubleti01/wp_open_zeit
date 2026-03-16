(function(){
  function refreshRow(row){
    var closed = row.querySelector('input[type="checkbox"]');
    var block = row.querySelector('.igw-openzeit-intervals');
    if(!closed || !block) return;
    block.style.display = closed.checked ? 'none' : '';
  }
  document.addEventListener('change', function(e){
    if(e.target.matches('.igw-openzeit-day-row input[type="checkbox"]')){
      refreshRow(e.target.closest('.igw-openzeit-day-row'));
    }
  });
  document.addEventListener('click', function(e){
    if(e.target.matches('.igw-add-interval')){
      var row = e.target.closest('.igw-openzeit-day-row');
      var box = row.querySelector('.igw-openzeit-intervals');
      var day = row.getAttribute('data-day');
      var index = box.querySelectorAll('.igw-openzeit-interval-row').length;
      var html = '<div class="igw-openzeit-interval-row">'
        + '<input type="time" name="igw_wp_open_zeit_data[weekly]['+day+'][intervals]['+index+'][start]" value="" />'
        + '<span>–</span>'
        + '<input type="time" name="igw_wp_open_zeit_data[weekly]['+day+'][intervals]['+index+'][end]" value="" />'
        + '<button type="button" class="button-link-delete igw-remove-interval">×</button>'
        + '</div>';
      box.insertAdjacentHTML('beforeend', html);
    }
    if(e.target.matches('.igw-remove-interval')){
      var line = e.target.closest('.igw-openzeit-interval-row');
      var box = line.parentNode;
      line.remove();
    }
  });
  document.querySelectorAll('.igw-openzeit-day-row').forEach(refreshRow);
})();
