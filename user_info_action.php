<?php

include_once 'header.php';
include_once 'database.php';
include_once 'mail.php';
include_once 'tohtml.php';

// Not all login can be queried from ldap. Let user edit everything.
$res = updateTable( 
        "logins"
        , "login"
        , "valid_until,first_name,last_name,title" . 
             ",laboffice,joined_on,alternative_email"
        , $_POST 
    );

if( $res )
{
    echo printInfo( "User details have been updated sucessfully" );
    // Now send an email to user.
    $info = getUserInfo( $_SESSION[ 'user' ] );

    sendPlainTextEmail( 
        arrayToVerticalTableHTML( $info, "details" )
        , "Your details have been updated successfully."
        , $info[ 'email' ]
        );

    goToPage( 'user.php', 1 );
    exit;
}

echo printWarning( "Could not update user details " );
echo goBackToPageLink( "user.php", "Go back" );
exit;

?>
