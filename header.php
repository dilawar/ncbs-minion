<?php 
ob_start();

if(!isset($_SESSION)) 
    session_start();

// If this is not a index.php page. check if access is valid. 
if( ! $_SERVER['PHP_SELF'] == 'index.php' ) 
    include_once( 'is_valid_access.php' );

error_reporting( E_ALL );
ini_set( "display_errors", 1 );

?>

<!DOCTYPE html>
<html>
<head>
<link href="minion.css" rel="stylesheet" type="text/css" />
</head>
<div class="header">

<title>NCBS Minion</title>

<h1><a href="<?php echo dirname( 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ) ?>">NCBS Minion</a></h1>
<a href="https://github.com/dilawar/ncbs-minion" target='_blank'>Github</a>
<a href='https://github.com/dilawar/ncbs-minion/issues' target="_blank">Report issue</a></small>
</div>
<br />
<br />

<div class="bottom"> GNU GPL-v3, (c) 2016, Dilawar Singh </div>
</html>

<!-- Here are the js script -->
<script src="js/jquery-1.12.4.js"></script>
<script src="js/jquery-ui.js"></script>

<script src="js/jquery-ui.multidatespicker.js"></script>
<link rel="stylesheet" href="js/jquery-ui.css">


<script type="text/javascript" src="js/jquery-timepicker/jquery.timepicker.js"></script>
<link rel="stylesheet" type="text/css" href="js/jquery-timepicker/jquery.timepicker.css" />

<script type="text/javascript" src="js/jquery-timepicker/lib/site.js"></script>
<link rel="stylesheet" type="text/css" href="lib/site.css" />

<script>
$( function() {
    $( "input.datepicker" ).datepicker( { dateFormat: "yy-mm-dd" } );
  } );
$( function() {
    $( "input.datetimepicker" ).datepicker( { dateFormat: "yy-mm-dd" } );
  } );
</script>

<script>
$(function(){
    $('input.timepicker').timepicker({ 'timeFormat' : 'H:i', 'step' : '15' });
});

</script>

<!-- CKEDIR -->
<script src="./ckeditor/ckeditor.js"></script>


