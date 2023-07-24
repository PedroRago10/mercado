
$(function() {

  // Choose filters
  jQuery(document).off('change', '.tipoBusca');
  jQuery(document).on('change', '.tipoBusca', function() {
    let _this = $(this);

    if(_this.val() == '1') {
      $("#buscaLinks").addClass('d-none')
      $("#buscaCategorias").removeClass('d-none')

      $(".linksInputs").remove();
      addNewItemSet();
    }else {
      $("#buscaCategorias").addClass('d-none')
      $("#buscaLinks").removeClass('d-none')

      $("#buscaCategorias input").prop("checked", false);
    }
  });


  //Links inputs
  jQuery(document).off('click', '.addNewLink');
  jQuery(document).on('click', '.addNewLink', function() {
    jQuery(this).remove();
    addNewItemSet();
  });


  jQuery(document).on('submit', '#formularioBuscaRapida', function(event) {
    var progressBar = $('#barra-progresso');
    progressBar.css("width", "100%");
  });
});

function addNewItemSet() {
let id = Math.random();
let box = `<div class="input-group mt-3 linksInputs">
<span class="input-group-text" id="link${id}">https://www.superpaguemenos.com.br/</span>
<input type="text" class="form-control" name="links[]" placeholder="URL" id="basic-url${id}" aria-describedby="link${id}" />
<button type="button" class="btn btn-success addNewLink"><i class="bx bx-plus"></i></button>
</div>`;

$(".container-links").append(box)
}