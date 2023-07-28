@extends('layouts/contentNavbarLayout')

@section('title', 'Resultado da Busca')
@section('page-script')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.0/jquery.mask.js"></script>

<script src="{{asset('assets/js/buscaResult.js')}}"></script>

@endsection

<?php
$siteTitle = '';
if($type == 0) {
    $siteTitle = 'Super Pague Menos';
}else if($type == 1){
  $siteTitle = 'Tenda Atacado';
}else {
  $siteTitle = 'Atacadão';
}
?>

<div class="loading">Loading&#8230;</div>
@section('content')

<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-confirm">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <meta name="csrf-token" content="{{ csrf_token() }}" />
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel">Confirmar</h4>
        <!-- <button type="button" style='background: transparent;border: none;font-size: 2em;color: #5663b8;' class="bx:x-circle" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> -->
      </div>
      <div class='modal-body'>
        <p>Realmente deseja atualizar <strong></strong> no banco de dados?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" id="modal-btn-no">Cancelar</button>
        <button type="button" class="btn btn-primary" id="modal-btn-si">Atualizar</button>
      </div>
    </div>
  </div>
</div>


<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light"><a href="/busca-rapida" class="text-muted fw-light">Busca Rápida</a> / </span> <?=$siteTitle?>
</h4>

<?php


