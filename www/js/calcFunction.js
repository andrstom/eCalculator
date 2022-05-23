
// Show/Hide
$(document).ready(function () {
    $(".toggle-assay").click(function () {
        $(".help-assay").slideToggle("slow");
    });
    $(".toggle-source").click(function () {
        $(".help-source").slideToggle("slow");
    });
    $(".toggle-param").click(function () {
        $(".help-param").slideToggle("slow");
    });
});

function chooseParameter(event) {

    var metodaId = document.getElementById("MetodaId").value;

    if (metodaId) {
        $.ajax({
            type: 'POST',
            url: './ajax.php',
            data: {
                MetodaId: metodaId
            },
            success: function (response) {
                $('#assay-parameter').html(response);
            }
        });
    }

    /**
     * Array.prototype.[method name] allows you to define/overwrite an objects method
     * needle is the item you are searching for
     * this is a special variable that refers to "this" instance of an Array.
     * returns true if needle is in the array, and false otherwise
     */
    Array.prototype.contains = function (needle) {
        for (i in this) {
            if (this[i] == needle)
                return true;
        }
        return false;
    }

}

function chooseSource(event) {

    var source = document.getElementById("source").value;

    if (readervystup) {
        $.ajax({
            type: 'POST',
            url: './ajax.php',
            data: {
                source: source
            },
            success: function (response) {
                $('#assay-source').html(response);
            }
        });
    }
}