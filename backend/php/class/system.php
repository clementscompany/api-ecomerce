<?php

include_once "../../php/api/functions/functions.php";
class ecomerce{

    private $dbHost = "localhost";
    private $dbUsername = "root";
    private $dbPassword = "";
    private $dbName = "arcam_ecommerce";
    private $conn;
    private $acces_key = "488721william@";
    
    
    
    public function connect(){
        $this->conn = mysqli_connect($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName);

        if ($this->conn == true) {

            return $this->conn;

        } else{

            return false;

        }
    }


    // (((((((((((((((((((((((((((((((((((((((((((((Autentications))))))))))))))))))))))))))))))))))))))))))))))) //

    //login Admin //
    public function loginAdmin($username, $password){
        $response = [];
        try {
            if ($this->connect()) {
                $sql = $this->conn->prepare("SELECT password FROM admin WHERE user_name = ?");
                $sql->bind_param("s", $username);
                if ($sql->execute()) {
                    $result = $sql->get_result();
                    if ($result->num_rows > 0 ) {
                        # code...
                    } else{
                        $response['error'] = "Usuario ou senha incorrecto!";
                    }
                }
            }
        } catch (Exception $th) {
            $response['error'] = "Erro: " . $th->getMessage();
        }
    }

    // jwt ADM
    public function jwtAtuenticate($jwt){
        if (decodeJWTadmin($jwt, $this->acces_key)) {
            return true;
        } else{
            return false;
        }
    }

