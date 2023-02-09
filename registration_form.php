<?php 

include_once('includes/functions.php');
error_reporting(0);
    if(isset($_POST['register']))
    {
		// CR-33 by Deepak
		$_POST=stripcslashes_ankur($_POST);
		$_POST=mysqliescape_ankur($_POST);
	
        $username = $_POST['username'];
		$password = $_POST['password'];
        $email = $_POST['email'];
        
        $phone = $_POST['phone'];
		$address = $_POST['address'];
        $city = $_POST['city'];
        
        $state = $_POST['state'];
		$country = $_POST['country'];
        $preferred_college = $_POST['preferred_college'];
		$country_code = $_POST['country_code'];
		$memberId=$_POST['memberId'];
		$password1=$_POST['pass'];
		$entity=$_POST['ent_type'];

        $curl = curl_init();

        $request1 = array('operation' => 'user_registration','username'=>$username,'email'=>$email,'phone'=>$phone,'country_code'=>$country_code,
        'password'=>$password,'Address'=>$address,'city'=>$city,'state'=>$state,'country'=>$country,
        'preferred_college'=>$preferred_college,'SMC_member_Password'=>$password1,'SMC_Member_ID'=>$memberId,
		'SMC_entity_id'=>$entity,'api_key' =>'cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s');
 
	//print_r($request1);
        $operationInput = json_encode($request1);

        curl_setopt_array($curl, array(
            CURLOPT_URL => app_url()."/api3/user_registration",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>  $operationInput, 
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Postman-Token: e821a808-e126-457b-86a4-e39b0f017e6d",
                "cache-control: no-cache"
            ),
        ));
        
        $response = curl_exec($curl);
		//echo $response;exit;
      //  $err = curl_error($curl);

        
		
        curl_close($curl);
        
        // if ($err) 
        // {
            // $jsondecode =  json_decode($err);
            // $msg = $jsondecode->responseMessage;
			
						  //echo $msg; exit;
        // } else {
            $jsondecode =  json_decode($response);
			//print_r($jsondecode);exit;
            $msg = $jsondecode->responseMessage;
			$a=$jsondecode->data;
			$d=$a[0];
			$userID=$d->id;
			//print_r( $e);exit;
			//$userId = $res_cat["UserID"];
			$resp=$jsondecode->responseStatus;
			if($resp==200)
			{
				$_SESSION['register_sucess']=TRUE;
			$_SESSION['user_id'] = $id;
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
$os=$operating_system;
date_default_timezone_set('Asia/Kolkata');
 $time= date("d-m-Y H:i:s") ; 
$_SESSION['time']=$time;
	//for information from profile
                  $user_id = $_SESSION['user_id'];
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
                
                    $user_info = $jsondecode->data;
					$state=$user_info->state;
					$city=$user_info->city;
					$country=$user_info->country;
					$SMC_college=$user_info->college_name;
					$SMC_College_id=$user_info->college_id;
					$pref_college=$user_info->preferred_college;
						if($SMC_college=='')
						{
							$college=$pref_college;
						}
						else 
						{
							$college=$SMC_college;
						}
						
					if ($memberId !='' && $entity !='')
					{
					 $url="https://smartcookie.in/core/Version5/Assign_Brown_Points.php";
					 $myvars_cat=array(
			'Member_ID'=>$memberId,
			'Entity_TypeID'=>$entity,
			'Source'=>"Campus Radio",
			'Category'=>"Connect to Smartcookie",
			'Sub_Category'=>"Connect to Smartcookie",
			'Reason'=>"Connect to Smartcookie",
			'Referral_Reason'=>"Connect to Smartcookie",
			'Time_Duration'=>""
				);
				
				$res_cat = get_curl_result($url,$myvars_cat);	
					}	
						
						
						
						
			$url=app_url()."/api3/logs";
$data=array( 'operation'=>'logs',
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
'IPAddress'=>$ip_server,
'OSVersion'=>$os,
'CountryCode'=>91,
'PosLat'=>'',
'PosLong'=>'',
'college_id'=>$SMC_College_id,
'college_name'=>$college,
'city'=>$city,
'state'=>$state,
'country'=>$country,
'api_key'=>'cda11aoip2Ry07CGWmjEqYvPguMZTkBel1V8c3XKIxwA6zQt5s');
$res_cat = get_curl_result($url,$data);
//print_r($res_cat);	exit;			
					header("Location:login.php");
				
				
			}else{
				$_SESSION['register_sucess']=FALSE;
                echo ("<script LANGUAGE='JavaScript'>
					window.alert('User Already Exist ');
					
				</script>");
			}
        //}
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

    <title> Campus Radio Registration Form </title>
</head>
<body>
    <div class="container col-md-6" style="margin-top:2%;">
        
        <div class="card">
     
        <!--<img src="logo.png" alt="Logo" height=20% width=10% />-->
       
            <div class="card-header bg-muted" style="text-align:center;">Registration Form</div>
            
            <div class="card-body">

            <br>
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="username">User Name</label><span
                                    style="color:red;font-size: 20px;">*</span>
                            <input type="text" name="username"id="name" class="form-control" placeholder="Enter User Name">
						
                  </div>
				  

                        <div class="form-group col-sm-6">
                            <label for="pwd">Password</label><span
                                    style="color:red;font-size: 20px;">*</span>
                            <input type="text" name="password" id="pass" class="form-control" placeholder="Enter password"  >
							
                        </div>
						<div class='col-md-4 indent-small' id="errorName" style="color:#FF0000;font-size:15px">
                        </div>	
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<div class='col-md-4 indent-small' id="errorPassword" style="color:#FF0000;font-size:15px">
                  </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="email">Email ID</label><span
                                    style="color:red;font-size: 20px;">*</span>
                            <input type="text" name="email" class="form-control" placeholder="Enter Email" id="userEmail">
							
                        </div>
						
						</div>
						<div class='col-md-4 indent-small' id="errorEmail" style="color:#FF0000;font-size:14px">
                  </div>
						<div class="row">
						<div class="form-group col-sm-6">
						<label for="country_code">Country Code</label><span
                                    style="color:red;font-size: 20px;">*</span>
                           <select class="form-control" name="country_code" >
									<option data-countryCode="IN" value="+91" Selected>India (+91)</option>
									<option data-countryCode="US" value="+1">USA (+1)</option>
									<optgroup label="Other countries">
										<option data-countryCode="DZ" value="+213">Algeria (+213)</option>
										<option data-countryCode="AD" value="+376">Andorra (+376)</option>
										<option data-countryCode="AO" value="+244">Angola (+244)</option>
										<option data-countryCode="AI" value="+1264">Anguilla (+1264)</option>
										<option data-countryCode="AG" value="+1268">Antigua &amp; Barbuda (+1268)</option>
										<option data-countryCode="AR" value="+54">Argentina (+54)</option>
										<option data-countryCode="AM" value="+374">Armenia (+374)</option>
										<option data-countryCode="AW" value="+297">Aruba (+297)</option>
										<option data-countryCode="AU" value="+61">Australia (+61)</option>
										<option data-countryCode="AT" value="+43">Austria (+43)</option>
										<option data-countryCode="AZ" value="+994">Azerbaijan (+994)</option>
										<option data-countryCode="BS" value="+1242">Bahamas (+1242)</option>
										<option data-countryCode="BH" value="+973">Bahrain (+973)</option>
										<option data-countryCode="BD" value="+880">Bangladesh (+880)</option>
										<option data-countryCode="BB" value="+1246">Barbados (+1246)</option>
										<option data-countryCode="BY" value="+375">Belarus (+375)</option>
										<option data-countryCode="BE" value="+32">Belgium (+32)</option>
										<option data-countryCode="BZ" value="+501">Belize (+501)</option>
										<option data-countryCode="BJ" value="+229">Benin (+229)</option>
										<option data-countryCode="BM" value="+1441">Bermuda (+1441)</option>
										<option data-countryCode="BT" value="+975">Bhutan (+975)</option>
										<option data-countryCode="BO" value="+591">Bolivia (+591)</option>
										<option data-countryCode="BA" value="+387">Bosnia Herzegovina (+387)</option>
										<option data-countryCode="BW" value="+267">Botswana (+267)</option>
										<option data-countryCode="BR" value="+55">Brazil (+55)</option>
										<option data-countryCode="BN" value="+673">Brunei (+673)</option>
										<option data-countryCode="BG" value="+359">Bulgaria (+359)</option>
										<option data-countryCode="BF" value="+226">Burkina Faso (+226)</option>
										<option data-countryCode="BI" value="+257">Burundi (+257)</option>
										<option data-countryCode="KH" value="+855">Cambodia (+855)</option>
										<option data-countryCode="CM" value="+237">Cameroon (+237)</option>
										<option data-countryCode="CA" value="+1">Canada (+1)</option>
										<option data-countryCode="CV" value="+238">Cape Verde Islands (+238)</option>
										<option data-countryCode="KY" value="+1345">Cayman Islands (+1345)</option>
										<option data-countryCode="CF" value="+236">Central African Republic (+236)</option>
										<option data-countryCode="CL" value="+56">Chile (+56)</option>
										<option data-countryCode="CN" value="+86">China (+86)</option>
										<option data-countryCode="CO" value="+57">Colombia (+57)</option>
										<option data-countryCode="KM" value="+269">Comoros (+269)</option>
										<option data-countryCode="CG" value="+242">Congo (+242)</option>
										<option data-countryCode="CK" value="+682">Cook Islands (+682)</option>
										<option data-countryCode="CR" value="+506">Costa Rica (+506)</option>
										<option data-countryCode="HR" value="+385">Croatia (+385)</option>
										<option data-countryCode="CU" value="+53">Cuba (+53)</option>
										<option data-countryCode="CY" value="+90392">Cyprus North (+90392)</option>
										<option data-countryCode="CY" value="+357">Cyprus South (+357)</option>
										<option data-countryCode="CZ" value="+42">Czech Republic (+42)</option>
										<option data-countryCode="DK" value="+45">Denmark (+45)</option>
										<option data-countryCode="DJ" value="+253">Djibouti (+253)</option>
										<option data-countryCode="DM" value="+1809">Dominica (+1809)</option>
										<option data-countryCode="DO" value="+1809">Dominican Republic (+1809)</option>
										<option data-countryCode="EC" value="+593">Ecuador (+593)</option>
										<option data-countryCode="EG" value="+20">Egypt (+20)</option>
										<option data-countryCode="SV" value="+503">El Salvador (+503)</option>
										<option data-countryCode="GQ" value="+240">Equatorial Guinea (+240)</option>
										<option data-countryCode="ER" value="+291">Eritrea (+291)</option>
										<option data-countryCode="EE" value="+372">Estonia (+372)</option>
										<option data-countryCode="ET" value="+251">Ethiopia (+251)</option>
										<option data-countryCode="FK" value="+500">Falkland Islands (+500)</option>
										<option data-countryCode="FO" value="+298">Faroe Islands (+298)</option>
										<option data-countryCode="FJ" value="+679">Fiji (+679)</option>
										<option data-countryCode="FI" value="+358">Finland (+358)</option>
										<option data-countryCode="FR" value="+33">France (+33)</option>
										<option data-countryCode="GF" value="+594">French Guiana (+594)</option>
										<option data-countryCode="PF" value="+689">French Polynesia (+689)</option>
										<option data-countryCode="GA" value="+241">Gabon (+241)</option>
										<option data-countryCode="GM" value="+220">Gambia (+220)</option>
										<option data-countryCode="GE" value="+7880">Georgia (+7880)</option>
										<option data-countryCode="DE" value="+49">Germany (+49)</option>
										<option data-countryCode="GH" value="+233">Ghana (+233)</option>
										<option data-countryCode="GI" value="+350">Gibraltar (+350)</option>
										<option data-countryCode="GR" value="+30">Greece (+30)</option>
										<option data-countryCode="GL" value="+299">Greenland (+299)</option>
										<option data-countryCode="GD" value="+1473">Grenada (+1473)</option>
										<option data-countryCode="GP" value="+590">Guadeloupe (+590)</option>
										<option data-countryCode="GU" value="+671">Guam (+671)</option>
										<option data-countryCode="GT" value="+502">Guatemala (+502)</option>
										<option data-countryCode="GN" value="+224">Guinea (+224)</option>
										<option data-countryCode="GW" value="+245">Guinea - Bissau (+245)</option>
										<option data-countryCode="GY" value="+592">Guyana (+592)</option>
										<option data-countryCode="HT" value="+509">Haiti (+509)</option>
										<option data-countryCode="HN" value="+504">Honduras (+504)</option>
										<option data-countryCode="HK" value="+852">Hong Kong (+852)</option>
										<option data-countryCode="HU" value="+36">Hungary (+36)</option>
										<option data-countryCode="IS" value="+354">Iceland (+354)</option>
										
										<option data-countryCode="ID" value="+62">Indonesia (+62)</option>
										<option data-countryCode="IR" value="+98">Iran (+98)</option>
										<option data-countryCode="IQ" value="+964">Iraq (+964)</option>
										<option data-countryCode="IE" value="+353">Ireland (+353)</option>
										<option data-countryCode="IL" value="+972">Israel (+972)</option>
										<option data-countryCode="IT" value="+39">Italy (+39)</option>
										<option data-countryCode="JM" value="+1876">Jamaica (+1876)</option>
										<option data-countryCode="JP" value="+81">Japan (+81)</option>
										<option data-countryCode="JO" value="+962">Jordan (+962)</option>
										<option data-countryCode="KZ" value="+7">Kazakhstan (+7)</option>
										<option data-countryCode="KE" value="+254">Kenya (+254)</option>
										<option data-countryCode="KI" value="+686">Kiribati (+686)</option>
										<option data-countryCode="KP" value="+850">Korea North (+850)</option>
										<option data-countryCode="KR" value="+82">Korea South (+82)</option>
										<option data-countryCode="KW" value="+965">Kuwait (+965)</option>
										<option data-countryCode="KG" value="+996">Kyrgyzstan (+996)</option>
										<option data-countryCode="LA" value="+856">Laos (+856)</option>
										<option data-countryCode="LV" value="+371">Latvia (+371)</option>
										<option data-countryCode="LB" value="+961">Lebanon (+961)</option>
										<option data-countryCode="LS" value="+266">Lesotho (+266)</option>
										<option data-countryCode="LR" value="+231">Liberia (+231)</option>
										<option data-countryCode="LY" value="+218">Libya (+218)</option>
										<option data-countryCode="LI" value="+417">Liechtenstein (+417)</option>
										<option data-countryCode="LT" value="+370">Lithuania (+370)</option>
										<option data-countryCode="LU" value="+352">Luxembourg (+352)</option>
										<option data-countryCode="MO" value="+853">Macao (+853)</option>
										<option data-countryCode="MK" value="+389">Macedonia (+389)</option>
										<option data-countryCode="MG" value="+261">Madagascar (+261)</option>
										<option data-countryCode="MW" value="+265">Malawi (+265)</option>
										<option data-countryCode="MY" value="+60">Malaysia (+60)</option>
										<option data-countryCode="MV" value="+960">Maldives (+960)</option>
										<option data-countryCode="ML" value="+223">Mali (+223)</option>
										<option data-countryCode="MT" value="+356">Malta (+356)</option>
										<option data-countryCode="MH" value="+692">Marshall Islands (+692)</option>
										<option data-countryCode="MQ" value="+596">Martinique (+596)</option>
										<option data-countryCode="MR" value="+222">Mauritania (+222)</option>
										<option data-countryCode="YT" value="+269">Mayotte (+269)</option>
										<option data-countryCode="MX" value="+52">Mexico (+52)</option>
										<option data-countryCode="FM" value="+691">Micronesia (+691)</option>
										<option data-countryCode="MD" value="+373">Moldova (+373)</option>
										<option data-countryCode="MC" value="+377">Monaco (+377)</option>
										<option data-countryCode="MN" value="+976">Mongolia (+976)</option>
										<option data-countryCode="MS" value="+1664">Montserrat (+1664)</option>
										<option data-countryCode="MA" value="+212">Morocco (+212)</option>
										<option data-countryCode="MZ" value="+258">Mozambique (+258)</option>
										<option data-countryCode="MN" value="+95">Myanmar (+95)</option>
										<option data-countryCode="NA" value="+264">Namibia (+264)</option>
										<option data-countryCode="NR" value="+674">Nauru (+674)</option>
										<option data-countryCode="NP" value="+977">Nepal (+977)</option>
										<option data-countryCode="NL" value="+31">Netherlands (+31)</option>
										<option data-countryCode="NC" value="+687">New Caledonia (+687)</option>
										<option data-countryCode="NZ" value="+64">New Zealand (+64)</option>
										<option data-countryCode="NI" value="+505">Nicaragua (+505)</option>
										<option data-countryCode="NE" value="+227">Niger (+227)</option>
										<option data-countryCode="NG" value="+234">Nigeria (+234)</option>
										<option data-countryCode="NU" value="+683">Niue (+683)</option>
										<option data-countryCode="NF" value="+672">Norfolk Islands (+672)</option>
										<option data-countryCode="NP" value="+670">Northern Marianas (+670)</option>
										<option data-countryCode="NO" value="+47">Norway (+47)</option>
										<option data-countryCode="OM" value="+968">Oman (+968)</option>
										<option data-countryCode="PW" value="+680">Palau (+680)</option>
										<option data-countryCode="PA" value="+507">Panama (+507)</option>
										<option data-countryCode="PG" value="+675">Papua New Guinea (+675)</option>
										<option data-countryCode="PY" value="+595">Paraguay (+595)</option>
										<option data-countryCode="PE" value="+51">Peru (+51)</option>
										<option data-countryCode="PH" value="+63">Philippines (+63)</option>
										<option data-countryCode="PL" value="+48">Poland (+48)</option>
										<option data-countryCode="PT" value="+351">Portugal (+351)</option>
										<option data-countryCode="PR" value="+1787">Puerto Rico (+1787)</option>
										<option data-countryCode="QA" value="+974">Qatar (+974)</option>
										<option data-countryCode="RE" value="+262">Reunion (+262)</option>
										<option data-countryCode="RO" value="+40">Romania (+40)</option>
										<option data-countryCode="RU" value="+7">Russia (+7)</option>
										<option data-countryCode="RW" value="+250">Rwanda (+250)</option>
										<option data-countryCode="SM" value="+378">San Marino (+378)</option>
										<option data-countryCode="ST" value="+239">Sao Tome &amp; Principe (+239)</option>
										<option data-countryCode="SA" value="+966">Saudi Arabia (+966)</option>
										<option data-countryCode="SN" value="+221">Senegal (+221)</option>
										<option data-countryCode="CS" value="+381">Serbia (+381)</option>
										<option data-countryCode="SC" value="+248">Seychelles (+248)</option>
										<option data-countryCode="SL" value="+232">Sierra Leone (+232)</option>
										<option data-countryCode="SG" value="+65">Singapore (+65)</option>
										<option data-countryCode="SK" value="+421">Slovak Republic (+421)</option>
										<option data-countryCode="SI" value="+386">Slovenia (+386)</option>
										<option data-countryCode="SB" value="+677">Solomon Islands (+677)</option>
										<option data-countryCode="SO" value="+252">Somalia (+252)</option>
										<option data-countryCode="ZA" value="+27">South Africa (+27)</option>
										<option data-countryCode="ES" value="+34">Spain (+34)</option>
										<option data-countryCode="LK" value="+94">Sri Lanka (+94)</option>
										<option data-countryCode="SH" value="+290">St. Helena (+290)</option>
										<option data-countryCode="KN" value="+1869">St. Kitts (+1869)</option>
										<option data-countryCode="SC" value="+1758">St. Lucia (+1758)</option>
										<option data-countryCode="SD" value="+249">Sudan (+249)</option>
										<option data-countryCode="SR" value="+597">Suriname (+597)</option>
										<option data-countryCode="SZ" value="+268">Swaziland (+268)</option>
										<option data-countryCode="SE" value="+46">Sweden (+46)</option>
										<option data-countryCode="CH" value="+41">Switzerland (+41)</option>
										<option data-countryCode="SI" value="+963">Syria (+963)</option>
										<option data-countryCode="TW" value="+886">Taiwan (+886)</option>
										<option data-countryCode="TJ" value="+7">Tajikstan (+7)</option>
										<option data-countryCode="TH" value="+66">Thailand (+66)</option>
										<option data-countryCode="TG" value="+228">Togo (+228)</option>
										<option data-countryCode="TO" value="+676">Tonga (+676)</option>
										<option data-countryCode="TT" value="+1868">Trinidad &amp; Tobago (+1868)</option>
										<option data-countryCode="TN" value="+216">Tunisia (+216)</option>
										<option data-countryCode="TR" value="+90">Turkey (+90)</option>
										<option data-countryCode="TM" value="+7">Turkmenistan (+7)</option>
										<option data-countryCode="TM" value="+993">Turkmenistan (+993)</option>
										<option data-countryCode="TC" value="+1649">Turks &amp; Caicos Islands (+1649)</option>
										<option data-countryCode="TV" value="+688">Tuvalu (+688)</option>
										<option data-countryCode="UG" value="+256">Uganda (+256)</option>
										<option data-countryCode="GB" value="+44">UK (+44)</option> 
										<option data-countryCode="UA" value="+380">Ukraine (+380)</option>
										<option data-countryCode="AE" value="+971">United Arab Emirates (+971)</option>
										<option data-countryCode="UY" value="+598">Uruguay (+598)</option>
										<!-- <option data-countryCode="US" value="1">USA (+1)</option> -->
										<option data-countryCode="UZ" value="+7">Uzbekistan (+7)</option>
										<option data-countryCode="VU" value="+678">Vanuatu (+678)</option>
										<option data-countryCode="VA" value="+379">Vatican City (+379)</option>
										<option data-countryCode="VE" value="+58">Venezuela (+58)</option>
										<option data-countryCode="VN" value="+84">Vietnam (+84)</option>
										<option data-countryCode="VG" value="+84">Virgin Islands - British (+1284)</option>
										<option data-countryCode="VI" value="+84">Virgin Islands - US (+1340)</option>
										<option data-countryCode="WF" value="+681">Wallis &amp; Futuna (+681)</option>
										<option data-countryCode="YE" value="+969">Yemen (North)(+969)</option>
										<option data-countryCode="YE" value="+967">Yemen (South)(+967)</option>
										<option data-countryCode="ZM" value="+260">Zambia (+260)</option>
										<option data-countryCode="ZW" value="+263">Zimbabwe (+263)</option>
									</optgroup>
  								</select>
								 </div>
                        <div class="form-group col-sm-6">
                            <label for="phone">Phone No.</label><span
                                    style="color:red;font-size: 20px;">*</span>
							<br>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter Phone Number" maxlength="10">
							
                        </div>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class='col-md-4 indent-small' id="errorPhone" style="color:#FF0000;font-size:15px" >
                  </div>
						</div>
						
                    

                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="addr">Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group col-sm-6">
                            <label for="city">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Enter City" >
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="state">State</label>
                            <input type="text" name="state" class="form-control" placeholder="Enter State">
                        </div>

                        <div class="form-group col-sm-6">
                            <label for="country">Country</label>
                            <input type="text" name="country" class="form-control" placeholder="Enter Country" >
                        </div>
                    </div>
                
                    <div class="row">
                        <div class="form-group col-sm-12">
                            <label for="pc">Preferred College</label>
                            <input type="text" name="preferred_college" class="form-control" placeholder="Enter Preferred College">
                        </div>
                    </div>
 <div class="field-column">
			<div class="terms">

                    <input type="checkbox" name="myCheck" id="myCheck" onclick="myFunction()"/> Connect SmartCookie Reward Platform
                </div>
				
				<div id="myDIV" style="display:none">
				 <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="memberId">Member ID</label><span
                                    style="color:red;font-size: 20px;">*</span>&nbsp;&nbsp;<a href="otpForm.php">Know Your MemberID</a>
                            <input type="text" name="memberId" id="member_id" class="form-control" placeholder="Enter Member ID">
							
                        </div>

                        <div class="form-group col-sm-6">
                            <label for="pass">Password</label><span
                                    style="color:red;font-size: 20px;">*</span>
                            <input type="password" name="pass" id="password" class="form-control" placeholder="Enter Password" >
							
                    </div>
					<div class='col-md-4 indent-small' id="errorID" style="color:#FF0000">
                  </div>
				  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				  <div class='col-md-4 indent-small' id="errorp" style="color:#FF0000"></div>
                        </div>
								<label>Select Entity Type</label><span
                                    style="color:red;font-size: 20px;">*</span>
								<select id="ent_type" name="ent_type" >
								
								<option value="">Select</option> 
								
									<option value="105">Student</option>
									<option value="103">Teacher</option></select>
									<div class='col-md-4 indent-small' id="errorEnt" style="color:#FF0000">
												
</div>
						Don't have An Account?<a href="https://smartcookie.in/core/express_registration_sp.php" target="_blank">Click Here</a>To Register With SmartCookie Rewards Platform
                </div>             
				<style>
				 select {
        width: 195px;
		height: 31px;
        margin: 9px;
		border: #5791da 1px solid;
    }
    select:focus {
        min-width: 150px;
        width: 200px;
    }   
				</style>
            
				<script>
function myFunction() {
  var x = document.getElementById("myDIV");
  if (x.style.display === "block") {
    x.style.display = "none";
  } else {
    x.style.display = "block";
  }
}
</script>



<script>
function valid()
{
	//regx1=/^[A-z0-9\.\- ]+$/;
		regx1=/^[A-z\.\- ]+$/;
var Class = document.getElementById("name").value;
		if(Class.trim()=="" || Class.trim()==null)
            {

                document.getElementById('errorName').innerHTML='Please Enter Your Name';

                return false;
            }
			else if (!regx1.test(Class)) {
			 
			  document.getElementById('errorUserName').innerHTML='Please Enter Valid Name';

      			 return false;
		 }
		 
 var password = document.getElementById("pass").value;
		if(password.trim()=="" || password.trim()==null)
            {

                document.getElementById('errorPassword').innerHTML='Please Enter Password';

                return false;
            }


     var email = document.getElementById("userEmail").value;
		var pattern = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
       if(email.trim()=="" || email.trim()==null)
		{
			document.getElementById('errorEmail').innerHTML='Please Enter Email ID';
			return false;
		}
        
        else if (pattern.test(email)) {
            
        }
		else{
			document.getElementById('errorEmail').innerHTML='Please Enter Valid Email ID';
       
		return false;
		}         
		var phone = document.getElementById("phone").value;
		var country_code = document.getElementById("country_code").value;
		var pattern =/^[6789]\d{9}$/;
       if(phone.trim()=="" || phone.trim()==null)
		{
			
			document.getElementById('errorPhone').innerHTML='Please Enter Mobile Number';
			return false;
		}
        
        else if (pattern.test(phone)) {
            
        }
		else if(phone.Length!=10)
		{
			document.getElementById('errorPhone').innerHTML='Please Enter Valid Mobile Number';
		return false;
		}
		// else{
        // document.getElementById('errorPhone').innerHTML='Please Enter Valid Mobile Number';
		// return false;
		// }
		

var member_id = document.getElementById("member_id").value;
var pass = document.getElementById("password").value;
var ent_type = document.getElementById("ent_type").value;
if(document.getElementById('myCheck').checked){
   
		if(member_id.trim()=="" || member_id.trim()==null)
            {

                document.getElementById('errorID').innerHTML='Please Enter SmartCookie Member ID';

                return false;
            }
			else if(pass.trim()=="" || pass.trim()==null)
            {

                document.getElementById('errorp').innerHTML='Please Enter SmartCookie Password';

                return false;
            }
			else if(ent_type.trim()=="" || ent_type.trim()==null)
            {

                document.getElementById('errorEnt').innerHTML='Please Select Student/Teacher';

                return false;
            }
            
               
}
}
</script>
 
			
            </div>
                    <center>
                        <input type="submit" name="register" value="Register" class="btn btn-success" onClick="return valid();">
                        <button class="btn btn-primary"><a href="login.php" style="text-decoration:none; color:white;">Back</a></button>
                    </center>
                </form>
            </div> 
        </div>
    </div>
</body>
</html>