<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';

echo userHTML( );

echo '
    <form action="" method="get" accept-charset="utf-8">
    <input type="text" name="query" value="" >
    <button type="submit" name="response" value="Search">Search</button>
    </form>
    ';

$query = $_GET['query' ];
echo printInfo( "Searching for $query" );

$awses = queryAWS( $query );
echo printInfo( "Total matches " .  count( $awses ) );
foreach( $awses as $aws )
{
    echo arrayToVerticalTableHTML( $aws, 'show_aws', ''
    , array( 'id', 'time', 'supervisor_2'
        , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4'
        )
    );
    echo '<br>';
}

echo goBackToPageLink( "user_aws.php", "Go back" );

?>
