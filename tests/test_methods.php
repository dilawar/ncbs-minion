<?php

set_include_path( '..' );

include_once( 'methods.php' );


//$pat = constructRepeatPattern( "tue,wed,fri", "", "2" );
//echo "User pattern $pat \n";
//echo " My construction ";
//$pat = repeatPatToDays( $pat, '2017-04-11' );
//var_dump( $pat );
//print( "\nTest 2 </br> \n" );
$pat = constructRepeatPattern( "Mon Wed", "", "2" );
print_r( $pat );
print_r( repeatPatToDays( $pat, dbDate('today') ) );

$pat = constructRepeatPattern( "Tue", "second,fourth", "6" );
print_r( $pat );
print_r( repeatPatToDays( $pat, dbDate('today') ) );

?>
