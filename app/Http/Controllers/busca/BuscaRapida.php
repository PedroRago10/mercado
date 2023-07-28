<?php

namespace App\Http\Controllers\busca;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;
use Nesk\Puphpeteer\Puppeteer;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;


class BuscaRapida extends Controller
{
        /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    
    public function index()
    {
        $type = isset($_GET['type']) ? $_GET['type'] : 0;

        // $dbConnection = DB::connection();
        // $results = $dbConnection->select('SELECT * FROM users');
        // var_dump($results);
        return view('content.busca.index')->with(['type' => $type]);
    }
    
    public function busca()
    {
        $current_site                       = $_POST['current_site'];
        $site                               = $_POST['site'];
        $order                              = isset($_POST['orderBy']) ? $_POST['orderBy'] : false;
        $paginas                            = isset($_POST['paginas']) ? $_POST['paginas'] : false;
        $tipoBusca                          = isset($_POST['tipoBusca']) ? $_POST['tipoBusca'] : false;
        $categorias                         = isset($_POST['categorias']) ? $_POST['categorias'] : false;
        $links                              = isset($_POST['links']) ? $_POST['links'] : false;
        $pjPf                               = isset($_POST['pjPf']) ? $_POST['pjPf'] : false;
        $type                               = $_POST['type'];

        $options                            = [];
        $options['buscarPaginaInicial']     = isset($_POST['buscarPaginaInicial']) ? $_POST['buscarPaginaInicial'] : false;
        $options['buscarNomeProduto']       = isset($_POST['buscarNomeProduto']) ? $_POST['buscarNomeProduto'] : false;
        $options['buscarPrecoProduto']      = isset($_POST['buscarPrecoProduto']) ? $_POST['buscarPrecoProduto'] : false; 
        $options['buscarDescontoProduto']   = isset($_POST['buscarDescontoProduto']) ? $_POST['buscarDescontoProduto'] : false;
        $options['buscarLinkProduto']       = isset($_POST['buscarLinkProduto']) ? $_POST['buscarLinkProduto'] : false;
        $options['precoAnterior']           = isset($_POST['precoAnterior']) ? $_POST['precoAnterior'] : false;
        $options['buscarMarcaProduto']      = isset($_POST['buscarMarcaProduto']) ? $_POST['buscarMarcaProduto'] : false;
        $options['buscarCategoriaProduto']  = isset($_POST['buscarCategoriaProduto']) ? $_POST['buscarCategoriaProduto'] : false;
        $options['buscarEstoqueProduto']    = isset($_POST['buscarEstoqueProduto']) ? $_POST['buscarEstoqueProduto'] : false;
        
        $cep                                = isset($_POST['cep']) ? $_POST['cep'] : false;

        if($type != 1) {
            $urls                               = [rtrim($site, "/ ")];
        }
        
        if($tipoBusca) {
            if($tipoBusca == '1' && !$categorias && !$options['buscarPaginaInicial']) {
                return Redirect::back()->withErrors(['msg' => 'Selecione uma categoria']);
            }else if($tipoBusca == '2' && !$links && !$options['buscarPaginaInicial']) {
                return Redirect::back()->withErrors(['msg' => 'Adicione ao menos uma URL']);
            }else if($tipoBusca == '1' && $categorias) {
                $urls = $categorias;
            }else if($tipoBusca == '2' && $links) {
                $urls = $links;
            }
        }else{
            if(!$categorias) {
                return Redirect::back()->withErrors(['msg' => 'Selecione uma categoria']);
            }
            $urls = $categorias;
        }

        $this->siteDefault = rtrim($site, "/ ");
        $this->options     = $options;
     
        if($type == 0) {
            $result = self::scraping($urls);

        }else if($type == 1){
            $result = self::scrapingSyncType1($urls, $type, $paginas, $order);
        }else {
            $result = self::scrapingSyncType2($urls, $type, $cep, $pjPf, $paginas);
        }
       
        return view('content.busca.result')->with(["resultado" => $result, "site" => $site, 'type' => $type, 'options' => $options, 'current_site' => $current_site]);
    }

