<?php

include_once 'tohtml.php' ;
include_once 'methods.php' ;
include_once 'calendar/calendar.php' ;


session_save_path("/tmp/");

// If user is already authenticated, redirect him to user.php
// NOTE: DO NOT put this block before loading configuration files.
if( array_key_exists( 'AUTHENTICATED', $_SESSION) && $_SESSION[ 'AUTHENTICATED' ] )
{
    if( $_SESSION[ 'user' ] != 'anonymous' )
    {
        echo printInfo( "Already logged-in" );
        goToPage( 'user.php', 0 );
        exit;
    }
}

$_SESSION['user'] = 'anonymous'; // This for testing purpose.
$_SESSION[ 'oauth_credential' ] =
    '/etc/hippo/client_secret_636127149215-mn7vk37265hlq48d39qt45asnsvdbti0.apps.googleusercontent.com.json';
$_SESSION[ 'calendar_id'] = 
    'd2jud2r7bsj0i820k0f6j702qo@group.calendar.google.com'; 

$_SESSION[ 'service_key_file' ] = '/etc/hippo/hippo-f1811b036a3f.json';

$_SESSION[ 'timezone' ] = 'Asia/Kolkata';
ini_set( 'date.timezone', 'Asia/Kolkata' );

// Now create a login form.
echo "<table class=\"index\">";
echo '</tr>';
echo loginForm();
echo '</tr>';
echo "</table>";

// Show background image only on index.php page.
$thisPage = basename( $_SERVER[ 'PHP_SELF' ] );
if( strpos( $thisPage, 'index.php' ) !== false )
{
    // Select one background picture.
    $command = 'nohup python ' 
        . __DIR__ . '/fetch_backgrounds.py > /dev/null 2>&1 &'
        ;
    // Run command.
    shell_exec( $command );

    // Select one image from directory _backgrounds.
    $background = random_jpeg( "data/_backgrounds" );
    if( $background )
    {
        echo "<body style=\" background-image:url($background);
        filter:alpha(Opactity=30);opacity=0.3;
        width:800px;
        \">";
    }
}


//echo '<br>';
//echo '<div class="public_calendar">';
//echo calendarIFrame( );
//echo '</div>';

echo "<br><br>";

include_once 'footer.php';
?>
