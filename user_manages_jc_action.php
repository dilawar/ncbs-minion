<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

if( $_POST[ 'response' ] == 'Unsubscribe' )
{
    $_POST[ 'status' ] = 'UNSUBSCRIBED';
    $res = updateTable(
        'jc_subscriptions'
        , 'login,jc_id', 'status', $_POST
    );
    if( $res )
    {
        echo printInfo( 'Successfully unsubscribed from ' . $_POST['jc_id'] );
        goToPage( 'user_manages_jc.php', 1 );
        exit;
    }
}
else if( $_POST[ 'response' ] == 'Subscribe' )
{
    $_POST[ 'status' ] = 'VALID';
    $res = insertOrUpdateTable(
        'jc_subscriptions', 'login,jc_id', 'status',  $_POST
    );
    if( $res )
    {
        echo printInfo( 'Successfully subscribed to ' . $_POST['jc_id'] );
        goToPage( 'user_manages_jc.php', 1 );
        exit;
    }
}
else
{
    echo "Action " . $_POST[ 'response' ] . " is not yet supported";
}



echo goBackToPageLink( "user_manages_jc.php", "Go Back" );


?>
