<?php

include_once('includes/functions.php');
include_once('conn.php');

require 'google-api/vendor/autoload.php';
// Creating new google client instance
$client = new Google_Client();

// Enter your Client ID
$client->setClientId('155448222581-v652qvv9h6v7ft8v9b4kj30d57t9liis.apps.googleusercontent.com');
//76194456101-i5osf959c526le8i5fgdv7aro6ju0urq.apps.googleusercontent.com  (ClientID for StartupWorld)
//217740909396-8ick511s9cn15vegkg8859lu2ufcubhp.apps.googleusercontent.com  (ClientID for SmartCookie)
//294968797830-1nfrm77qh1b3u65tpir6pbd9inj1tm1n.apps.googleusercontent.com  (ClientID for Campus Tv)
//676851858260-itjvvcr7bi18hampvu590vvr0bedk1gv.apps.googleusercontent.com  (ClientID for Campus Radio)
// Enter your Client Secrect
$client->setClientSecret('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');
//s3nWSQ7rxTvFpKhRHoIk4aHL (Client Secrete for StartupWorld)
//Nj0VFwI7Het5xDDvzbRe6Xmc (Client Secrete for SmartCookie)
//05Zr7KGo2cqGjsIaijriWmTS (Client Secrete for Campus Tv)
//ZiVU6zZhpkCmN-_SufqlGUFv (Client Secrete for Campus Radio)
// Enter the Redirect URL

$server_name = $_SERVER['SERVER_NAME'];
switch($server_name) {
    case "tplay.campusradio.rocks":
    $client->setRedirectUri('http://tplay.campusradio.rocks/login.php');
    break;
    case "play.campusradio.rocks":
    $client->setRedirectUri('http://play.campusradio.rocks/login.php');
    break;
    default :
    $client->setRedirectUri('http://localhost.campusradio.in/login.php');
    break;
}

// Adding those scopes which we want to get (email & profile Information)
$client->addScope("email");
$client->addScope("profile");
////$client->setState("testingthestate");

if(isset($_GET['code'])):

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if(!isset($token["error"])){

    $client->setAccessToken($token['access_token']);

    // getting profile information
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    //print_r($google_account_info);
    // email 
    // familyName
    // givenName
    // id=103154684345041300619
    $g_id=$google_account_info->id;
    $givenName=$google_account_info->givenName;
    $familyName=$google_account_info->familyName;
    $emid=$google_account_info->email;
    $_SESSION['email_google']=$emid;

    // Storing data into database
    $id = $google_account_info->id."<br>";
    $full_name = $google_account_info->name."<br>";
    $email = $google_account_info->email."<br>";
    $profile_pic = $google_account_info->picture;

    $q1="SELECT * FROM tbl_user where email='$emid'";	
    $query=mysqli_query($conn,$q1);
    $res=mysqli_fetch_assoc($query);

    if($res['id'] !='')

    {
        $user_ids=$res['id'];
        $u_country_code=$res['country_code'];
        $u_smc_collegeID=$res['college_id'];
        $u_college=$res['college_name'];
        $u_state=$res['state'];
        $u_country=$res['country'];
        $u_city=$res['city'];

        $ip_server= $_SERVER['REMOTE_ADDR']; 
        function get_operating_system() {
            $u_agent = $_SERVER['HTTP_USER_AGENT'];
            $operating_system = 'Unknown Operating System';

            //Get the operating_system
            if (preg_match('/linux/i', $u_agent)) {
                $operating_system = 'Linux';
            } elseif (preg_match('/macintosh|mac os x|mac_powerpc/i', $u_agent)) {
                $operating_system = 'Mac';
            } elseif (preg_match('/windows|win32|win98|win95|win16/i', $u_agent)) {
                $operating_system = 'Windows';
            } elseif (preg_match('/ubuntu/i', $u_agent)) {
                $operating_system = 'Ubuntu';
            } elseif (preg_match('/iphone/i', $u_agent)) {
                $operating_system = 'IPhone';
            } elseif (preg_match('/ipod/i', $u_agent)) {
                $operating_system = 'IPod';
            } elseif (preg_match('/ipad/i', $u_agent)) {
                $operating_system = 'IPad';
            } elseif (preg_match('/android/i', $u_agent)) {
                $operating_system = 'Android';
            } elseif (preg_match('/blackberry/i', $u_agent)) {
                $operating_system = 'Blackberry';
            } elseif (preg_match('/webos/i', $u_agent)) {
                $operating_system = 'Mobile';
            }

            return $operating_system;
        }
        //$os=$operating_system;
        date_default_timezone_set('Asia/Kolkata');
        $time= date("d-m-Y H:i:s") ; 
        //Insert login logs	
        $url= app_url()."/api3/logs";
        $data=array( 'operation'=>'logs',
                    'App_Name'=>'Campus Radio',
                    'UserID'=>$user_ids,
                    'Action'=>'Login', //Login,Logout,Registration,Update
                    'PlaylistItemID'=>'',
                    'CategoryID'=>'',
                    'ChannelID'=>'',
                    'ChannelCategoryName'=>'',
                    'ActionTime'=>$time, //28-04-2020 15:35:59
                    'ActionDuration'=>'',//28-04-2020 15:45:59
                    'DeviceName'=>'Web',
                    'IPAddress'=>$ip_server,
                    'OSVersion'=>'Windows',
                    'CountryCode'=>$u_country_code,
                    'PosLat'=>'',
                    'PosLong'=>'',
                    'college_id'=>$u_smc_collegeID,
                    'college_name'=>$u_college,
                    'city'=>$u_city,
                    'state'=>$u_state,
                    'country'=>$u_country,
                    'api_key'=>'cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s');
        $res_cat = get_curl_result($url,$data);

    } else 
    {

        $req_url= app_url()."/api3/user_registration_google";
        $request1 = array('operation' => 'user_registration_google','username'=>$givenName,'email'=>$emid,
                          'api_key' =>'cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s');
        $res_cat1 = get_curl_result($req_url,$request1);
        print_r($request1);
        print_r($res_cat1);



        $a=$res_cat1['data'];
        $d=$a[0];
        $userID=$d['id'];
        echo $userID;
        $resp=$res_cat1['responseStatus'];
        if($resp==200)
        {
            //insert registration logs 
            $time= date("d-m-Y H:i:s") ; 
            $url2= app_url()."/api3/logs";
            $data2=array( 'operation'=>'logs',
                         'App_Name'=>'college radio',
                         'UserID'=>$userID,
                         'Action'=>'Register', //Login,Logout,Registration,Update
                         'PlaylistItemID'=>'',
                         'CategoryID'=>'',
                         'ChannelID'=>'',
                         'ChannelCategoryName'=>'',
                         'ActionTime'=>$time, //28-04-2020 15:35:59
                         'ActionDuration'=>'',//28-04-2020 15:45:59
                         'DeviceName'=>'Web',
                         'IPAddress'=>'',
                         'OSVersion'=>'',
                         'CountryCode'=>91,
                         'PosLat'=>'',
                         'PosLong'=>'',
                         'college_id'=>'',
                         'college_name'=>'',
                         'city'=>'',
                         'state'=>'',
                         'country'=>'',
                         'api_key'=>'cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s');
            $res_cat = get_curl_result($url2,$data2);
        }

    }
    header('location:index.php');
    exit();

}
else{
    //header('Location: test.php');
    echo "email not found";
    exit;
}

else: 
// Google Login Url = $client->createAuthUrl(); 
?>

<?php endif; ?>