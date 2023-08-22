<?php

namespace App\Http\Controllers\busca;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

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
        return view('content.busca.index')->with(['type' => $type]);
    }

    public function busca()
    {
        $current_site = $_POST['current_site'];
        $site = $_POST['site'];
        $order = isset($_POST['orderBy']) ? $_POST['orderBy'] : false;
        $paginas = isset($_POST['paginas']) ? $_POST['paginas'] : false;
        $tipoBusca = isset($_POST['tipoBusca']) ? $_POST['tipoBusca'] : false;
        $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : false;
        $links = isset($_POST['links']) ? $_POST['links'] : false;
        $pjPf = isset($_POST['pjPf']) ? $_POST['pjPf'] : false;
        $type = $_POST['type'];

        $options = [
            'buscarPaginaInicial' => isset($_POST['buscarPaginaInicial']) ? $_POST['buscarPaginaInicial'] : false,
            'buscarNomeProduto' => isset($_POST['buscarNomeProduto']) ? $_POST['buscarNomeProduto'] : false,
            'buscarPrecoProduto' => isset($_POST['buscarPrecoProduto']) ? $_POST['buscarPrecoProduto'] : false,
            'buscarDescontoProduto' => isset($_POST['buscarDescontoProduto']) ? $_POST['buscarDescontoProduto'] : false,
            'buscarLinkProduto' => isset($_POST['buscarLinkProduto']) ? $_POST['buscarLinkProduto'] : false,
            'precoAnterior' => isset($_POST['precoAnterior']) ? $_POST['precoAnterior'] : false,
            'buscarMarcaProduto' => isset($_POST['buscarMarcaProduto']) ? $_POST['buscarMarcaProduto'] : false,
            'buscarCategoriaProduto' => isset($_POST['buscarCategoriaProduto']) ? $_POST['buscarCategoriaProduto'] : false,
            'buscarEstoqueProduto' => isset($_POST['buscarEstoqueProduto']) ? $_POST['buscarEstoqueProduto'] : false,
        ];

        $cep = isset($_POST['cep']) ? $_POST['cep'] : false;

        if ($type != 1) {
            $urls = [rtrim($site, "/ ")];
        }

        if ($tipoBusca) {
            if ($tipoBusca == '1' && !$categorias && !$options['buscarPaginaInicial']) {
                return Redirect::back()->withErrors(['msg' => 'Selecione uma categoria']);
            } else if ($tipoBusca == '2' && !$links && !$options['buscarPaginaInicial']) {
                return Redirect::back()->withErrors(['msg' => 'Adicione ao menos uma URL']);
            } else if ($tipoBusca == '1' && $categorias) {
                $urls = $categorias;
            } else if ($tipoBusca == '2' && $links) {
                $urls = $links;
            }
        } else {
            if (!$categorias) {
                return Redirect::back()->withErrors(['msg' => 'Selecione uma categoria']);
            }
            $urls = $categorias;
        }

        $this->siteDefault = rtrim($site, "/ ");
        $result = $this->scraping($urls, $type, $options, $cep, $pjPf, $paginas, $order);

        return view('content.busca.result')->with(["resultado" => $result, "site" => $site, 'type' => $type, 'options' => $options, 'current_site' => $current_site]);
    }

    public function save()
    {
        $data = $_POST['data'];
        // Percorrer o array e fazer as operações de inserção/atualização nas tabelas
        foreach ($data as $item) {
            $productId = $this->insertOrUpdateProduct($item);
            $this->insertOrUpdateProductComplement($productId, $item['price']);
        }
        return true;
    }

    // Função para inserir ou atualizar um produto na tabela "produtos"
    public function insertOrUpdateProduct($product)
    {
        $name = $product['name'];
        $price = $product['price'];

        // Verificar se o produto já existe na tabela "produtos"
        $existingProduct = DB::table('produto')->where('descricaocompleta', $name)->first();

        if ($existingProduct) {
            // Atualizar o preço do produto se ele já existir
            DB::table('produto')->where('id', $existingProduct->id)->update([
                'precovenda' => $price,
            ]);
            return $existingProduct->id;
        } else {
            // Inserir um novo produto na tabela "produtos" se ele não existir
            $productId = DB::table('produto')->insertGetId([
                'descricaocompleta' => $name,
                'precovenda' => $price,
            ]);
            return $productId;
        }
    }

    // Função para inserir ou atualizar dados complementares do produto na tabela "produtocomplemento"
    public function insertOrUpdateProductComplement($productId, $price)
    {
        $exists = DB::table('produtocomplemento')->where('id_produto', $productId)->first();

        if ($exists) {
            // Atualizar dados complementares do produto se eles já existirem
            DB::table('produtocomplemento')->where('id_produto', $productId)->update([
                'precovenda' => $price,
            ]);
        } else {
            // Inserir dados complementares do produto se eles não existirem
            DB::table('produtocomplemento')->insert([
                'id_produto' => $productId,
                'precovenda' => $price,
            ]);
        }
    }

    public function parsePrice($price)
    {
        $price = str_replace("R$", "", $price);
        $price = str_replace(".", "", $price);
        $price = str_replace(",", ".", $price);
        return floatval($price);
    }

    public function scraping($urls, $type, $options = [], $cep = null, $pjPf = null, $paginas = null, $order = null)
    {
        // Inicializar as variáveis de configuração com valores padrão
        $buscarPaginaInicial = $options['buscarPaginaInicial'] ?? false;
        $buscarNomeProduto = $options['buscarNomeProduto'] ?? false;
        $buscarPrecoProduto = $options['buscarPrecoProduto'] ?? false;
        $buscarDescontoProduto = $options['buscarDescontoProduto'] ?? false;
        $buscarLinkProduto = $options['buscarLinkProduto'] ?? false;
        $precoAnterior = $options['precoAnterior'] ?? false;
        $buscarMarcaProduto = $options['buscarMarcaProduto'] ?? false;
        $buscarCategoriaProduto = $options['buscarCategoriaProduto'] ?? false;
        $buscarEstoqueProduto = $options['buscarEstoqueProduto'] ?? false;
    
        $result = [];
    
        foreach ($urls as $key => $url) {
            // Criar um novo cliente GuzzleHttp desabilitando a verificação do certificado SSL
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
                $response = $client->request('GET', $url);
    
                // Extraia os preços dos produtos do conteúdo da página
                $html = $response->getBody()->getContents();
    
                // Faça o parse do HTML usando o DomCrawler
                $crawler = new Crawler($html);
    
                $produtosPagina = $crawler->filter('.item-product')->each(function (Crawler $node, $i) use ($buscarNomeProduto, $buscarPrecoProduto, $buscarLinkProduto, $buscarDescontoProduto, $precoAnterior, $buscarMarcaProduto, $buscarCategoriaProduto, $buscarEstoqueProduto) {
                    $nome = $buscarNomeProduto ? ($node->filter('.title')->count() > 0 ? $node->filter('.title')->text() : '') : '';
                    $preco = $buscarPrecoProduto ? ($node->filter('.price')->count() > 0 ? $node->filter('.price')->text() : '') : '';
                    $link = $buscarLinkProduto ? ($node->filter('.title a')->count() > 0 ? $node->filter('.title a')->attr('href') : '') : '';
                    $desconto = $buscarDescontoProduto ? ($node->filter('.descont_percentage')->count() > 0 ? $node->filter('.descont_percentage')->text() : '') : '';
                    $precoAnterior = $precoAnterior ? ($node->filter('.unit_price')->count() > 0 ? $node->filter('.unit_price')->text() : '') : '';
    
                    $exists = false;
                    $existingProduct = DB::table('produto')->where('descricaocompleta', $nome)->first();
    
                    if ($existingProduct) {
                        $existingProductValue = DB::table('produtocomplemento')->where('id_produto', $existingProduct->id)->first();
                        if (floatval($existingProductValue->precovenda) == $this->parsePrice($preco)) {
                            $exists = 'table-primary';
                        } else {
                            $exists = 'table-warning';
                        }
                    }
    
                    return [
                        'nome' => $nome,
                        'preco' => $preco,
                        'precoAnterior' => $precoAnterior,
                        'link' => $link,
                        'desconto' => $desconto,
                        'exists' => $exists
                    ];
                });
    
                // Adicione os produtos da página atual à lista geral de produtos
                $produtos = array_merge($produtos, $produtosPagina);
    
                // Verifique se existe uma próxima página
                $proximaPaginaLink = $crawler->filter('.pagination .next > a')->count() > 0 ? $crawler->filter('.pagination .next > a')->attr('href') : false;
                $url = null;
                if ($proximaPaginaLink && $urlCategoria != 'https://www.superpaguemenos.com.br' && $proximaPaginaLink != '#') {
                    if (!in_array($urlCategoria . '/' . ltrim($proximaPaginaLink, '/'), $pages)) {
                        $url = $urlCategoria . '/' . ltrim($proximaPaginaLink, '/');
                        array_push($pages, $url);
                    }
                }
            } while ($url);
    
            array_push($result, [$urlCategoria, $produtos]);
        }
    
        return $result;
    }
}