    public function save()
    {
        $data = $_POST['data'];
        // Percorrer o array e fazer as operações de inserção/atualização nas tabelas
        foreach ($data as $item) {
            $productId = self::insertOrUpdateProduct($item);
            self::insertOrUpdateProductComplement($productId, $item['price']);
        }
        return true;
    }
    
    public function scraping($urls) {
        if($this->options['buscarPaginaInicial'] && $urls[0] != $this->siteDefault) {
            array_push($urls, $this->siteDefault);
        }

        $result = [];

        foreach($urls as $key => $url) {
            // Crie um novo cliente GuzzleHttp desabilitando a verificação do certificado SSL
            $urlCategoria = $url;
            $client = new Client([
                'base_uri' => $this->siteDefault,
                'verify' => false // Desabilita a verificação do certificado SSL
            ]);
            
            // Variável para armazenar todos os produtos
            $produtos = [];
            $pages = [];
            do {
                // Faça a requisição GET para obter o conteúdo da página
                $response   = $client->request('GET', $url);
                
                // Extraia os preços dos produtos do conteúdo da página
                $html       = $response->getBody()->getContents();
                
                // Faça o parse do HTML usando o DomCrawler
                $crawler    = new Crawler($html);
            
                $produtosPagina            = $crawler->filter('.item-product')->each(function (Crawler $node, $i) {
                    $nome           = false;
                    $preco          = false;
                    $link           = false;
                    $desconto       = false;
                    $precoAnterior  = false;

                    if($this->options['buscarNomeProduto']) {
                        $nome = $node->filter('.title')->count() > 0 ? $node->filter('.title')->text() : '';
                    }
                    if($this->options['buscarPrecoProduto']) {
                        $preco = $node->filter('.price')->count() > 0 ?$node->filter('.price')->text() : '';
                    }
                    if($this->options['buscarLinkProduto']) {
                        $link = $node->filter('.title a')->count() > 0 ? $node->filter('.title a')->attr('href') : '';
                    }
                    if($this->options['buscarDescontoProduto']) {
                        $desconto = $node->filter('.descont_percentage')->count() > 0 ? $node->filter('.descont_percentage')->text() : '';
                    }
                    if($this->options['precoAnterior']) {
                        $precoAnterior = $node->filter('.unit_price')->count() > 0 ? $node->filter('.unit_price')->text() : '';
                    }
                    
                    $exists = false;
                    $existingProduct = DB::table('public.produto')->where('descricaocompleta', $nome)->first();
                 
                    if($existingProduct) {
                        $existingProductValue = DB::table('public.produtocomplemento')->where('id_produto', $existingProduct->id)->first();
                        if(floatval($existingProductValue->precovenda) == self::parsePrice($preco)) {
                            $exists = 'table-primary';
                        }else{
                            $exists = 'table-warning';
                        }
                    }

                    return [
                        'nome'          => $nome,
                        'preco'         => $preco,
                        'precoAnterior' => $precoAnterior,
                        'link'          => $link,
                        'desconto'      => $desconto,
                        'exists'        => $exists
                    ];
                });

                // Adicione os produtos da página atual à lista geral de produtos
                $produtos = array_merge($produtos, $produtosPagina);

                // Verifique se existe uma próxima página
                $proximaPaginaLink = $crawler->filter('.pagination .next > a')->count() > 0 ? $crawler->filter('.pagination .next > a')->attr('href') : false;
                $url = null;
                if($proximaPaginaLink && $urlCategoria != 'https://www.superpaguemenos.com.br' && $proximaPaginaLink != '#') {
                    if(!in_array($urlCategoria .'/'. ltrim($proximaPaginaLink, '/'), $pages)){
                        $url = $urlCategoria .'/'. ltrim($proximaPaginaLink, '/');
                        array_push($pages, $url);
                    }
                }

            } while ($url);

            array_push($result, [$urlCategoria, $produtos]);
        }
        
        return $result;
    }
    public function scrapingSyncType2($urls, $type, $cep = null, $pjPf = null, $paginas) {
        // Busca CEP analytics 
        $client = new GuzzleClient([
            'verify' => false // Desativa a verificação do certificado SSL
        ]);

        if($type == 1) {

        }
        $cep = str_replace("-", "", $cep);

        $response = $client->get('https://apis.cotabest.com.br/logistic/address/'.$cep);
        $json = $response->getBody()->getContents();
        $cepAnalytics = json_decode($json, true);

        if(!count($cepAnalytics)) {
            return Redirect::back()->withErrors(['msg' => 'Não foi encontrada nenhuma cidade com esse CEP']);
        }
        $cityID = $cepAnalytics['neighborhood']['id'];

        //Busca bairros 

        $client = new GuzzleClient([
            'verify' => false // Desativa a verificação do certificado SSL
        ]);
        $response = $client->get('https://apis.cotabest.com.br/logistic/logistics/neighborhoods/'.$cityID.'/'.$pjPf.'/available-logistics');
        $json = $response->getBody()->getContents();
        $neighborhoods = json_decode($json, true);

        if(!count($neighborhoods)) {
            return Redirect::back()->withErrors(['msg' => 'Não foi encontrado nenhum fornecedor para esse cep na modalidade']);
        }

        $neighborhoods = implode(",", $neighborhoods);

        $catalogo = '/catalogo?';
        $results = [];
        
        foreach($urls as $key => $url) {
            $client = new GuzzleClient([
                'verify' => false // Desativa a verificação do certificado SSL
            ]);
            $key += 1;
            $results[$key] = [];
            $results[$key]['results'] = []; 
        
            for ($i=1; $i <= $paginas; $i++) {
                $response = $client->get($this->siteDefault.$catalogo.'category='.$url.'&commaSeparatedRegionIds='.$neighborhoods.'&page='.$i);
                $json = $response->getBody()->getContents();
                $array = json_decode($json, true);
                
                if($array['pageCount'] == 0) {
                    break;
                }
                $array['category'] = $url;
                $results[$key]['pageCount'] = isset($results[$key]['pageCount']) ? $results[$key]['pageCount'] + $array['pageCount'] : $array['pageCount'];
                $results[$key]['category']  = $array['category'];
                $results[$key]['totalPaginas'] = $i;
                $results[$key]['cep'] = $cep;

                foreach($array['results'] as $ckey => &$resultValue) {
                    $exists = false;
                    $existingProduct = DB::table('public.produto')->where('descricaocompleta', $resultValue['name'])->first();
                 
                    if($existingProduct) {
                        $existingProductValue = DB::table('public.produtocomplemento')->where('id_produto', $existingProduct->id)->first();
                        if(floatval($existingProductValue->precovenda) == self::parsePrice($resultValue['providers'][0]['prices'][0]['price'])) {
                            $exists = 'table-primary';
                        }else{
                            $exists = 'table-warning';
                        }
                    }
                    $resultValue['exists'] = $exists;

                }
                unset($resultValue);
                
                if (is_array($array['results'])) {
                    array_push($results[$key]['results'], ...$array['results']); // Use operador de spread para adicionar elementos individuais
                } else {
                    $results[$key]['results'] = $array['results'];
                }
            }
            // print("<pre>".print_r($results[1]['results'],true)."</pre>");

            //     die;
        }
       
        // Verificar se houve algum erro na decodificação do JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar o JSON: ' . json_last_error_msg());
        }

