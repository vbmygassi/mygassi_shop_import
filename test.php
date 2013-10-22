<?php
$conn = mysqli_connect("localhost", "root", "2317.187.fuckingsuck", "fuck");
if(mysqli_connect_errno()){
	print "Error Bäbeh: " . mysqli_connect_error() . "\n";
	exit(1);
}

// $sql = "insert into fuck (id, text) values ('5', 'fuckär')";

$sql  = "";;
$sql .= "create procedure if not exist Fuck(Message TEXT) ";
$sql .= "begin ";
$sql .= "insert into fuck (id, text) values (2, 'muddi');";
$sql .= "end";

print $sql; 
print "\n";

$res = mysqli_query($conn, $sql);

print $res; 
print "\n";

if(!($res = mysqli_prepare($conn, 'Fuck("theWaldfee")'))){
}

$res->execute();

print $res; 
print "\n";
