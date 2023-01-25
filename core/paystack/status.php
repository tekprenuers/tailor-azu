<?php

$error = null;

if(isset($_GET) && isset($_GET['error'])){
	$error = base64_decode($_GET['error']);
	echo '
	<script id="gmas_cul">
	window.history.pushState({}, document.title, "/" + "paystack/status");
	document.querySelector("#gmas_cul").remove();
	</script>
	';
}else{
	header("Location: https://givemeastar.com/pricing");
	exit();
}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Transaction Not Successful</title>
</head>
<body>
	<p style="
    text-align: center;
    font-size: 1.5em;
    padding: 10px;
    background-color: #ff000029;
    /* margin: auto; */
    color: #ff0202;
"><?php echo $error; ?></p>
<div align="center">
	<button style="
    padding: 10px;
    border: 1px solid #ff0202;
    color: #ff0202;
    background-color: #ffd6d6;
"><a href="https://givemeastar.com/contact" 
style="text-decoration:none;color:inherit;" target="_self">Contact us</a></button>
</div>
    
</body>
</html>