        return $results;
    }

     public function scrapingSyncType1($urls, $type, $paginas, $order) {
        // Busca CEP analytics 
        $client = new GuzzleClient([
            'verify' => false // Desativa a verificação do certificado SSL
        ]);

        $results = [];
        $key = 0;
        foreach($urls as $id => $url) {
            $url = array_key_first($url);
            $url = str_replace('"{', "{", $url);
            $url = str_replace('}"', "}", $url);

            $url = json_decode($url, true);

           
           
            $client = new GuzzleClient([
                'verify' => false // Desativa a verificação do certificado SSL
            ]);
            $key += 1;
            $results[$key] = [];
            $results[$key]['results'] = []; 
            
            for ($i=1; $i <= $paginas; $i++) {
                $response = $client->get($this->siteDefault.'/category/'.$id.'/products?query={"link":"'.$url['slug'].'"}&page='.$i.'&order='.$order);
                $json = $response->getBody()->getContents();
                $array = json_decode($json, true);

                if($array['total_products'] == 0) {
                    break;
                }
                
                // Loop através dos produtos e remova a chave 'seoHome' do array original
                foreach ($array['products'] as &$product) {
                    unset($product['lojaConfigTO']);
                    unset($product['inventory']);
                    $product['exists'] = false;
                    $existingProduct = DB::table('public.produto')->where('descricaocompleta', $product['name'])->first();
                    if($existingProduct) {
                        $existingProductValue = DB::table('public.produtocomplemento')->where('id_produto', $existingProduct->id)->first();
                        if(floatval($existingProductValue->precovenda) == $product['price']) {
                            $product['exists'] = 'table-primary';
                        }else{
                            $product['exists'] = 'table-warning';
                        }
                    }
                }
                unset($product); // Desvincular a referência para evitar problemas posteriores
    
             

                $array['category'] = $url['title'];
                $results[$key]['pageCount'] = $i;
                $results[$key]['category']  = $array['category'];
                $results[$key]['totalProducts'] = $array['total_products'];
                if (is_array($array['products'])) {
                    array_push($results[$key]['results'], ...$array['products']); // Use operador de spread para adicionar elementos individuais
                } else {
                    $results[$key]['results'] = $array['products'];
                }
            }
         
        }
        // print("<pre>".print_r($results[1]['results'][1],true)."</pre>");

        // die;
        // Verificar se houve algum erro na decodificação do JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar o JSON: ' . json_last_error_msg());
        }

        return $results;
    }

    // Função para inserir ou atualizar um produto na tabela "produtos"
    function insertOrUpdateProduct($product)
    {
        $name = $product['name'];
        $price = $product['price'];

        // Verificar se o produto já existe na tabela "produtos"
        $existingProduct = DB::table('public.produto')->where('descricaocompleta', $name)->first();
      
        if ($existingProduct) {
            // Atualizar as informações do produto
            DB::table('public.produto')
                ->where('id', $existingProduct->id)
                ->update([
                    'descricaocompleta' => $name,
                    'descricaogondola' => $name,
                    'descricaoreduzida' => substr($name, 0, 22),
                    'dataalteracao' => date('Y-m-d H:i:s')
                ]);

            // Retornar o ID do produto atualizado
            return $existingProduct->id;
        } else {
            // Buscar o último ID na tabela "produtos"
            $lastProductId = DB::table('public.produto')->max('id');

            // Incrementar o ID para o novo produto
            $newProductId = $lastProductId + 1;
       
            // Inserir o novo produto com o ID incrementado
           $result = DB::table('public.produto')->insert([
                'id' => $newProductId,
                'descricaocompleta' => $name,
                'descricaogondola' => $name,
                'descricaoreduzida' => substr($name, 0, 22),
                'qtdembalagem' => 1,
                'id_tipoembalagem' => 1,
                'mercadologico1' => 1,
                'mercadologico2' => 1,
                'mercadologico3' => 1,
                'mercadologico4' => 1,
                'mercadologico5' => 0,
                'id_comprador' => 1,
                'custofinal' => 0,
                'pesoliquido' => 0,
                'datacadastro' => date('Y-m-d H:i:s'),
                'validade' => 0,
                'pesobruto' => 0,
                'comprimentoembalagem' => 0,
                'larguraembalagem' => 0,
                'alturaembalagem' => 0,
                'perda' => 0,
                'verificacustotabela' => false,
                'percentualipi' => 0,
                'percentualfrete' => 0,
                'percentualencargo' => 0,
                'percentualperda' => 0,
                'percentualsubstituicao' => 0,
                'id_tipomercadoria' => 99,
                'sugestaopedido' => true,
                'aceitamultiplicacaopdv' => true,
                'id_fornecedorfabricante' => 1,
                'id_divisaofornecedor' => 0,
                'id_tipopiscofins' => 1,
                'sazonal' => false, 
                'consignado' => false,
                'ncm1' => 9999,
                'ncm2' => 99,
                'ncm3' => 99,
                'ddv' => 0,
                'permitetroca' => true,
                'temperatura' => 0,
                'id_tipoorigemmercadoria' => 0,
                'ipi' => 0,
                'pesavel' => false,
                'id_tipopiscofinscredito' => 1,
                'vendacontrolada' => false,
                'vendapdv' => true,
                'conferido' => false,
                'permitequebra' => true, 
                'permiteperda' => true,
                'impostomedionacional' => 0,
                'impostomedioimportado' => 0,
                'sugestaocotacao' => true,
                'tara' => 0, 
                'utilizatabelasubstituicaotributaria' => false,
                'id_tipolocaltroca' => 0,
                'qtddiasminimovalidade' => 0,
                'utilizavalidadeentrada' => 0,
                'impostomedioestadual' => 0,
                'id_tipocompra' => 1,
                'numeroparcela' => 0,
                'id_tipoembalagemvolume' => 0,
                'volume' => 1,
                'id_normacompra' => 1,
                'promocaoauditada' => false,
                'permitedescontopdv' => true,
                'verificapesopdv' => false,
                'produtoecommerce' => false,
                'id_tipoorigemmercadoriaentrada' => 0, 
                'alteradopaf' => false,
            ]);
            // Retornar o ID do novo produto inserido
            return $newProductId;
        }
    }

    // Função para inserir ou atualizar um produto na tabela "produtocomplemento"
    function insertOrUpdateProductComplement($productId, $price)
    {
        $price = self::parsePrice($price);
        // Verificar se o produto já existe na tabela "produtocomplemento"
        $existingProductComplement = DB::table('public.produtocomplemento')->where('id_produto', $productId)->first();

        if ($existingProductComplement) {
            // Atualizar o preço do produto na tabela "produtocomplemento"
            DB::table('public.produtocomplemento')
                ->where('id_produto', $productId)
                ->update(['precovenda' => $price]);
        } else {
            // Inserir o novo produto complemento na tabela "produtocomplemento"
            DB::table('public.produtocomplemento')->insert([
                'id_produto' => $productId,
                'precovenda' => $price,
                'prateleira' => '',
                'secao'      => '',
                'estoqueminimo' => 0,
                'estoquemaximo' => 0,
                'valoripi'      => 0, 
                'custosemimposto' => 0,
                'custocomimposto' => 0,
                'custosemimpostoanterior' => 0,
                'custocomimpostoanterior' => 0,
                'precovendaanterior' => 0,
                'precodiaseguinte' => 0,
                'estoque' => 0,
                'troca' => 0,
                'emiteetiqueta' => 0,
                'custosemperdasemimposto' => 0,
                'custosemperdasemimpostoanterior' => 0,
                'customediocomimposto' => 0,
                'customediocomimposto' => 0,
                'customediosemimposto' => 0,
                'id_aliquotacredito' => 6,
                'teclaassociada' => 0,
                'id_situacaocadastro' => 1,
                'id_loja' => 1,
                'descontinuado' => false,
                'quantidadeultimaentrada' => 0,
                'centralizado' => false,
                'operacional' => 0,
                'valoricmssubstituicao' => 0, 
                'cestabasica' => 0, 
                'customediocomimpostoanterior' => 0,
                'customediosemimpostoanterior' => 0,
                'id_tipopiscofinscredito' => 1,
                'valoroutrassubstituicao' => 0,
                'id_tipocalculoddv' => 1,
                'id_normareposicao' => 1,
                'id_tipoproduto' => 0,
                'fabricacaopropria' => false,
                'dataprimeiraentrada' => date('Y-m-d'),
                'alteradopaf' => false,
                'margem' => 0,
                'margemminima' => 0,
                'margemmaxima' => 0
            ]);
        }
    }
    // Função para converter o preço no formato correto (R$ 0,79 -> 0.79)
    function parsePrice($price)
    {
        $price = str_replace(['R$', ','], ['', '.'], $price);
        return (float) $price;
    }

}
