<?php
//seesion is added by Sayali Balkawade
session_start();
function get_curl_result($url,$data)
{
    $ch = curl_init($url); 			
    $data_string = json_encode($data);    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_string)));$result = json_decode(curl_exec($ch),true);
    return $result;
}

function stripcslashes_ankur($value) {

    $value = is_array($value) ?
        array_map('stripcslashes_ankur', $value) :
    stripcslashes($value);

    return $value;
}


function mysqliescape_ankur($value) {

    $server_name = $_SERVER['SERVER_NAME'];
    Switch($server_name) {
        case "tplay.campusradio.rocks":
        $db_host = "148.72.88.25";
        $db_user = "tappcampusradio";
        $db_pass = "Bpsi@1234";
        $db_name = "tapp.campusradio.rocks";
        break;

        case "play.campusradio.rocks":
        $db_host = "148.72.88.25";
        $db_user = "app";
        $db_pass = "h4TngD}{i-9I";
        $db_name = "app.campusradio.rocks";
        break;

        default :
        $db_host = "localhost";
        $db_user = "root";
        $db_pass = "";
        $db_name = "test";
        break;
    }

    $conn=mysqli_connect($db_host, $db_user, $db_pass,$db_name) or die('Unable to establish a DB connection');
    $value = is_array($value) ?
        array_map('mysqliescape_ankur', $value) :
    mysqli_real_escape_string($conn, $value);
    return $value;
}


/**

 * First Read This Documents

 * app_url() is for dynamic purpose of app.campusradio.rocks url

 * isSecure() for http secure response

 * base_url() for this project base url

 * Below All Code Added By Saumya R Pani 

 * 

*/	

function app_url() {

    $issecure=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) ? "https" : 'http' .'://';

    $a= explode('.',$_SERVER['SERVER_NAME'])[0];

    $ab= $a[0];

    if($ab=='p'){

        return $issecure.'app.campusradio.rocks'; //for production

    }elseif($ab=='t'){

        return $issecure.'tapp.campusradio.rocks'; // for test

    }elseif($ab=='d'){

        return $issecure.'dapp.campusradio.rocks'; //for dev

    }elseif($ab=='l'){

        return $issecure.'localhost.appcampusradio.in'; // for localhost if user dns is different change url accrdingly

    }else{

        return $issecure.'localhost.appcampusradio.in';

    }
}

function isSecure() {

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) ? "https" : 'http' .'://';

}

function base_url() {

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) ? "https" : 'http' .'://'.$_SERVER['HTTP_HOST'];

}

function  remove_str($data)
{
    $arr = array(',','\'','[',']','{','}','(',')','alert','on','script','$','%','#','!','-','<','>','`','pro');
    $res= str_replace($arr , '', $data);
    return $res;
}
?>