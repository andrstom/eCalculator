/* Tabulka - standardni */

$(document).ready(function() {
    $('#table-standard').dataTable( {
        "scrollY": 600,
        "scrollX": true,
        "paging": false,
        "ordering": true,
        "info": false,
        "language": {
            "lengthMenu": "Zobrazit _MENU_ záznamů",
            "zeroRecords": "Vašemu požadavku neodpovídá žádný záznam.",
            "info": "Zobrazit stranku _PAGE_ z _PAGES_",
            "infoEmpty": "Žádný záznam",
            "search": "Vyhledat",
            "infoFiltered": "(filtered from _MAX_ total records)"
        }
    });
});

$(document).ready(function() {
    $('#table2-standard').dataTable( {
        "scrollY": 600,
        "scrollX": true,
        "paging": false,
        "ordering": true,
        "info": false,
        "language": {
            "lengthMenu": "Zobrazit _MENU_ záznamů",
            "zeroRecords": "Vašemu požadavku neodpovídá žádný záznam.",
            "info": "Zobrazit stranku _PAGE_ z _PAGES_",
            "infoEmpty": "Žádný záznam",
            "search": "Vyhledat",
            "infoFiltered": "(filtered from _MAX_ total records)"
        }
    });
});
