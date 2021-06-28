function getSelectedValues() {
    var selectedVal = $("#multiselect").val();
    for (var i = 0; i < selectedVal.length; i++) {
        if (window.CP.shouldStopExecution(0)) break;

        function innerFunc(i) {
            setTimeout(function() {
                location.href = selectedVal[i];
            }, i * 2000);
        }
        innerFunc(i);
    }
    window.CP.exitedLoop(0);
}

$(document).ready(function() {

    // Activate tooltip
    $('[data-toggle="tooltip"]').tooltip();

    // Select/Deselect checkboxes
    var checkbox = $('table tbody input[type="checkbox"]');

    $("#selectAll").click(function() {
        if (this.checked) {
            checkbox.each(function() {
                this.checked = true;
            });
        } else {
            checkbox.each(function() {
                this.checked = false;
            });
        }
    });

    checkbox.click(function() {
        if (!this.checked) {
            $("#selectAll").prop("checked", false);
        }
    });

    $('.daterange').daterangepicker();

    /* dropdown checkbox */
    $('#multiselect').multiselect({
        includeSelectAllOption: true,
        nonSelectedText: 'All member'
    });

    // multi select
    $(".js-select2").select2({
        closeOnSelect: false,
        placeholder: "Choosen card",
        allowHtml: true,
        allowClear: true,
        tags: true
    });
    $(".placeholder-member").select2({
        closeOnSelect: false,
        placeholder: "Choosen member",
        allowHtml: true,
        allowClear: true,
        tags: true
    });

    $('.icons_select2').select2({
        width: "100%",
        templateSelection: iformat,
        templateResult: iformat,
        allowHtml: true,
        placeholder: "Placeholder",
        dropdownParent: $('.select-icon'),
        allowClear: true,
        multiple: false
    });

    function iformat(icon, badge) {
        var originalOption = icon.element;
        var originalOptionBadge = $(originalOption).data('badge');

        return $('<span><i class="fa ' + $(originalOption).data('icon') + '"></i> ' + icon.text + '<span class="badge">' + originalOptionBadge + '</span></span>');
    }
});

$(document).ready(function() {
    $('#reservation').daterangepicker(null, function(start, end, label) {
        console.log(start.toISOString(), end.toISOString(), label);
    });
});

$(function() {
    $('input[name="reservation"]').daterangepicker({}, function(start, end, label) {
        document.getElementById("startDate").value = start.format('YYYY-MM-DD');
        document.getElementById("endDate").value = end.format('YYYY-MM-DD');
    });
});
