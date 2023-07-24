@extends('layouts/contentNavbarLayout')

@section('title', 'Busca Rápida')

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.0/jquery.mask.js"></script>
<script src="{{asset('assets/js/buscaRapida.js')}}"></script>

@endsection

<?php 
$categorias1 = [];
switch ($type) {
    case 1:
        $current_site = 'https://api.tendaatacado.com.br/api/public/store/';
        $siteExport = $current_site;
        $categorias1 = [
            3412 => ["slug" => "produtos-select", "title" => "Marca Própria"],
            12   => ["slug" => "mercearia", "title" => "Mercearia"],
            4    => ["slug" => "bebias", "title" => "Bebidas"],
            8    => ["slug" => "frios-e-laticinios","title" => "Frios e Laticínios"], 
            7    => ["slug" => "congelados","title" => "Congelados"],
            6    => ["slug" => "carnes-aves-e-peixes","title" => "Carnes, Aves e Peixes"],
            11   => ["slug" => "limpeza","title" => "Limpeza"],
            9    => ["slug" => "higiene-e-perfumaria","title" => "Higiene e Perfumaria"],
            3    => ["slug" => "bebe","title" => "Bebê"],
            5    => ["slug" => "bomboniere","title" => "Bomboniere"],
            13   => ["slug" => "paes-e-bolos","title" => "Paes e bolos"],
            10   => ["slug" => "hortifruti","title" => "Hortifruti"],
            2    => ["slug" => "bazar","title" => "Bazar"],
            14   => ["slug" => "pet-shop","title" => "PetShop"]
            ];
        break;
    case 2:
        $current_site = 'https://algolia.cotabest.com.br/';
        $siteExport = $current_site;

        break;    
    default:
        $current_site = 'https://www.superpaguemenos.com.br/';
        $siteExport = $current_site;
        break;
}


?>

@section('content')
<div id="barra-progresso"></div>


<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light"> Dashboard /</span> Busca Rápida
</h4>

@if($errors->any())
<div class="alert alert-danger" role="alert">
    {{$errors->first()}}
