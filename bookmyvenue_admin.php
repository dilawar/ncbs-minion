<?php 

// This is admin interface for book my venue.
// We are here to manage the requests.
include_once "header.php";
include_once "methods.php";
include_once "database.php";
include_once "tohtml.php";
include_once "check_access_permissions.php";

mustHaveAllOfTheseRoles( array( 'BOOKMYVENUE_ADMIN' ) );

echo userHTML( );

echo '<table class="show_info">
    <tr>
    <td>
       <strong>Make sure you are logged-in using correct google account </strong>
        </strong>
    </td>
        <td>
            <a href="bookmyvenue_admin_synchronize_events_with_google_calendar.php">
            Synchronize public calendar </a> 
        </td>
    </tr>
    </table>
    ';

echo '<h2> Pending requests </h2>';
$requests = getPendingRequestsGroupedByGID( ); 

if( count( $requests ) == 0 )
    echo printInfo( "Cool! No request is pending for review" );
else
    echo printInfo( "These requests needs your attention" );

$html = "<div style=\"font-size:small\">";
$html .= '<table class="show_request">';
foreach( $requests as $r )
{
    $html .= '<form action="bookmyvenue_admin_request_review.php" method="post">';
    $html .= '<tr><td>';
    // Hide some buttons to send information to next page.
    $html .= '<input type="hidden" name="gid" value="' . $r['gid'] . '" />';
    $html .= '<input type="hidden" name="rid" value="' . $r['rid'] . '" />';
    $html .= arrayToTableHTML( $r, 'events'
        , ' '
        ,  'last_modified_on,status,modified_by,timestamp,url,external_id,gid,rid'
    );
    $html .= '</td>';
    $html .= '<td style="background:white">
        <button name="response" value="Review" title="Review request"> ' . 
            $symbReview . '</button> </td>';
    $html .= '</tr>';
    $html .= '</form>';
}
$html .= '</table>';
$html .= "</div>";
echo $html;
echo goBackToPageLink( "user.php", "Go back" );

?>

<h2>Upcoming (approved) events in next 4 weeks </h2>

<?php

$events = getEventsBeteen( 'today', '+4 week' );

//echo alertUser( 'Public events will apprear first' );
//$publicEvents = array( );
//$nonPublicEvents = array( );
//foreach( $events as $e )
//{
//    if( $e[ 'is_public_event' ] == 'YES' )
//    {
//        //var_dump( $e );
//        $publicEvents[] = $e;
//    }
//    else
//        $nonPublicEvents[] = $e;
//}

//$events = $publicEvents + $nonPublicEvents;

if( count( $events ) > 0 )
{
    $html = '<div style="font-size:small;">';
    $event = $events[0];
    $html .= "<table class=\"show_events\">";

    $tofilter = 'eid,calendar_id,calendar_event_id' .  
        ',external_id,gid,last_modified_on';

    $html .= arrayHeaderRow( $event, 'show_events', $tofilter );

    foreach( $events as $event )
    {
        // Today's event if they are passed, don't display them.
        if( $event[ 'date' ] == dbDate( 'today' ) && $event[ 'start_time'] < dbTime( 'now' ) )
            continue;

        $gid = $event['gid'];
        $eid = $event['eid'];
        $html .= "<tr><form method=\"post\" action=\"bookmyvenue_admin_edit.php\">";
        $html .= "<td>";
        $html .= arrayToRowHTML( $event, 'events', $tofilter );
        $html .= "</td>";

        $html .= "<td> <button title=\"Edit this entry\"  name=\"response\" 
                value=\"edit\">" . $symbEdit .  "</button></td>";
        $html .= "<input name=\"gid\" type=\"hidden\" value=\"$gid\" />";
        $html .= "<input name=\"eid\" type=\"hidden\" value=\"$eid\" />";
        $html .= "</td></form></tr>";
    }

    $html .= "</table>";
    $html .= "</div>";
    echo $html;
}

echo goBackToPageLink( "user.php", "Go back" );

?>

