<?php

set_include_path( '..' );

include_once( 'methods.php' );


//$pat = constructRepeatPattern( "tue,wed,fri", "", "2" );
//echo "User pattern $pat \n";
//echo " My construction ";
//$pat = repeatPatToDays( $pat, '2017-04-11' );
//var_dump( $pat );
//print( "\nTest 2 </br> \n" );
$pat = constructRepeatPattern( "Tue Wed", "", "2" );
echo( $pat );
print_r( repeatPatToDays( $pat, '2017-04-10' ) );

?>
