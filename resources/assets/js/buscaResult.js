$(function() {
    if($(".cep").length) {
        $(".cep").mask('00000-000', {reverse: true});
      }

    var listTr = [];

    $(document).off("click", ".tr-click");
    $(document).on("click", ".tr-click", function() {
        let _this = $(this);
        let id = _this.data('id').replace("tr-", "") ;
        let array = {
            "id": id,
            "name": _this.find(".name").text(),
            "price": _this.find(".price").text(),
            "brand": _this.find(".brand").text(),
            "estoque": _this.find(".estoque").text(),
            "url": _this.find(".url").text()
        };

        // Encontre o índice do item no array (se existir)
        let index = listTr.findIndex(item => item.id === id);
            
        if (index !== -1) {
            listTr.splice(index, 1); // Remover o item do array usando splice()
            _this.removeClass('table-success');
        } else {
            array.id = id; // Adicionar a chave "id" ao objeto "array"
            listTr.push(array); // Adicionar o item ao array usando push()
            _this.addClass('table-success');
        }
        
        $(".countSelect").text(listTr.length)
        if(listTr.length > 0) {
            $(".btnDados").fadeIn();
        }
    });

    var table = new DataTable('.table-response', {
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

    table.on('click', 'td.dt-control', function (e) {
        $(this).find("i").toggleClass("dt-control-active");
        let tr = e.target.closest('tr');
        let row = table.row(tr);
     
        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
        }
        else {
            // Open this row
            row.child(format($(this))).show();
        }
        $(".dt-hasChild").next().children('td').addClass("no-padding")
    });
    
})

function format(obj) {  
    let box = obj.find('.box-detail').html();
    return box;
}