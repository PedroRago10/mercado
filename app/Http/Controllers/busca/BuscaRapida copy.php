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
        $site                               = $_POST['site'];
        $tipoBusca                          = $_POST['tipoBusca'];
        $categorias                         = isset($_POST['categorias']) ? $_POST['categorias'] : false;
        $links                              = isset($_POST['links']) ? $_POST['links'] : false;
        $type                               = $_POST['type'];

        $options                            = [];
        $options['buscarPaginaInicial']     = isset($_POST['buscarPaginaInicial']) ? $_POST['buscarPaginaInicial'] : false;
        $options['buscarNomeProduto']       = isset($_POST['buscarNomeProduto']) ? $_POST['buscarNomeProduto'] : false;
        $options['buscarPrecoProduto']      = isset($_POST['buscarPrecoProduto']) ? $_POST['buscarPrecoProduto'] : false; 
        $options['buscarDescontoProduto']   = isset($_POST['buscarDescontoProduto']) ? $_POST['buscarDescontoProduto'] : false;
        $options['buscarLinkProduto']       = isset($_POST['buscarLinkProduto']) ? $_POST['buscarLinkProduto'] : false;
        $options['precoAnterior']           = isset($_POST['precoAnterior']) ? $_POST['precoAnterior'] : false;
        
        $urls                               = [rtrim($site, "/ ")];
        
        if($tipoBusca == '1' && !$categorias && !$options['buscarPaginaInicial']) {
            return Redirect::back()->withErrors(['msg' => 'Selecione uma categoria']);
        }else if($tipoBusca == '2' && !$links && !$options['buscarPaginaInicial']) {
            return Redirect::back()->withErrors(['msg' => 'Adicione ao menos uma URL']);
        }else if($tipoBusca == '1' && $categorias) {
            $urls = $categorias;
        }else if($tipoBusca == '2' && $links) {
            $urls = $links;
        }

        $this->siteDefault = rtrim($site, "/ ");
        $this->options     = $options;
     
        if($type == 0) {
            $result = self::scraping($urls);

        }else {
            $result = self::fetchPagesAsync($urls, 10, true);
            var_export($result);
            die;
        }
        return view('content.busca.result')->with(["resultado" => $result, "site" => $site, 'options' => $options]);
    }
    
    public function scrapingSync($type, $urls)
    {
        if ($this->options['buscarPaginaInicial'] && $urls[0] != $this->siteDefault) {
            array_push($urls, $this->siteDefault);
        }
    
        foreach ($urls as $key => $url) {
            if ($key > 0) {
                $urls[$key] = $this->siteDefault . $url;
            }
        }
    
        $puppeteer = new Puppeteer();
        $browser = $puppeteer->launch();
    
        $results = [];
    
        foreach ($urls as $url) {
            $page = $browser->newPage();
            $page->goto($url);
    
            // Aguarde o carregamento de todos os elementos necessários
            // Exemplo:
            $page->waitForSelector('.product-box');
    
            // Extraia os dados dos produtos usando o método evaluate()
            $products = $page->evaluate('function() {
                const productNodes = document.querySelectorAll(".product-box");
                const data = [];
    
                productNodes.forEach(function(element) {
                    const link = ' . ($this->options['buscarLinkProduto'] ? 'element.querySelector("a").href' : 'false') . ';
                    const nome = ' . ($this->options['buscarNomeProduto'] ? 'element.querySelector(".product-box__name").textContent' : 'false') . ';
                    const preco = ' . ($this->options['buscarPrecoProduto'] ? 'element.querySelector(".product-box__price--number").textContent' : 'false') . ';
                    const fornecedor = ' . ($this->options['buscarFornecedor'] ? 'element.querySelector(".js-product-box__supplier").textContent' : 'false') . ';
                    const desconto = ' . ($this->options['buscarDescontoProduto'] ? 'element.querySelector(".product-box__economy-tag").textContent' : 'false') . ';
    
                    data.push({
                        link: link,
                        nome: nome,
                        preco: preco,
                        fornecedor: fornecedor,
                        desconto: desconto
                    });
                });
    
                return data;
            }');
    
            // Adicione os resultados desta página ao array de resultados
            $results = array_merge($results, $products);
    
            $page->close();
        }
    
        $browser->close();
    
        // Faça algo com os dados obtidos
        var_dump($results);
    }
    public function scrapingSync1($type, $urls) 
    {
        if($this->options['buscarPaginaInicial'] && $urls[0] != $this->siteDefault) {
            array_push($urls, $this->siteDefault);
        }

        $client = new Client([
            'base_uri' => $this->siteDefault,
            'verify' => false // Desabilita a verificação do certificado SSL
        ]);
        $produtos = [];
        $result = [];
        try {
            foreach($urls as $url) {
                $response = $client->request('GET', $url);
                $body = (string) $response->getBody();
    
                $crawler = new Crawler($body);
    
                // Encontre os elementos que contêm os produtos usando seletores CSS ou XPath
                if($type == 2) {
                    $productNodes = $crawler->filter('.product-box');
                    var_dump($productNodes);
                    die;
                }
                $nome           = false;
                $preco          = false;
                $link           = false;
                $fornecedor     = false;
                $supplier       = false;
              
                $productNodes->each(function (Crawler $productNode) use (&$produtos) {
                    if($this->options['buscarNomeProduto']) {
                        $nome      = $productNode->filter('.product-box__name')->text();
                    }
                    if($this->options['buscarPrecoProduto']) {
                        $preco      = $productNode->filter('.product-box__price--number')->text();
                    }
                    if($this->options['buscarLinkProduto']) {
                        $link       = $productNode->filter('a')->attr('href');
                    }
                    if($this->options['buscarFornecedor']) {
                        $fornecedor   = $productNode->filter('.js-product-box__supplier')->text();
                    }
                    if($this->options['buscarDescontoProduto']) {
                        $desconto   = $productNode->filter('.product-box__economy-tag')->text();
                    }
                    
                    
                    $produtos[] = [
                        'link' => $link,
                        'nome' => $nome,
                        'preco' => $preco,
                        'desconto' => $desconto,
                        'fornecedor' => $fornecedor,
                    ];
                    
                });
            }
           
            return $result;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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


    public static function fetchPagesAsync (array $urls, int $maxConcurrency = 10, bool $debug) : array
    {
    $result = [];
    $urls = ["https://www.atacadao.com.br/bebidas"];

    
    $client = new GuzzleClient([
        'verify' => false // Desativa a verificação do certificado SSL
    ]);
   
    $promises = (function () use ($urls, $client) {
        foreach ($urls as $index => $url) {
            yield $client->getAsync($url)->then(
                function (ResponseInterface $response) use ($index) {
                    return [
                        'index' => $index,
                        'body' => $response->getBody()->getContents() // Captura todo o HTML do corpo da resposta
                    ];
                },
                function (Throwable $ex) use ($index) {
                    return [
                        'index' => $index,
                        'exception' => $ex
                    ];
                }
            );
        }
    })();

    ob_start(); // Inicia o buffer de saída

    (new EachPromise($promises, [
        'concurrency' => $maxConcurrency,
        'fulfilled' => function ($response) use (&$result, $urls, $debug) {
            $index = $response['index'];
            $body = $response['body'];

            $result[$index] = $body;

            if ($debug) {
                $_url = $urls[$index];
                print "ASYNC Fetch SUCCESS: $index: $_url\n";
            }
        },
        'rejected' => function ($response) use (&$result, $urls, $debug) {
            $index = $response['index'];
            $exception = $response['exception'];

            $result[$index] = null;

            if ($debug) {
                $_url = $urls[$index];
                print "ASYNC Fetch FAILURE: for $_url: " . $exception->getMessage() . ' ' . $exception->getCode() . "\n";
            }
        }
    ]))->promise()->wait();

    $output = ob_get_clean(); // Recupera o conteúdo do buffer de saída
    print $output; // Imprime todo o conteúdo capturado

    return $result;
}

}
