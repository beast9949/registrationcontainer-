<?php

function app_url()
{
	$issecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) ? "https" . '://' : 'http' . '://';
	$a = explode('.', $_SERVER['SERVER_NAME'])[0];
	$ab = $a[0];
	if ($ab == 'a') {
		return $issecure . 'play.campusradio.rocks'; //for production
	} elseif ($ab == 't') {
		return $issecure . 'tplay.campusradio.rocks'; // for test
	} elseif ($ab == 'd') {
		return $issecure . 'dplay.campusradio.rocks'; //for dev
	} else {
		return $issecure . 'localhost.campusradio.in';
	}
}

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Headers: Content-Type");
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // The request is using the POST method
//    header("HTTP/1.1 200 OK");
    return;
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once "src/PHPMailer.php";
require_once "src/SMTP.php";
require_once "src/Exception.php";

require_once("Rest.inc.php");

class API extends REST
{

	public $data = "";
	const demo_version = false;

	private $db 	= NULL;
	private $mysqli = NULL;
	public function __construct()
	{
		// Init parent contructor
		parent::__construct();
		// Initiate Database connection
		$this->dbConnect();
		//error_reporting(E_ALL);
		error_reporting(E_ERROR | E_PARSE);
	}

	/*
		 *  Connect to Database
		*/
	private function dbConnect()
	{
		require_once("../includes/config.php");
		$this->mysqli = new mysqli($host, $user, $pass, $database);
		$this->mysqli->query('SET CHARACTER SET utf8');
	}

	/*
		 * Dynmically call the method based on the query string
		 */
	public function processApi()
	{
		$func = strtolower(trim(str_replace("/", "", $_REQUEST['x'])));
		if ((int)method_exists($this, $func) > 0)
			$this->$func();
		else
			$this->response('Ooops, no method found!', 404); // If the method not exist with in this class "Page not found".
	}

	/* Api Checker */
	private function checkConnection()
	{
		if (mysqli_ping($this->mysqli)) {
			//echo "Responses : Congratulations, database successfully connected.";
			$respon = array(
				'status' => 'ok', 'database' => 'connected'
			);
			$this->response($this->json($respon), 200);
		} else {
			$respon = array(
				'status' => 'failed', 'database' => 'not connected'
			);
			$this->response($this->json($respon), 404);
		}
	}

	private function get_posts()
	{

		include "../includes/config.php";
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if (isset($_GET['api_key'])) {

			$access_key_received = $_GET['api_key'];
			$features = $_REQUEST['channelstate'];
			$userid = $_REQUEST['userid'];
			$channel_language = $_REQUEST['channel_language'];
			$channel_geura = $_REQUEST['channel_geura'];
			if ($access_key_received == $api_key) {
				$sql = "select * from tbl_user where id='$userid'";
				$sqlresult = mysqli_query($connect, $sql);
				$queryrow = mysqli_fetch_array($sqlresult);
				$favourite_channel2 = $queryrow['favourite_channel'];
				$favourite_channel1 = explode(',', $favourite_channel2);
				if ($this->get_request_method() != "GET") $this->response('', 406);
				$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
				$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

				$offset = ($page * $limit) - $limit;

				$count_total = $this->get_count_result("SELECT COUNT(DISTINCT n.id) FROM tbl_channel n WHERE channel_visible=1");
				if (($features == 0 || $features == 1 || $features == 2 || $features == 3) && ($channel_language == '' && $channel_geura == '')) {
					$query = "SELECT distinct 
								n.id AS 'channel_id',
								n.category_id,
								n.channel_name, 
								n.channel_image, 
								n.channel_url,
								n.channel_description,
								n.channel_feature,
								c.category_name,
								n.channel_type,
								n.channel_player_type,
								k.favourite_channel
								
							FROM 
								tbl_channel n, 
								tbl_category c,
                                tbl_user	k							
								
							WHERE 
								n.category_id = c.cid AND n.channel_visible=1 AND n.channel_feature='$features' AND k.id='$userid' ORDER BY n.channel_order ASC, n.id DESC LIMIT $limit OFFSET $offset";
				} else if (($features == 0 || $features == 1 || $features == 2 || $features == 3) && ($channel_language != '' || $channel_geura != '')) {
					if ($channel_language != '' && $channel_geura == '') {
						$a = "AND channel_language='$channel_language'";
					}
					if ($channel_language == '' && $channel_geura != '') {
						$a = "AND channel_genre='$channel_geura'";
					}
					if ($channel_language != '' && $channel_geura != '') {
						$a = "AND channel_language='$channel_language' AND channel_genre='$channel_geura'";
					}
					$query = "SELECT distinct 
								n.id AS 'channel_id',
								n.category_id,
								n.channel_name, 
								n.channel_image, 
								n.channel_url,
								n.channel_description,
								n.channel_feature,
								c.category_name,
								n.channel_type,
								n.channel_player_type,
								
								k.favourite_channel
							FROM 
								tbl_channel n, 
								tbl_category c ,
								tbl_user	k
								
							WHERE 
								n.category_id = c.cid AND n.channel_visible=1 AND n.channel_feature='$features' $a AND k.id='$userid' ORDER BY n.channel_order ASC, n.id DESC LIMIT $limit OFFSET $offset";
				} else {
					if ($features == 4 && $channel_language != '' && $channel_geura == '') {
						$a = "AND channel_language='$channel_language'";
					}
					if ($features == 4 && $channel_language == '' && $channel_geura != '') {
						$a = "AND channel_genre='$channel_geura'";
					}
					if ($features == 4 && $channel_language != '' && $channel_geura != '') {
						$a = "AND channel_language='$channel_language' AND channel_genre='$channel_geura'";
					}
					$query = "SELECT distinct 
								n.id AS 'channel_id',
								n.category_id,
								n.channel_name, 
								n.channel_image, 
								n.channel_url,
								n.channel_description,
								n.channel_feature,
								c.category_name,
								n.channel_type,
								n.channel_player_type,
								
								k.favourite_channel
							FROM 
								tbl_channel n, 
								tbl_category c ,
								tbl_user	k
								
							WHERE 
								n.category_id = c.cid AND n.channel_visible=1  $a AND k.id='$userid' ORDER BY n.channel_order ASC, n.id DESC LIMIT $limit OFFSET $offset";
				}
				$post = mysqli_query($connect, $query);

				$postss = array();
				while ($data = mysqli_fetch_array($post)) {
					$channel_id = trim($data['channel_id']);
					$category_id = $data['category_id'];
					$channel_name = $data['channel_name'];
					$channel_image = $data['channel_image'];
					$channel_url = $data['channel_url'];
					$channel_description = $data['channel_description'];
					$category_name = $data['category_name'];
					$channel_type = $data['channel_type'];
					$channel_player_type = $data['channel_player_type'];
					//$favourite_channel1 = $data['favourite_channel'];

					if (in_array($channel_id, $favourite_channel1)) {
						$favourite_channel = '1';
					} else {
						$favourite_channel = '0';
					}

					$postss[] = array("channel_id" => $channel_id, "category_id" => $category_id, "channel_name" => $channel_name, "channel_image" => $channel_image, "channel_url" => $channel_url, "channel_description" => $channel_description, "category_name" => $category_name, "channel_type" => $channel_type, "channel_player_type" => $channel_player_type, "favourite_channel" => $favourite_channel);
				}


				$count = count($post);
				$respon = array(
					'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $postss
				);
				$this->response($this->json($respon), 200);
			} else {
				die('Oops, API Key is Incorrect!');
			}
		} else {
			die('Forbidden, API Key is Required!');
		}
	}

	private function get_category_index()
	{

		include "../includes/config.php";
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if (isset($_GET['api_key'])) {

			$access_key_received = $_GET['api_key'];

			if ($access_key_received == $api_key) {

				if ($this->get_request_method() != "GET") $this->response('', 406);
				$count_total = $this->get_count_result("SELECT COUNT(DISTINCT cid) FROM tbl_category WHERE category_visible=1");

				$query = "SELECT distinct 
								cid,
								category_name,
								category_image
								
							FROM
								tbl_category WHERE category_visible=1 ORDER BY category_order ASC, cid DESC";

				$news = $this->get_list_result($query);
				$count = count($news);
				$respon = array(
					'status' => 'ok', 'count' => $count, 'categories' => $news
				);
				$this->response($this->json($respon), 200);
			} else {
				die('Oops, API Key is Incorrect!');
			}
		} else {
			die('Forbidden, API Key is Required!');
		}
	}

	// Inserted by Tapan 
	private function logs()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$App_Name = $obj->App_Name;
			$UserID = $obj->UserID;
			$Action = $obj->Action;
			$PlaylistItemID = $obj->PlaylistItemID;
			$CategoryID = $obj->CategoryID;
			$ChannelID = $obj->ChannelID;
			$ChannelCategoryName = $obj->ChannelCategoryName;
			$track_id = $obj->track_id;
			$ActionTime = $obj->ActionTime;
			$ActionDuration1 = $obj->ActionDuration;
			$DeviceName = $obj->DeviceName;
			$IPAddress = $obj->IPAddress;
			$OSVersion = $obj->OSVersion;
			$CountryCode = $obj->CountryCode;
			$PosLat = $obj->PosLat;
			$PosLong = $obj->PosLong;
			$college_id = $obj->college_id;
			$college_name = $obj->college_name;
			$city = $obj->city;
			$state = $obj->state;
			$country = $obj->country;

