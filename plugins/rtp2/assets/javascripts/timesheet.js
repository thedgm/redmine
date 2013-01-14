function targetField(label_element) {
  return  $(label_element.attributes.for.value);
}

function selectAllOptions(element) {
  for (var i = 0; i < element.options.length; i++) {
    element.options[i].selected = true;
  }
}

$(document).ready(function() {
    $('#select_timesheet_period_type_2').click(function() {
        $('#timesheet_period_type_2').attr('checked','checked');
    });

    $('#select_timesheet_period_type_1').focus(function() {
        $('#timesheet_period_type_1').attr('checked','checked');
    });
});

/*
Event.observe(window, 'load',
  function() { 
    $$('label.select_all').each(function(element) {
      Event.observe(element, 'click', function (e) { selectAllOptions(targetField(this)); });
    });
  }
);
*/
