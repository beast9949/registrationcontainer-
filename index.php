<?php 
error_reporting(0);
    include_once('includes/functions.php');
	include_once('conn.php');

	if(isset($_SESSION['email_google'])){ $a=$_SESSION['email_google']; };
	
//echo $a;
if($a!='' && $a!=NULL){
$q1="SELECT * FROM tbl_user where email='$a'";	
$query=mysqli_query($conn,$q1);
$res=mysqli_fetch_assoc($query);
}else{
	$res['email'] =='';
}

$_SESSION['uids']=$res['id'];

	if($_COOKIE['useid'] !='')
{
 $user_id= $_COOKIE['useid'];
 
}else if($res['email'] !='' )
{
	$user_id=$_SESSION['uids'];	
}
else{
	 $user_id= $_SESSION['userId']; 
}

	$timeNow=date('Y-m-d');

if($_COOKIE['times'] !=$timeNow)
{
					$ip_server= $_SERVER['REMOTE_ADDR']; 
			function get_operating_system() {
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $operating_system = 'Unknown Operating System';

//Get the operating_system
    if (preg_match('/linux/i', $u_agent)) {
        $operating_system = 'Linux';
    } elseif (preg_match('/macintosh|mac os x|mac_powerpc/i', $u_agent)) {
      return  $operating_system = 'Mac';
    } elseif (preg_match('/windows|win32|win98|win95|win16/i', $u_agent)) {
      return  $operating_system = 'Windows';
    } elseif (preg_match('/ubuntu/i', $u_agent)) {
      return  $operating_system = 'Ubuntu';
    } elseif (preg_match('/iphone/i', $u_agent)) {
      return  $operating_system = 'IPhone';
    } elseif (preg_match('/ipod/i', $u_agent)) {
      return  $operating_system = 'IPod';
    } elseif (preg_match('/ipad/i', $u_agent)) {
      return  $operating_system = 'IPad';
    } elseif (preg_match('/android/i', $u_agent)) {
      return  $operating_system = 'Android';
    } elseif (preg_match('/blackberry/i', $u_agent)) {
      return  $operating_system = 'Blackberry';
    } elseif (preg_match('/webos/i', $u_agent)) {
       return $operating_system = 'Mobile';
    }else{
		return $operating_system ;
	}
    

}
$os=get_operating_system();
date_default_timezone_set('Asia/Kolkata');
 $time= date("d-m-Y H:i:s") ; 
$_SESSION['time']=$time;
	//for information from profile
                 // $user_id = $_SESSION['userId'];
                  $curl = curl_init();

                  curl_setopt_array($curl, array(
                    CURLOPT_URL => app_url()."/api3/user_profile_show",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => "{\r\n\r\n\"operation\":\"user_profile_show\",\r\n\r\n\"userid\":\"$user_id\",\r\n\r\n\"api_key\":\"cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s\"\r\n\r\n}",
                    CURLOPT_HTTPHEADER => array(
                      "Content-Type: application/json",
                      "Postman-Token: 9f4c60f3-df44-40ac-9fb1-5f0514ed634d",
                      "cache-control: no-cache"
                    ),
                  ));

                  $response = curl_exec($curl);
				
                  curl_close($curl);

                
                    $jsondecode =  json_decode($response);
				//	print_r($jsondecode);exit;
                    $user_info = $jsondecode->data;
					$state=$user_info->state;
					$city=$user_info->city;
					$country=$user_info->country;
					$SMC_college=$user_info->college_name;
					$SMC_College_id=$user_info->college_id;
					$pref_college=$user_info->preferred_college;
					$country_code=$user_info->country_code;
					
					

						if($SMC_college=='')
						{
							$college=$pref_college;
						}
						else 
						{
							$college=$SMC_college;
						}
			$url= app_url()."/api3/logs";
$data=array( 'operation'=>'logs',
'App_Name'=>'Campus Radio',
'UserID'=>$user_id,
'Action'=>'Login', //Login,Logout,Registration,Update
'PlaylistItemID'=>'',
'CategoryID'=>'',
'ChannelID'=>'',
'ChannelCategoryName'=>'',
'ActionTime'=>$time, //28-04-2020 15:35:59
'ActionDuration'=>'',//28-04-2020 15:45:59
'DeviceName'=>'Web',
'IPAddress'=>$ip_server,
'OSVersion'=>$os,
'CountryCode'=>$country_code,
'PosLat'=>'',
'PosLong'=>'',
'college_id'=>$SMC_College_id,
'college_name'=>$college,
'city'=>$city,
'state'=>$state,
'country'=>$country,
'api_key'=>'cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s');
$res_cat = get_curl_result($url,$data);

setcookie ('times',$timeNow,time() +10 * 365 * 24 * 60 * 60);
}
	//echo $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>  Campus Radio</title>	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
	  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src='https://kit.fontawesome.com/a076d05399.js'></script>
	<style>
	

	html{
		font-size:100%;
	}
	
body{
	/*background-image: url('back.png');*/
    background-repeat: no-repeat;
	background-attachment: fixed; 
  <!--background-size: 100% 100%;-->
  height:120px;
}


.fixed-header{
	position: relative;
	background-image: url('back.png');
	color: #fff;
	height:100px;
}
.fixed-footer{	
	position: relative;
	background:rgba(0,0,0,0.5);
	padding: 0px 16px;
	color: #fff;
	height:auto;
	
}




nav a{
	color: #fff;
	text-decoration: none;
	padding: 7px 25px;
	display: inline-block;
}
*{
  box-sizing: border-box;
}

body {
  background-color: #f1f1f1;
  padding: 0px;
 font-family: Arial;
}





/* Slideshow container */
.slideshow-container{
  height:150px;
position:relative;
  margin: auto;
  width: auto;
  padding-left:auto;
  
 
}



p{

    font-size: 20px;
	 text-align: left;
}
#search_btn:hover{
	background:#ccc;
}
.dot {
  height: 10px;
  width: 10px;
  background-color: green;
  border-radius: 50%;
  display: inline-block;
}
.dot1{
  height: 10px;
  width: 10px;
  background-color:red;
  border-radius: 50%;
  display: inline-block;
}

.work.fa{
  color: #0000ff;
  font-size:20px;
}

.nwork.fa{
  color: #0f0f0f;
  font-size:20px;
}

button {
    background-color: Transparent;
    background-repeat:no-repeat;
    border: none;
    cursor:pointer;
    overflow: hidden;
    outline:none;
}
</style>
</head>
</html>