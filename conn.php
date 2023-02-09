<?php

/**By Saumya
 * mysqli_connect("148.72.88.25", "tappcampusradio", "Bpsi@1234","tapp.campusradio.rocks");
 *                  hostname           username         pasword     databasename
 */
$server_name = $_SERVER['SERVER_NAME'];
$GLOBALS['URLNAME']=$server_name;
switch($server_name){
    case 'play.campusradio.in': // for production 
        $conn=mysqli_connect("148.72.88.25", "app", "h4TngD}{i-9I","app.campusradio.rocks");
        if (!$conn) {
            die("Unable to establish a DB connection: " . mysqli_connect_error());
            }else{
            // echo "Connected successfully";
            }
        // mysqli_select_db($conn,$db_name);
        break;
    case 'tplay.campusradio.in':
        $conn=mysqli_connect("148.72.88.25", "tappcampusradio", "Bpsi@1234","tapp.campusradio.rocks");
        if (!$conn) {
            die("Unable to establish a DB connection: " . mysqli_connect_error());
            }else{
            // echo "Connected successfully";
            }
        // mysqli_select_db($conn,$db_name);
        break;
    case 'localhost.campusradio.in':
//    case 'localhost':
        $conn=mysqli_connect("148.72.88.25", "tappcampusradio", "Bpsi@1234","tapp.campusradio.rocks");
        if (!$conn) {
            die("Unable to establish a DB connection: " . mysqli_connect_error());
            }else{
            // echo "Connected successfully";
            }
        // mysqli_select_db($conn,$db_name);
        break;
    default:
        $conn=mysqli_connect("148.72.88.25", "tappcampusradio", "Bpsi@1234","tapp.campusradio.rocks");
        if (!$conn) {
            die("Unable to establish a DB connection: " . mysqli_connect_error());
            }else{
            // echo "Connected successfully";
            }
        // mysqli_select_db($conn,$db_name);
        break;

}

mysqli_query($conn,"SET NAMES 'utf8'");
$tvconnect=mysqli_connect("148.72.88.25", "app", "h4TngD}{i-9I","app.campusradio.rocks" ) ;

// mysqli_close($conn);
// mysqli_select_db($conn,$db_name);




?>