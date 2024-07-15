<?php
function genereteJWT_admin($username, $data_expire, $acces_key){

    $header = [
        "alg" => "HS256",
        "typ" => "JWT",
    ];
    $header = json_encode($header);
    $header = base64_encode($header);

    $payload = [
        "username" => $username,
        "exp" => $data_expire,
        "permission"=>"root",
    ];
    $payload = json_encode($payload);
    $payload = base64_encode($payload);

    $assinature = hash_hmac("sha256", "$header.$payload", $acces_key, true);
    $assinature = base64_encode($assinature);
    $token = "$header.$payload.$assinature";

    return $token;
}

function genereteJWT_client($username,$data_expire, $acces_key,$id){
    $header = [
        'alg'=>"HS256",
        'typ'=>"JWT",
    ];
    $header = json_encode($header);
    $header = base64_encode($header);

    $payload = [
        'username'=>$username,
        'exp'=>$data_expire,
        'permission'=>'client',
        'userid'=>$id,
    ];

    $payload = json_encode($payload);
    $payload = base64_encode($payload);

    $assinature = hash_hmac("sha256", "$header.$payload", $acces_key, true);
    $assinature = base64_encode($assinature);

    $token = "$header.$payload.$assinature";
    return $token;
}

function decodeJWTclient($jwt, $acces_key){
    $arrJWT = explode(".", $jwt);
    $header = $arrJWT[0];
    $payload = $arrJWT[1];
    $assinature = $arrJWT[2];

    $header = base64_decode($header);
    $header = json_decode($header);

    $payload = base64_decode($payload);
    $payload = json_decode($payload);
    if ($header->alg == "HS256" && $header->typ == "JWT") {
        if ($payload->exp > time() && $payload->permission == "client") {
          
            $newHeader = json_encode($header);
            $newHeader = base64_encode($newHeader);

            $newPayLoad = json_encode($payload);
            $newPayLoad = base64_encode($newPayLoad);

            $newAssinature = hash_hmac("sha256", "$newHeader.$newPayLoad", $acces_key, true);
            $newAssinature = base64_encode($newAssinature);

            if ($newAssinature === $assinature){

                $sendData = base64_decode($newPayLoad);
                $sendData = json_decode($sendData);

                $data = [
                    'id'=>$sendData->userid,
                    'username'=>$sendData->username
                ];

                return $data;

            } else{
                return false;
            }
            
        } else{
            return false;
        }

    } else{
        return false;
    }
}

function decodeJWTadmin($jwt, $acces_key) {

    $arrayJWT = explode(".", $jwt);
    $heaer = $arrayJWT[0];
    $payload = $arrayJWT[1];
    $assignature = $arrayJWT[2];

    $newToken = hash_hmac("sha256", "$heaer.$payload", $acces_key, true);
    $newToken = base64_encode($newToken);

    if (!empty($jwt)) {
        if ($newToken === $assignature) {

            $payload = base64_decode($payload);
            $payload = json_decode($payload);
    
            if ($payload->exp > time() && $payload->username === "root" || $payload->permission === "root") {
                return true;
            } else {
    
                return false;
            }
        } else {
    
            return false;
            
        }
    } else{
        return false;
    }

}

function descontoProduto($percentagem, $preco_original){

    define('MAX_DESC', 80);

    if ($percentagem > MAX_DESC) {

        return false;

    } else{

        $desconto = $preco_original  * ($percentagem / 100);
        return $desconto;

    }
    

}

