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
    public function buscaAjax()
    {
        // https://api.tendaatacado.com.br/api/public/store/category/12/products?query=%7B"link":"mercearia"%7D&page=1
        // query: {"link":"mercearia"}
        // page: 1
        // order: relevance
        // save: true
        // cartId: 7413956
        // current_page
        // : 
        // 1
        // products
        // : 
        // [,…]
        // products_per_page
        // : 
        // 20
        // searchId
        // : 
        // "a417a72f-a633-49a9-b701-c1168daa2bbf"
        // total_pages
        // : 
        // 152
        // total_products
        // : 
        // 3029

        $obj =' {
            "draw": 1,
            "recordsTotal": 57,
            "recordsFiltered": 57,
            "data": [
              [
                "Airi",
                "Satou",
                "Accountant",
                "Tokyo",
                "28th Nov 08",
                "$162,700"
              ],
              [
                "Angelica",
                "Ramos",
                "Chief Executive Officer (CEO)",
                "London",
                "9th Oct 09",
                "$1,200,000"
              ],
              [
                "Ashton",
                "Cox",
                "Junior Technical Author",
                "San Francisco",
                "12th Jan 09",
                "$86,000"
              ],
              [
                "Bradley",
                "Greer",
                "Software Engineer",
                "London",
                "13th Oct 12",
                "$132,000"
              ],
              [
                "Brenden",
                "Wagner",
                "Software Engineer",
                "San Francisco",
                "7th Jun 11",
                "$206,850"
              ],
              [
                "Brielle",
                "Williamson",
                "Integration Specialist",
                "New York",
                "2nd Dec 12",
                "$372,000"
              ],
              [
                "Bruno",
                "Nash",
                "Software Engineer",
                "London",
                "3rd May 11",
                "$163,500"
              ],
              [
                "Caesar",
                "Vance",
                "Pre-Sales Support",
                "New York",
                "12th Dec 11",
                "$106,450"
              ],
              [
                "Cara",
                "Stevens",
                "Sales Assistant",
                "New York",
                "6th Dec 11",
                "$145,600"
              ],
              [
                "Cedric",
                "Kelly",
                "Senior Javascript Developer",
                "Edinburgh",
                "29th Mar 12",
                "$433,060"
              ]
            ]
          }';
        return $obj; 
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
                    
                    return [
                        'nome'          => $nome,
                        'preco'         => $preco,
                        'precoAnterior' => $precoAnterior,
                        'link'          => $link,
                        'desconto'      => $desconto
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
}