			$online_date = date('Y-m-d');
			$online_time = date('H:i:s');
			$offline_date = date('Y-m-d');
			$offline_time = date('H:i:s');
			$input = "App_Name=" . $App_Name . ",UserID=" . $UserID . ",Action=" . $Action . ",PlaylistItemID=" . $PlaylistItemID . ",CategoryID=" . $CategoryID . ",ChannelID=" . $ChannelID . ",ChannelCategoryName=" . $ChannelCategoryName . ",ActionTime=" . $ActionTime . ",ActionDuration=" . $ActionDuration1 . ",DeviceName=" . $DeviceName . ",IPAddress=" . $IPAddress . ",OSVersion=" . $OSVersion . ",CountryCode=" . $CountryCode . ",PosLat=" . $PosLat . ",PosLong=" . $PosLong . ",college_id=" . $college_id . ",college_name=" . $college_name . ",date=" . $online_date;
			if ($ActionDuration1 == "") {
				$ActionDuration = "";
			} else {
				$ActionDuration2 = abs(strtotime($ActionDuration1) - strtotime($ActionTime)) / 60;
				$ActionDuration = round($ActionDuration2);
			}
			$sql = "INSERT INTO play_logs (App_Name,UserID,Action,PlaylistItemID,CategoryID,ChannelID,ChannelCategoryName,ActionTime,ActionDuration,DeviceName,IPAddress,OSVersion,CountryCode,PosLat,PosLong,college_id,college_name,date,city,state,country,track_id) VALUES ('$App_Name','$UserID','$Action','$PlaylistItemID','$CategoryID','$ChannelID','$ChannelCategoryName','$ActionTime','$ActionDuration','$DeviceName','$IPAddress','$OSVersion','$CountryCode','$PosLat','$PosLong','$college_id','$college_name','$online_date','$city','$state','$country','$track_id')";
			$result = mysqli_query($connect, $sql);
			if ($result) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$query = "select * from online_statistics where user_id='$UserID'";
				$queryresult = mysqli_query($connect, $query);
				$cou = mysqli_num_rows($queryresult);
				if ($cou == 0) {
					$statquery = "INSERT INTO online_statistics (user_id,online_time,online_date,status,college_id,college_name,channel_id) VALUES ('$UserID','$online_date','$online_time','1','$college_id','$college_name','$ChannelID')";
					$statresult = mysqli_query($connect, $statquery);
				} else {
					if ($ActionDuration1 == '') {
						$updateonline = "UPDATE online_statistics SET online_time='$online_time',online_date='$online_date',status='1',offline_date='',offline_time='',channel_id='$ChannelID' where user_id='$UserID'";
						$updateresult = mysqli_query($connect, $updateonline);
					} else {
						$updonline = "UPDATE online_statistics SET status='0',offline_date='$offline_date',offline_time='$offline_time',channel_id='$ChannelID' where user_id='$UserID'";
						$updresult = mysqli_query($connect, $updonline);
					}
				}
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Insert,Please Try Again!";
			}
			$response = json_encode($postvalue);
			print $response;
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function online_user_count()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;

		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$channel_id = $obj->channel_id;
			$dateforuser = date("Y-m-d");
			$querysqls = "select * from online_statistics where channel_id='$channel_id' and status='1' and online_date ='$dateforuser'";
			$resultsqls = mysqli_query($connect, $querysqls);
			$countuser = mysqli_num_rows($resultsqls);

			$postvalue['responseStatus'] = 200;
			$postvalue['responseMessage'] = "OK";
			$postvalue['channel_user_online'] =  $countuser;
			$response = json_encode($postvalue);
			print $response;
		} else {
			$postvalue['responseStatus'] = 204;
			$postvalue['responseMessage'] = "API_KEY not correct please check!";
			$response = json_encode($postvalue);
			print $response;
		}
	}

	private function user_registration()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$username = $obj->username;
			$email = $obj->email;
			$country_code = $obj->country_code;
			$phone = $obj->phone;
			$password = $obj->password;
			$Address = $obj->Address;
			$city = $obj->city;
			$state = $obj->state;
			$country = $obj->country;
			$preferred_college = $obj->preferred_college;

			$input = "username=" . $username . ",email=" . $email . ",phone=" . $phone;

			$userquery = mysqli_query($connect, "select * from tbl_user where username='$username' AND username !=''");
			$userfetch = mysqli_num_rows($userquery);
			if ($userfetch > 0) {
				$postvalue['responseStatus'] = 212;
				$postvalue['responseMessage'] = 'This Username Already Registered Please Choose Another UserID!';
				$response = json_encode($postvalue);
				print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);

				die;
			}
            

			if ($email == '' && $phone == '') {
				$postvalue['responseStatus'] = 208;
				$postvalue['responseMessage'] = 'Please Set Phone or emailid!';
				$response = json_encode($postvalue);
				print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);

				die;
			} else if ($username == '') {
				$postvalue['responseStatus'] = 208;
				$postvalue['responseMessage'] = 'Please Set UserName';
				$response = json_encode($postvalue);
				//print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);

				die;
			} else if ($password == '') {
				$postvalue['responseStatus'] = 208;
				$postvalue['responseMessage'] = 'Please Set Password';
				$response = json_encode($postvalue);
				print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);


				die;
			}

			if ($email == '' && $phone != '' && $username != '') {
				$pho = $country_code . "" . $phone;
				$parameter = "(phone='$phone' OR username='$username' OR phone='$pho')";
			} else if ($phone == '' && $username != '' && $email != '') {
				$parameter = "(email='$email' OR username='$username')";
			} else if ($username == '' && $phone != '' && $email != '') {
				$pho = $country_code . "" . $phone;
				$parameter = "(email='$email' OR phone='$phone' OR phone='$pho')";
			} else if ($username != '' && $phone != '' && $email == '') {
				$pho = $country_code . "" . $phone;
				$parameter = "(username='$username' OR phone='$phone' OR phone='$pho')";
			} else if ($username == '' && $phone == '' && $email != '') {
				$parameter = "email='$email'";
			} else if ($username == '' && $phone != '' && $email == '') {
				$pho = $country_code . "" . $phone;
				$parameter = "(phone='$phone' OR phone='$pho')";
			} else if ($username != '' && $phone == '' && $email == '') {
				$parameter = "username='$username'";
			} else if ($email != '' && $phone != '' && $username != '') {
				$pho = $country_code . "" . $phone;
				$parameter = "(email='$email' OR phone='$phone' OR username='$username' OR phone='$pho')";
			}

			$member_Password = $obj->SMC_member_Password;
			$Member_ID = $obj->SMC_Member_ID;
			$SMC_entity_id = $obj->SMC_entity_id;
			$datetime = date('Y-m-d H:i:s');
            

			if ($member_Password != "" && $Member_ID != "" && $SMC_entity_id != "") {
				$curl = curl_init();
				$request1 = array('SMC_entity_id' => $SMC_entity_id, 'SMC_Member_ID' => $Member_ID, 'SMC_member_Password' => $member_Password);
				$operationInput = json_encode($request1);
				curl_setopt_array($curl, array(
					CURLOPT_URL => "https://smartcookie.in/core/Version3/campustv_registration_api.php",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $operationInput,
					CURLOPT_HTTPHEADER => array(
						"cache-control: no-cache",
						"content-type: application/json"

					),
				));

				$response = curl_exec($curl);
				$err = curl_error($curl);
				curl_close($curl);
				$jsondecode =  json_decode($response);
				$convertarray = array($jsondecode);


				foreach ($convertarray as $array) {
					$responseStatus23 = $array->responseStatus;
					$posts = $array->posts;
					if (isset($posts)) {
						foreach ($posts as $dataset) {
							$RewardPoint = $dataset->RewardPoint;
							$school_id1 = $dataset->school_id;
							$school_name1 = $dataset->school_name;
						}
					}
				}
				if ($responseStatus23 == 200) {
					$total_rewardpoint = $RewardPoint + 20;
					$school_id  = $school_id1;
					$school_name = $school_name1;
				} else {
					$postvalue['responseStatus'] = 222;
					$postvalue['responseMessage'] = "User Not Registered with SMC Please Registered!";
					$response = json_encode($postvalue);
					print $response;
					die;
				}
			}

			$selectquery = "select * from tbl_user where $parameter";
			//echo $selectquery;
			$selectqueryresult = mysqli_query($connect, $selectquery);
			$rows = mysqli_fetch_array($selectqueryresult);
			$id2 = $rows['id'];
			$username2 = $rows['username'];
			$email2 = $rows['email'];
			$phone2 = $rows['phone'];
			$info2[] = array('id' => $id2, 'username' => $username2, 'email' => $email2, 'phone' => $phone2);
			$count = mysqli_num_rows($selectqueryresult);
			//echo $count;
			if ($count > 0) {
				$postvalue['responseStatus'] = 210;
				$postvalue['responseMessage'] = 'User Already Registered!';
				$postvalue['data'] = $info2;
				$response = json_encode($postvalue);
				print $response;
			} else {
				if (isset($total_rewardpoint)) {
					$sql = "INSERT INTO tbl_user (username,email,phone,user_role,registration_date,Member_ID,member_Password,SMC_entity_id,user_reward_points,college_id,college_name,Address,city,state,country,preferred_college,password,country_code) VALUES ('$username','$email','$phone','103','$datetime','$Member_ID','$member_Password','$SMC_entity_id','$total_rewardpoint','$school_id','$school_name','$Address','$city','$state','$country','$preferred_college','$password','$country_code')";
					$result = mysqli_query($connect, $sql);
					$sql2 = "select * from tbl_user where phone='$phone' OR email='$email'";
					$result2 = mysqli_query($connect, $sql2);
					$row2 = mysqli_fetch_array($result2);
					$user_id = $row2['id'];
                    //echo "Line no 621";

					$date = date('Y-m-d');

					$insertpointlog = mysqli_query($connect, "INSERT INTO user_points (action_start,action_end,total_duration,user_id,date,total_points,given_point) VALUES ('Registration','SmartCookies With CampusRadio','','$user_id','$date','$total_rewardpoint','20')");
				} else {
					$sql = "INSERT INTO tbl_user (username,email,phone,user_role,registration_date,user_reward_points,Address,city,state,country,preferred_college,password,country_code) VALUES ('$username','$email','$phone','103','$datetime','10','$Address','$city','$state','$country','$preferred_college','$password','$country_code')";
					$result = mysqli_query($connect, $sql);


					$sql2 = "select * from tbl_user where phone='$phone' OR email='$email'";
					$result2 = mysqli_query($connect, $sql2);
					$row2 = mysqli_fetch_array($result2);
					$user_id = $row2['id'];


					$date = date('Y-m-d');

					$insertpointlog = mysqli_query($connect, "INSERT INTO user_points (action_start,action_end,total_duration,user_id,date,total_points,given_point) VALUES ('Registration','CampusRadio','','$user_id','$date','10','10')");
				}
				if ($result) {
					$query = "select * from tbl_user where email='$email' OR phone='$phone' ORDER BY id desc limit 1";
					$queryresult = mysqli_query($connect, $query);
					$count = mysqli_num_rows($queryresult);
					$row = mysqli_fetch_array($queryresult);
					$id = $row['id'];
					$username = $row['username'];
					$email = $row['email'];
					$phone = $row['phone'];
					$info[] = array('id' => $id, 'username' => $username, 'email' => $email, 'phone' => $phone);

					if ($count > 0) {
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "OK";
						$postvalue['data'] = $info;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
					}
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Data Not Insert,Please Try Again!";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}
        //echo " Line no 685";
		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function user_registration_google()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$username = $obj->username;
			$email = $obj->email;

			$datetime = date('Y-m-d H:i:s');

			$sql = mysqli_query($connect, "select * from tbl_user where google_username !='' order by id desc limit 1");
			$coun = mysqli_num_rows($sql);
			if ($coun > 0) {
				$row = mysqli_fetch_array($sql);
				$a = $row['password'];
				$b = explode("-", $a);
				$c = $b[1] + 1;
				$password = "CAM-" . $c;
			} else {
				$password = 'CAM-123';
			}
			// $usern = substr($username,0,3);
			// $username_real = $usern."@".$password;
			$username_len = strlen($username);
			$username_real = substr($username, 0, $username_len);

			$sql2 = "INSERT INTO tbl_user (username,google_username,email,user_role,registration_date,password) VALUES ('$username_real','$username','$email','103','$datetime','$password')";
			$result = mysqli_query($connect, $sql2);

			if ($result) {
				$query = "select * from tbl_user where email='$email' ORDER BY id desc limit 1";
				$queryresult = mysqli_query($connect, $query);
				$count = mysqli_num_rows($queryresult);
				$row = mysqli_fetch_array($queryresult);
				$id = $row['id'];
				$username = $row['username'];
				$email = $row['email'];
				$google_username = $row['google_username'];
				$info[] = array('id' => $id, 'username' => $username, 'google_username' => $google_username, 'email' => $email);

				if ($count > 0) {
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$postvalue['data'] = $info;
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}


	private function user_login()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$username = $obj->username;
			$email = $obj->email;
			$country_code = $obj->country_code;
			$phone = $obj->phone;
			$otp = $obj->otp;
			$password = $obj->password;
            
			if ($email == '' && $phone == '' && $username == '') {
				$postvalue['responseStatus'] = 208;
				$postvalue['responseMessage'] = 'Please Set Phone or emailid or username!';
				$response = json_encode($postvalue);
				print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);

				die;
			}
			if ($otp != '') {

				if ($country_code != "" && $phone != "" && $email == "") {
					$pho = $country_code . "" . $phone;
					$a = "mobileno='$phone' AND otp='$otp'";
					$b = "mobileno='$phone'";
					$c = "phone='$phone' OR phone='$pho'";
					$d = "This Mobile Number Is Not Registered!";
					$e = "Mobile Number And OTP are Not Match Please try again";
				}
				if ($country_code == "" && $phone == "" && $email != "") {
					$a = "email='$email' AND otp='$otp'";
					$b = "email='$email'";
					$c = "email='$email'";
					$d = "This Email ID Is Not Registered!";
					$e = "Email ID And OTP are Not Match Please try again";
				}

				$sql3 = "select * from tbl_user where $c";
				$resul = mysqli_query($connect, $sql3);
				$countss = mysqli_num_rows($resul);
				if ($countss < 1) {
					$postvalue['responseStatus'] = 206;
					$postvalue['responseMessage'] = $d;
					$response = json_encode($postvalue);
					print $response;

					$datetimeforlogs = date('Y-m-d H:i:s');
					$ipforlogs = $_SERVER['REMOTE_ADDR'];
					$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



					$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
					$resultforlogs = mysqli_query($connect, $sqlforlogs);

					die;
				}

				$sql = "select * from tbl_otp where $a";
				$result = mysqli_query($connect, $sql);
				$count = mysqli_num_rows($result);
				if ($count > 0) {
					$query = "delete from tbl_otp where $b";
					$queryresult = mysqli_query($connect, $query);

					$sql2 = "select * from tbl_user where $c";
					$result2 = mysqli_query($connect, $sql2);
					$json_array2 = array();
					while ($row2 = mysqli_fetch_assoc($result2)) {
						$uid = $row2['id'];
						$Member_ID = $row2['Member_ID'];
						$member_Password = $row2['member_Password'];
						$SMC_entity_id = $row2['SMC_entity_id'];
						$PRN_number = $row2['PRN_number'];

						if ($Member_ID != '' && $member_Password != '' && $SMC_entity_id != '' && $PRN_number == '') {
							$curl = curl_init();
							$request1 = array('SMC_entity_id' => $SMC_entity_id, 'SMC_Member_ID' => $Member_ID, 'SMC_member_Password' => $member_Password);
							$operationInput = json_encode($request1);
							curl_setopt_array($curl, array(
								CURLOPT_URL => "https://smartcookie.in/core/Version3/campustv_registration_api.php",
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 30,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "POST",
								CURLOPT_POSTFIELDS => $operationInput,
								CURLOPT_HTTPHEADER => array(
									"cache-control: no-cache",
									"content-type: application/json"

								),
							));

							$response = curl_exec($curl);
							$err = curl_error($curl);
							curl_close($curl);
							$jsondecode =  json_decode($response);
							$convertarray = array($jsondecode);
							foreach ($convertarray as $array) {
								$responseStatus23 = $array->responseStatus;
								$posts = $array->posts;
								if (isset($posts)) {
									foreach ($posts as $dataset) {
										$PRN_Number_result = $dataset->UserID;
									}
								}
							}
							$query_update = mysqli_query($connect, "UPDATE tbl_user SET PRN_number='$PRN_Number_result' WHERE id='$uid'");
						}


						$json_array2[] = $row2;
					}
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "User Login Successfully!";
					$postvalue['data'] = $json_array2;
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 202;
					$postvalue['responseMessage'] = $e;
					$response = json_encode($postvalue);
					print $response;
				}
			} else {
				if ($email == '' && $phone != '' && $username != '') {
					$pho = $country_code . "" . $phone;
					$parameter = "(phone='$phone' OR username='$username' OR phone='$pho')";
					$b = "UserName OR Mobile Number And Password Not Match Please try again";
				} else if ($phone == '' && $username != '' && $email != '') {
					$parameter = "(email='$email' OR username='$username')";
					$b = "UserName OR Email_ID And Password Not Match Please try again";
				} else if ($username == '' && $phone != '' && $email != '') {
					$pho = $country_code . "" . $phone;
					$parameter = "(email='$email' OR phone='$phone' OR phone='$pho')";
					$b = "Mobile Number OR Email_ID And Password Not Match Please try again";
				} else if ($username == '' && $phone == '' && $email != '') {
					$parameter = "email='$email'";
					$b = "Email_ID And Password Not Match Please try again";
				} else if ($username == '' && $phone != '' && $email == '') {
					$pho = $country_code . "" . $phone;
					$parameter = "(phone='$phone' OR phone='$pho')";
					$b = "Mobile Number And Password Not Match Please try again";
				} else if ($username != '' && $phone == '' && $email == '') {
					$parameter = "username='$username'";
					$b = "UserName And Password Not Match Please try again";
				} else if ($email != '' && $phone != '' && $username != '') {
					$pho = $country_code . "" . $phone;
					$parameter = "(email='$email' OR phone='$phone' OR username='$username' OR phone='$pho')";
					$b = "UserName OR Email_ID OR Mobile Number And Password Not Match Please try again";
				}

				$selectquery = "select * from tbl_user where $parameter AND password='$password'";
				$selectqueryresult = mysqli_query($connect, $selectquery);
				// $rows = mysqli_fetch_array($selectqueryresult);
				// $id2 = $rows['id'];
				// $username2 = $rows['username'];
				// $email2 = $rows['email'];
				// $phone2 = $rows['phone'];
				// $info2[] = array('id'=>$id2,'username'=>$username2,'email'=>$email2,'phone'=>$phone2);
				$count = mysqli_num_rows($selectqueryresult);
				if ($count > 0) {
					$json_array = array();
					while ($row = mysqli_fetch_assoc($selectqueryresult)) {
						$uid = $row['id'];
						$Member_ID = $row['Member_ID'];
						$member_Password = $row['member_Password'];
						$SMC_entity_id = $row['SMC_entity_id'];
						$PRN_number = $row['PRN_number'];

						if ($Member_ID != '' && $member_Password != '' && $SMC_entity_id != '' && $PRN_number == '') {
							$curl = curl_init();
							$request1 = array('SMC_entity_id' => $SMC_entity_id, 'SMC_Member_ID' => $Member_ID, 'SMC_member_Password' => $member_Password);
							$operationInput = json_encode($request1);
							curl_setopt_array($curl, array(
								CURLOPT_URL => "https://smartcookie.in/core/Version3/campustv_registration_api.php",
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 30,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "POST",
								CURLOPT_POSTFIELDS => $operationInput,
								CURLOPT_HTTPHEADER => array(
									"cache-control: no-cache",
									"content-type: application/json"

								),
							));

							$response = curl_exec($curl);
							$err = curl_error($curl);
							curl_close($curl);
							$jsondecode =  json_decode($response);
							$convertarray = array($jsondecode);
							foreach ($convertarray as $array) {
								$responseStatus23 = $array->responseStatus;
								$posts = $array->posts;
								if (isset($posts)) {
									foreach ($posts as $dataset) {
										$PRN_Number_result = $dataset->UserID;
									}
								}
							}
							$query_update = mysqli_query($connect, "UPDATE tbl_user SET PRN_number='$PRN_Number_result' WHERE id='$uid'");
						}
						$json_array[] = $row;
					}
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = 'User Login Successfully!';
					$postvalue['data'] = $json_array;
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = $b;
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}





	private function forgot_password()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {

			$country_code = $obj->country_code;
			$phonenumber = $obj->phone_number;
			$email_id = $obj->email_id;
			$c_phone_number = $country_code . "" . $phonenumber;

			if ($country_code != '' && $phonenumber != '' && $email_id == '') {
				$a = "(phone='$phonenumber' OR phone='$c_phone_number')";
			} else if ($country_code == '' && $phonenumber == '' && $email_id != '') {
				$a = "email='$email_id'";
			}


			$query = mysqli_query($connect, "select * from tbl_user where $a");
			$cpon = mysqli_num_rows($query);
			if ($cpon == 0) {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "User Email Or Phone Not Registed!";
				$response = json_encode($postvalue);
				print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);


				die;
			} else {
				$rowsd = mysqli_fetch_array($query);
				$name = $rowsd['username'];
				$user_id = $rowsd['id'];
			}

			$otpnumber = mt_rand(1000, 9999);
			$passwordmaker = "CMR@" . $otpnumber;
			$OTPResponse = "Your Otp for Forgot password is $passwordmaker. Campus Radio";

			$query = "select * from tbl_sms_email_credencial where active='1'";
			$reusltquery = mysqli_query($connect, $query);
			$row = mysqli_fetch_array($reusltquery);
			$user = $row['username'];
			$password = $row['password'];
			$senderid = $row['senderid'];
			$pe_id = $row['pe_id'];
			$template_id = $row['template_id'];
			$sms_url = $row['sms_url'];

			$user2 = $row['email_sender_username'];
			$password2 = $row['email_sender_password'];



			if ($country_code != '' && $phonenumber != "" && $email_id == "") {
				if ($country_code == '+91') {
					$request = "";

					$param['user'] = $user;
					$param['password'] = $password;
					$param["sender"] = $senderid;
					$param['PhoneNumber'] = "$phonenumber";
					$param["msgType"] = "PT";
					$param['Text'] = "$OTPResponse";
					$param['pe_id'] = $pe_id;
					$param['template_id'] = $template_id;

					foreach ($param as $key => $val) {
						$request .= $key . "=" . urlencode($val);

						$request .= "&";
					}
					$request = substr($request, 0, strlen($request) - 1);
					$url = $sms_url . "?" . $request;
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$curl_scraped_page = curl_exec($ch);
					curl_close($ch);
					$curl_scraped_page;
					$xml = new SimpleXMLElement($curl_scraped_page);
					$record = json_decode(json_encode($xml), true);

					if (!$record['ErrorCode']) {

						$query = mysqli_query($connect, "UPDATE tbl_user SET password='$passwordmaker' WHERE id='$user_id'");
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "Password Send On Your registerd PhoneNumber";
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Failed to Send Password please try again";
						$response = json_encode($postvalue);
						print $response;
					}
				} else if ($country_code != '+91') {
					require 'twilio.php';

					$ApiVersion = "2010-04-01";
					// set our AccountSid and AuthToken
					$AccountSid = "ACf8730e89208f1dfc6f741bd6546dc055";
					$AuthToken = "45e624a756b26f8fbccb52a6a0a44ac9";
					// instantiate a new Twilio Rest Client
					$client = new TwilioRestClient($AccountSid, $AuthToken);
					$number = $country_code . "" . $phonenumber;
					$message = "$OTPResponse";
					$response = $client->request(
						"/$ApiVersion/Accounts/$AccountSid/SMS/Messages",
						"POST",
						array(
							"To" => $number,
							"From" => "732-798-7878",
							"Body" => $message
						)
					);
					$myArray =	json_decode(json_encode($response), true);
					$HttpStatus = $myArray['HttpStatus'];
					if ($HttpStatus == 400) {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Failed to Send Password please try again";
						$response = json_encode($postvalue);
						print $response;
					} else {
						$query = mysqli_query($connect, "UPDATE tbl_user SET password='$passwordmaker' WHERE id='$user_id'");
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "Password Send On Your registerd PhoneNumber";
						$response = json_encode($postvalue);
						print $response;
					}
				}
			}



			// if($country_code == "" && $phonenumber != "" && $email_id != ""){
			// $request ="";
			// $param['user'] = $user;
			// $param['password'] = $password;
			// $param["sender"] = $senderid;
			// $param['PhoneNumber'] = "$phonenumber";
			// $param['Text'] = "$OTPResponse";
			// foreach($param as $key=>$val)
			// {
			// $request.= $key."=".urlencode($val);

			// $request.= "&";

			// }
			// $request = substr($request, 0, strlen($request)-1);
			// $url = "http://www.smswave.in/panel/sendsms.php?".$request;
			// $ch = curl_init($url);
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// $curl_scraped_page = curl_exec($ch);
			// curl_close($ch);
			// $curl_scraped_page;
			// $record = explode("|",$curl_scraped_page);
			// $S = $record[1];
			// $Success = trim($S);



			// $Email_Headers="MIME-Version: 1.0\n";
			// $Email_Headers.="Content-type: text/html; charset=utf-8\n";
			// $Email_Headers.="X-Priority: 3\n";
			// $Email_Headers.="X-MSMail-Priority: High\n";
			// $Email_Headers.="Reply-To: collegeradiorocks@gmail.com\r\n";
			// $Email_Headers.="Return-Path:collegeradiorocks@gmail.com\r\n";
			// $Email_Headers.="X-Mailer: My Mailer\n";
			// $subject = "Campus Radio Forgot Password";
			// $emailMsg = "Dear $name,
			// <br/><br/>
			// Your Campus Radio Password is $passwordmaker.
			// <br/><br/>
			// Regards, <br/>
			// Team Campus Radio";

			// $emailHeader = $Email_Headers."From:collegeradiorocks@gmail.com<collegeradiorocks@gmail.com>\n"."\r\n";
			// $result=mail($email_id,$subject,$emailMsg,$emailHeader);

			// if($Success == "Success"){
			// $query = mysqli_query($connect,"UPDATE tbl_user SET password='$passwordmaker' WHERE id='$user_id'");

			// $postvalue['responseStatus'] = 200;
			// $postvalue['responseMessage'] = "Password Send On your Registed Phone_Number And Email_Id";
			// $response = json_encode($postvalue);
			// print $response;
			// }else{
			// $postvalue['responseStatus'] = 204;
			// $postvalue['responseMessage'] = "Failed to Send Password please try again";
			// $response = json_encode($postvalue);
			// print $response;
			// }


			// }	



			if ($email_id != "" && $phonenumber == "" && $country_code == "") {
				//$name = $obj->name;

				error_reporting(0);
				define("SMTP_HOST", "SMTP_HOST_NAME");
				define("SMTP_PORT", "SMTP_PORT");
				define("SMTP_UNAME", "VALID_EMAIL_ACCOUNT");
				define("SMTP_PWORD", "VALID_EMAIL_ACCOUNTS_PASSWORD");
				include "class.phpmailer.php";
				$myFile = "emaillog.txt";
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->Host = "smtp.gmail.com";
				$mail->Port = 587;
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = "tls";
				$mail->Username = $user2;
				$mail->Password = $password2;
				$mail->AddReplyTo($user2, "CampusRadio");
				$mail->SetFrom($user2, "CampusRadio");
				$mail->AddAddress($email_id);
				$mail->Subject = "Campus Radio Registration OTP";
				$mail->MsgHTML("Dear $name,
		<br/><br/>
		Your Campus Radio Password is $passwordmaker.
		<br/><br/>
		Regards, <br/>
		Team Campus Radio");
				$send = $mail->Send();

				// $Email_Headers="MIME-Version: 1.0\n";
				// $Email_Headers.="Content-type: text/html; charset=utf-8\n";
				// $Email_Headers.="X-Priority: 3\n";
				// $Email_Headers.="X-MSMail-Priority: High\n";
				// $Email_Headers.="Reply-To: collegeradiorocks@gmail.com\r\n";
				// $Email_Headers.="Return-Path:collegeradiorocks@gmail.com\r\n";
				// $Email_Headers.="X-Mailer: My Mailer\n";
				// $subject = "Campus Radio Forgot Password";
				// $emailMsg = "Dear $name,
				// 		<br/><br/>
				// 		Your Campus Radio Password is $passwordmaker.
				// 		<br/><br/>
				// 		Regards, <br/>
				// 		Team Campus Radio";

				//  $emailHeader = $Email_Headers."From:collegeradiorocks@gmail.com<collegeradiorocks@gmail.com>\n"."\r\n";
				//  $result=mail($email_id,$subject,$emailMsg,$emailHeader);
				if ($send) {

					$query = mysqli_query($connect, "UPDATE tbl_user SET password='$passwordmaker' WHERE id='$user_id'");
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "Password Send On your EmailID";
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Failed to Send Password please try again";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}


		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}









	private function user_profile_show()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;

		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');

		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$username = $obj->userid;
			$input = "userid=" . $username;
			$query = "select * from tbl_user where id='$username'";
			$queryresult = mysqli_query($connect, $query);
			$count = mysqli_num_rows($queryresult);
			$row = mysqli_fetch_array($queryresult);

			$preferred_language1 = $row['preferred_language'];
			$lan_sql = "select id,values_name,images from tbl_languages WHERE id IN ($preferred_language1)";
			$lan_result = mysqli_query($connect, $lan_sql);
			$preferred_language = array();
			while ($lan_row = mysqli_fetch_array($lan_result)) {
				$lan_id = $lan_row['id'];
				$lang_name = $lan_row['values_name'];
				$lang_image1 = $lan_row['images'];
				if ($lang_image1 == "") {
					$lang_image = "";
				} else {
					$lang_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $lang_image1;
				}
				$preferred_language[] = array("id" => $lan_id, "Language" => $lang_name, "Language_Image" => $lang_image);
			}
			$user_reward_points = $row['user_reward_points'];
			$college_id = $row['college_id'];
			$college_name = $row['college_name'];
			$preferred_college = $row['preferred_college'];
			$Address = $row['Address'];
			$phone = $row['phone'];
			$city = $row['city'];
			$state = $row['state'];
			$country = $row['country'];
			$pincode = $row['pincode'];
			$username3 = $row['username'];
			$email = $row['email'];
			$user_id = $row['id'];
			$country_code = $row['country_code'];
			$registration_flag = $row['registration_flag'];
			$SMC_Member_ID = $row['Member_ID'];
			$song_genre1 = $row['song_genre'];
			$song_sql = "select id,values_name,images from tbl_languages WHERE id IN ($song_genre1)";
			$song_result = mysqli_query($connect, $song_sql);
			$song_genre = array();
			while ($song_row = mysqli_fetch_array($song_result)) {
				$son_id = $song_row['id'];
				$song_name1 = $song_row['values_name'];
				$song_image2 = $song_row['images'];
				if ($song_image2 == "") {
					$song_image1 = "";
				} else {
					$song_image1 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $song_image2;
				}
				$song_genre[] = array("id" => $son_id, "Songs" => $song_name1, "Song_Image" => $song_image1);
			}
			$user_image20 = $row['user_image'];
			if ($user_image20 == "") {
				$imagepath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/user_image/default.jpg";
			} else {
				$imagepath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $user_image20;
			}
			$info = array('username' => $username3, 'user_id' => $user_id, 'email' => $email, 'country_code' => $country_code, 'phone' => $phone, 'user_image' => $imagepath, 'Address' => $Address, 'city' => $city, 'state' => $state, 'country' => $country, 'pincode' => $pincode, 'User_registration_type' => $registration_flag, 'preferred_college' => $preferred_college, 'preferred_language' => $preferred_language, 'song_genre' => $song_genre, 'college_id' => $college_id, 'college_name' => $college_name, 'SMC_Member_ID' => $SMC_Member_ID, 'RewardPoint' => $user_reward_points);

			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['data'] = $info;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
			}
			$response = json_encode($postvalue);
			print $response;
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}


		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}









	private function user_profile_update()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$usernamepara = $obj->username;
			$username = $obj->userid;
			$email1 = $obj->email;
			$country_code1  = $obj->country_code;
			$phone1 = $obj->phone;
			$Address1 = $obj->Address;
			$city1 = $obj->city;
			$state1 = $obj->state;
			$country1 = $obj->country;
			$pincode1 = $obj->pincode;
			$password1 = $obj->password;
			$preferred_language2 = $obj->preferred_language;
			$preferred_language1 = implode(',', $preferred_language2);
			$preferred_college1 = $obj->preferred_college;
			$song_genre2 = $obj->song_genre;
			$song_genre1 = implode(',', $song_genre2);
			$user_image = $obj->user_image;
			$registration_flag = $obj->registration_flag;
			$member_Password1 = $obj->SMC_member_Password;
			$Member_ID1 = $obj->SMC_Member_ID;
			$SMC_entity_id1 = $obj->SMC_entity_id;
			$input = "username=" . $usernamepara . ",userid=" . $username . ",email=" . $email1 . ",phone=" . $phone1 . ",Address=" . $Address1 . ",city=" . $city1 . ",state=" . $state1 . ",country=" . $country1 . ",pincode=" . $pincode1 . ",preferred_language=" . $preferred_language2 . ",preferred_college=" . $preferred_college1 . ",song_genre=" . $song_genre2 . ",user_image=" . $user_image . ",registration_flag=" . $registration_flag;
			$phquery = "select * from tbl_user where id='$username'";
			$phresult = mysqli_query($connect, $phquery);
			$phrow = mysqli_fetch_array($phresult);
			$phusername = $phrow['username'];
			$phphone = $phrow['phone'];
			$phemail = $phrow['email'];
			$phaddress = $phrow['Address'];
			$phcity = $phrow['city'];
			$phstate = $phrow['state'];
			$phcountry = $phrow['country'];
			$phpincode = $phrow['pincode'];
			$phpreferred_language = $phrow['preferred_language'];
			$phpreferred_college = $phrow['preferred_college'];
			$phsong_genre = $phrow['song_genre'];
			$phimage = $phrow['user_image'];
			$phmember_id = $phrow['Member_ID'];
			$phmember_password = $phrow['member_Password'];
			$phSMC_entity_id = $phrow['SMC_entity_id'];
			$phcollegeid = $phrow['college_id'];
			$phcollegename = $phrow['college_name'];
			$phpassword = $phrow['password'];
			$phcountry_code = $phrow['country_code'];

			if ($country_code1 == '') {
				$country_code = $phcountry_code;
			} else {
				$country_code = $country_code1;
			}
			if ($usernamepara == '') {
				$name = $phusername;
			} else {
				$name = $usernamepara;
			}
			if ($phone1 == '') {
				$phone = $phphone;
			} else {
				$phone = $phone1;
			}
			if ($email1 == '') {
				$email = $phemail;
			} else {
				$email = $email1;
			}
			if ($Address1 == '') {
				$Address = $phaddress;
			} else {
				$Address = $Address1;
			}
			if ($city1 == '') {
				$city = $phcity;
			} else {
				$city = $city1;
			}
			if ($state1 == '') {
				$state = $phstate;
			} else {
				$state = $state1;
			}
			if ($country1 == '') {
				$country = $phcountry;
			} else {
				$country = $country1;
			}
			if ($preferred_language1 == '') {
				$preferred_language = $phpreferred_language;
			} else {
				$preferred_language = $preferred_language1;
			}
			if ($song_genre1 == '') {
				$song_genre = $phsong_genre;
			} else {
				$song_genre = $song_genre1;
			}
			if ($pincode1 == '') {
				$pincode = $phpincode;
			} else {
				$pincode = $pincode1;
			}
			if ($preferred_college1 == '') {
				$preferred_college = $phpreferred_college;
			} else {
				$preferred_college = $preferred_college1;
			}

			if ($password1 == '') {
				$password = $phpassword;
			} else {
				$password = $password1;
			}

			if ($phmember_password == '' && $phmember_id == '' && $phSMC_entity_id == '' && $SMC_entity_id1 != '' && $Member_ID1 != '' && $member_Password1 != '') {

				$curl = curl_init();
				$request1 = array('SMC_entity_id' => $SMC_entity_id1, 'SMC_Member_ID' => $Member_ID1, 'SMC_member_Password' => $member_Password1);

				$operationInput = json_encode($request1);
				curl_setopt_array($curl, array(
					CURLOPT_URL => "https://smartcookie.in/core/Version3/campustv_registration_api.php",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $operationInput,
					CURLOPT_HTTPHEADER => array(
						"cache-control: no-cache",
						"content-type: application/json"

					),
				));

				$response = curl_exec($curl);
				$err = curl_error($curl);
				curl_close($curl);
				$jsondecode =  json_decode($response);
				$convertarray = array($jsondecode);


				foreach ($convertarray as $array) {
					$responseStatus23 = $array->responseStatus;
					$posts = $array->posts;
					if (isset($posts)) {
						foreach ($posts as $dataset) {
							$RewardPoint = $dataset->RewardPoint;
							$school_id = $dataset->school_id;
							$school_name = $dataset->school_name;
						}
					}
				}
				if ($responseStatus23 == 200) {
					$total_rewardpoint = $RewardPoint + 20;
					$member_Password = $member_Password1;
					$Member_ID = $Member_ID1;
					$SMC_entity_id = $SMC_entity_id1;
					$college_id = $school_id;
					$college_name = $school_name;
				} else {
					$postvalue['responseStatus'] = 222;
					$postvalue['responseMessage'] = "Use Not Registered with SMC Please Registered!";
					$response = json_encode($postvalue);
					print $response;



					die;
				}
			} else {
				$member_Password = $phmember_password;
				$Member_ID = $phmember_id;
				$SMC_entity_id = $phSMC_entity_id;
				$college_id = $phcollegeid;
				$college_name = $phcollegename;
			}

			if ($email == '' && $phone == '') {
				$postvalue['responseStatus'] = 208;
				$postvalue['responseMessage'] = 'Please Set Phone or emailid!';
				$response = json_encode($postvalue);
				print $response;
			} else {
				$blogid = uniqid();
				$CurrentYear = date("Y");
				$Currentmonth = date("m");

				$full_name_path = '../upload/user_image/' . $CurrentYear . '/' . $Currentmonth . '/';
				if (!file_exists($full_name_path)) {
					mkdir($full_name_path, 0777, true);
				}
				if ($user_image == "") {

					if ($phimage == 'user_image/default.jpg' || $phimage == '') {
						$insertpath = "user_image/default.jpg";
					} else {
						$insertpath = $phimage;
					}
				} else {
					$insertpath = "user_image/" . $CurrentYear . '/' . $Currentmonth . '/' . $blogid . "." . "jpg";


					$filenm = $full_name_path . $blogid . "." . "jpg";

					$user_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $user_image));
					file_put_contents($filenm, $user_image);
				}

				// if($registration_flag == 'email'){
				// $a = "phone='$phone',";
				// }
				// if($registration_flag == 'phone'){
				// $a = "email='$email',";
				// }

				$sql = "UPDATE tbl_user set email='$email',phone='$phone',username='$name',Address='$Address',city='$city',state='$state',country='$country',preferred_language='$preferred_language',song_genre='$song_genre',user_image='$insertpath',registration_flag='$registration_flag',pincode='$pincode',preferred_college='$preferred_college',Member_ID='$Member_ID',member_Password='$member_Password',SMC_entity_id='$SMC_entity_id',college_name='$college_name',college_id='$college_id',password='$password',country_code='$country_code' where id='$username' ";
				$result = mysqli_query($connect, $sql);
				if ($result) {
					$query = "select * from tbl_user where id='$username'";
					$queryresult = mysqli_query($connect, $query);
					$count = mysqli_num_rows($queryresult);
					$row = mysqli_fetch_array($queryresult);
					$id = $row['id'];
					$username = $row['username'];
					$email = $row['email'];
					$phone = $row['phone'];
					$country_code = $row['country_code'];
					$Address = $row['Address'];
					$city = $row['city'];
					$state = $row['state'];
					$country = $row['country'];
					$pincode = $row['pincode'];
					$preferred_language1 = $row['preferred_language'];
					$member_id = $row['Member_ID'];
					$college_id = $row['college_id'];
					$college_name = $row['college_name'];
					$user_reward_points = $row['user_reward_points'];
					$lan_sql = "select id,values_name,images from tbl_languages WHERE id IN ($preferred_language1)";
					$lan_result = mysqli_query($connect, $lan_sql);
					$preferred_language = array();
					while ($lan_row = mysqli_fetch_array($lan_result)) {
						$lan_id = $lan_row['id'];
						$lang_name = $lan_row['values_name'];
						$lang_image1 = $lan_row['images'];
						if ($lang_image1 == "") {
							$lang_image = "";
						} else {
							$lang_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
								"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $lang_image1;
						}
						$preferred_language[] = array("id" => $lan_id, "Language" => $lang_name, "Language_Image" => $lang_image);
					}
					$preferred_college = $row['preferred_college'];
					$song_genre1 = $row['song_genre'];
					$song_sql = "select id,values_name,images from tbl_languages WHERE id IN ($song_genre1)";
					$song_result = mysqli_query($connect, $song_sql);
					$song_genre = array();
					while ($song_row = mysqli_fetch_array($song_result)) {
						$son_id = $song_row['id'];
						$song_name1 = $song_row['values_name'];
						$song_image2 = $song_row['images'];
						if ($song_image2 == "") {
							$song_image1 = "";
						} else {
							$song_image1 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
								"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $song_image2;
						}
						$song_genre[] = array("id" => $son_id, "Songs" => $song_name1, "Song_Image" => $song_image1);
					}
					$user_image20 = $row['user_image'];
					$imagepath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $user_image20;

					if (isset($total_rewardpoint)) {

						$date = date('Y-m-d');

						$user_reward_points2 = $user_reward_points + $total_rewardpoint;

						$updatepint = mysqli_query($connect, "UPDATE tbl_user SET user_reward_points='$user_reward_points2' WHERE id='$id'");

						$info = array('id' => $id, 'username' => $username, 'email' => $email, 'country_code' => $country_code, 'phone' => $phone, 'Address' => $Address, 'city' => $city, 'state' => $state, 'country' => $country, 'pincode' => $pincode, 'preferred_college' => $preferred_college, 'user_image' => $imagepath, 'preferred_language' => $preferred_language, 'song_genre' => $song_genre, 'SMC_Member_ID' => $member_id, 'college_id' => $college_id, 'college_name' => $college_name, 'RewardPoint' => $user_reward_points2);

						$insertpointlog = mysqli_query($connect, "INSERT INTO user_points (action_start,action_end,total_duration,user_id,date,total_points,given_point) VALUES ('Update','CampusRadio With SMC','','$id','$date','$user_reward_points2','$total_rewardpoint')");
					} else {
						$info = array('id' => $id, 'username' => $username, 'email' => $email, 'country_code' => $country_code, 'phone' => $phone, 'Address' => $Address, 'city' => $city, 'state' => $state, 'country' => $country, 'pincode' => $pincode, 'preferred_college' => $preferred_college, 'user_image' => $imagepath, 'preferred_language' => $preferred_language, 'song_genre' => $song_genre, 'SMC_Member_ID' => $member_id, 'college_id' => $college_id, 'college_name' => $college_name, 'RewardPoint' => $user_reward_points);
					}
					if ($count > 0) {
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "User Profile Update Successfully!";
						$postvalue['data'] = $info;
						if (isset($total_rewardpoint)) {
							$postvalue['RewardPoint'] = $total_rewardpoint;
						}
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
					}
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Data Not Update,Please Try Again!";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}



	private function dropdown_show()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;

		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		$api_key_get = $obj->api_key;
		function convert_smart_quotes($string)

		{
			$search = array(
				chr(145),
				chr(146),
				chr(147),
				chr(148),
				chr(151)
			);

			$replace = array(
				"",
				"",
				'',
				'',
				''
			);

			return str_replace($search, $replace, $string);
		}
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$sql = "select * from tbl_languages WHERE key_name='Language' ORDER BY values_name";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$key = $row['key_name'];
				$name = $row['values_name'];
				$id = $row['id'];
				$user_image20 = $row['images'];
				if ($user_image20 == "") {
					$imagepath = "";
				} else {

					$imagepath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $user_image20;
				}
				$info[] = array('id' => $id, 'name' => $name, 'image' => $imagepath);
			}
			$sql1 = "select * from tbl_languages WHERE key_name='Country' ORDER BY values_name";
			$result1 = mysqli_query($connect, $sql1);
			$count = mysqli_num_rows($result1);
			while ($row1 = mysqli_fetch_array($result1)) {
				$key1 = $row1['key_name'];
				$name1 = $row1['values_name'];
				$id1 = $row1['id'];
				$countrycode = $row1['sub_id'];
				$user_image2000 = $row1['images'];
				if ($user_image2000 == '') {
					$imagepath1 = "";
				} else {
					$imagepath1 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $user_image2000;
				}
				$info1[] = array('id' => $id1, 'name' => $name1, 'image' => $imagepath1, 'Country_code' => $countrycode);
			}
			$sql2 = "select * from tbl_languages WHERE key_name='Songs' ORDER BY values_name";
			$result2 = mysqli_query($connect, $sql2);
			$count = mysqli_num_rows($result2);
			while ($row2 = mysqli_fetch_array($result2)) {
				$key2 = $row2['key_name'];
				$name2 = $row2['values_name'];
				$id2 = $row2['id'];
				$user_image200 = $row2['images'];
				if ($user_image200 == "") {
					$imagepath2 = "";
				} else {
					$imagepath2 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/lang_img/" . $user_image200;
				}
				$info2[] = array('id' => $id2, 'name' => $name2, 'image' => $imagepath2);
			}

			$sql3 = "select * from groups where id > 5";
			$result3 = mysqli_query($radioconnect, $sql3);
			while ($row3 = mysqli_fetch_array($result3)) {
				$name = convert_smart_quotes($row3['description']);
				$id = $row3['id'];
				$collage_id = $row3['name'];
				$info3[] = array('id' => $id, 'College_id' => $collage_id, 'college_name' => $name);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['language'] = $info;
				$postvalue['country'] = $info1;
				$postvalue['song_genre'] = $info2;
				$postvalue['College_List'] = $info3;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
			}

			$response = json_encode($postvalue);
			print $response;
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}



	private function slider_show()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$sql = "select * from tbl_slider where slider_visible=1 order by slider_order ASC";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$slider_name = $row['slider_name'];
				$slider_image = $row['slider_image'];
				$slider_time = $row['slider_time'];
				$imagepath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/slider/" . $slider_image;
				$info[] = array('slider_name' => $slider_name, 'slider_image' => $imagepath, 'slider_time' => $slider_time);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['sliders'] = $info;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
			}
			$response = json_encode($postvalue);
			print $response;
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}


	private function old_track()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
			//$title_tag = $obj->title_tag;
			$title = $obj->title;
			$name = "title" . $title;
			//$title_description = $obj->title_description;
			$sql = "select * from track where title LIKE '%$title%' OR title_tag LIKE '%$title%' OR description LIKE '%$title%'";
			$result = mysqli_query($radioconnect, $sql);
			while ($row = mysqli_fetch_array($result)) {
				$title_name = $row['title'];
				$title_description = $row['description'];
				$title_tag = $row['title_tag'];
				$url = "http://broadcast.campusradio.rocks/" . $row['url'];
				$info[] = array('Title_Name' => $title_name, 'Tag' => $title_tag, 'Title_Description' => $title_description, 'Title_Url' => $url);
			}
			$count = mysqli_num_rows($result);
			//$url = $row['url'];
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['title_url'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}




	private function favourite_channel_add()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$userid = $obj->userid;
			$favourite_channel = $obj->favourite_channel;
			$input = "userid=" . $userid . ",favourite_channel=" . $favourite_channel;
			$query = "select favourite_channel from tbl_user where id='$userid'";
			$queryresult = mysqli_query($connect, $query);
			$rowqury = mysqli_fetch_array($queryresult);
			$fav_channel = $rowqury['favourite_channel'];
			if ($fav_channel == "") {
				// $favourite_channel = implode(',', $favourite_channel2);
				$sql = "UPDATE tbl_user SET favourite_channel='$favourite_channel' WHERE id='$userid'";
				$result = mysqli_query($connect, $sql);
			} else {
				$favourite_channel1 = $fav_channel . "," . $favourite_channel;
				$sql = "UPDATE tbl_user SET favourite_channel='$favourite_channel1' WHERE id='$userid'";
				$result = mysqli_query($connect, $sql);
			}
			if ($result) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "Added to Favorite";
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "User Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function favourite_channel_get()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$userid = $obj->userid;
			$input = "userid=" . $userid;
			$sql = "select * from tbl_user where id = '$userid'";
			$result = mysqli_query($connect, $sql);
			$row = mysqli_fetch_array($result);
			$favourite_channel = $row['favourite_channel'];
			$query = "select * from tbl_channel WHERE id IN ($favourite_channel) order by id desc";
			$queryresult = mysqli_query($connect, $query);
			$count = mysqli_num_rows($queryresult);
			$info = array();
			while ($queryrow = mysqli_fetch_array($queryresult)) {
				$id = $queryrow['id'];
				$category_id = $queryrow['category_id'];


				$catquery = "select * from tbl_category where cid='$category_id'";
				$catresult = mysqli_query($connect, $catquery);
				$catrow = mysqli_fetch_array($catresult);
				$category_name = $catrow['category_name'];
				$category_image1 = $catrow['category_image'];
				$category_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/category/" . $category_image1;

				$channel_name = $queryrow['channel_name'];
				$channel_image1 = $queryrow['channel_image'];
				$channel_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $channel_image1;
				$channel_url = $queryrow['channel_url'];
				$channel_description = $queryrow['channel_description'];
				$channel_type = $queryrow['channel_type'];
				$channel_player_type = $queryrow['channel_player_type'];
				$info[] = array("channel_id" => $id, "category_id" => $category_id, "category_name" => $category_name, "category_image" => $category_image, "channel_name" => $channel_name, "channel_image" => $channel_image, "channel_url" => $channel_url, "channel_description" => $channel_description, "channel_type" => $channel_type, "channel_player_type" => $channel_player_type);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Channel_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "User Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function favourite_channel_remove()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$userid = $obj->userid;
			$removeid = $obj->remove_favourite_channel;
			$input = "userid=" . $userid . ",remove_favourite_channel=" . $removeid;
			$sql = "select * from tbl_user where id = '$userid'";
			$result = mysqli_query($connect, $sql);
			$row = mysqli_fetch_array($result);
			$favourite_channel = $row['favourite_channel'];
			$parts = explode(',', $favourite_channel);
			while (($i = array_search($removeid, $parts)) !== false) {
				unset($parts[$i]);
			}
			$x =  implode(',', $parts);
			$query = "UPDATE tbl_user SET favourite_channel='$x' WHERE id='$userid'";
			$resultquery = mysqli_query($connect, $query);
			if ($resultquery) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "Removed from Favorite";
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function FAQ_get()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$sql = "select * from tbl_faq_table order by id asc";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$id = $row['id'];
				$question = $row['question'];
				$answer = $row['answer'];
				$question_catagory = $row['question_catagory'];
				$info[] = array('QuestionId' => $id, 'Question' => $question, 'Answer' => $answer, 'Question_Catagory' => $question_catagory);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Question_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function get_url_api()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$type = $obj->type;
			$input = "type=" . $type;
			$sql = "SELECT * FROM tbl_languages WHERE key_name='Url' AND values_name='$type'";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			$row = mysqli_fetch_array($result);
			$url = $row['Description'];

			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['url'] = $url;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Please Select Correct Type!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}




	private function get_advertisments()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			function searchForId($id, $array)
			{
				foreach ($array as $key => $val) {
					if ($val['id'] === $id) {
						return $key;
					}
				}
				return null;
			}
			$userid = $obj->userid;
			$input = "userid=" . $userid;
			$checksql = "select * from tbl_user where id='$userid'";
			$checkresult = mysqli_query($connect, $checksql);
			$checkcount = mysqli_num_rows($checkresult);
			if ($checkcount == 0) {
				$postvalue['responseStatus'] = 208;
				$postvalue['responseMessage'] = "UserId Not Found!";
				$response = json_encode($postvalue);
				print $response;

				$datetimeforlogs = date('Y-m-d H:i:s');
				$ipforlogs = $_SERVER['REMOTE_ADDR'];
				$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



				$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
				$resultforlogs = mysqli_query($connect, $sqlforlogs);


				die;
			}

			$sql = "select * from tbl_user where id='$userid'";
			$result = mysqli_query($connect, $sql);
			$row = mysqli_fetch_array($result);
			$collage_id = $row['college_id'];
			if ($collage_id == "" || $collage_id == 0) {
				$yx = "";
				$xy = "";
			} else {
				$groupquery = "select * from users_groups where user_id='$collage_id'";
				$grpresult = mysqli_query($radioconnect, $groupquery);
				while ($grprow = mysqli_fetch_array($grpresult)) {
					$grpids[] = $grprow['group_id'];
				}
				$zy = implode(",", $grpids);
				$yx = "where college_id='$collage_id'";
				$xy = "(college_id='$collage_id' OR college_grp_id IN ($zy))  and";
			}
			$logselect = "select * from adver_logs $yx order by id desc limit 1";
			$logselectresult = mysqli_query($connect, $logselect);
			$logselectrow = mysqli_fetch_array($logselectresult);
			$addver_id = $logselectrow['ad_id'];



			$query = "select * from tbl_advt where $xy advertisement_visible='1' AND student_count > 0";
			$queryresult = mysqli_query($connect, $query);
			$coun = mysqli_num_rows($queryresult);
			if ($coun > 0) {
				$filter_field = array();
				while ($queryrow = mysqli_fetch_array($queryresult)) {
					$id = $queryrow['id'];
					$advertisement_name = $queryrow['advertisement_name'];
					$advertisement_img_size = $queryrow['advertisement_img_size'];
					$vid = $queryrow['vendor_id'];
					preg_match_all('!\d+!', $advertisement_img_size, $matches);
					foreach ($matches as $matche) {
						$x =  $matche[0];
						$y = $matche[1];
					}
					$student_count = $queryrow['student_count'];
					$advertisement_image1 = $queryrow['advertisement_image'];
					$advertisement_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
						"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/advertisement/" . $advertisement_image1;
					$array[] = array("id" => $id, "name" => $advertisement_name, "image_width" => $x, "image_height" => $y, "image" => $advertisement_image, "vid" => $vid, "student_count" => $student_count);
				}
				if ($addver_id == "") {
					$x = $array[0];
				} else {
					$id = searchForId($addver_id, $array);
					$keyincremnt = $id + 1;

					if (isset($array[$keyincremnt]['id']) == "") {
						$x = $array[0];
					} else {
						$x = $array[$keyincremnt];
					}
				}
				$randomid = $x['id'];
				$addname = $x['name'];
				$venid = $x['vid'];
				$stucount = $x['student_count'];
				$addimages = $x['image'];
				$minus = $stucount - 1;
				$datetime = date('Y-m-d H:i:s');

				$logsquery = "INSERT INTO adver_logs (ad_name,ad_id,vender_id,user_id,college_id,add_image,date_time) VALUES ('$addname','$randomid','$venid','$userid','$collage_id','$addimages','$datetime')";
				$logresult = mysqli_query($connect, $logsquery);


				$updatequery = "update tbl_advt set student_count='$minus' where id='$randomid'";
				$updateresult = mysqli_query($connect, $updatequery);
				if ($updateresult) {
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$postvalue['Advertisment'] = $x;
					$response = json_encode($postvalue);
					print $response;
				}
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Advertisment Not Found!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
	}


	private function send_otp()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$country_code = $obj->country_code;
			$phonenumber = $obj->phone_number;
			$email_id = $obj->email_id;
			$input = "phone_number=" . $phonenumber . ",email_id=" . $email_id;
			$otpnumber = mt_rand(1000, 9999);
			$OTPResponse = "Your OTP for Verification is $otpnumber.CampusRadio";

			$query = "select * from tbl_sms_email_credencial where active='1'";
			$reusltquery = mysqli_query($connect, $query);
			$row = mysqli_fetch_array($reusltquery);
			$user = $row['username'];
			$password = $row['password'];
			$senderid = $row['senderid'];
			$pe_id = $row['pe_id'];
			$template_id = $row['template_id'];
			$sms_url = $row['sms_url'];

			$user2 = $row['email_sender_username'];
			$password2 = $row['email_sender_password'];

			if ($country_code != "" && $phonenumber != "" && $email_id == "") {


				if ($country_code == '+91') {

					$request = "";

					$param['user'] = $user;
					$param['password'] = $password;
					$param["sender"] = $senderid;
					$param['PhoneNumber'] = "$phonenumber";
					$param["msgType"] = "PT";
					$param['Text'] = "$OTPResponse";
					$param['pe_id'] = $pe_id;
					$param['template_id'] = $template_id;

					foreach ($param as $key => $val) {
						$request .= $key . "=" . urlencode($val);

						$request .= "&";
					}
					$request = substr($request, 0, strlen($request) - 1);
					$url = $sms_url . "?" . $request;
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$curl_scraped_page = curl_exec($ch);
					curl_close($ch);
					$curl_scraped_page;
					$xml = new SimpleXMLElement($curl_scraped_page);
					$record = json_decode(json_encode($xml), true);

					if (!$record['ErrorCode']) {
						$sql = "insert into tbl_otp (otp,mobileno) values ('$otpnumber','$phonenumber')";
						$result = mysqli_query($connect, $sql);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "OK";
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Failed to Send OTP please try again";
						$response = json_encode($postvalue);
						print $response;
					}
				} else if ($country_code != '+91') {
					require 'twilio.php';

					$ApiVersion = "2010-04-01";
					// set our AccountSid and AuthToken
					$AccountSid = "ACf8730e89208f1dfc6f741bd6546dc055";
					$AuthToken = "45e624a756b26f8fbccb52a6a0a44ac9";
					// instantiate a new Twilio Rest Client
					$client = new TwilioRestClient($AccountSid, $AuthToken);
					$number = $country_code . "" . $phonenumber;
					$message = "$OTPResponse";
					$response = $client->request(
						"/$ApiVersion/Accounts/$AccountSid/SMS/Messages",
						"POST",
						array(
							"To" => $number,
							"From" => "732-798-7878",
							"Body" => $message
						)
					);
					$myArray =	json_decode(json_encode($response), true);
					$HttpStatus = $myArray['HttpStatus'];
					if ($HttpStatus == 400) {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Failed to Send OTP please try again";
						$response = json_encode($postvalue);
						print $response;
					} else {
						$sql = "insert into tbl_otp (otp,mobileno) values ('$otpnumber','$phonenumber')";
						$result = mysqli_query($connect, $sql);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "OK";
						$response = json_encode($postvalue);
						print $response;
					}
				}
			}

			if ($country_code == "" && $phonenumber == "" && $email_id != "") {
				$name = $obj->name;
				/*
					error_reporting(0);
define("SMTP_HOST", "SMTP_HOST_NAME");
define("SMTP_PORT", "SMTP_PORT");
define("SMTP_UNAME", "VALID_EMAIL_ACCOUNT"); 
define("SMTP_PWORD", "VALID_EMAIL_ACCOUNTS_PASSWORD"); 
include "class.phpmailer.php";
$myFile ="emaillog.txt";
$mail = new PHPMailer;
$mail->IsSMTP(); 
$mail->Host ="smtp.gmail.com"; 
$mail->Port = 465;
$mail->SMTPAuth = true; 
	$mail->Username = $user2; 
	$mail->Password = $password2; 
	$mail->AddReplyTo($user2, "CampusRadio"); 
	$mail->SetFrom($user2, "CampusRadio"); 
$mail->AddAddress( "$email_id", "tapan");
$mail->Subject = "Campus Radio Registration OTP" ;
$mail->MsgHTML( "Dear $name,
		<br/><br/>
		Your one time Password is $otpnumber.
		<br/><br/>
		Regards, <br/>
		Team Campus Radio" ); 
 $send =$mail->Send();
*/
				$Email_Headers = "MIME-Version: 1.0\n";
				$Email_Headers .= "Content-type: text/html; charset=utf-8\n";
				$Email_Headers .= "X-Priority: 3\n";
				$Email_Headers .= "X-MSMail-Priority: High\n";
				$Email_Headers .= "Reply-To: collegeradiorocks@gmail.com\r\n";
				$Email_Headers .= "Return-Path:collegeradiorocks@gmail.com\r\n";
				$Email_Headers .= "X-Mailer: My Mailer\n";
				$subject = "Campus Radio Registration OTP";
				$emailMsg = "Dear $name,
		<br/><br/>
		$otpnumber is your Campus Radio Verification Code.
		<br/><br/>
		Regards, <br/>
		Team Campus Radio";

				$emailHeader = $Email_Headers . "From:collegeradiorocks@gmail.com<collegeradiorocks@gmail.com>\n" . "\r\n";
				$result = mail($email_id, $subject, $emailMsg, $emailHeader);
				if ($result) {
					$sql = "insert into tbl_otp (otp,email) values ('$otpnumber','$email_id')";
					$result = mysqli_query($connect, $sql);
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Failed to Send OTP please try again";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}



	private function varify_otp()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$country_code = $obj->country_code;
			$phonenumber1 = $obj->phone_number;
			$email_id = $obj->email_id;
			$otp = $obj->otp;
			$phonenumber =  substr($phonenumber1, -10);
			$input = "phone_number=" . $phonenumber . ",email_id=" . $email_id . ",otp=" . $otp;
			if ($country_code != "" && $phonenumber != "" && $email_id == "") {
				$a = "mobileno='$phonenumber' AND otp='$otp'";
				$b = "mobileno='$phonenumber'";
				$querys = "select * from tbl_user where phone='$phonenumber'";
				$results = mysqli_query($connect, $querys);
				$rows = mysqli_fetch_array($results);
				$name = $rows['username'];
				$userid = $rows['id'];
			}
			if ($country_code == "" && $phonenumber == "" && $email_id != "") {
				$a = "email='$email_id' AND otp='$otp'";
				$b = "email='$email_id'";
				$querys = "select * from tbl_user where email='$email_id'";
				$results = mysqli_query($connect, $querys);
				$rows = mysqli_fetch_array($results);
				$name = $rows['username'];
				$userid = $rows['id'];
			}
			$sql = "select * from tbl_otp where $a";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			if ($count > 0) {
				$query = "delete from tbl_otp where $b";
				$queryresult = mysqli_query($connect, $query);
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['User_Name'] = $name;
				$postvalue['User_Id'] = $userid;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Your Contact And OTP are not matched";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}


	private function send_login_otp()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$country_code = $obj->country_code;
			$phonenumber = $obj->phone_number;
			$email_id = $obj->email_id;
			$otpnumber = mt_rand(1000, 9999);
			$input = "phone_number=" . $phonenumber . ",email_id=" . $email_id;
			$OTPResponse = "Your OTP for Verification is $otpnumber.CampusRadio";

			$query = "select * from tbl_sms_email_credencial where active='1'";
			$reusltquery = mysqli_query($connect, $query);
			$row = mysqli_fetch_array($reusltquery);
			$user = $row['username'];
			$password = $row['password'];
			$senderid = $row['senderid'];
			$pe_id = $row['pe_id'];
			$template_id = $row['template_id'];
			$sms_url = $row['sms_url'];

			$user2 = $row['email_sender_username'];
			$password2 = $row['email_sender_password'];



			if ($country_code != "" && $phonenumber != "" && $email_id == "") {
				$query3 = "select * from tbl_user where phone='$phonenumber'";
				$resultqiery3 = mysqli_query($connect, $query3);
				$row3 = mysqli_num_rows($resultqiery3);

				if ($row3 == "") {
					$postvalue['responseStatus'] = 208;
					$postvalue['responseMessage'] = 'User Not Found Please Enter Correct Mobile Number';
					$response = json_encode($postvalue);
					print $response;

					$datetimeforlogs = date('Y-m-d H:i:s');
					$ipforlogs = $_SERVER['REMOTE_ADDR'];
					$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



					$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
					$resultforlogs = mysqli_query($connect, $sqlforlogs);

					die;
				}
				if ($country_code == '+91') {
					$request = "";
					$param['user'] = $user;
					$param['password'] = $password;
					$param["sender"] = $senderid;
					$param['PhoneNumber'] = "$phonenumber";
					$param["msgType"] = "PT";
					$param['Text'] = "$OTPResponse";
					$param['pe_id'] = $pe_id;
					$param['template_id'] = $template_id;

					foreach ($param as $key => $val) {
						$request .= $key . "=" . urlencode($val);

						$request .= "&";
					}
					$request = substr($request, 0, strlen($request) - 1);
					$url = $sms_url . "?" . $request;
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$curl_scraped_page = curl_exec($ch);
					curl_close($ch);
					$curl_scraped_page;
					$xml = new SimpleXMLElement($curl_scraped_page);
					$record = json_decode(json_encode($xml), true);

					if (!$record['ErrorCode']) {
						$sql = "insert into tbl_otp (otp,mobileno) values ('$otpnumber','$phonenumber')";
						$result = mysqli_query($connect, $sql);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "OK";
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Failed to Send OTP please try again";
						$response = json_encode($postvalue);
						print $response;
					}
				} else if ($country_code != '+91') {
					require 'twilio.php';

					$ApiVersion = "2010-04-01";
					// set our AccountSid and AuthToken
					$AccountSid = "ACf8730e89208f1dfc6f741bd6546dc055";
					$AuthToken = "45e624a756b26f8fbccb52a6a0a44ac9";
					// instantiate a new Twilio Rest Client
					$client = new TwilioRestClient($AccountSid, $AuthToken);
					$number = $country_code . "" . $phonenumber;
					$message = "$OTPResponse";
					$response = $client->request(
						"/$ApiVersion/Accounts/$AccountSid/SMS/Messages",
						"POST",
						array(
							"To" => $number,
							"From" => "732-798-7878",
							"Body" => $message
						)
					);
					$myArray =	json_decode(json_encode($response), true);
					$HttpStatus = $myArray['HttpStatus'];
					if ($HttpStatus == 400) {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Failed to Send OTP please try again";
						$response = json_encode($postvalue);
						print $response;
					} else {
						$sql = "insert into tbl_otp (otp,mobileno) values ('$otpnumber','$phonenumber')";
						$result = mysqli_query($connect, $sql);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "OK";
						$response = json_encode($postvalue);
						print $response;
					}
				}
			}

			if ($country_code == "" && $email_id != "" && $phonenumber == "") {
				$query3 = "select * from tbl_user where email='$email_id'";
				$resultqiery3 = mysqli_query($connect, $query3);
				$count3 = mysqli_num_rows($resultqiery3);

				if ($count3 == 0) {
					$postvalue['responseStatus'] = 208;
					$postvalue['responseMessage'] = 'User Not Found Please Enter Correct Email';
					$response = json_encode($postvalue);
					print $response;

					$datetimeforlogs = date('Y-m-d H:i:s');
					$ipforlogs = $_SERVER['REMOTE_ADDR'];
					$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



					$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
					$resultforlogs = mysqli_query($connect, $sqlforlogs);


					die;
				} else {
					$row3 = mysqli_fetch_array($resultqiery3);
					$name = $row3['username'];
				}


				$Email_Headers = "MIME-Version: 1.0\n";
				$Email_Headers .= "Content-type: text/html; charset=utf-8\n";
				$Email_Headers .= "X-Priority: 3\n";
				$Email_Headers .= "X-MSMail-Priority: High\n";
				$Email_Headers .= "Reply-To: collegeradiorocks@gmail.com\r\n";
				$Email_Headers .= "Return-Path:collegeradiorocks@gmail.com\r\n";
				$Email_Headers .= "X-Mailer: My Mailer\n";
				$subject = "Campus Radio Registration OTP";
				$emailMsg = "Dear $name,
		<br/><br/>
		$otpnumber is your Campus Radio Verification Code.
		<br/><br/>
		Regards, <br/>
		Team Campus Radio";

				$emailHeader = $Email_Headers . "From:collegeradiorocks@gmail.com<collegeradiorocks@gmail.com>\n" . "\r\n";
				$result = mail($email_id, $subject, $emailMsg, $emailHeader);
				if ($result) {
					$sql = "insert into tbl_otp (otp,email) values ('$otpnumber','$email_id')";
					$result = mysqli_query($connect, $sql);
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Failed to Send OTP please try again";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function Statistics_Online()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');

		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$college_na = $obj->college_name;
			if ($college_na == "") {
				$sql = "select * from  online_statistics where status='1'";
				$result = mysqli_query($connect, $sql);
				$a = mysqli_num_rows($result);

				$query = "select * from  online_statistics where status='0'";
				$queryresult = mysqli_query($connect, $query);
				$b = mysqli_num_rows($queryresult);
				if ($a > 0) {
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$postvalue['Online_Users'] = $a;
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "No User Online";
					$response = json_encode($postvalue);
					print $response;
				}
			} else {
				$sql1 = "select * from  online_statistics where status='1' and college_id='$college_na'";
				$result1 = mysqli_query($connect, $sql1);
				$c = mysqli_num_rows($result1);
				$abc = mysqli_fetch_array($result1);
				$college_n = $abc['college_name'];
				if ($c) {
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$postvalue['College_Name'] = $college_n;
					$postvalue['Online_Users'] = $c;
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "No User Online";
					$response = json_encode($postvalue);
					print $response;
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function College_List()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$cquery = "select * from groups where id NOT IN ('1','2')";
			$cresult = mysqli_query($radioconnect, $cquery);
			while ($crow = mysqli_fetch_array($cresult)) {
				$name = $crow['name'];
				$id = $crow['id'];
				$info[] = array('college_id' => $id, 'college_name' => $name);
			}
			if ($id) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['College_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No College Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function Channel_Details()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$sql = "select * from tbl_channel where channel_visible='1'";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$channel_id = $row['id'];
				$category_id = $row['category_id'];
				$channel_name = $row['channel_name'];
				$channel_image1 = $row['channel_image'];
				$channel_url = $row['channel_url'];
				$channel_description = $row['channel_description'];
				$channel_type = $row['channel_type'];
				$channel_player_type = $row['channel_player_type'];
				$channel_genre = $row['channel_genre'];
				$referid = substr($channel_url, strpos($channel_url, "=") + 1);
				$tracks_url = "http://broadcast.campusradio.rocks/index.php/stream/week_calendar/tracks/widget?id=" . $referid;
				$shows_url = "http://broadcast.campusradio.rocks/index.php/stream/week_calendar/events/widget?id=" . $referid;
				$channel_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $channel_image1;
				$info[] = array('channel_id' => $channel_id, 'category_id' => $category_id, 'channel_name' => $channel_name, 'channel_image' => $channel_image, 'channel_url' => $channel_url, 'tracks_url' => $tracks_url, 'shows_url' => $shows_url, 'channel_description' => $channel_description, 'channel_type' => $channel_type, 'channel_player_type' => $channel_player_type, 'channel_genre' => $channel_genre);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['College_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No Channel List Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function channel_feature_details()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$channel_cat = $obj->channel_cat;
			$sql = "select * from tbl_channel where channel_visible = '1' and channel_feature = '$channel_cat'";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$channel_id = $row['id'];
				$category_id = $row['category_id'];
				$channel_name = $row['channel_name'];
				$channel_image1 = $row['channel_image'];
				$channel_url = $row['channel_url'];
				$channel_description = $row['channel_description'];
				$channel_type = $row['channel_type'];
				$channel_player_type = $row['channel_player_type'];
				$channel_genre = $row['channel_genre'];
				$referid = substr($channel_url, strpos($channel_url, "=") + 1);
				$tracks_url = "http://broadcast.campusradio.rocks/index.php/stream/week_calendar/tracks/widget?id=" . $referid;
				$shows_url = "http://broadcast.campusradio.rocks/index.php/stream/week_calendar/events/widget?id=" . $referid;
				$channel_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $channel_image1;
				$info[] = array('channel_id' => $channel_id, 'category_id' => $category_id, 'channel_name' => $channel_name, 'channel_image' => $channel_image, 'channel_url' => $channel_url, 'tracks_url' => $tracks_url, 'shows_url' => $shows_url, 'channel_description' => $channel_description, 'channel_type' => $channel_type, 'channel_player_type' => $channel_player_type, 'channel_genre' => $channel_genre);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['College_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No Channel List Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function radio_guide()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		$todaydate1 = date('Y-m-d H:i:s');
		$todaydate2 = date('Y-m-d');
		$dayofweek = date('w', strtotime($todaydate2));

		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$test_date = $obj->start_date;
			$test_time = $obj->start_time;
			if ($test_date == '' && $test_time == '') {
				$todaydate = $todaydate1;
				$dayofweek = date('w', strtotime($todaydate2));
				$todaydate2 = $todaydate2;
			} else if ($test_time == '') {
				$test_time1 = "00:00:00";
				$todaydate = date('Y-m-d H:i:s', strtotime("$test_date $test_time1"));
				$dayofweek = date('w', strtotime($test_date));
				$todaydate2 = $test_date;
			} else if ($test_date == '') {
				$todaydate = date('Y-m-d H:i:s', strtotime("$todaydate2 $test_time"));
				$dayofweek = date('w', strtotime($todaydate2));
				$todaydate2 = $todaydate2;
			} else {
				$todaydate = date('Y-m-d H:i:s', strtotime("$test_date $test_time"));
				$dayofweek = date('w', strtotime($test_date));
				$todaydate2 = $test_date;
			}

			$sql = "select * from calendar where (FIND_IN_SET('$dayofweek',repeat_days) AND repeat_type !='-1') OR (starts < '$todaydate' and ends > '$todaydate') OR (end_date > '$todaydate2')";
			mysqli_set_charset($radioconnect, 'utf8');
			$result = mysqli_query($radioconnect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$default_user_id = $row['default_user_id'];
				$xyz[] = $default_user_id;
			}
			$List = implode(',', $xyz);

			$livequery = "SELECT * FROM `app.campusradio.rocks`.tbl_channel where (channel_url LIKE 'http://broadcast.campusradio.rocks/index.php/stream?%') and (SUBSTRING_INDEX(channel_url, 'id=', -1) IN ($List))";
			mysqli_set_charset($connect, 'utf8');
			$liveresult = mysqli_query($connect, $livequery);
			while ($row1 = mysqli_fetch_array($liveresult)) {
				$channel_id = $row1['id'];
				$channel_name = $row1['channel_name'];
				$img1 = $row1['channel_image'];
				$channel_url = $row1['channel_url'];
				$img = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $img1;
				$chanid = substr($channel_url, strpos($channel_url, "=") + 1);
				$event_name = array();
				$starts_time = array();
				$ends_time = array();
				$duration = array();
				$tracks = array();
				$description = array();
				$album_img = array();
				$artist_img = array();
				$sql1 = "select *,cast(starts as time) as timeerset from calendar where (FIND_IN_SET('$dayofweek',repeat_days) AND repeat_type !='-1' AND default_user_id='$chanid') OR ((cast(starts as date) = '$todaydate2' and cast(ends as date) = '$todaydate2') AND repeat_type='-1' AND default_user_id='$chanid' ) OR (end_date > '$todaydate2' AND default_user_id='$chanid') ORDER BY timeerset ASC";

				mysqli_set_charset($radioconnect, 'utf8');
				$result1 = mysqli_query($radioconnect, $sql1);
				while ($row2 = mysqli_fetch_array($result1)) {
					$calander_ids = $row2['id'];
					$event_name[] = $row2['name'];
					$event_name2 = $row2['name'];
					$starts_time1 = $row2['starts'];
					$ends_time1 = $row2['ends'];
					$starts_time_for = new DateTime($starts_time1);
					$starts_time[] = $starts_time_for->format('H:i:s');
					$starts_time2 = $starts_time_for->format('H:i:s');
					$ends_time_for = new DateTime($ends_time1);
					$ends_time[] = $ends_time_for->format('H:i:s');
					$ends_time2 = $ends_time_for->format('H:i:s');
					$duration[] = $row2['duration'] / 60;
					// if($ends_time2 == "00:00:00"){
					// $ends_time2 = "23:59:59";
					// }else if($ends_time2 < $starts_time2){
					// $starts_time2 = "00:00:00";
					// $ends_time2 = "23:59:59";
					// }else{
					// $ends_time2 = $ends_time2;
					// }
					// SELECT calendar.id as calenderid, calendar.name as calendername,
					// playlist.id as playlistid , playlist.name as playlistname , playlist_calendar.time_start as playstime, playlist_calendar.time_end as playetime, 
					// track.title, 
					// track_playlist.time_start + playlist_calendar.time_start as trackstart, 
					// track_playlist.time_end + playlist_calendar.time_start as trackend 
					// from playlist_calendar 
					// JOIN track_playlist ON playlist_calendar.playlist_id = track_playlist.playlist_id 
					// JOIN track ON track.id = track_playlist.track_id 
					// JOIN calendar ON calendar.id = playlist_calendar.calendar_id 
					// JOIN playlist ON playlist.id = playlist_calendar.playlist_id
					// where playlist_calendar.calendar_id='52'


					$sqltrack = "SELECT track.title,track.description,track.artist_img,track.album_img,
						track_playlist.time_start + playlist_calendar.time_start as trackstart, 
		                track_playlist.time_end + playlist_calendar.time_start as trackend 
                        from playlist_calendar 
                        JOIN track_playlist ON playlist_calendar.playlist_id = track_playlist.playlist_id 
                        JOIN track ON track.id = track_playlist.track_id 
                        where playlist_calendar.calendar_id='$calander_ids' ORDER BY trackstart ASC";
					$resulttrack = mysqli_query($radioconnect, $sqltrack);
					while ($rowtrack = mysqli_fetch_array($resulttrack)) {
						$tracks1 = $rowtrack['title'];
						if ($rowtrack['description'] == "") {
							$description[] = $rowtrack['description'];
						} else {
							$description[] = $event_name2 . "@" . $rowtrack['description'];
						}
						if ($rowtrack['artist_img'] == "") {
							$artist_img[] = $rowtrack['artist_img'];
						} else {
							$artist_img[] = $event_name2 . "@" . "http://broadcast.campusradio.rocks/" . $rowtrack['artist_img'];
						}
						if ($rowtrack['album_img'] == "") {
							$album_img[] = $rowtrack['album_img'];
						} else {
							$album_img[] = $event_name2 . "@" . "http://broadcast.campusradio.rocks/" . $rowtrack['album_img'];
						}

						$trackstime = round($rowtrack['trackstart']);
						$tracketime = round($rowtrack['trackend']);

						$time_start1 = strtotime("+$trackstime seconds", strtotime($starts_time2));
						$time_start =  date('H:i:s', $time_start1);

						$time_end1 = strtotime("+$tracketime seconds", strtotime($starts_time2));
						$time_end =  date('H:i:s', $time_end1);
						//	if(($starts_time2 <= $time_start &&  $starts_time2 < $time_end) && ($ends_time2 >= $time_start &&  $ends_time2 > $time_end)){

						$tracks[] = $event_name2 . "#" . $starts_time2 . "=" . $ends_time2 . "@" . $time_start . "-" . $time_end . " " . $tracks1;
						//}
					}
				}
				$post[] = array('channel_id' => $channel_id, 'channel_name' => $channel_name, 'channel_image' => $img, 'event_name' => $event_name, 'start_time' => $starts_time, 'end_time' => $ends_time, 'tracks' => $tracks, 'track_discription' => $description, 'artist_img' => $artist_img, 'album_img' => $album_img, 'duration' => $duration);
			}


			$blanckquery = "SELECT * FROM `app.campusradio.rocks`.tbl_channel where (channel_url LIKE 'http://broadcast.campusradio.rocks/index.php/stream?%') and (SUBSTRING_INDEX(channel_url, 'id=', -1) NOT IN ($List))";
			mysqli_set_charset($connect, 'utf8');
			$blackresult = mysqli_query($connect, $blanckquery);
			while ($rowss = mysqli_fetch_array($blackresult)) {
				$channel_id1 = $rowss['id'];
				$channel_name1 = $rowss['channel_name'];
				$img1 = $rowss['channel_image'];
				$img2 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $img1;
				$post2[] = array(
					'channel_id' => $channel_id1, 'channel_name' => $channel_name1, 'channel_image' => $img2, 'event_name' => '',
					'start_time' => '', 'end_time' => '', 'tracks' => '', 'track_discription' => '', 'artist_img' => '', 'album_img' => '', 'duration' => ''
				);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Live_Channel_List'] = $post;
				$postvalue['Blank_Channel_List'] = $post2;
				$response = json_encode($postvalue, JSON_UNESCAPED_UNICODE);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No Channel List Found";
				$response = json_encode($postvalue, JSON_UNESCAPED_UNICODE);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function current_track()
	{
		include "../includes/config.php";

		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
			$channel_url1 = $obj->channel_url;
			$channel_url = substr($channel_url1, strpos($channel_url1, "=") + 1);

			$sqlcatfnd = mysqli_query($connect, "select * from tbl_channel where channel_url='$channel_url1'");
			$rowcatfnd = mysqli_fetch_array($sqlcatfnd);
			$category_id = $rowcatfnd['category_id'];
			$chnids = $rowcatfnd['id'];
			$datetime = date("H:i:s");
			$datetimess = date("Y-m-d H:i:s");
			$todaydate2 = date('Y-m-d');
			$dayofweek = date('w', strtotime($todaydate2));
			$sql = "select * from calendar where  (
           (starts < '$datetimess' and ends > '$datetimess') AND  default_user_id = '$channel_url') OR ((cast(starts as time)  <= '$datetime' 
           AND cast(ends as time) > '$datetime') AND  default_user_id='$channel_url' AND repeat_type !='-1' AND FIND_IN_SET('$dayofweek',repeat_days))";
			$result = mysqli_query($radioconnect, $sql);
			$countsd = mysqli_num_rows($result);
			if ($countsd == 0) {
				$sql = "select * from calendar where  (
           (starts < '$datetimess' and ends > '$datetimess') AND  default_user_id = '$channel_url') OR ((cast(starts as time)  <= '$datetime' 
           OR cast(ends as time) > '$datetime') AND  default_user_id='$channel_url' AND repeat_type !='-1' AND FIND_IN_SET('$dayofweek',repeat_days))";
				$result = mysqli_query($radioconnect, $sql);
			}
			//while($row=mysqli_fetch_array($result)){
			$row = mysqli_fetch_array($result);
			$calander_ids = $row['id'];
			$starts_time1 = $row['starts'];
			$ends_time1 = $row['ends'];
			$starts_time_for = new DateTime($starts_time1);
			$starts_time[] = $starts_time_for->format('H:i:s');
			$starts_time2 = $starts_time_for->format('H:i:s');
			$ends_time_for = new DateTime($ends_time1);
			$ends_time[] = $ends_time_for->format('H:i:s');
			$playquery = "SELECT track.id,track.title,track.artist_img,track.album_img,
						track_playlist.time_start + playlist_calendar.time_start as trackstart, 
		                track_playlist.time_end + playlist_calendar.time_start as trackend 
                        from playlist_calendar 
                        JOIN track_playlist ON playlist_calendar.playlist_id = track_playlist.playlist_id 
                        JOIN track ON track.id = track_playlist.track_id 
                        where playlist_calendar.calendar_id='$calander_ids'";
			$playresult = mysqli_query($radioconnect, $playquery);
			while ($playrow = mysqli_fetch_array($playresult)) {
				$track_id1 = $playrow['id'];
				$tracks1	   = $playrow['title'];

				$trackstime = round($playrow['trackstart']);
				$tracketime = round($playrow['trackend']);

				$time_start1 = strtotime("+$trackstime seconds", strtotime($starts_time2));
				$time_start =  date('H:i:s', $time_start1);

				$time_end1 = strtotime("+$tracketime seconds", strtotime($starts_time2));
				$time_end =  date('H:i:s', $time_end1);
				if ($datetime < $time_end && $datetime > $time_start) {
					$titletime[] = $time_start . "-" . $time_end;
					$ttitletimeer = (strtotime($time_end) - strtotime($time_start)) * 1000;
					$title[] = $tracks1;
					$track_id = $track_id1;
					if ($playrow['artist_img'] == "") {
						$artist_img[] = "";
					} else {
						$artist_img[] = "http://broadcast.campusradio.rocks/" . $playrow['artist_img'];
					}
					if ($playrow['album_img'] == "") {
						$album_img[] = "";
					} else {
						$album_img[] = "http://broadcast.campusradio.rocks/" . $playrow['album_img'];
					}
				}

				//$group_name[] = $rows['name'];
			}
			//}
			if ($title) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Track_time'] = $titletime;
				$postvalue['Track_Name'] = $title;
				$postvalue['Artist_img'] = $artist_img;
				$postvalue['Album_img'] = $album_img;
				$postvalue['Track_ID'] = $track_id;
				$postvalue['Channel_ID'] = $chnids;
				$postvalue['Playlist_ID'] = $calander_ids;
				$postvalue['Category_ID'] = $category_id;
				$postvalue['Track_time_milisec'] = "$ttitletimeer";
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}




	private function channel_track_details()
	{
		include "../includes/config.php";

		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			// $radioconnect = mysqli_connect('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
			$channel_url1 = $obj->channel_url;
			$channel_url = substr($channel_url1, strpos($channel_url1, "=") + 1);

			$sqlcatfnd = mysqli_query($connect, "select * from tbl_channel where channel_url='$channel_url1'");
			$rowcatfnd = mysqli_fetch_array($sqlcatfnd);
			$category_id = $rowcatfnd['category_id'];
			$chnids = $rowcatfnd['id'];
			$datetime = date("H:i:s");
			$datetimess = date("Y-m-d H:i:s");
			$todaydate2 = date('Y-m-d');
			$dayofweek = date('w', strtotime($todaydate2));
			$sql = "select * from calendar where  (
           (starts < '$datetimess' and ends > '$datetimess') AND  default_user_id = '$channel_url') OR ((cast(starts as time)  <= '$datetime' 
           AND cast(ends as time) > '$datetime') AND  default_user_id='$channel_url' AND repeat_type !='-1' AND FIND_IN_SET('$dayofweek',repeat_days))";
			$result = mysqli_query($radioconnect, $sql);
			$countsd = mysqli_num_rows($result);
			if ($countsd == 0) {
				$sql = "select * from calendar where  (
           (starts < '$datetimess' and ends > '$datetimess') AND  default_user_id = '$channel_url') OR ((cast(starts as time)  <= '$datetime' 
           OR cast(ends as time) > '$datetime') AND  default_user_id='$channel_url' AND repeat_type !='-1' AND FIND_IN_SET('$dayofweek',repeat_days))";
				$result = mysqli_query($radioconnect, $sql);
			}
			//while($row=mysqli_fetch_array($result)){
			$row = mysqli_fetch_array($result);
			$calander_ids = $row['id'];
			$starts_time1 = $row['starts'];
			$ends_time1 = $row['ends'];
			$starts_time_for = new DateTime($starts_time1);
			$starts_time[] = $starts_time_for->format('H:i:s');
			$starts_time2 = $starts_time_for->format('H:i:s');
			$ends_time_for = new DateTime($ends_time1);
			$ends_time[] = $ends_time_for->format('H:i:s');
			$playquery = "SELECT track.id,track.title,track.artist_img,track.album_img,
						track_playlist.time_start + playlist_calendar.time_start as trackstart, 
		                track_playlist.time_end + playlist_calendar.time_start as trackend 
                        from playlist_calendar 
                        JOIN track_playlist ON playlist_calendar.playlist_id = track_playlist.playlist_id 
                        JOIN track ON track.id = track_playlist.track_id 
                        where playlist_calendar.calendar_id='$calander_ids'";
			$playresult = mysqli_query($radioconnect, $playquery);
			while ($playrow = mysqli_fetch_array($playresult)) {
				$track_id1 = $playrow['id'];
				$tracks1	   = $playrow['title'];

				$trackstime = round($playrow['trackstart']);
				$tracketime = round($playrow['trackend']);

				$time_start1 = strtotime("+$trackstime seconds", strtotime($starts_time2));
				$time_start =  date('H:i:s', $time_start1);

				$time_end1 = strtotime("+$tracketime seconds", strtotime($starts_time2));
				$time_end =  date('H:i:s', $time_end1);

				$titletime[] = $time_start . "-" . $time_end;
				$ttitletimeer = (strtotime($time_end) - strtotime($time_start)) * 1000;
				$title[] = $tracks1;
				$track_id = $track_id1;
				if ($playrow['artist_img'] == "") {
					$artist_img[] = "";
				} else {
					$artist_img[] = "http://broadcast.campusradio.rocks/" . $playrow['artist_img'];
				}
				if ($playrow['album_img'] == "") {
					$album_img[] = "";
				} else {
					$album_img[] = "http://broadcast.campusradio.rocks/" . $playrow['album_img'];
				}


				//$group_name[] = $rows['name'];
			}
			//}
			if ($title) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Track_time'] = $titletime;
				$postvalue['Track_Name'] = $title;
				$postvalue['Artist_img'] = $artist_img;
				$postvalue['Album_img'] = $album_img;
				$postvalue['Track_ID'] = $track_id;
				$postvalue['Channel_ID'] = $chnids;
				$postvalue['Playlist_ID'] = $calander_ids;
				$postvalue['Category_ID'] = $category_id;
				$postvalue['Track_time_milisec'] = "$ttitletimeer";
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}


	private function all_channel_tracks()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
			$calander_ids = $obj->event_id;
			$starts_time2 = $obj->event_start_time;
			$ends_time1 = $obj->event_end_time;
			if ($ends_time1 == "00:00:00") {
				$ends_time = "23:59:59";
			} else if ($ends_time1 < $starts_time2) {
				$starts_time2 = "00:00:00";
				$ends_time = "23:59:59";
			} else {
				$ends_time = $ends_time1;
			}
			$playquery = "SELECT track.title,track.artist_img,track.album_img,track.description,
						track_playlist.time_start + playlist_calendar.time_start as trackstart, 
		                track_playlist.time_end + playlist_calendar.time_start as trackend 
                        from playlist_calendar 
                        JOIN track_playlist ON playlist_calendar.playlist_id = track_playlist.playlist_id 
                        JOIN track ON track.id = track_playlist.track_id 
                        where playlist_calendar.calendar_id='$calander_ids' ORDER BY trackstart ASC";
			$playresult = mysqli_query($radioconnect, $playquery);
			while ($playrow = mysqli_fetch_array($playresult)) {
				$tracks1 = $playrow['title'];
				$description1 = $playrow['description'];
				if ($description1 == "") {
					$description = "null";
				} else {
					$description = $description1;
				}
				$trackstime = round($playrow['trackstart']);
				$tracketimeend = round($playrow['trackend']);

				$time_start1 = strtotime("+$trackstime seconds", strtotime($starts_time2));
				$time_start =  date('H:i:s', $time_start1);

				$time_end1 = strtotime("+$tracketimeend seconds", strtotime($starts_time2));
				$time_end =  date('H:i:s', $time_end1);
				if (($starts_time2 <= $time_start &&  $starts_time2 < $time_end) && ($ends_time >= $time_start &&  $ends_time > $time_end)) {
					$titletime = $time_start . "-" . $time_end;
					$title = $tracks1;
					if ($playrow['artist_img'] == "") {
						$artist_img = "null";
					} else {
						$artist_img = "http://broadcast.campusradio.rocks/" . $playrow['artist_img'];
					}
					if ($playrow['album_img'] == "") {
						$album_img = "null";
					} else {
						$album_img = "http://broadcast.campusradio.rocks/" . $playrow['album_img'];
					}

					$info[] = array('Track_time' => $titletime, 'Track_Name' => $title, 'Artist_img' => $artist_img, 'Album_img' => $album_img, 'description' => $description);
				}
				//$group_name[] = $rows['name'];
			}

			if ($title) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Track_details'] = $info;

				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function all_channel_events()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		$todaydate1 = date('Y-m-d H:i:s');
		$todaydate2 = date('Y-m-d');
		$dayofweek = date('w', strtotime($todaydate2));

		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$test_date = $obj->start_date;
			$test_time = $obj->start_time;
			if ($test_date == '' && $test_time == '') {
				$todaydate = $todaydate1;
				$todaydate2 = $todaydate2;
				$dayofweek = date('w', strtotime($todaydate2));
			} else if ($test_time == '') {
				$test_time1 = "00:00:00";
				$todaydate = date('Y-m-d H:i:s', strtotime("$test_date $test_time1"));
				$dayofweek = date('w', strtotime($test_date));
				$todaydate2 = $test_date;
			} else if ($test_date == '') {
				$todaydate = date('Y-m-d H:i:s', strtotime("$todaydate2 $test_time"));
				$dayofweek = date('w', strtotime($todaydate2));
				$todaydate2 = $todaydate2;
			} else {
				$todaydate = date('Y-m-d H:i:s', strtotime("$test_date $test_time"));
				$dayofweek = date('w', strtotime($test_date));
				$todaydate2 = $test_date;
			}
			$channel_url1 = $obj->channel_url;
			$chanid = substr($channel_url1, strpos($channel_url1, "=") + 1);

			$sql1 = "select *,cast(starts as time) as timeerset from calendar where (FIND_IN_SET('$dayofweek',repeat_days) AND repeat_type !='-1' AND default_user_id='$chanid') OR ((cast(starts as date) = '$todaydate2' and cast(ends as date) = '$todaydate2') AND repeat_type='-1' AND default_user_id='$chanid') OR (end_date > '$todaydate2' AND default_user_id='$chanid') ORDER BY timeerset ASC";

			mysqli_set_charset($radioconnect, 'utf8');
			$result1 = mysqli_query($radioconnect, $sql1);
			$count = mysqli_num_rows($result1);
			while ($row2 = mysqli_fetch_array($result1)) {
				$calander_ids = $row2['id'];
				$event_name = $row2['name'];
				$starts_time1 = $row2['starts'];
				$ends_time1 = $row2['ends'];
				$starts_time_for = new DateTime($starts_time1);
				$starts_time = $starts_time_for->format('H:i:s');
				$ends_time_for = new DateTime($ends_time1);
				$ends_time = $ends_time_for->format('H:i:s');
				$info[] = array('Event_Name' => $event_name, 'Event_Id' => $calander_ids, 'Event_Start_Time' => $starts_time, 'Event_End_Time' => $ends_time);
			}
			if ($count > 0) {
				$passparameter = "YES";
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Track_details'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "Data Not Found,Please Try Again!";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function school_radio_url()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$school_id = $obj->school_id;
			$sql = "select * from groups where name='$school_id'";
			$result = mysqli_query($radioconnect, $sql);
			$count = mysqli_num_rows($result);
			$query2 = "select * from tbl_channel where channel_url='http://broadcast.campusradio.rocks/index.php/stream?id=6'";
			$resultquery2 = mysqli_query($connect, $query2);
			$rows2 = mysqli_fetch_assoc($resultquery2);
			$id = $rows2['id'];
			$category_id = $rows2['category_id'];
			$channel_name = $rows2['channel_name'];
			$img12 = $rows2['channel_image'];
			$channel_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
				"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $img12;
			$channel_url = $rows2['channel_url'];
			//$channel_description = $rows2['channel_description'];
			$channel_type = $rows2['channel_type'];
			$channel_player_type = $rows2['channel_player_type'];
			$channel_visible = $rows2['channel_visible'];
			$channel_order = $rows2['channel_order'];
			$channel_feature = $rows2['channel_feature'];
			$channel_state = $rows2['channel_state'];
			$channel_city = $rows2['channel_city'];
			$channel_country = $rows2['channel_country'];
			$channel_language = $rows2['channel_language'];
			$channel_genre = $rows2['channel_genre'];
			$into2 = array('id' => $id, 'category_id' => $category_id, 'channel_name' => $channel_name, 'channel_image' => $channel_image, 'channel_url' => $channel_url, 'channel_type' => $channel_type, 'channel_player_type' => $channel_player_type, 'channel_visible' => $channel_visible, 'channel_order' => $channel_order, 'channel_feature' => $channel_feature, 'channel_state' => $channel_state, 'channel_city' => $channel_city, 'channel_country' => $channel_country, 'channel_language' => $channel_language, 'channel_genre' => $channel_genre);
			if ($count > 0) {
				$row = mysqli_fetch_array($result);
				$trackurl = $row['id'];
				$query = "select * from tbl_channel where channel_url='http://broadcast.campusradio.rocks/index.php/stream?id=$trackurl'";
				$resultquery = mysqli_query($connect, $query);
				$coun = mysqli_num_rows($resultquery);
				$rows = mysqli_fetch_array($resultquery);
				$id = $rows['id'];
				$category_id = $rows['category_id'];
				$channel_name = $rows['channel_name'];
				$img1 = $rows['channel_image'];
				$channel_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
					"https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/upload/" . $img1;
				$channel_url = $rows['channel_url'];
				// $channel_description = $rows['channel_description'];
				$channel_type = $rows['channel_type'];
				$channel_player_type = $rows['channel_player_type'];
				$channel_visible = $rows['channel_visible'];
				$channel_order = $rows['channel_order'];
				$channel_feature = $rows['channel_feature'];
				$channel_state = $rows['channel_state'];
				$channel_city = $rows['channel_city'];
				$channel_country = $rows['channel_country'];
				$channel_language = $rows['channel_language'];
				$channel_genre = $rows['channel_genre'];
				$into = array('id' => $id, 'category_id' => $category_id, 'channel_name' => $channel_name, 'channel_image' => $channel_image, 'channel_url' => $channel_url, 'channel_type' => $channel_type, 'channel_player_type' => $channel_player_type, 'channel_visible' => $channel_visible, 'channel_order' => $channel_order, 'channel_feature' => $channel_feature, 'channel_state' => $channel_state, 'channel_city' => $channel_city, 'channel_country' => $channel_country, 'channel_language' => $channel_language, 'channel_genre' => $channel_genre);
				if ($coun > 0) {
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "OK";
					$postvalue['School_Url'] = $into;
					$response = json_encode($postvalue);
					print $response;
				} else {
					$postvalue['responseStatus'] = 204;
					$postvalue['responseMessage'] = "Not Found Url";
					$postvalue['School_Url'] = $into2;
					$response = json_encode($postvalue);
					print $response;
				}
			} else {
				$postvalue['responseStatus'] = 206;
				$postvalue['responseMessage'] = "School_Id Not Found, Please Insert Correct Id!";
				$postvalue['School_Url'] = $into2;
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 208;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}

		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}

	private function college_grp_list_advr()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
		function convert_smart_quotes($string)

		{
			$search = array(
				chr(145),
				chr(146),
				chr(147),
				chr(148),
				chr(151)
			);

			$replace = array(
				"",
				"",
				'',
				'',
				''
			);

			return str_replace($search, $replace, $string);
		}
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$cquery = "select * from groups where id > 1000";
			$cresult = mysqli_query($radioconnect, $cquery);
			while ($crow = mysqli_fetch_array($cresult)) {
				$name = convert_smart_quotes($crow['description']);
				$id = $crow['id'];
				$info[] = array('group_id' => $id, 'group_name' => $name);
			}
			if ($id) {
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Group_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No College Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
	}

	private function college_list_advr()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;

		function convert_smart_quotes($string)

		{
			$search = array(
				chr(145),
				chr(146),
				chr(147),
				chr(148),
				chr(151)
			);

			$replace = array(
				"",
				"",
				'',
				'',
				''
			);

			return str_replace($search, $replace, $string);
		}
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			$cquery = "select * from tbl_channel where id > 1000";
			$cresult = mysqli_query($connect, $cquery);
			while ($crow = mysqli_fetch_array($cresult)) {
				$name = convert_smart_quotes($crow['channel_name']);
				$id = $crow['id'];
				$info[] = array('college_id' => $id, 'college_name' => $name);
			}
			if ($id) {
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['College_List'] = $info;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No College Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
	}

	private function get_all_user_logs()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		$datepicher1 = $obj->Event_date;
		$Event_End_date = $obj->Event_End_date;
		$event_time = $obj->Event_Start_Time;
		$event_end_time = $obj->Event_End_Time;
		$college_name = $obj->college_name;
		$city = $obj->city;
		$state = $obj->state;
		$country = $obj->country;
		$channel_id = $obj->channel_id;
		$category_id = $obj->category_id;
		$user_action = $obj->user_action;
		if ($api_key_get == $api_key) {
			if ($datepicher1 == '' && $event_time == '' && $college_name == '' && $Event_End_date == '') {
				$datepicher = date('Y-m-d');
				$a = "date='$datepicher'";
			} else if ($datepicher1 != '' && $event_time == '' && $college_name == '' && $Event_End_date == '') {
				$datepicher = $datepicher1;
				$a = "date='$datepicher'";
			} else if ($datepicher1 != '' && $event_time == '' && $college_name == '' && $Event_End_date != '') {
				$datepicher = $datepicher1;
				$a = "date BETWEEN '$datepicher1' AND '$Event_End_date'";
			} else if ($datepicher1 == '' && $event_time != '' && $event_end_time != '' && $college_name == '') {
				$todate = date('Y-m-d');
				$datepicher = date('d-m-Y') . " " . $event_time;
				$datepicherend = date('d-m-Y') . " " . $event_end_time;
				$a = "(ActionTime BETWEEN '$datepicher' AND '$datepicherend') AND date='$todate'";
			} else if ($datepicher1 != '' && $event_time != '' && $event_end_time != '' && $college_name == '' && $Event_End_date == '') {
				$todate = date("d-m-Y", strtotime($datepicher1));
				$datepicher = $todate . " " . $event_time;
				$datepicherend = $todate . " " . $event_end_time;
				$a = "(ActionTime BETWEEN '$datepicher' AND '$datepicherend') AND date='$datepicher1'";
			} else if ($datepicher1 != '' && $event_time != '' && $event_end_time != '' && $college_name == '' && $Event_End_date != '') {
				$todate = date("d-m-Y", strtotime($datepicher1));
				$todate2 = date("d-m-Y", strtotime($Event_End_date));
				$datepicher = $todate . " " . $event_time;
				$datepicherend = $todate2 . " " . $event_end_time;
				$a = "(ActionTime BETWEEN '$datepicher' AND '$datepicherend') AND (date BETWEEN '$datepicher1' AND '$Event_End_date')";
			} else if ($datepicher1 == '' && $event_time == '' && $college_name != '') {
				$datepicher = date('Y-m-d');
				$a = "date='$datepicher' AND college_name='$college_name'";
			} else if ($datepicher1 != '' && $event_time == '' && $college_name != '') {
				$datepicher = $datepicher1;
				$a = "date='$datepicher' AND college_name='$college_name'";
			} else if ($datepicher1 == '' && $event_time != '' && $event_end_time != '' && $college_name != '') {
				$todate = date('Y-m-d');
				$datepicher = date('d-m-Y') . " " . $event_time;
				$datepicherend = date('d-m-Y') . " " . $event_end_time;
				$a = "(ActionTime BETWEEN '$datepicher' AND '$datepicherend') AND date='$todate' AND college_name='$college_name'";
			} else if ($datepicher1 != '' && $event_time != '' && $event_end_time != '' && $college_name != '') {
				$todate = date("d-m-Y", strtotime($datepicher1));;
				$datepicher = $todate . " " . $event_time;
				$datepicherend = $todate . " " . $event_end_time;
				$a = "(ActionTime BETWEEN '$datepicher' AND '$datepicherend') AND date='$datepicher1' AND college_name='$college_name'";
			} else if ($datepicher1 != '' && $event_time != '' && $event_end_time != '' && $college_name != '' && $Event_End_date != '') {
				$todate = date("d-m-Y", strtotime($datepicher1));
				$todate2 = date("d-m-Y", strtotime($Event_End_date));
				$datepicher = $todate . " " . $event_time;
				$datepicherend = $todate2 . " " . $event_end_time;
				$a = "(ActionTime BETWEEN '$datepicher' AND '$datepicherend') AND (date BETWEEN '$datepicher1' AND '$Event_End_date') AND college_name='$college_name'";
			}

			if ($city == '' && $state == '' && $country == '') {
				$b = "";
			} else if ($city != '' && $state == '' && $country == '') {
				$b = "AND city='$city'";
			} else if ($city == '' && $state != '' && $country == '') {
				$b = "AND state='$state'";
			} else if ($city == '' && $state == '' && $country != '') {
				$b = "AND country='$country'";
			} else if ($city != '' && $state != '' && $country == '') {
				$b = "AND city='$city' AND state='$state'";
			} else if ($city == '' && $state != '' && $country != '') {
				$b = "AND state='$state' AND country='$country'";
			} else if ($city != '' && $state == '' && $country != '') {
				$b = "AND city='$city' AND country='$country'";
			} else if ($city != '' && $state != '' && $country != '') {
				$b = "AND city='$city' AND state='$state' AND country='$country'";
			} else {
				$b = "";
			}

			if ($channel_id != "") {
				$c = "AND ChannelID='$channel_id'";
			} else {
				$c = "";
			}

			if ($category_id != "") {
				$d = "AND CategoryID='$category_id'";
			} else {
				$d = "";
			}

			if ($user_action != "") {
				$e = "AND Action='$user_action'";
			} else {
				$e = "";
			}

			$sql = "SELECT * FROM play_logs where $a $b $c $d $e order by id desc";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			$querycount = mysqli_query($connect, "SELECT DISTINCT(UserID) FROM play_logs where $a $b $c $d $e order by id desc");
			$user_count = mysqli_num_rows($querycount);

			while ($row = mysqli_fetch_assoc($result)) {

				$App_Name = $row['App_Name'];
				$UserID = $row['UserID'];
				$Action = $row['Action'];
				$PlaylistItemID = $row['PlaylistItemID'];
				$CategoryID = $row['CategoryID'];
				$ChannelID = $row['ChannelID'];
				$ChannelCategoryName = $row['ChannelCategoryName'];
				$ActionTime = $row['ActionTime'];
				$ActionDuration = $row['ActionDuration'];
				$DeviceName = $row['DeviceName'];
				$IPAddress = $row['IPAddress'];
				$OSVersion = $row['OSVersion'];
				$CountryCode = $row['CountryCode'];
				$PosLat = $row['PosLat'];
				$PosLong = $row['PosLong'];
				$college_id = $row['college_id'];
				$college_name = $row['college_name'];
				$date = $row['date'];
				$city = $row['city'];
				$state = $row['state'];
				$country = $row['country'];

				if ($UserID != '') {
					$query = mysqli_query($connect, "select * from tbl_user where id='$UserID'");
					$rows = mysqli_fetch_array($query);
					$user_name = $rows['username'];
				} else {
					$user_name = "";
				}
				if ($CategoryID != '') {
					$query2 = mysqli_query($connect, "select * from tbl_category where cid='$CategoryID'");
					$rows2 = mysqli_fetch_array($query2);
					$category_name = $rows2['category_name'];
				} else {
					$category_name = "";
				}
				if ($ChannelID != '') {
					$query3 = mysqli_query($connect, "select * from tbl_channel where id='$ChannelID'");
					$rows3 = mysqli_fetch_array($query3);
					$channel_name = $rows3['channel_name'];
				} else {
					$channel_name = "";
				}
				$json_result[] = array(
					'App_Name' => $App_Name, 'User_ID' => $UserID, 'User_Name' => $user_name, 'User_Action' => $Action,
					'Track_Item_ID' => $PlaylistItemID, 'Category_Name' => $category_name, 'Channel_Name' => $channel_name, 'User_Action_Time' => $ActionTime, 'User_Action_Duration' => $ActionDuration, 'DeviceName' => $DeviceName, 'IPAddress' => $IPAddress, 'OSVersion' => $OSVersion, 'Country_Code' => $CountryCode, 'User_PosLat' => $PosLat, 'User_PosLong' => $PosLong, 'college_id' => $college_id, 'college_name' => $college_name, 'date' => $date, 'city' => $city, 'state' => $state, 'country' => $country
				);
			}

			if ($count > 0) {
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['Total_User_Active'] = $user_count;
				$postvalue['User_Logs'] = $json_result;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No Result Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
	}

	private function get_user_action()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$sql = "select * from tbl_languages where key_name='User_Action'";
			$result = mysqli_query($connect, $sql);
			$count = mysqli_num_rows($result);
			while ($row = mysqli_fetch_array($result)) {
				$values_name = $row['values_name'];
				$post[] = array("Values" => $values_name);
			}
			if ($count > 0) {
				$postvalue['responseStatus'] = 200;
				$postvalue['responseMessage'] = "OK";
				$postvalue['User_Action'] = $post;
				$response = json_encode($postvalue);
				print $response;
			} else {
				$postvalue['responseStatus'] = 204;
				$postvalue['responseMessage'] = "No Result Found";
				$response = json_encode($postvalue);
				print $response;
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
	}






	private function get_category_posts()
	{


		include "../includes/config.php";
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if (isset($_GET['api_key'])) {

			$access_key_received = $_GET['api_key'];

			if ($access_key_received == $api_key) {

				$id = $_GET['id'];

				if ($this->get_request_method() != "GET") $this->response('', 406);
				$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
				$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

				$offset = ($page * $limit) - $limit;
				$count_total = $this->get_count_result("SELECT COUNT(DISTINCT id) FROM tbl_channel WHERE channel_visible=1 AND category_id = '$id'");

				$query = "SELECT distinct 
								cid,
								category_name,
								category_image
								
							FROM
								tbl_category 

							WHERE 
								cid = '$id' AND category_visible=1

							ORDER BY category_order ASC, cid DESC";

				$query2 = "SELECT distinct 
								n.id AS 'channel_id',
								n.category_id,
								n.channel_name, 
								n.channel_image, 
								n.channel_url,
								n.channel_description,
								
								c.category_name,
								n.channel_type,
								n.channel_player_type
								
							FROM 
								tbl_channel n, 
								tbl_category c 
								
							WHERE 
								n.category_id = c.cid AND c.cid = '$id' AND c.category_visible=1 AND n.channel_visible=1  ORDER BY n.channel_order ASC, n.id DESC LIMIT $limit OFFSET $offset";

				$category = $this->get_category_result($query);
				$post = $this->get_list_result($query2);
				$count = count($post);
				$respon = array(
					'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'category' => $category, 'posts' => $post
				);
				$this->response($this->json($respon), 200);
			} else {
				die('Oops, API Key is Incorrect!');
			}
		} else {
			die('Forbidden, API Key is Required!');
		}
	}

	private function get_search_results()
	{

		include "../includes/config.php";
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if (isset($_GET['api_key'])) {

			$access_key_received = $_GET['api_key'];

			if ($access_key_received == $api_key) {

				$search = $_GET['search'];

				if ($this->get_request_method() != "GET") $this->response('', 406);
				$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
				$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

				$offset = ($page * $limit) - $limit;
				$count_total = $this->get_count_result("SELECT COUNT(DISTINCT n.id) FROM tbl_channel n, tbl_category c WHERE n.channel_visible=1 AND n.category_id = c.cid AND (n.channel_name LIKE '%$search%' OR n.channel_description LIKE '%$search%')");

				$query = "SELECT distinct 
								n.id AS 'channel_id',
								n.category_id,
								n.channel_name, 
								n.channel_image, 
								n.channel_url,
								n.channel_description,
								
								c.category_name,
								n.channel_type,
								n.channel_player_type
								
							FROM 
								tbl_channel n, 
								tbl_category c 
								
							WHERE n.channel_visible=1 AND n.category_id = c.cid AND (n.channel_name LIKE '%$search%' OR n.channel_description LIKE '%$search%') 

							LIMIT $limit OFFSET $offset";

				$post = $this->get_list_result($query);
				$count = count($post);
				$respon = array(
					'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
				);
				$this->response($this->json($respon), 200);
			} else {
				die('Oops, API Key is Incorrect!');
			}
		} else {
			die('Forbidden, API Key is Required!');
		}
	}

	private function like_recode()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$operation = $obj->operation;
		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

		if ($api_key_get == $api_key) {
			// $radioconnect = new mysqli('broadcastradio.crlqlgczdrqb.ap-south-1.rds.amazonaws.com', 'broadcastradio', 'CRMZU92Tke3Uy99L', 'broadcastradio');
			$channel_id = $obj->channel_id;
			$user_id = $obj->user_id;
			$like_flag = $obj->like_flag;
			$track_id = $obj->track_id;
			if ($channel_id != "" && $track_id == "") {
				if ($like_flag == 0) {

					$sql = mysqli_query($connect, "select * from tbl_channel where id='$channel_id'");
					$array = mysqli_fetch_array($sql);
					$channel_like = $array['channel_like'];
					$channel_dislike = $array['channel_dislike'];
					$channel_like_user = $array['channel_like_user'];
					$channel_dislike_user = $array['channel_dislike_user'];
					$a = explode(",", $channel_like_user);



					if (in_array($user_id, $a)) {
						$status_y = "true";
					} else {
						$status_y = "false";
					}


					$info = array('Total_Likes_C' => $channel_like, 'User_Liked_C' => $status_y);
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "Ok";
					$postvalue['Result'] = $info;
					$response = json_encode($postvalue);
					print $response;
				} else if ($like_flag == 1) {
					$query_check = mysqli_query($connect, "select * from tbl_channel where FIND_IN_SET('$user_id',channel_like_user) AND id='$channel_id'");
					$ch_count = mysqli_num_rows($query_check);

					if ($ch_count > 0) {
						$sql3 = mysqli_query($connect, "select * from tbl_channel where id='$channel_id'");
						$array3 = mysqli_fetch_array($sql3);
						$channel_like3 = $array3['channel_like'];
						$channel_dislike3 = $array3['channel_dislike'];
						$channel_like_user3 = $array3['channel_like_user'];

						$channel_like_count3 = $channel_like3 ;

						$parts = explode(',', $channel_like_user3);
						while (($i = array_search($user_id, $parts)) !== false) {
							unset($parts[$i]);
							$channel_like_count3 = $channel_like3 - 1 ;
						}
						$x =  implode(',', $parts);

						$sql2 = "UPDATE tbl_channel SET channel_like='$channel_like_count3',channel_like_user='$x' WHERE id='$channel_id'";
						$result = mysqli_query($connect, $sql2);

						$info3 = array('Total_Likes_C' => $channel_like_count3, 'User_Liked_C' => 'false');
						$postvalue['responseStatus'] = 208;
						$postvalue['responseMessage'] = "User Already Liked This Channel Removing Like";
						$postvalue['Result'] = $info3;
						$response = json_encode($postvalue);
						print $response;

						$datetimeforlogs = date('Y-m-d H:i:s');
						$ipforlogs = $_SERVER['REMOTE_ADDR'];
						$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



						$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
						$resultforlogs = mysqli_query($connect, $sqlforlogs);

						die;
					}
					$sql = mysqli_query($connect, "select * from tbl_channel where id='$channel_id'");
					$array = mysqli_fetch_array($sql);
					$channel_like = $array['channel_like'];
					$channel_like_user = $array['channel_like_user'];

					if ($channel_like == "") {
						$channel_like_count = 1;
					} else {
						$channel_like_count = $channel_like + 1;
					}

					if ($channel_like_user == "") {
						$user_add = $user_id;
					} else {
						$user_add = $channel_like_user . "," . $user_id;
					}

					$sql2 = "UPDATE tbl_channel SET channel_like='$channel_like_count',channel_like_user='$user_add' WHERE id='$channel_id'";
					$result = mysqli_query($connect, $sql2);
					if ($result) {
						$sql3 = mysqli_query($connect, "select * from tbl_channel where id='$channel_id'");
						$array2 = mysqli_fetch_array($sql3);
						$channel_like2 = $array2['channel_like'];
						$channel_dislike2 = $array2['channel_dislike'];
						$channel_like_user2 = $array2['channel_like_user'];
						$category_id = $array2['category_id'];
						$channel_nmes = $array2['channel_name'];
						$b = explode(",", $channel_like_user2);

						if (in_array($user_id, $b)) {
							$status_x = "true";
						} else {
							$status_x = "false";
						}

						$queryres = mysqli_query($connect, "select * from tbl_user where id='$user_id'");
						$userroe = mysqli_fetch_array($queryres);
						$college_name = $userroe['college_name'];
						$college_id = $userroe['college_id'];
						$city = $userroe['city'];
						$state = $userroe['state'];
						$country = $userroe['country'];
						$Member_ID = $userroe['Member_ID'];
						$SMC_entity_id = $userroe['SMC_entity_id'];

						$online_date = date('Y-m-d H:i:s');

						$sql434 = mysqli_query($connect, "INSERT INTO play_logs (App_Name,UserID,Action,CategoryID,ChannelID,college_id,college_name,date,city,state,country) VALUES ('Campus Radio','$user_id','Like Channel','$category_id','$channel_id','$college_id','$college_name','$online_date','$city','$state','$country')");

						if ($Member_ID != '' && $SMC_entity_id != '') {

							$curl3 = curl_init();
							$request11 = array('Member_ID' => $Member_ID, 'Entity_TypeID' => $SMC_entity_id, 'Source' => 'Campus Radio', 'Category' => $channel_nmes, 'Sub_Category' => $channel_nmes, 'Reason' => 'Channel Like', 'Referral_Reason' => 'Channel Like');

							$operationInput2 = json_encode($request11);
							curl_setopt_array($curl3, array(
								CURLOPT_URL => "https://smartcookie.in/core/Version5/Assign_Brown_Points.php",
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 30,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "POST",
								CURLOPT_POSTFIELDS => $operationInput2,
								CURLOPT_HTTPHEADER => array(
									"cache-control: no-cache",
									"content-type: application/json"

								),
							));

							$response3 = curl_exec($curl3);
							$err3 = curl_error($curl3);
							curl_close($curl3);
						}

						$info2 = array('Total_Likes_C' => $channel_like2, 'User_Liked_C' => $status_x);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "Ok";
						$postvalue['Result'] = $info2;
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Not Updated";
						$response = json_encode($postvalue);
						print $response;
					}
				} else if ($like_flag == 2) {
					$query_check = mysqli_query($connect, "select * from tbl_channel where FIND_IN_SET('$user_id',channel_dislike_user) AND id='$channel_id'");
					$ch_count = mysqli_num_rows($query_check);

					if ($ch_count > 0) {
						$sql3 = mysqli_query($connect, "select * from tbl_channel where id='$channel_id'");
						$array3 = mysqli_fetch_array($sql3);
						$channel_like3 = $array3['channel_like'];
						$channel_dislike3 = $array3['channel_dislike'];
						$channel_dislike_user3 = $array3['channel_dislike_user'];

						$channel_dislike_count3 = $channel_dislike3 - 1;
						
			

						$parts = explode(',', $channel_dislike_user3);
						while (($i = array_search($user_id, $parts)) !== false) {
							unset($parts[$i]);
						}
						$x =  implode(',', $parts);

						$sql2 = "UPDATE tbl_channel SET channel_dislike='$channel_dislike_count3',channel_dislike_user='$x' WHERE id='$channel_id'";
						$result = mysqli_fetch_array($sql2);
						//$result = mysqli_query($connect,$sql2);

						$info3 = array('Total_Likes_C' => $channel_like3, 'Total_Dislikes_C' => $channel_dislike_count3, 'User_Disliked_C' => 'false');
						$postvalue['responseStatus'] = 208;
						$postvalue['responseMessage'] = "User Already disliked This Channel";
						$postvalue['Result'] = $info3;
						$response = json_encode($postvalue);
						print $response;

						$datetimeforlogs = date('Y-m-d H:i:s');
						$ipforlogs = $_SERVER['REMOTE_ADDR'];
						$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



						$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
						$resultforlogs = mysqli_query($connect, $sqlforlogs);

						die;
					}
					$sql = mysqli_query($connect, "select * from channels where id='$channel_id'");
					$array = mysqli_fetch_array($sql);
					$channel_like = $array['channel_like'];
					$channel_dislike = $array['channel_dislike'];
					$channel_dislike_user = $array['channel_dislike_user'];

					if ($channel_dislike == "") {
						$channel_dislike_count = 1;
					} else {
						$channel_dislike_count = $channel_dislike + 1;
					}

					if ($channel_dislike_user == "") {
						$user_add = $user_id;
					} else {
						$user_add = $channel_dislike_user . "," . $user_id;
					}

					$sql2 = "UPDATE tbl_channel SET channel_dislike='$channel_dislike_count',channel_dislike_user='$user_add' WHERE id='$channel_id'";
					$result = mysqli_query($connect, $sql2);
					if ($result) {
						$sql3 = mysqli_query($connect, "select * from tbl_channel where id='$channel_id'");
						$array2 = mysqli_fetch_array($sql3);
						$channel_like2 = $array2['channel_like'];
						$channel_dislike2 = $array2['channel_dislike'];
						$channel_dislike_user2 = $array2['channel_dislike_user'];
						$b = explode(",", $channel_dislike_user2);

						if (in_array($user_id, $b)) {
							$status_x = "true";
						} else {
							$status_x = "false";
						}



						$info2 = array('Total_Likes_C' => $channel_like2, 'Total_Dislikes_C' => $channel_dislike2, 'User_Disliked_C' => $status_x);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "Ok";
						$postvalue['Result'] = $info2;
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Not Updated";
						$response = json_encode($postvalue);
						print $response;
					}
				}
			} else if ($channel_id == "" && $track_id != "") {
				if ($like_flag == 0) {

					$sql = mysqli_query($radioconnect, "select * from track where id='$track_id'");
					$array = mysqli_fetch_array($sql);
					$track_like = $array['track_like'];
					$track_dislike = $array['track_dislike'];
					$track_like_user = $array['track_like_user'];
					$track_dislike_user = $array['track_dislike_user'];
					$a = explode(",", $track_like_user);
					$c = explode(",", $track_dislike_user);


					if (in_array($user_id, $a)) {
						$status_y = "true";
					} else {
						$status_y = "false";
					}

					if (in_array($user_id, $c)) {
						$status_yx = "true";
					} else {
						$status_yx = "false";
					}
					$info = array('Total_Likes' => $track_like, 'Total_Disikes' => $track_dislike, 'User_Liked' => $status_y, 'User_Disliked' => $status_yx);
					$passparameter = "YES";
					$postvalue['responseStatus'] = 200;
					$postvalue['responseMessage'] = "Ok";
					$postvalue['Result'] = $info;
					$response = json_encode($postvalue);
					print $response;
				} else if ($like_flag == 1) {
					$query_check = mysqli_query($radioconnect, "select * from track where FIND_IN_SET('$user_id',track_like_user) AND id='$track_id'");
					$ch_count = mysqli_num_rows($query_check);

					if ($ch_count > 0) {
						$sql3 = mysqli_query($radioconnect, "select * from track where id='$track_id'");
						$array3 = mysqli_fetch_array($sql3);
						$track_like3 = $array3['track_like'];
						$track_dislike3 = $array3['track_dislike'];
						$track_like_user3 = $array3['track_like_user'];

						$track_like_count3 = $track_like3 - 1;

						$parts = explode(',', $track_like_user3);
						while (($i = array_search($user_id, $parts)) !== false) {
							unset($parts[$i]);
						}
						$x =  implode(',', $parts);

						$sql2 = "UPDATE track SET track_like='$track_like_count3',track_like_user='$x' WHERE id='$track_id'";
						$result = mysqli_query($radioconnect, $sql2);

						$info3 = array('Total_Likes' => $track_like_count3, 'Total_Disikes' => $track_dislike3, 'User_Liked' => 'false');
						$postvalue['responseStatus'] = 208;
						$postvalue['responseMessage'] = "User Already Liked This Tracks";
						$postvalue['Result'] = $info3;
						$response = json_encode($postvalue);
						print $response;

						$datetimeforlogs = date('Y-m-d H:i:s');
						$ipforlogs = $_SERVER['REMOTE_ADDR'];
						$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



						$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
						$resultforlogs = mysqli_query($connect, $sqlforlogs);

						die;
					}
					$sql = mysqli_query($radioconnect, "select * from track where id='$track_id'");
					$array = mysqli_fetch_array($sql);
					$track_like = $array['track_like'];
					$track_like_user = $array['track_like_user'];

					if ($track_like == "") {
						$track_like_count = 1;
					} else {
						$track_like_count = $channel_like + 1;
					}

					if ($track_like_user == "") {
						$user_add = $user_id;
					} else {
						$user_add = $track_like_user . "," . $user_id;
					}

					$sql2 = "UPDATE track SET track_like='$track_like_count',track_like_user='$user_add' WHERE id='$track_id'";
					$result = mysqli_query($radioconnect, $sql2);
					if ($result) {
						$sql3 = mysqli_query($radioconnect, "select * from track where id='$track_id'");
						$array2 = mysqli_fetch_array($sql3);
						$track_like2 = $array2['track_like'];
						$track_dislike2 = $array2['track_dislike'];
						$track_like_user2 = $array2['track_like_user'];
						$title = $array2['title'];
						$b = explode(",", $track_like_user2);

						if (in_array($user_id, $b)) {
							$status_x = "true";
						} else {
							$status_x = "false";
						}
						$queryres = mysqli_query($connect, "select * from tbl_user where id='$user_id'");
						$userroe = mysqli_fetch_array($queryres);
						$college_name = $userroe['college_name'];
						$college_id = $userroe['college_id'];
						$city = $userroe['city'];
						$state = $userroe['state'];
						$country = $userroe['country'];
						$Member_ID = $userroe['Member_ID'];
						$SMC_entity_id = $userroe['SMC_entity_id'];

						$online_date = date('Y-m-d H:i:s');

						$sql434 = mysqli_query($connect, "INSERT INTO play_logs (App_Name,UserID,Action,track_id,ChannelCategoryName,college_id,college_name,date,city,state,country) VALUES ('Campus Radio','$user_id','Like Track','$track_id','$title','$college_id','$college_name','$online_date','$city','$state','$country')");

						if ($Member_ID != '' && $SMC_entity_id != '') {

							$curl3 = curl_init();
							$request11 = array('Member_ID' => $Member_ID, 'Entity_TypeID' => $SMC_entity_id, 'Source' => 'Campus Radio', 'Category' => $title, 'Sub_Category' => $title, 'Reason' => 'Track Like', 'Referral_Reason' => 'Track Like');

							$operationInput2 = json_encode($request11);
							curl_setopt_array($curl3, array(
								CURLOPT_URL => "https://smartcookie.in/core/Version5/Assign_Brown_Points.php",
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 30,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "POST",
								CURLOPT_POSTFIELDS => $operationInput2,
								CURLOPT_HTTPHEADER => array(
									"cache-control: no-cache",
									"content-type: application/json"

								),
							));

							$response3 = curl_exec($curl3);
							$err3 = curl_error($curl3);
							curl_close($curl3);
						}


						$info2 = array('Total_Likes' => $track_like2, 'Total_Disikes' => $track_dislike2, 'User_Liked' => $status_x);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "Ok";
						$postvalue['Result'] = $info2;
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Not Updated";
						$response = json_encode($postvalue);
						print $response;
					}
				} else if ($like_flag == 2) {
					$query_check = mysqli_query($radioconnect, "select * from track where FIND_IN_SET('$user_id',track_dislike_user) AND id='$track_id'");
					$ch_count = mysqli_num_rows($query_check);

					if ($ch_count > 0) {
						$sql3 = mysqli_query($radioconnect, "select * from track where id='$track_id'");
						$array3 = mysqli_fetch_array($sql3);
						$track_like3 = $array3['track_like'];
						$track_dislike3 = $array3['track_dislike'];
						$track_dislike_user3 = $array3['track_dislike_user'];

						$track_dislike_count3 = $track_dislike3 - 1;

						$parts = explode(',', $track_dislike_user3);
						while (($i = array_search($user_id, $parts)) !== false) {
							unset($parts[$i]);
						}
						$x =  implode(',', $parts);

						$sql2 = "UPDATE track SET track_dislike='$track_dislike_count3',track_dislike_user='$x' WHERE id='$track_id'";
						$result = mysqli_query($radioconnect, $sql2);

						$info3 = array('Total_Likes' => $track_like3, 'Total_Disikes' => $track_dislike_count3, 'User_Disliked' => 'false');
						$postvalue['responseStatus'] = 208;
						$postvalue['responseMessage'] = "User Already disliked This Tracks";
						$postvalue['Result'] = $info3;
						$response = json_encode($postvalue);
						print $response;

						$datetimeforlogs = date('Y-m-d H:i:s');
						$ipforlogs = $_SERVER['REMOTE_ADDR'];
						$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";



						$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','Fail')";
						$resultforlogs = mysqli_query($connect, $sqlforlogs);

						die;
					}
					$sql = mysqli_query($radioconnect, "select * from track where id='$track_id'");
					$array = mysqli_fetch_array($sql);
					$track_like = $array['track_like'];
					$track_dislike = $array['track_dislike'];
					$track_dislike_user = $array['track_dislike_user'];

					if ($track_dislike == "") {
						$track_dislike_count = 1;
					} else {
						$track_dislike_count = $track_dislike + 1;
					}

					if ($track_dislike_user == "") {
						$user_add = $user_id;
					} else {
						$user_add = $track_dislike_user . "," . $user_id;
					}

					$sql2 = "UPDATE track SET track_dislike='$track_dislike_count',track_dislike_user='$user_add' WHERE id='$track_id'";
					$result = mysqli_query($radioconnect, $sql2);
					if ($result) {
						$sql3 = mysqli_query($radioconnect, "select * from track where id='$track_id'");
						$array2 = mysqli_fetch_array($sql3);
						$track_like2 = $array2['track_like'];
						$track_dislike2 = $array2['track_dislike'];
						$track_dislike_user2 = $array2['track_dislike_user'];
						$title = $array2['title'];
						$b = explode(",", $track_dislike_user2);

						if (in_array($user_id, $b)) {
							$status_x = "true";
						} else {
							$status_x = "false";
						}

						$queryres = mysqli_query($connect, "select * from tbl_user where id='$user_id'");
						$userroe = mysqli_fetch_array($queryres);
						$college_name = $userroe['college_name'];
						$college_id = $userroe['college_id'];
						$city = $userroe['city'];
						$state = $userroe['state'];
						$country = $userroe['country'];

						$online_date = date('Y-m-d H:i:s');

						$sql434 = mysqli_query($connect, "INSERT INTO play_logs (App_Name,UserID,Action,track_id,ChannelCategoryName,college_id,college_name,date,city,state,country) VALUES ('Campus Radio','$user_id','DisLike Track','$track_id','$title','$college_id','$college_name','$online_date','$city','$state','$country')");


						$info2 = array('Total_Likes' => $track_like2, 'Total_Disikes' => $track_dislike2, 'User_Disliked' => $status_x);
						$passparameter = "YES";
						$postvalue['responseStatus'] = 200;
						$postvalue['responseMessage'] = "Ok";
						$postvalue['Result'] = $info2;
						$response = json_encode($postvalue);
						print $response;
					} else {
						$postvalue['responseStatus'] = 204;
						$postvalue['responseMessage'] = "Not Updated";
						$response = json_encode($postvalue);
						print $response;
					}
				}
			}
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
          
		$datetimeforlogs = date('Y-m-d H:i:s');
		$ipforlogs = $_SERVER['REMOTE_ADDR'];
		$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if ($passparameter == "YES") {
			$status_code = "Success";
		} else {
			$status_code = "Fail";
		}

		$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('$operation','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
		$resultforlogs = mysqli_query($connect, $sqlforlogs);
	}




	private function with_startupworld_registration_radio()
	{
		include "../includes/config.php";
		date_default_timezone_set("Asia/Kolkata");
		$json = file_get_contents('php://input');
		$obj = json_decode($json);

		$api_key_get = $obj->api_key;
		$setting_qry    = "SELECT * FROM tbl_fcm_api_key where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];
		if ($api_key_get == $api_key) {
			$username = $obj->username;
			$email = $obj->email;
			$country_code = $obj->country_code;
			$phone = $obj->phone;
			$password = $obj->password;
			$Address = $obj->Address;
			$city = $obj->city;
			$state = $obj->state;
			$country = $obj->country;
			$school_id = $obj->school_id;
			$school_name = $obj->school_name;
			$Member_ID = $obj->Member_ID;
			$member_Password = $obj->member_Password;
			$SMC_entity_id = $obj->SMC_entity_id;
			$datetime = date('Y-m-d H:i:s');
			$query = mysqli_query($connect, "select * from tbl_user where phone='$phone' OR email='$email'");
			$count = mysqli_num_rows($query);
			$rowcount = mysqli_fetch_array($query);
			if ($count > 0) {
				$usr_id = $rowcount['id'];

				$sql = "UPDATE tbl_user SET Member_ID='$Member_ID',member_Password='$member_Password',SMC_entity_id='$SMC_entity_id',college_id='$school_id',college_name='$school_name' where id='$usr_id'";
				$result = mysqli_query($connect, $sql);
			} else {
				$sql = "INSERT INTO tbl_user (username,email,phone,user_role,registration_date,Member_ID,member_Password,SMC_entity_id,college_id,college_name,Address,city,state,country,password,country_code) VALUES ('$username','$email','$phone','103','$datetime','$Member_ID','$member_Password','$SMC_entity_id','$school_id','$school_name','$Address','$city','$state','$country','$password','$country_code')";
				$result = mysqli_query($connect, $sql);
			}
			$postvalue['responseStatus'] = 200;
			$postvalue['responseMessage'] = "Ok";

			$response = json_encode($postvalue);
			print $response;

			$datetimeforlogs = date('Y-m-d H:i:s');
			$ipforlogs = $_SERVER['REMOTE_ADDR'];
			$urlforlogs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


			$status_code = "Success";


			$sqlforlogs = "INSERT INTO api_logs (api_name,param,output,datetime,url,ip_address,status_code) VALUES ('with_startupworld_registration_radio','$json','$response','$datetimeforlogs','$urlforlogs','$ipforlogs','$status_code')";
			$resultforlogs = mysqli_query($connect, $sqlforlogs);
		} else {
			$postvalue['responseStatus'] = 206;
			$postvalue['responseMessage'] = 'Oops, API Key is Incorrect!';
			$response = json_encode($postvalue);
			print $response;
		}
	}

	//don't edit all the code below
	private function get_list($query)
	{
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			$result = array();
			while ($row = $r->fetch_assoc()) {
				$result[] = $row;
			}
			$this->response($this->json($result), 200); // send user details
		}
		$this->response('', 204);	// If no records "No Content" status
	}

	private function get_list_result($query)
	{
		$result = array();
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			while ($row = $r->fetch_assoc()) {
				$result[] = $row;
			}
		}
		return $result;
	}

	private function get_object_result($query)
	{
		$result = array();
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			while ($row = $r->fetch_assoc()) {
				$result = $row;
			}
		}
		return $result;
	}

	private function get_category_result($query)
	{
		$result = array();
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			while ($row = $r->fetch_assoc()) {
				$result = $row;
			}
		}
		return $result;
	}

	private function get_one($query)
	{
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			$result = $r->fetch_assoc();
			$this->response($this->json($result), 200); // send user details
		}
		$this->response('', 204);	// If no records "No Content" status
	}

	private function get_count($query)
	{
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			$result = $r->fetch_row();
			$this->response($result[0], 200);
		}
		$this->response('', 204);	// If no records "No Content" status
	}

	private function get_count_result($query)
	{
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
		if ($r->num_rows > 0) {
			$result = $r->fetch_row();
			return $result[0];
		}
		return 0;
	}

	private function post_one($obj, $column_names, $table_name)
	{
		$keys 		= array_keys($obj);
		$columns 	= '';
		$values 	= '';
		foreach ($column_names as $desired_key) { // Check the recipe received. If blank insert blank into the array.
			if (!in_array($desired_key, $keys)) {
				$$desired_key = '';
			} else {
				$$desired_key = $obj[$desired_key];
			}
			$columns 	= $columns . $desired_key . ',';
			$values 	= $values . "'" . $this->real_escape($$desired_key) . "',";
		}
		$query = "INSERT INTO " . $table_name . "(" . trim($columns, ',') . ") VALUES(" . trim($values, ',') . ")";
		//echo "QUERY : ".$query;
		if (!empty($obj)) {
			//$r = $this->mysqli->query($query) or trigger_error($this->mysqli->error.__LINE__);
			if ($this->mysqli->query($query)) {
				$status = "success";
				$msg 		= $table_name . " created successfully";
			} else {
				$status = "failed";
				$msg 		= $this->mysqli->error . __LINE__;
			}
			$resp = array('status' => $status, "msg" => $msg, "data" => $obj);
			$this->response($this->json($resp), 200);
		} else {
			$this->response('', 204);	//"No Content" status
		}
	}

	private function post_update($id, $obj, $column_names, $table_name)
	{
		$keys = array_keys($obj[$table_name]);
		$columns = '';
		$values = '';
		foreach ($column_names as $desired_key) { // Check the recipe received. If key does not exist, insert blank into the array.
			if (!in_array($desired_key, $keys)) {
				$$desired_key = '';
			} else {
				$$desired_key = $obj[$table_name][$desired_key];
			}
			$columns = $columns . $desired_key . "='" . $this->real_escape($$desired_key) . "',";
		}

		$query = "UPDATE " . $table_name . " SET " . trim($columns, ',') . " WHERE id=$id";
		if (!empty($obj)) {
			// $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
			if ($this->mysqli->query($query)) {
				$status = "success";
				$msg 	= $table_name . " update successfully";
			} else {
				$status = "failed";
				$msg 	= $this->mysqli->error . __LINE__;
			}
			$resp = array('status' => $status, "msg" => $msg, "data" => $obj);
			$this->response($this->json($resp), 200);
		} else {
			$this->response('', 204);	// "No Content" status
		}
	}

	private function delete_one($id, $table_name)
	{
		if ($id > 0) {
			$query = "DELETE FROM " . $table_name . " WHERE id = $id";
			if ($this->mysqli->query($query)) {
				$status = "success";
				$msg 		= "One record " . $table_name . " successfully deleted";
			} else {
				$status = "failed";
				$msg 		= $this->mysqli->error . __LINE__;
			}
			$resp = array('status' => $status, "msg" => $msg);
			$this->response($this->json($resp), 200);
		} else {
			$this->response('', 204);	// If no records "No Content" status
		}
	}

	private function responseInvalidParam()
	{
		$resp = array("status" => 'Failed', "msg" => 'Invalid Parameter');
		$this->response($this->json($resp), 200);
	}

	/* ==================================== End of API utilities ==========================================
		 * ====================================================================================================
		 */

	/* Encode array into JSON */
	private function json($data)
	{
		if (is_array($data)) {
			return json_encode($data, JSON_NUMERIC_CHECK);
		}
	}

	/* String mysqli_real_escape_string */
	private function real_escape($s)
	{
		return mysqli_real_escape_string($this->mysqli, $s);
	}
}

// Initiate Library
$api = new API;
$api->processApi();

//solved CR 37 AND CR 51