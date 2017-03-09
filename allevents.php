<?php

include_once 'header.php';
include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

// Show it only if accessed from intranet or user have logged in.
if( ! (isIntranet( ) || $_SESSION[ 'AUTHENTICATED' ] ) )
{
    echo printWarning( "To access this page, either use Intranet or log-in first" );
    echo closePage( );
    exit;
}

// This page displays all events on campus. Select all venues.
$venues = getVenues( $sortby = 'id' );
$venuesDict = array( );
foreach( $venues as $v )
    $venuesDict[ $v[ 'id' ] ] = $v;

$venuesIds = array_map( function( $v ) { return $v['id']; }, $venues );

$defaults = array( 'venues' => implode(',', $venuesIds)
        , 'date' => dbDate( 'today' )
        );

if( array_key_exists( 'date', $_GET ) )
    $defaults[ 'date' ] = $_GET[ 'date' ];

if( array_key_exists( 'venues', $_GET ) )
    $defaults[ 'venues' ] = implode( ',', $_GET[ 'venues' ] );


echo '<form action="" method="get" accept-charset="utf-8">
    <table class="info">
    <tr>
        <td> <input type="date" class="datepicker" name="date" value="' . 
            $defaults[ 'date' ] . '" /> </td>
            <td> <button name="response" value="' . $defaults[ 'venues' ] . 
                '" >' . $symbScan . '</button> </td>
    </tr>
    </table>
    </form>';

$calendarDate = humanReadableDate( $defaults[ 'date' ] );
echo "<h1> Table of events on $calendarDate </h1>";

//foreach( explode( ",", $defaults[ 'venues' ]) as $venueId )
$events = getEventsOn( $defaults['date' ] );
echo '<table width="250px">';
echo '<tr>';

$count = 0;
foreach( $events as $ev )
{
    $count += 1;
    if( $count % 7 == 0 )
        echo "</tr><tr>";
    echo "<td style=\"min-width:150px;max-width:300px;border:1px dotted;\">";
    echo eventToShortHTML( $ev );
    echo "</td>";
}

echo '</tr>';
echo '</table>';
echo '</br>';

echo closePage( );

?>
