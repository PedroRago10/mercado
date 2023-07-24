$(function() {
    new DataTable('.table-response', {
        pagingType: 'full_numbers',
        language: {
            'paginate': {
              'previous': '<i class="tf-icon bx bx-chevron-left"></i>',
              'next': '<i class="tf-icon bx bx-chevron-right"></i>',
              'first': '<i class="tf-icon bx bx-chevrons-left"></i>',
              'last': '<i class="tf-icon bx bx-chevrons-right"></i>',
              'search': "Pesquisar"
            },
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
        },
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
})