    public function jwtAtuenticateClient($jwt){
        if (decodeJWTclient($jwt, $this->acces_key)) {
            return true;
        } else{
            return false;
        }
    }
    // ACCES ROOT
    public function authenticationMaster($username, $password) {
        $response = [];
        try {
            if ($this->connect()) {
                $system_get_data = $this->conn->prepare("SELECT * FROM system_settings WHERE setting_name = ?");
                $system_get_data->bind_param("s", $username);
                if($system_get_data->execute()){
                    $result = $system_get_data->get_result()->fetch_assoc();

                    if (password_verify($password, $result['setting_value'])) {
                      
                        $data_expire = time() + 7 * 24 * 60 * 60;
                        $token = genereteJWT_admin($username, $data_expire, $this->acces_key);

                        $response['sucess'] = "Dados confirmados com sucesso!";
                        $response['token'] = $token;
       
                       
                    } else{
                        $response['error'] = "Usuario ou senha errada!";
                    }
                    
                } else{
                    $response['error'] =  "Erro desconhecido!";
                }
               
            } else {
                $response['error'] = "Erro na conexão com o banco de dados.";
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }
    



// (((((((((((((((((((((((((((((((((((((((((((((((((APPLICATION)))))))))))))))))))))))))))))))))))))))))))))))))//


//lOGIN cLIENT
public function loginClient($email,$password){
    $response = [];
    try {
        if ($this->connect()) {
            $get_loged_Data = $this->conn->prepare("SELECT * FROM clientes WHERE email = ? OR telefone = ?");
            $get_loged_Data->bind_param("ss", $email,$email);
            $get_loged_Data->execute();
            $result = $get_loged_Data->get_result();
            if ($result->num_rows > 0 ) {
                $pass = $result->fetch_assoc();
                if (password_verify($password, $pass['senha'])) {
                    $data_expire = time() + 7 * 24 * 60 * 60;
                    $response['sucess'] = "Dados confirmados com sucesso!";
                    $response['token'] = genereteJWT_client($email,$data_expire,$this->acces_key,$pass['id']);
                } else{
                    $response['error'] = "Email ou senha incorrectos!";
                }
            } else{
                $response['error'] = "Email ou senha incorrectos!";
            }
        }
    } catch (Exception $th) {
        $response['error'] = $th->getMessage();
    }
    return $response;
}

// (((((((((((((((((((((((((((((((((((((((((((((((((List Data)))))))))))))))))))))))))))))))))))))))))))))))))//
    public function listData(){
        $response = [];
        try {
            if ($this->connect()) {
                $sql = $this->conn->prepare("SELECT * FROM categoria ORDER BY id DESC");
                $sql2 = $this->conn->prepare("SELECT * FROM produtos ORDER BY id DESC");
                $sql3 = $this->conn->prepare("SELECT * from catalogo INNER JOIN produtos 
                ON produtos.id = catalogo.produto_id");
                
                $total_pedidos = $this->conn->prepare("SELECT COUNT(*) AS totalpedidos FROM pedido");
                $total_pedidos->execute();
                $result_total_pedidos = $total_pedidos->get_result();
                $data_total = $result_total_pedidos->fetch_assoc();
                $response['totalpedidos'] = $data_total['totalpedidos'];


                if ($sql3->execute()) {
                    $store_produtos = $sql3->get_result();
                    if ($store_produtos->num_rows > 0 ) {
                       while ($store = $store_produtos->fetch_assoc()) {
                            $response['loja']['produtos'][] = $store;
                       }
                    } else{
                        $response['emptyStore'][] = "Nenhum registro!";
                        
                    }
                }

                if ($sql2->execute()) {
                    $categoria_result = $sql2->get_result();
                    if ($categoria_result->num_rows > 0) {
                        while ($data_categoria = $categoria_result->fetch_assoc()) {
                            $response['produtos'][] = $data_categoria;
                        }
                    } else{
                        $response['emptyProdutos'][] = "Nenhum registro!";
                    }
                    
                }
                if ($sql->execute()) {
                    $result = $sql->get_result();
                    if ($result->num_rows > 0) {
                        while ($data = $result->fetch_assoc()) {
                            $response['categorias'][] = $data;
                        }
                    } else{
                        $response['empryCategorias'][] = "Nenhum reistro!";
                    }
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    //LISTAR PEDIDOS 
    public function listarPedidos($limit, $admin){
       $response  = [];
        try {
            if($this->connect()){
                if (decodeJWTadmin($admin, $this->acces_key)) {
                    if ($limit < 300) {
                        $limit = 300;
                    } 
                        $getDaata = $this->conn->prepare(
                            "SELECT 
                            pedido.id,
                            pedido.user_id,
                            pedido.status,
                            pedido.total_pedido,
                            pedido.method_pagamento,
                            pedido.date_pedido,
                            cl.nome
                            FROM pedido INNER JOIN clientes AS cl 
                            ORDER BY pedido.id DESC LIMIT ?"
                            );
                    
                        $getDaata->bind_param("i", $limit);
                        $getDaata->execute();
                        $result = $getDaata->get_result();

                        if ($result->num_rows > 0 ) {
                            while($data = $result->fetch_assoc()){
                                $response['pedidos'][] = $data;
                            }
                        } else{
                            $response['error'] = "Sem registros!";
                        }
                    
                } else{
                    $response['error'] = "Acosso negado!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    public function processarPedido($id){
        $response = [];
        try {
            if($this->connect()){
                $get_pedido = $this->conn->prepare("SELECT * FROM pedido WHERE pedido.id = ?");
                $get_pedido->bind_param("i", $id);
                $get_pedido->execute();
                $result_pedido = $get_pedido->get_result();
                if ($result_pedido->num_rows > 0 ) {
                    $dta_pedido = $result_pedido->fetch_assoc();
                    $id_pedido = $dta_pedido['id'];
                    $usr_pedido = $dta_pedido['user_id'];
                    $location_id = $dta_pedido['endereco_entrega_id'];
                    $payment_method = $dta_pedido['method_pagamento'];
                    $status = $dta_pedido['status'];
                    $created_at = $dta_pedido['date_pedido'];
                    $total = $dta_pedido['total_pedido'];
                    $option_pedido = $dta_pedido['option_pedido'];


                    $selectData = $this->conn->prepare(
                    "SELECT 
                     pd.id,
                     pd.produto_id,
                     pd.user_id,
                     pd.quant,
                     prod.nome,
                     prod.preco,
                     prod.estoque
                        

                     FROM items_pedido AS pd INNER JOIN produtos AS prod
                     ON prod.id = pd.produto_id 

                     WHERE pd.pedido_id = ?"
                    );
                    $selectData->bind_param("i", $id_pedido);
                    if ($selectData->execute()) {

                        $response['pegamento'] = $payment_method;
                        $response['status'] = $status;
                        $response['data'] = $created_at;
                        $response['total'] = $total;
                        $response['opcao'] = $option_pedido;

                        $dataResult = $selectData->get_result();
                        if ($dataResult->num_rows > 0) {

                            $get_user_data = $this->conn->prepare(
                                "SELECT 
                                nome,
                                sobrenome, 
                                email, 
                                telefone 
                                FROM clientes WHERE clientes.id = ?"
                                );

                            $get_user_data->bind_param("i", $usr_pedido);
                            $get_user_data->execute();
                            $result_user_data = $get_user_data->get_result();
                            if ($result_user_data->num_rows > 0 ) {
                                $data_user = $result_user_data->fetch_assoc();
                                $response['datauser'] = $data_user;
                            } else{
                                $response['datauser'] = "Ususrio desconhecido!";
                            } 
                            
                            //get Location 
                            $get_location_pedido = $this->conn->prepare(
                                "SELECT * FROM address WHERE address.id = ? AND user_id = ?"
                            );
                            $get_location_pedido->bind_param("ii",$location_id, $usr_pedido);
                            $get_location_pedido->execute();
                            $result_location = $get_location_pedido->get_result();
                            if ($result_location->num_rows > 0 ) {
                                $location = $result_location->fetch_assoc();
                                $response['location'] = $location;
                            }

                            //sending data 
                            while ($data = $dataResult->fetch_assoc()) {
                                $response['sucess'][] = $data;
                            }
                        } else{
                            $response['error'] = "Nenhum registro encontrado!";
                        }
                    } else{
                        $response['error'] = "Erro desconhecido!";
                    }
                } else{
                    $response['error'] = "Nenhum registro encontrado!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    public function atualizarStatusPedido($id,$status){
        $response = [];
        try {
            if ($this->connect()) {
                $update = $this->conn->prepare("UPDATE pedido SET status = ? WHERE pedido.id = ?");
                $update->bind_param("si", $status ,$id);
                if ($update->execute()) {
                    $response['sucess'] = "status atualizado!";
                } else{
                    $response['error'] = "Erro ao atualizar o status";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    public function fecharVenda($id){
       $response = [];
        try {
            if ($this->connect()) {
            $get_pedido = $this->conn->prepare("SELECT * FROM pedido WHERE pedido.id = ? LIMIT 1");
            $get_pedido->bind_param("i", $id);

                if ($get_pedido->execute()) {
                    $result_pedio = $get_pedido->get_result();
                    if ($result_pedio->num_rows > 0 ) {
                        $data_pedido = $result_pedio->fetch_assoc();
                        $id_pedido = $data_pedido['id'];

                        $id_user = $data_pedido['user_id'];
                        $total_pedido = $data_pedido['total_pedido'];
                        $data = $data_pedido['date_pedido'];
                        $new_status = "Entregue!";
                        $metodo_pagamaneto = $data_pedido['method_pagamento'];
                        $endereco_entrega_id = $data_pedido['endereco_entrega_id'];
                        $option_pedido = $data_pedido['option_pedido'];
               


                        $get_items = $this->conn->prepare("SELECT * FROM items_pedido  WHERE items_pedido.pedido_id = ?");
                        $get_items->bind_param("i", $id_pedido);
                        $get_items->execute();
                        $result_items = $get_items->get_result();

                        $create_venda1  = $this->conn->prepare(
                            "INSERT INTO 
                            venda (user_id, date_pedido, total_pedido, status, method_pagamento, endereco_entrega_id, option_pedido)

                            VALUES (?,?,?,?,?,?,?)
                            "
                            );
                         $create_venda1->bind_param("issssis",
                            $id_user, 
                            $data, 
                            $total_pedido, 
                            $new_status,
                            $metodo_pagamaneto,
                            $endereco_entrega_id,
                            $option_pedido
                        );

                        if ($create_venda1->execute()) {

                            $create_venda  = $this->conn->prepare(
                            "INSERT INTO items_venda (pedido_id, produto_id, user_id, quant)
                             VALUES (?,?,?,?)"
                            );
                            $update_estoque = $this->conn->prepare(
                                "UPDATE produtos SET estoque_max = estoque_max - ? WHERE produtos.id = ?"
                            );
                            if ($result_items->num_rows > 0 ) {
                                while ($data_item = $result_items->fetch_assoc()) {
                                
                                $create_venda->bind_param("iiii", 
                                    $data_item['pedido_id'], 
                                    $data_item['produto_id'], 
                                    $data_item['user_id'],
                                    $data_item['quant']
                                );
                                $update_estoque->bind_param("ii",
                                    $data_item['quant'], 
                                    $data_item['produto_id']
                                );

                                $result_end = $create_venda->execute();
                                $result_end2 = $update_estoque->execute();

                                }
                                
                                if ($result_end && $result_end2) {
                                    $response['sucess'] = "Venda Realizadacom sucesso!";
                                } else{
                                    $response['error']= "Erro ao realizar a Venda";
                                }
                            } else{
                                $response['error']= "Nenhum item encontrado!";
                            }
                        }

                    } else{
                        $response['error']= "Nenhum registro encontrado!";
                    }
                } else{
                    $response['error']= "Erro desconhecido!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        } 
        return $response;
    }

    public function buscarTotal($id_user) {
        $response = [];
        try {
            if ($this->connect()) {
                $id_user = decodeJWTclient($id_user, $this->acces_key);
                $get_data = $this->conn->prepare(
                    "SELECT COUNT(*) as totalpedidos FROM pedido 
                    WHERE status = ? 
                    AND user_id = ?"
                );
                $get_car_count = $this->conn->prepare(
                    "SELECT COUNT(*) AS carrinho FROM shopping_cart WHERE user_id = ?"
                );
                $get_car_count->bind_param("i", $id_user);
                if ($get_car_count->execute()) {
                    $count_car = $get_car_count->get_result();
                    $carrimho = $count_car->fetch_assoc();
                    $response['totalcarrinho'] = $carrimho['carrinho'];
                }
                $status = "Pendente";
                $get_data->bind_param("si", $status, $id_user);

                if ($get_data->execute()) {
                    $result = $get_data->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $response['totalpedidos'] = $row['totalpedidos'];
                    }
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        } 
        return $response;
    }
    
    // Busca por id 
    public function getProduct($id){
        $response = [];
        try {
            if($this->connect()){
                $sql = $this->conn->prepare(
                "SELECT catalogo.nome AS titulo, 
                catalogo.descricao, 
                pd.id, 
                pd.nome, 
                pd.status, 
                pd.estoque, 
                pd.preco,
                pd.imagem
                from catalogo INNER JOIN produtos AS pd
                ON pd.id = catalogo.produto_id WHERE catalogo.produto_id = ?");
                $sql->bind_param("i", $id);

                if ($sql->execute()) {
                    $result = $sql->get_result();
                    if ($result->num_rows > 0) {
                        $data = $result->fetch_assoc();
                        $response['sucess'] = $data;
                    } else{
                        $response['error'] = "Produto nao encontrado!";
                    }
                    
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    public function confirmarPedido($location, $idUser){
        $response = [];
        try {
            if ($this->connect() && decodeJWTclient($idUser, $this->acces_key)) {
                $sql = $this->conn->prepare("SELECT * from address WHERE address.id = ?");
                $sql->bind_param("i", $location);
                $sql->execute();
                $result = $sql->get_result();
                
                if ($result->num_rows > 0) {
                    $data = $result->fetch_assoc();
                    $response['address'] = $data;
                    $response['pedido'] = $this->buscarItemCart($idUser);
                }
            } else{
                $response['error'] = "Produto não enconrado!";
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    public function buscarItemCart($idUser){
        $response = [];
        try {
            if($this->connect() && decodeJWTclient($idUser, $this->acces_key)){
                $idUser = decodeJWTclient($idUser, $this->acces_key)['id'];
                $getData = $this->conn->prepare("SELECT 
                shopping_cart.product_id,
                shopping_cart.quantity,
                pd.nome as nomeproduto,
                pd.preco,
                pd.estoque

                from shopping_cart
                JOIN produtos as pd ON pd.id = shopping_cart.product_id
                WHERE shopping_cart.user_id = ?");

                $getData->bind_param("i", $idUser);
                $getData->execute();
                $result = $getData->get_result();
                
                if ($result->num_rows > 0) {
                    while($data = $result->fetch_assoc()){
                        $response['sucess'][] = $data;
                        $quantidade[] = intval($data['quantity']) * intval($data['preco']);
                    }

                    $quantidade = array_sum($quantidade);
                    $quantidade = number_format($quantidade, 2, ',', '.');
                    $response['total'] = $quantidade;
                } else{
                    $response['error'] = "Produto nao encontrado!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    public function getLocationUser($id){
        $response = [];
        try {
            if ($this->connect() && decodeJWTclient($id, $this->acces_key)) {
                $id = decodeJWTclient($id, $this->acces_key)['id'];
                $sql = $this->conn->prepare("SELECT * FROM address WHERE address.user_id = ? ORDER BY id DESC");
                $sql->bind_param("i", $id);
                if ($sql->execute()) {
                    $result = $sql->get_result();
                    if ($result->num_rows > 0) {
                        while($data = $result->fetch_assoc()){
                            $response['datauser'][] = $data;
                        }
                    }
                } 
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    public function listarPedido($user_id){
        $response = [];
        try {
            if($this->connect()){
                $user_id = decodeJWTclient($user_id, $this->acces_key)['id'];
               $pedidos = $this->conn->prepare("SELECT pedido.id as pedidoid,
               pedido.date_pedido,
               pedido.total_pedido,
               pedido.endereco_entrega_id,
               pedido.created_at,
               pedido.status AS status_pedido

               FROM 
               pedido

               WHERE pedido.user_id = ?
               ");
               $pedidos->bind_param("i", $user_id);
               $pedidos->execute();
               $result = $pedidos->get_result();
                if ($result->num_rows > 0) {
                 while($data = $result->fetch_assoc()){
                    $response['pedidos'][] = $data;
                 }
                } else{
                    $response['error'] = "Sem registros!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }
    // (((((((((((((((((((((((((((((((((((((((((((((((Delete Data))))))))))))))))))))))))))))))))))))))))))))))//

    //delecar carrinhoItem id
    public function deletarCarrinhoItem($id, $token){
        $response = [];
        try {
            if ($this->connect() && decodeJWTclient($token, $this->acces_key)) {
                $id_user = decodeJWTclient($token, $this->acces_key)['id'];
                $sql = $this->conn->prepare("DELETE FROM shopping_cart WHERE product_id = ? AND user_id = ? ");
                $sql->bind_param("ii", $id, $id_user);
                if ($sql->execute()) {
                    $response['succes'] = "Item eliminado!";
                } else{
                    $response['error'] = "Erro desconhecido!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }

        return $response;
    }

    public function deletarPedido($id, $user_id){
        $response = [];
        try {
            if ($this->connect()) {
             $user = decodeJWTclient($user_id, $this->acces_key)['id'];
             $delete_items = $this->conn->prepare("DELETE FROM items_pedido WHERE pedido_id = ? AND user_id = ?");
             $delete_pedido = $this->conn->prepare("DELETE FROM pedido WHERE pedido.id = ? AND user_id = ?");
             $delete_pedido->bind_param("ii", $id, $user);
             $delete_items->bind_param("ii", $id, $user);
             if ($delete_items->execute()) {
                $sucess = $delete_pedido->execute();
                if ($sucess) {
                    $response['sucess'] = "Eliminado!";
                } else {
                    $response['error'] = "Erro ao eliminar o item";
                }
            } else{
                 $response['error'] = "Erro ao eliminar o item";
             }
            }
        } catch (Exception $th) {
            $response['error'] = "Erro ao eliminar o item";
        }
        return $response;
    }

    // (((((((((((((((((((((((((((((((((((((((((((((((Post Data))))))))))))))))))))))))))))))))))))))))))))))//
    //cadastrar produtos...//
    public function cadastrarProdutos($nome,$preco,$status,$categoria_id,$image){
        $response = [];
        try {
            if($this->connect() == true){
                $sql1 = $this->conn->prepare("SELECT id FROM produtos WHERE nome = ? AND categoria_id = ?");

                $sql1->bind_param("si", $nome, $categoria_id);
                $sql1->execute();
                $result = $sql1->get_result();

                if($result->num_rows > 0){
                    $response['error'] = "Este produto ja foi cadstrado!";

                } else{
                    $sql = $this->conn->prepare("INSERT INTO produtos (nome,preco,status,categoria_id,imagem)
                    VALUES (?,?,?,?,?)");
                
                    $sql->bind_param("sssss",$nome,$preco,$status,$categoria_id,$image);

                    if($sql->execute()){
                        $response['sucess'] = "Produto cadastrado com sucesso!";
                    } else{
                        $response['error'] = "Erro ao cadastrar os dados!";
                    }
                }

            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response; 
    }

    ///Cadastrar categoria///
    public function cadastrarCategoria($name) {
        $response = [];
        try {
            if ($this->connect() == true) {
                $sql2 = $this->conn->prepare("SELECT id FROM categoria WHERE nome = ?");
                $sql2->bind_param("s", $name);
                $sql2->execute();
                $result = $sql2->get_result();
    
                if ($result->num_rows > 0) {
                    $response['error'] = "Esta categoria já foi cadastrada!";
                } else {

                    $sql = $this->conn->prepare("INSERT INTO categoria (nome) VALUES (?)");
                    $sql->bind_param("s", $name);
                    if ($sql->execute()) {
                        $response['sucess'] = "Categoria adicionada!";
                    } else {
                        $response['error'] = "Erro ao cadastrar!";
                    }
                }
            }
        } catch (Exception $err) {
            $response['error'] = $err->getMessage();
        }
        return $response;
    }

    public function setCatalogo($nome, $produto_id, $descricao){
        $response = [];
        try {
            if ($this->connect()) {
                $getData = $this->conn->prepare("SELECT * from catalogo WHERE catalogo.produto_id = ?");
                $setData = $this->conn->prepare("INSERT INTO catalogo (nome,produto_id,descricao)
                VALUES (?,?,?)");
                $setData->bind_param("sis", $nome, $produto_id,$descricao);
                $getData->bind_param("i", $produto_id);
                if ($getData->execute()) {
                    $result = $getData->get_result();
                    if($result->num_rows > 0){
                        $response['error'] = "Este produto já foi adicionado!";
                    } else{
                        if ($setData->execute()) {
                            $response['sucess'] = "Adicionado com sucesso!";
                        } else{
                            $response['error'] = "Erro desconhecido!";
                        }
                    }
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    public function set_password_root($password){
        $response = [];
        try {
            if($this->connect()){
                $setting_name = "root";
                $hash_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = $this->conn->prepare("INSERT INTO system_settings (setting_name,setting_value)
            VALUES (?,?)");

            $sql->bind_param("ss",$setting_name, $hash_password);

                if ($sql->execute()) {
                    $response['sucess'] = "Senha adicionada com sucesso!";
                } else{
                    $response['error'] = "erro desconhecido!";
                }

            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }
    
    public function adicionarMetodo($nome) {
        $response = [];
        try {
            if ($this->connect()) {
                $validar_metodo = $this->conn->prepare("SELECT id FROM metodos_pagamentos WHERE nome = ?");
                $validar_metodo->bind_param("s", $nome);
                $validar_metodo->execute();
                $result = $validar_metodo->get_result()->num_rows;
    
                if ($result > 0) {
                    $response['error'] = "Este metodo já foi adicionado!";
                } else {
                    $cadastrar_metodo = $this->conn->prepare("INSERT INTO metodos_pagamentos (nome) VALUES (?)");
                    $cadastrar_metodo->bind_param("s", $nome);
    
                    if ($cadastrar_metodo->execute()) {
                        $response['sucess'] = "Cadastrado com sucesso";
                    } else {
                        $response['error'] = "Erro desconhecido!";
                    }
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }


    //////////////////////////////// Cadastrar novo User-----

    //CADASTRO CLIENT
    public function cadastrarClient($nome,$sobrenome,$email,$senha,$telefone){
        $response = [];
        try {
            if($this->connect()){
                
                $get_user_data = $this->conn->prepare("SELECT id FROM clientes WHERE email = ? OR telefone = ?");
                $get_user_data->bind_param("ss", $email, $telefone);
                $get_user_data->execute();
                $result = $get_user_data->get_result()->num_rows;
                if ($result > 0) {
                    $response['error'] = "Estes dados estão sendo usados por outra pessoa";
                } else{
                    $senha = password_hash($senha, PASSWORD_DEFAULT);
                    $set_user_data = $this->conn->prepare(
                        "INSERT INTO clientes (nome, sobrenome, email, senha, telefone) 
                        VALUES (?,?,?,?,?)"
                    );
                    
                    $set_user_data->bind_param("sssss", $nome,$sobrenome,$email,$senha,$telefone);

                    if ($set_user_data->execute()) {
                        $response['sucess'] = "Cadastrado com sucesso!";
                    } else{
                        $response['error'] = "Erro desconhecido!";
                    }
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }


    //////////////////////////////// adicionar carrinho-----
    public function adicionarCarrinho($user_id, $id, $quantidade) {
        $response = [];
        try {
            if ($this->connect() && decodeJWTclient($user_id, $this->acces_key)) {

                $verify_quantity = $this->conn->prepare("SELECT estoque FROM produtos WHERE produtos.id = ?");
                $verify_quantity->bind_param("i", $id);
                $verify_quantity->execute();
                $result_quantity = $verify_quantity->get_result();
                
                if ($result_quantity->num_rows > 0) {
                    $quantidade_existente = $result_quantity->fetch_assoc();
                    $estq = intval($quantidade_existente['estoque']);
                    if ($quantidade <= $estq) {

                        $user_id = decodeJWTclient($user_id,$this->acces_key)['id'];
                        $verificar_cart = $this->conn->prepare("SELECT id FROM shopping_cart 
                        WHERE shopping_cart.product_id = ?");
                        $verificar_cart->bind_param("i", $id);
                        $verificar_cart->execute();
                        $result = $verificar_cart->get_result()->num_rows;
                        $verificar_cart->close();
                        
                        if ($result > 0) {
                            
                            $atualizarCart = $this->conn->prepare("UPDATE shopping_cart SET quantity = ? WHERE shopping_cart.product_id = ?");
                            $atualizarCart->bind_param("ii", $quantidade, $id);
                            if ($atualizarCart->execute()) {
                                $response['sucess'] = "Item atualizado com sucesso!";
                            } else {
                                $response['error'] = "Erro ao atualizar o item!";
                            }
                            $atualizarCart->close();
                        } else {
                
                            $inserirCart = $this->conn->prepare("INSERT INTO shopping_cart(user_id, product_id, quantity) VALUES (?, ?, ?)");
                            $inserirCart->bind_param("iii", $user_id, $id, $quantidade);
                            if ($inserirCart->execute()) {
                                $response['sucess'] = "Item adicionado ao carrinho!";
                            } else {
                                $response['error'] = "Erro ao adicionar o item!";
                            }
                            $inserirCart->close();
                        }
                    } else{
                        $response['error'] = "A quantidade solicitada é superior ao estoque existente!";
                    }
                    
                } else{
                    $response['error'] = "Erro desconhecido!";
                }

            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }

    /////////////////------Faazer pedido
    public function realizarPedido($location, $idUser, $methodPayment, $total, $option){
        $response = [];
        try {
            if($this->connect()){

                $pedido = $this->conn->prepare("INSERT INTO pedido 
                (user_id,
                total_pedido,
                status,
                method_pagamento,
                endereco_entrega_id,
                option_pedido)

                VALUES (?,?,?,?,?,?)");

                $status = "Pendente";
                $endereco_entrega_id = $location;
                $id_user = decodeJWTclient($idUser, $this->acces_key)['id'];



                $pedido->bind_param("isssis",
                $id_user,
                $total,
                $status,
                $methodPayment,
                $endereco_entrega_id,
                $option );

                if ($pedido->execute()) {

                    $id_pedido = $this->conn->prepare("SELECT pedido.id FROM pedido WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                    $id_pedido->bind_param("i", $id_user);
                    $id_pedido->execute();
                    $result = $id_pedido->get_result();
                    $id_pedido_get = $result->fetch_assoc();

                    $reduzir_estoque = $this->conn->prepare(
                        "UPDATE produtos SET estoque = estoque - ? WHERE produtos.id = ?"
                    );

                    $sql = $this->conn->prepare("SELECT * FROM shopping_cart WHERE user_id = ?");
                    $sql->bind_param("i", $id_user);
                    $sql->execute();
                    
                    if($sql){
                        $data_result = $sql->get_result();
                        $sql2 = $this->conn->prepare("INSERT INTO items_pedido 
                        (pedido_id, produto_id, user_id, quant) 
                        VALUES (?,?,?,?)");

                        if ($data_result->num_rows > 0) {
                            while($data = $data_result->fetch_assoc()){
                                
                                $sql2->bind_param("iiis",
                                    $id_pedido_get['id'],
                                    $data['product_id'],
                                    $data['user_id'],
                                    $data['quantity']
                                );
                                $sql2->execute();

                                $reduzir_estoque->bind_param("ii", $data['quantity'], $data['product_id']);
                                $reduzir_estoque->execute();
                            }

                            if ($sql2) {
                                $sql3 = $this->conn->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
                                $sql3->bind_param("i", $id_user);
                                if ($sql3->execute()) {
                                    $response['sucess'] = "Pedido realizado com sucesso!";
                                } else{
                                    $response['error'] = "Houve um erro";
                                }

                            } else{
                                $response['error'] = "Houve um erro";
                            }
                        }
                    }
                } else{
                    $response['error'] = "Houve um erro";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        return $response;
    }
    
    //////////////////////////////// adicionar Localização -----
    public function adicionarLocalizacao($provincia,$municipio,$bairro,$nome,$detalhes,$token){
        $response = [];
        try {
            if ($this->connect() && decodeJWTclient($token, $this->acces_key)){
                $id = decodeJWTclient($token, $this->acces_key)['id'];
                $sql = $this->conn->prepare("INSERT INTO address (user_id, provincia, municipio,bairro,nome,detalhes) VALUES (?,?,?,?,?,?)");
                $sql->bind_param("isssss", $id,$provincia,$municipio,$bairro,$nome,$detalhes);
                if ($sql->execute()) {
                    $response['sucess'] = "Adicionada com sucesso!";
                } else{
                    $response['error'] = "Erro desconhecido!";
                }
            }
        } catch (Exception $th) {
            $response['error'] = $th->getMessage();
        }
        
       return $response;
    }
    
}

// $sistema = new ecomerce;
// var_dump($sistema->fecharVenda(55));