foreach($resultado as $result) { 
  $titleCategory = '';

  if($type == 0) {
    if($result[0] == 'https://www.superpaguemenos.com.br') {
      $titleCategory = 'Página Inicial';
    }else{
        $titleCategory = str_replace("-", " ", $result[0]);
        $titleCategory = ucfirst($result[0]);
    }
  }else if($type == 1) {
    $titleCategory = ucwords($result['category']);
  }else{
    $categorias = [
      65  => "bebidas",
      147 => "mercearia",
      54  => "produtos de limpeza",
      255 => "higiene pessoal e perfumaria",
      99  => "frios e latícinios",
      378 => "carnes, aves e peixes",
      188 => "embelagens e descatáveis",
      52  => "congelados",
      58  => "papelaria e escritório",
      325 => "cestas alimenticios"
    
    ];
    $titleCategory = ucwords($categorias[$result['category']]);
  }

?>

@if ($type == 0) 
<div class="card" style='margin-bottom: 3em'>
<h5 class="card-header text-primary" style='display: flex;justify-content: space-between;'>
    <span>
      <strong><?=$titleCategory?></strong><span class="badge bg-label-primary me-1 countSelect" style="margin-left: 0.5em;">0</span>
    </span>
    <span>
      <button type="button" class="btn btn-outline-primary btnDados" style='display: none'>Atualizar Banco de Dados</button>
    </span>
  </h5>
  <div class="table-responsive text-nowrap">
    <table class="table table-response" >
      <thead>
        <tr>
          <?php if($options['buscarNomeProduto']) { ?><th><i class="fab fa-angular fa-lg text-danger me-3"></i>Nome</th> <?php } ?>
          <?php if($options['buscarPrecoProduto']) { ?><th>Preço</th> <?php } ?>
          <?php if($options['precoAnterior']) { ?><th>Preço Anterior</th> <?php } ?>
          <?php if($options['buscarDescontoProduto']) { ?><th>Desconto</th> <?php } ?>
          <?php if($options['buscarLinkProduto']) { ?><th style='width: 30%'>URL</th> <?php } ?>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach($result[1] as $key => $resultValue)
        <?php 
        ?>
        <tr data-id='tr-<?=$key?>' class='tr-click <?php echo ($resultValue['exists']) ? $resultValue['exists'] : ''?>'> 
        <?php if($options['buscarNomeProduto']) { ?> <td class='name'><i class="fab fa-angular fa-lg text-danger me-3"></i> <strong><?=$resultValue['nome']?></strong></td><?php } ?>
        <?php if($options['buscarPrecoProduto']) { ?> <td class='price'><?=$resultValue['preco']?></td><?php } ?>
        <?php if($options['precoAnterior']) { ?> <td><?=$resultValue['precoAnterior']?></td><?php } ?>
        <?php if($options['buscarDescontoProduto'] != '') { ?> <td><?=$resultValue['desconto']?></td><?php } ?>
        <?php if($options['buscarLinkProduto']) { ?> <td ><?=$resultValue['link']?></td> <?php } ?>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@if ($type == 2)
<div class="card" style='margin-bottom: 3em'>
<h5 class="card-header text-primary" style='display: flex;justify-content: space-between;'>
    <span>
      <strong><?=$titleCategory?></strong><span class="badge bg-label-primary me-1 countSelect" style="margin-left: 0.5em;">0</span>
    </span>
    <span>
      <button type="button" class="btn btn-outline-primary btnDados" style='display: none'>Atualizar Banco de Dados</button>
    </span>
  </h5>
  <div class="table-responsive text-nowrap">
    <table class="table table-response" >
      <thead>
        <tr>
          <th style='width: 30px; padding-right: 0em;'>#</th>
          <?php if($options['buscarNomeProduto']) { ?><th style='width: 27%'>Nome</th> <?php } ?>
          <?php if($options['buscarCategoriaProduto']) {?><th>Categoria</th><?php } ?>
          <?php if($options['buscarMarcaProduto']) {?><th>Marca</th><?php } ?>
          <?php if($options['buscarLinkProduto']) { ?><th style='width: 35%'>URL</th> <?php } ?>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach($result['results'] as $key => $resultValue)
        <?php 
        ?>
        <tr data-target='<?=$key?>' data-id='tr-<?=$key?>' class='tr-click <?php echo ($resultValue['exists']) ? $resultValue['exists'] : ''?>'>
        <td class='dt-control' data-id="<?=$key?>">
          <i class='bx bx-chevron-right-square'></i>
          <div class='box-detail' style='display: none'>
            <table class='table'>
              <tr class='table-primary'>
                <th>Fornecedor</th>
                <th>Preço</th>
                <th>Entrega</th>
                <th>Estoque</th>
              </tr>

              @foreach($resultValue['providers'] as $provider)
              <tr class='table-primary'>  
                <td><?=$provider['name']?></td>
                <td class='price priceFirst'><?php echo 'R$ '. number_format($provider['prices'][0]['price'], 2, ',', '.')?></td>
                <td><?=$provider['delivery_time_in_days']?> <?php echo ($provider['delivery_time_in_days']) > 1 ? ' dias' : ' dias'?></td>
                <td><?=$provider['prices'][0]['stock_count']?></td>
              </tr>
              @endforeach
            </table>
          </div>
        </td>
        <?php if($options['buscarNomeProduto']) { ?> <td><i class="fab fa-angular fa-lg text-danger me-3"></i> <strong class='name'><?=$resultValue['name']?></strong></td><?php } ?>
        <?php if($options['buscarCategoriaProduto']) { ?><td></i><?=$resultValue['category']?></td><?php } ?>
        <?php if($options['buscarMarcaProduto']) { ?><td><?=$resultValue['brand']?></td><?php } ?>
        <?php if($options['buscarLinkProduto']) { ?> <td style='width: 5em'><?=$resultValue['slug']?></td> <?php } ?>
        </tr>
        
        @endforeach
      </tbody>
      <tfoot>
        <tr class='table-active'>
          <th></th>
          <th></th>
          <th></th>
          <th>CEP</th>
          <th class='text-end'>Total de páginas encontradas</th>
        </tr>
        <tr class='table-active'>
          <td></td>
          <td></td>
          <td></td>
          <td><strong class='cep'><?=$result['cep']?></strong></td>
          <td class='text-end'><strong><?=$result['totalPaginas']?></strong></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@endif


@if ($type == 1)
<div class="card" style='margin-bottom: 3em'>
  <h5 class="card-header text-primary" style='display: flex;justify-content: space-between;'>
    <span>
      <strong><?=$titleCategory?></strong><span class="badge bg-label-primary me-1 countSelect" style="margin-left: 0.5em;">0</span>
    </span>
    <span>
      <button type="button" class="btn btn-outline-primary btnDados" style='display: none'>Atualizar Banco de Dados</button>
    </span>
  </h5>
  <div class="table-responsive text-nowrap">
    <table class="table table-response" >
      <thead>
        <tr>
          <?php if($options['buscarNomeProduto']) { ?><th><i class="fab fa-angular fa-lg text-danger me-3"></i>Nome</th> <?php } ?>
          <?php if($options['buscarPrecoProduto']) {?><th>Preço</th><?php } ?>
          <?php if($options['buscarMarcaProduto']) {?><th>Marca</th><?php } ?>
          <?php if($options['buscarEstoqueProduto']) { ?><th>Estoque</th><?php } ?>
          <?php if($options['buscarLinkProduto']) { ?><th style='width: 5em'>URL</th> <?php } ?>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach($result['results'] as $key => $resultValue)
        <?php 
        ?>
        <tr data-id='tr-<?=$key?>' class='tr-click <?php echo ($resultValue['exists']) ? $resultValue['exists'] : ''?>'>
        <?php if($options['buscarNomeProduto']) { ?> <td class='name'><i class="fab fa-angular fa-lg text-danger me-3"></i><strong><?=$resultValue['name']?></strong></td><?php } ?>
        <?php if($options['buscarPrecoProduto']) { ?><td class='price'></i><?php echo 'R$ '. number_format($resultValue['price'], 2, ',', '.')?></td><?php } ?>
        <?php if($options['buscarMarcaProduto']) { ?><td class='brand'><?php echo str_replace(" | null", "", $resultValue['brand'])?></td><?php } ?>
        <?php if($options['buscarEstoqueProduto']) { ?><td class='text-end estoque'><?=$resultValue['totalStock']?></td><?php } ?>
        <?php if($options['buscarLinkProduto']) { ?> <td class='url' style='width: 5em'><?=$resultValue['url']?></td> <?php } ?>
        </tr>
        
        @endforeach
      </tbody>
      <tfoot>
        <tr class='table-active'>
          <th>Total de Produtos na Categoria</th>
          <th></th>
          <th></th>
          <th></th>
          <th class='text-end'>Total de páginas encontradas</th>
        </tr>
        <tr class='table-active'>
          <td><strong><?=$result['totalProducts']?></strong></td>
          <td></td>
          <td></td>
          <td></td>
          <td class='text-end'><strong><?=$result['pageCount']?></strong></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@endif


<?php } ?>

@endsection