</div>
@endif

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-pills flex-column flex-md-row mb-3">
            <li class="nav-item"><a class="nav-link <?php echo ($type == 0) ? 'active' : ''?>" href="/"><i class="bx bx-shopping-bag me-1"></i> Super Pague Menos</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($type == 1) ? 'active' : ''?>" href="/?&type=1"><i class="bx bx-shopping-bag me-1"></i> Tenda Atacada</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($type == 2) ? 'active' : ''?>" href="/?&type=2"><i class="bx bx-shopping-bag me-1"></i> Atacadão </a></li>
        </ul>
        <div class="card mb-4">
            <h5 class="card-header">Detalhes da Busca</h5>
            
            <hr class="my-0">
            <div class="card-body">
                <form id="formularioBuscaRapida" method="POST" action="/get/busca-rapida">
                    @csrf
                    <input type="hidden" id='site' name="site" value="<?=$siteExport?>">
                    <input type="hidden" id='current_site' name="current_site" value="<?=$current_site?>">
                    <input type="hidden" id='type' name="type" value="<?=$type?>">
                    
                    <div class='row mb-3'>
                        @if($type == 2) 
                        <div class="col-md-1">
                            <div>
                                <small class="text-light fw-semibold">Modalidade:</small>
                            </div>
                            <div class="btn-group" role="group" aria-label="Basic radio toggle button group" style='margin-top: 1em'>
                              <input type="radio" class="btn-check" value='PJ' name="pjPf" id="btnradio1" checked autocomplete="off" />
                              <label class="btn btn-outline-primary"  for="btnradio1">PJ</label>
                              <input type="radio" class="btn-check" value='PF' name="pjPf" id="btnradio2" autocomplete="off" />
                              <label class="btn btn-outline-primary" for="btnradio2">PF</label>
                            </div>
                        </div>
                        <div class='col-md-2' style='margin-left:2em;'>
                            <small class="text-light fw-semibold">CEP:</small>
                            <input type="text" class="form-control" style='margin-top: 1em' id="cep" required name='cep' placeholder="Digite o CEP desejado" aria-describedby="cep">
                        </div>
                        @endif

                        @if($type == 2 || $type == 1) 
                        <div class='col-md-2'>
                            <small class="text-light fw-semibold">Quantidade de Páginas:</small>
                            <input type="number" class="form-control" style='margin-top: 1em' id="paginas" required name='paginas' aria-describedby="paginas">
                        </div>
                        @endif
                        @if($type == 1) 
                        <div class='col-md-2'>
                            <small class="text-light fw-semibold">Ordenar Por:</small>
                            <select id="select-sort-container" name='orderBy' class="form-control" style="margin-top: 1em">
                                <option id="select-relevance" value="relevance">Relevância</option>
                                <option id="select-ascPrice" value="ascPrice">Menor Preço</option>
                                <option id="select-descPrice" value="descPrice">Maior Preço</option>
                                <option id="select-descDate" value="descDate">Lançamentos</option>
                                <option id="select-descDiscount" value="descDiscount">Maiores Descontos</option>
                                <option id="select-ascSold" value="ascSold">Mais Vendidos</option>
                                <option id="select-descSold" value="descSold">Menos Vendidos</option>
                            </select>
                        </div>
                        @endif

                        @if($type == 0) 
                        <div class="col-md-10">
                            <small class="text-light fw-semibold">Buscar por:</small>
                            <div class="d-flex" style='align-items: center;margin-top: 1em;'>
                                <div class="form-check" style="margin-right: 1em;">
                                    <input name="tipoBusca" class="form-check-input tipoBusca" type="radio" value="1" id="tipoBusca1" checked/>
                                    <label class="form-check-label" for="tipoBusca1">
                                      Categorias
                                    </label>
                                  </div>
                                  <div class="form-check">
                                    <input name="tipoBusca" class="form-check-input tipoBusca" type="radio" value="2" id="tipoBusca2"  />
                                    <label class="form-check-label" for="tipoBusca2">
                                      Links
                                    </label>
                                  </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($type == 0)
                    <div class="buscaCategorias row mt-3" >
                        <div>
                            <div class="row gy-3">
                                <div class="col-md">
                                    <small class="text-light fw-semibold">Categorias:</small>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="congelados" id="congelados" />
                                        <label class="form-check-label" for="congelados">
                                            Congelados
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="cafe-da-manha" id="cafeManha" />
                                        <label class="form-check-label" for="cafeManha">
                                            Café da manhã
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="higiene-e-beleza" id="higieneBeleza" />
                                        <label class="form-check-label" for="higieneBeleza">
                                            Higiene e beleza
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="hortifruti" id="hortifruti" />
                                        <label class="form-check-label" for="hortifruti">
                                            Hortifrúti
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="acougue" id="acougue"  />
                                        <label class="form-check-label" for="acougue">
                                            Açougue
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="" id="friosLaticinios"  />
                                        <label class="form-check-label" for="friosLaticinios">
                                            Frios e laticínios
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="frios-e-laticinios" id="limpeza"  />
                                        <label class="form-check-label" for="limpeza">
                                            Limpeza
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="bazar" id="bazar"  />
                                        <label class="form-check-label" for="bazar">
                                            Bazar
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="festivos" id="festivos"  />
                                        <label class="form-check-label" for="festivos">
                                            Festivos
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="mercearia" id="mercearia"  />
                                        <label class="form-check-label" for="mercearia">
                                            Mercearia
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="bebidas" id="bebidas"  />
                                        <label class="form-check-label" for="bebidas">
                                            Bedidas
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="petshop" id="petshop"  />
                                        <label class="form-check-label" for="petshop">
                                            PetShop
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="mamae-e-bebe" id="mamaeBebe"  />
                                        <label class="form-check-label" for="mamaeBebe">
                                            Mamãe e Bebê
                                        </label>
                                    </div>
                                </div>    
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($type == 1)
                    <div class="buscaCategorias row mt-3">
                        <div>
                            <div class="row gy-3">
                                <div class="col-md">
                                    <small class="text-light fw-semibold">Categorias:</small>
                                    <?php foreach($categorias1 as $id => $categoria) { ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name='categorias[<?=$id?>]["<?php echo json_encode($categoria); ?>"]' value="<?=$categoria['slug']?>" id="<?=$categoria['slug']?>" />
                                        <label class="form-check-label" for="<?=$categoria['slug']?>">
                                            <?=$categoria['title']?>
                                        </label>
                                    </div>
                                   <?php } ?>
                                </div>    
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($type == 2)
                    <div class="buscaCategorias row mt-3">
                        <div>
                            <div class="row gy-3">
                                <div class="col-md">
                                    <small class="text-light fw-semibold">Categorias:</small>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="65" id="bebidas" />
                                        <label class="form-check-label" for="bebidas">
                                            Bebidas
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="147" id="mercearia" />
                                        <label class="form-check-label" for="mercearia">
                                            Mercearia
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="54" id="produtos-de-limpeza" />
                                        <label class="form-check-label" for="produtos-de-limpeza">
                                            Produtos de Limpeza
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="255" id="higiene-pessoal-e-perfumaria" />
                                        <label class="form-check-label" for="higiene-pessoal-e-perfumaria">
                                            Higiena pessoal e perfumaria
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="99" id="frios-e-laticinios" />
                                        <label class="form-check-label" for="frios-e-laticinios">
                                            Frios e laticínios
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="378" id="carnes-aves-e-peixes" />
                                        <label class="form-check-label" for="carnes-aves-e-peixes">
                                            Carnes, aves e peixes
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="188" id="embalagens-e-descartaveis" />
                                        <label class="form-check-label" for="embalagens-e-descartaveis">
                                            Embalagens e descartaveis
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="52" id="congelados" />
                                        <label class="form-check-label" for="congelados">
                                            Congelados
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="58" id="papelaria-e-escritorio" />
                                        <label class="form-check-label" for="papelaria-e-escritorio">
                                            Papelaria e escritório
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="325" id="cestas-alimenticias" />
                                        <label class="form-check-label" for="cestas-alimenticias">
                                            Cestas alimentícias
                                        </label>
                                    </div>
                                </div>    
                            </div>
                        </div>
                     
                    </div>
                    @endif

                    <div id="buscaLinks" class="row mt-3 d-none">
                        <div>
                            <div class="row gy-3">
                                <div class="col-md container-links">
                                    <small class="text-light fw-semibold">Links:</small>
                                        <div class="input-group mt-3 linksInputs">
                                            <span class="input-group-text" id="link1"><?=$current_site?></span>
                                            <input type="text" class="form-control" name="links[]" placeholder="URL" id="basic-url1" aria-describedby="link1" />
                                            <button type="button" class="btn btn-success addNewLink"><i class="bx bx-plus"></i></button>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row mt-3'>
                        <div class="col-md">
                            <small class="text-light fw-semibold">Opções:</small>
                            @if($type == 0)
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarPaginaInicial" id="buscarPaginaInicial" checked>
                                <label class="form-check-label" for="buscarPaginaInicial">Buscar na Página Inicial</label>
                            </div>
                            @endif
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarNomeProduto" id="buscarNomeProduto" checked>
                                <label class="form-check-label" for="buscarNomeProduto">Nome do Produto</label>
                            </div>
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarPrecoProduto" id="buscarPrecoProduto" checked>
                                <label class="form-check-label" for="buscarPrecoProduto">Preço do Produto</label>
                            </div>
                            @if($type != 0)
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarMarcaProduto" id="buscarMarcaProduto" checked>
                                <label class="form-check-label" for="buscarMarcaProduto">Marca do Produto</label>
                            </div>
                            @endif
                            @if($type == 2)
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarCategoriaProduto" id="buscarMarcaProduto" checked>
                                <label class="form-check-label" for="buscarCategoriaProduto">Categoria do Produto</label>
                            </div>
                            @endif
                            @if($type == 1)
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarEstoqueProduto" id="buscarEstoqueProduto" checked>
                                <label class="form-check-label" for="buscarEstoqueProduto">Estoque do Produto</label>
                            </div>
                            @endif
                            @if($type == 0)
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="precoAnterior" id="precoAnterior" checked>
                                <label class="form-check-label" for="precoAnterior">Preço anterior do Produto</label>
                            </div>
                            @endif
                            @if($type == 0)
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarDescontoProduto" id="buscarDescontoProduto" checked>
                                <label class="form-check-label" for="buscarDescontoProduto">Desconto do Produto</label>
                            </div>
                            @endif
                            <div class="form-check form-switch mb-2 mt-3">
                                <input class="form-check-input" type="checkbox" name="buscarLinkProduto" id="buscarLinkProduto" checked>
                                <label class="form-check-label" for="buscarLinkProduto">Link do Produto</label>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 3em;">
                        <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        <button type="submit" class="btn btn-primary me-2">Realizar busca</button>
                    </div>
                </form>
            </div>
            <!-- /Account -->
        </div>
    </div>
</div>

@endsection



