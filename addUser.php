<?php

$servername = "localhost";
$username = "other_svc";
$password = "final-S3cr3t";
$dbname = "dokhlabwiki";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully<br>";

$username = 'Jian';
$password = md5('Kang1994$');
$email = 'jianopt@ad.unc.edu';
$level = 10;
$approved = 1;
$emailConfirmed = 1;

$sql = "insert into user(user_name, user_email) values('$username', '$email')";
$conn->query($sql);

$sql = "select user_id from user where user_name='$username'";
$r = $conn->query($sql);
$arr = $r->fetch_array();
$id = $arr[0];
$password = md5("{$id}-{$password}");

$sql = "update user set user_password = '$password' where user_id=$id";
$conn->query($sql);

$conn->close();

