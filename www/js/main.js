$(function () {
    $.nette.init();
});

// toggle
$(document).ready(function () {
    $("#interpretation").click(function () {
        $("#interpretation-inputs").slideToggle("slow");
    });
});
