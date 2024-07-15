<?php
use Vtiful\Kernel\Format;
 // No início do arquivo PHP
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type, Autorization, Token-Acces");
    header("Content-Type:Application/json");
   
require "../class/system.php";

    $inputs = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathName = basename($url);
    $response = [];
    $sistema = new ecomerce;
    $headers = getallheaders();
    $adminAcces = $headers['Autorization'];
    $clientAcces = $headers['Token-Acces'];
    $jsonData = json_decode(file_get_contents("php://input", true));

if(($_SERVER['REQUEST_METHOD'] === "POST")){


    if(!empty($pathName)){

        switch($pathName){
            case "produtos":

                if(isset($_FILES['image']) && !empty($_FILES['image']['name'])){

                    if ($sistema->jwtAtuenticate($adminAcces)) {
                            
                        $image_name = $_FILES['image']['name'];
                        $temp_name = $_FILES['image']['tmp_name'];
                        $permissions = ['png', 'jpg', 'jpeg'];
                        $extension = pathinfo($image_name, PATHINFO_EXTENSION);
                        $rand = rand(time(), 10000);
                        $novo_nome = $rand.$image_name;


                        if(!in_array($extension, $permissions)){
                            $response['error'] = "Tipo de imagem inválida!";
                        } else{

                            if (
                                !empty($inputs['nome']) && !empty($inputs['preco']) &&
                            
                                !empty($inputs['categoria'])
                            ) {
                                
                                $response['cadastro'] = $sistema->cadastrarProdutos(
                                    $inputs['nome'], 
                                    $inputs['preco'], 
                                    "Esgotado", 
                                    $inputs['categoria'],
                                    $novo_nome
                                );
                                if ($response['cadastro']['sucess']){

                                move_uploaded_file($temp_name,"../uploads/images/produtos/".$novo_nome);
                                    
                                }
                            } else{
                                $response ['error'] = "Preencha todos os campos...!";
                            }
                        }
                    }
                }
            break;

            case "categorias":
                if (!empty($inputs['categoria'])) {
                    $response['post'] = $sistema->cadastrarCategoria($inputs['categoria']);
                }
            break;

            case "root":
                if (!empty($inputs['user_name'] && !empty($inputs['password']))) {
                    $response['login'] = $sistema->authenticationMaster(
                        $inputs['user_name'],
                        $inputs['password']
                    );
                }
            break;

            case "catalogo":
                $response['catalogo'] = $sistema->setCatalogo(
                    $jsonData->catalogo->name, 
                    $jsonData->catalogo->id,
                    $jsonData->catalogo->description
                );
            break;
            
            /////////---------- APLICATIVO
            case "applogin":
                if (!empty($jsonData->username) && !empty($jsonData->password)) {

                    $response['login'] = $sistema->loginClient($jsonData->username,$jsonData->password);
                }
            break;

            case "addressapp":
                $response['address'] = $sistema->adicionarLocalizacao(
                    $inputs['provincia'],
                    $inputs['municipio'],
                    $inputs['bairro'],
                    $inputs['nome'],
                    $inputs['detalhes'],
                    $clientAcces
                );
            break;

            case "addcardshop":
                $response['addcard'] = $sistema->adicionarCarrinho(
                    $clientAcces,
                    $jsonData->productid,
                    $jsonData->quantidade
                );
            break;

            case "clientcadastro":
                if (
                    !empty($jsonData->cadastro->nome) &&
                    !empty($jsonData->cadastro->sobrenome && 
                    !empty($jsonData->cadastro->email) && 
                    !empty( $jsonData->cadastro->telefone)) &&  
                    !empty($jsonData->cadastro->senha)
                    ) {
                    
                    if (filter_var($jsonData->cadastro->email, FILTER_VALIDATE_EMAIL)) {

                        $response['cadastro'] = $sistema->cadastrarClient(
                            $jsonData->cadastro->nome,
                            $jsonData->cadastro->sobrenome,
                            $jsonData->cadastro->email,
                            $jsonData->cadastro->senha,
                            $jsonData->cadastro->telefone
                        );

                    } else{
                        $response['cadastro']['mailerror'] = "Email incorrecto!";
                    }
                }
            break; 

            case "deleteitemcar":
                if (!empty($jsonData->id)) {
                    $response['delete'] = $sistema->deletarCarrinhoItem($jsonData->id, $clientAcces);
                }
            break;

            case "deletepedido":
                if (!empty($jsonData->produtoid)) {
                    $response['delete'] = $sistema->deletarPedido($jsonData->produtoid,$clientAcces);
                }
            break;

            case "finishstore":
                $response['finish'] = $sistema->realizarPedido(
                    $jsonData->location,
                    $clientAcces,
                    $jsonData->metodo,
                    $jsonData->total,
                    $jsonData->option
            );
        }
    }

    echo json_encode($response);
}

// ((((((((((((((((SERVER GET )))))))))))))))) // 

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    switch($pathName){
        case "produtos":
            if ($sistema->jwtAtuenticate($adminAcces)) {
                $response['list'] = $sistema->listData();
            }
        break;

        case "listpedidos":
            if (isset($_GET['limit'])) {
                $response['listpedidos'] =   $sistema->listarPedidos($_GET['limit'], $adminAcces);
            }

        break;

        case "store":
            $response['list'] = $sistema->listData();
        break;

        case "search":
           if (isset($_GET['id']) && !empty($_GET['id'])) {
            $response['getdata'] = $sistema->getProduct($_GET['id']);
           }
        break;

        case "cartuser":
            if (isset($_GET['userid'])) {
                $id_user = $_GET['userid'];
                $response['carrinho'] = $sistema->buscarItemCart($id_user);
            }
        break;

        case "locationauser":
            if (isset($_GET['userid'])) {
                $response['location'] = $sistema->getLocationUser($_GET['userid']);;
            }
        break;  
        
        case "confirmordre":
            if (isset($_GET['location'])) {
                $response['confirm'] = $sistema->confirmarPedido($_GET['location'], $clientAcces);  
            }
        break;
        // confirmarPedido listordress

        case "listordress":
            if (isset($_GET['iduser'])) {
                $response['confirm'] = $sistema->listarPedido($_GET['iduser']);
            }
        break;
    }

    echo json_encode($response);
}


// Autorization
