$(function() {

    if($(".cep").length) {
        $(".cep").mask('00000-000', {reverse: true});
      }

    var listTr = [];

    var modalConfirm = function(callback){
        $(".btnDados").on("click", function(){
          let items = listTr.length > 1 ? ' items' : ' item';
          $("#modal-confirm .modal-body strong").text(listTr.length+items)
          $("#modal-confirm").modal('show');
        });
      
        $("#modal-btn-si").on("click", function(){
          callback(true);
          $("#modal-confirm").modal('hide');
        });
        
        $("#modal-btn-no").on("click", function(){
          callback(false);
          $("#modal-confirm").modal('hide');
        });
    };

    modalConfirm(function(confirm){
        if(confirm){
            $(".loading").fadeIn();
            $.ajax({
                type: "POST",
                url: "/send/products/ajax",
                data: {data: listTr},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response){
                }
            }).done((data) => {
                $(".loading").fadeOut();
                console.log(data)
                if(data) {
                    $(".table-success").addClass("table-primary");
                    $(".table-primary").removeClass("table-success")
                    listTr = [];
                    $(".countSelect").text(listTr.length)
                    $(".btnDados").fadeOut();

                }
            });

        }
    });
    // $(document).off("click", ".btnDados");
    // $(document).on("click", ".btnDados", function() {
    //     let _this = $(this);
    //     $("#modal-confirm").modal('show');
    // })

    $(document).off("click", ".tr-click");
    $(document).on("click", ".tr-click", function() {
        let _this = $(this);
        let id = _this.data('id').replace("tr-", "") ;
        let name = removeLeadingSpace(_this.find(".name").text());
        let price =  _this.find(".price").text();

        if(_this.find(".price").hasClass("priceFirst")) {
            price = removeLeadingSpace(price.split("R$")[1])
        }
        let array = {
            "id": id,
            "name": name,
            "price": price
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
        console.log(listTr)
    });

    function removeLeadingSpace(str) {
        if (str.charAt(0) === ' ') {
          return str.substring(1);
        }
        return str;
    }

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