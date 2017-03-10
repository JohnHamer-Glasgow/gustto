$(document).ready(function () {
  function checkNoSelections() {
    var numberSelections1 = $('input:checkbox:checked[value="1"]').not('.no-notifications').length;
    var numberSelections2 = $('input:checkbox:checked[value="2"]').not('.no-notifications').length;

    if (numberSelections1 == 0) $('.no-notifications[value="1"]').prop('checked', true);
    else $('.no-notifications[value="1"]').prop('checked', false);

    if (numberSelections2 == 0) $('.no-notifications[value="2"]').prop('checked', true);
    else $('.no-notifications[value="2"]').prop('checked', false);
    
  }

  function checkAllDigest() {
    var numberSelections = $('input:checkbox.digest:checked').not('.digest-all').length;
    if (numberSelections == 4) $('.digest-all').prop('checked', true);
    else $('.digest-all').prop('checked', false);
  }

  // settings form - make checkboxes act as radios (mutualy exclusive) + functionality for particular cases
  $('input:checkbox').change(function () {
    var chk = $(this);
    var cat = chk.attr('name');
    $('input:checkbox[name="'+ cat +'"]').not(chk).prop('checked', false);
    chk.prop('checked', $(this).prop('checked'));

    // a digest option gets diselected (but not by necessarily clicking on it)
    if (!chk.hasClass('digest-all')) $('.digest-all').prop('checked', false);
    // click on Follow Activity (cat 3) first checkbox
    if (cat == 'options3') $('input:checkbox.cat-4-2').prop('checked', false);
    // click on Follow Activity (cat 4) last checkbox
    if (cat == 'options4' && chk.val() == 2) $('input:checkbox.cat-3').prop('checked', false);
    
    if (cat == 'options6') {
      // click on digest-all
      // if (chk.val() == 1) {
      //   $('input:checkbox').not('input:checkbox.digest').prop('checked', false);
      //   if (chk.prop('checked')) $('.digest').prop('checked', true);
      //   else $('.digest').prop('checked', false); 
      // }
      // click on no-notifications
      if (chk.val() == 1) {
        $('input:checkbox[value="1"]').not('input:checkbox.no-notifications[value="1"]').prop('checked', false);
        $('input:checkbox.no-notifications[value="1"]').prop('checked', true);
      }
      else {
        $('input:checkbox[value="2"]').not('input:checkbox.no-notifications[value="2"]').prop('checked', false);
        $('input:checkbox.no-notifications[value="2"]').prop('checked', true);
      }
    }

    // checkAllDigest();
    checkNoSelections();

    
  });
});
