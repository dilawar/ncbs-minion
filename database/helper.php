<?php

require_once 'database/base.php';
require_once "methods.php";
require_once 'ldap.php';

// Option values for event/request.
$dbChoices = array(
    'bookmyvenue_requests.class' =>
        'UNKNOWN,TALK,INFORMAL TALK' .
        ',MEETING,LAB MEETING,THESIS COMMITTEE MEETING,JOURNAL CLUB MEETING' .
        ',SEMINAR,SIMONS SEMINAR,PRESYNOPSIS THESIS SEMINAR, THESIS SEMINAR,ANNUAL WORK SEMINAR' .
        ',SIMONS COLLOQUIA,SIMONS LECTURE SERIES' .
        ',LECTURE,PUBLIC LECTURE,CLASS,TUTORIAL' .
        ',INTERVIEW,SPORT EVENT,CULTURAL EVENT,OTHER'
    , 'events.class' =>
        'UNKNOWN,TALK,INFORMAL TALK,LECTURE,PUBLIC LECTURE' .
        ',MEETING,LAB MEETING,THESIS COMMITTEE MEETING,JOURNAL CLUB MEETING' .
        ',SEMINAR,THESIS SEMINAR,ANNUAL WORK SEMINAR' .
        ',SIMONS COLLOQUIA,SIMONS LECTURE SERIES' .
        ',LECTURE,PUBLIC LECTURE,CLASS,TUTORIAL' .
        ',INTERVIEW,SPORT EVENT,CULTURAL EVENT,OTHER'
    , 'venues.type' =>
        'OPEN AIR,MEETING ROOM,CAFETERIA,LECTURE HALL,SPORTS,AUDITORIUM,CENTER' .
        ',UNKNOWN,CONFERENCE ROOM'
    , 'talks.class' =>
        'TALK,INFORMAL TALK,LECTURE,PUBLIC LECTURE' .
        ',SEMINAR,THESIS SEMINAR,PRESYNOPSIS THESIS SEMINAR,SIMONS SEMINAR,ANNUAL WORK SEMINAR' .
        ',SIMONS COLLOQUIA,SIMONS LECTURE SERIES' .
        ',LECTURE,PUBLIC LECTURE,CLASS,TUTORIAL'
    , 'bookmyvenue.class' =>
        'UNKNOWN,TALK,INFORMAL TALK' .
        ',MEETING,LAB MEETING,THESIS COMMITTEE MEETING,JOURNAL CLUB MEETING' .
        ',SEMINAR,SIMONS SEMINAR,THESIS SEMINAR,' .
        ',SEMINAR,THESIS SEMINAR,SIMONS SEMINAR,PRESYNOPSIS THESIS SEMINAR,ANNUAL WORK SEMINAR' .
        ',LECTURE,PUBLIC LECTURE,CLASS,TUTORIAL' .
        ',INTERVIEW,SPORT EVENT,CULTURAL EVENT,OTHER'
    );

/**
    * @brief Return a sorted array out of choices.
    *
    * @param $choices
    * @param $key
    * @param $default
    * @param $sorted
    *
    * @return
 */
function getChoicesFromGlobalArray( $choices, $key, $default = 'UNKNOWN', $sorted = true )
{
    $choicesSplit = array_filter( explode( ',', __get__( $choices, $key, '' )));

    if( $sorted )
        sort( $choicesSplit );

    // Remove the default one and add the default at the front.
    $results = array_diff( $choicesSplit, array( $default ) );
    array_unshift( $results, $default );

    return array_unique( $results );
}

function getEventsOfTalkId( $talkId )
{
    $externalId = getTalkExternalId( $talkId );

    $entry = getTableEntry( 'events', 'external_id,status'
        , array( 'external_id' => "$externalId" , 'status' => 'VALID' )
        );
    return $entry;
}

function getBookingRequestOfTalkId( $talkId )
{
    $externalId = getTalkExternalId( $talkId );
    $entry = getTableEntry( 'bookmyvenue_requests', 'external_id,status'
        , array( 'external_id' => "$externalId", 'status' => 'PENDING' )
        );
    return $entry;
}

/**
 * @brief It does the following tasks.
 *  1. Move the entruies from upcoming_aws to annual_work_seminars lists.
 *
 * @return
 */
function doAWSHouseKeeping( )
{
    $oldAws = getTableEntries( 'upcoming_aws' , 'date', "status='VALID' AND date < NOW( )");
    $badEntries = array( );
    foreach( $oldAws as $aws )
    {
        if( strlen( $aws[ 'title' ]) < 1 || strlen( $aws[ 'abstract' ] ) < 1)
        {
            array_push( $badEntries, $aws );
            continue;
        }

        $res1 = insertIntoTable( 'annual_work_seminars'
            , 'speaker,date,time,supervisor_1,supervisor_2' .
                ',tcm_member_1,tcm_member_2,tcm_member_3,tcm_member_4' .
                ',title,abstract,is_presynopsis_seminar', $aws
            );

        if( $res1 )
        {
            $res2 = deleteFromTable( 'upcoming_aws', 'id', $aws );
            if( ! $res2 )
                array_push( $badEntries, $aws );
        }
        else
        {
            $badEntries[] =  $aws;
            echo printWarning( "Could not move entry to main AWS list" );
        }
    }
    return $badEntries;
}

function getVenues( $sortby = 'total_events DESC, id' )
{
    // Sort according to total_events hosted by venue
    $res = executeQuery( "SELECT * FROM venues ORDER BY $sortby" );
    return $res;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Execute given query.
    *
    * @Param $query
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function executeQuery( $query )
{
    global $hippoDB;
    $res = $hippoDB->query( $query );
    return fetchEntries( $res );
}

function executeURlQueries( $query )
{
    global $hippoDB;
    $res = $hippoDB->query( $query );
    return $res;
}

function getVenuesByType( $type )
{
    return getTableEntries( 'venues', 'id', "type='$type'" );
}

function getTableSchema( $tableName )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "DESCRIBE $tableName" );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getVenuesGroupsByType(  )
{
    // Sort according to total_events hosted by venue
    $venues = getVenues( );
    $newVenues = Array( );
    foreach( $venues as $venue )
    {
        $vtype = $venue['type'];
        if( ! array_key_exists( $vtype, $newVenues ) )
            $newVenues[ $vtype ] = Array();
        array_push( $newVenues[$vtype], $venue );
    }
    return $newVenues;
}

// Return the row representing venue for given venue id.
function getVenueById( $venueid )
{
    global $hippoDB;
    $venueid = trim( $venueid );
    $stmt = $hippoDB->prepare( "SELECT * FROM venues WHERE id=:id" );
    $stmt->bindValue( ':id', $venueid );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getPendingRequestsOnThisDay( $date )
{
    $requests = getTableEntries( 'bookmyvenue_requests', 'date,start_time'
            , "date='$date' AND status='PENDING'"
        );
    return $requests;
}

// Get all requests which are pending for review.
function getPendingRequestsGroupedByGID( )
{
    return getRequestsGroupedByGID( 'PENDING' );
}

// Get all requests with given status.
function getRequestsGroupedByGID( $status  )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( 'SELECT * FROM bookmyvenue_requests
        WHERE status=:status  GROUP BY gid ORDER BY date,start_time' );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

// Get all events with given status.
function getEventsByGroupId( $gid, $status = NULL  )
{
    global $hippoDB;
    $query = "SELECT * FROM events WHERE gid=:gid";
    if( $status )
        $query .= " AND status=:status ";

    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':gid', $gid );
    if( $status )
        $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

//  Get a event of given gid and eid. There is only one such event.
function getEventsById( $gid, $eid )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( 'SELECT * FROM events WHERE gid=:gid AND eid=:eid' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':eid', $eid );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Get list of requests made by this users. These requests must be
    * newer than the current date minus 2 days and time else they won't show up.
    *
    * @param $userid
    * @param $status
    *
    * @return
 */
function getRequestOfUser( $userid, $status = 'PENDING' )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        'SELECT * FROM bookmyvenue_requests WHERE created_by=:created_by
        AND status=:status AND date >= NOW() - INTERVAL 2 DAY
        GROUP BY gid ORDER BY date,start_time' );
    $stmt->bindValue( ':created_by', $userid );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOfUser( $userid, $from = 'today', $status = 'VALID' )
{
    global $hippoDB;
    $from = dbDate( $from );
    $stmt = $hippoDB->prepare( 'SELECT * FROM events WHERE created_by=:created_by
        AND date >= :from
        AND status=:status
        GROUP BY gid ORDER BY date,start_time' );
    $stmt->bindValue( ':created_by', $userid );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':from', $from );
    $stmt->execute( );
    return fetchEntries( $stmt );

}

/**
    * @brief Get all approved events starting from given date and duration.
    *
    * @param $from
    * @param $duration
    *
    * @return
 */
function getEventsBeteen( $from , $duration )
{
    $startDate = dbDate( $from );
    $endDate = dbDate( strtotime( $duration, strtotime( $from ) ) );
    $whereExpr = "date >= '$startDate' AND date <= '$endDate'";
    $whereExpr .= " AND status='VALID' ";
    return getTableEntries( 'events', 'date,start_time', $whereExpr );
}


// Fetch entries from database response object
function fetchEntries( $res, $how = PDO::FETCH_ASSOC )
{
    $array = Array( );
    if( $res ) {
        while( $row = $res->fetch( $how ) )
            array_push( $array, $row );
    }
    return $array;
}

// Get the request when group id and request id is given.
function getRequestById( $gid, $rid )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( 'SELECT * FROM bookmyvenue_requests WHERE gid=:gid AND rid=:rid' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':rid', $rid );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

// Return a list of requested with same group id.
function getRequestByGroupId( $gid )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( 'SELECT * FROM bookmyvenue_requests WHERE gid=:gid' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

// Return a list of requested with same group id and status
function getRequestByGroupIdAndStatus( $gid, $status )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( 'SELECT * FROM bookmyvenue_requests WHERE gid=:gid AND status=:status' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Change the status of request.
    *
    * @param $requestId
    * @param $status
    *
    * @return true on success, false otherwise.
 */
function changeRequestStatus( $gid, $rid, $status )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "UPDATE bookmyvenue_requests SET
        status=:status,last_modified_on=NOW() WHERE gid=:gid AND rid=:rid"
    );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':rid', $rid );
    return $stmt->execute( );
}

/**
    * @brief Change status of all request identified by group id.
    *
    * @param $gid
    * @param $status
    *
    * @return
 */
function changeStatusOfRequests( $gid, $status )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "UPDATE bookmyvenue_requests SET status=:status WHERE gid=:gid" );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':gid', $gid );
    return $stmt->execute( );
}

function changeStatusOfEventGroup( $gid, $user, $status )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "UPDATE events SET status=:status WHERE
        gid=:gid AND created_by=:created_by" );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':created_by', $user );
    return $stmt->execute( );
}

function changeStatusOfEvent( $gid, $eid, $user, $status )
{
    $res = updateTable( 'events', 'gid,eid,created_by', 'status'
        , array( 'gid' => $gid, 'eid' => $eid, 'status' => $status, 'created_by' => $user )
        );
    return $res;
}

/**
    * @brief Get the list of upcoming events.
 */
function getEvents( $from = 'today', $status = 'VALID' )
{
    global $hippoDB;
    $from = dbDate( $from );
    $stmt = $hippoDB->prepare( "SELECT * FROM events WHERE date >= :date AND
        status=:status ORDER BY date,start_time " );
    $stmt->bindValue( ':date', $from );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}


/**
  * @brief Get the list of upcoming events grouped by gid.
 */
function getEventsGrouped( $sortby = '', $from = 'today', $status = 'VALID' )
{
    global $hippoDB;
    $sortExpr = '';

    $sortby = explode( ',', $sortby );
    if( count($sortby) > 0 )
        $sortExpr = 'ORDER BY ' . implode( ', ', $sortby);

    $nowTime = dbTime( $from );
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events WHERE date >= :date
            AND status=:status GROUP BY gid $sortExpr"
        );
    $stmt->bindValue( ':date', $nowTime );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get the list of upcoming events.
 */
function getPublicEvents( $from = 'today', $status = 'VALID', $ndays = 1 )
{
    global $hippoDB;
    $from = dbDate( $from );
    $end = dbDate( strtotime( $from . " +$ndays day" ) );
    $stmt = $hippoDB->prepare( "SELECT * FROM events WHERE date >= :date AND
        date <= :end_date AND
        status=:status AND is_public_event='YES' ORDER BY date,start_time" );
    $stmt->bindValue( ':date', $from );
    $stmt->bindValue( ':end_date', $end );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get list of public event on given day.
    *
    * @param $date
    * @param $status
    *
    * @return
 */
function getPublicEventsOnThisDay( $date = 'today', $status = 'VALID' )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SELECT * FROM events WHERE date = :date AND
        status=:status AND is_public_event='YES' ORDER BY date,start_time"
        );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOn( $day, $status = 'VALID')
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SELECT * FROM events
        WHERE status=:status AND date = :date ORDER BY date,start_time" );
    $stmt->bindValue( ':date', $day );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOnThisVenueOnThisday( $venue, $date, $status = 'VALID' )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SELECT * FROM events
        WHERE venue=:venue AND status=:status AND date=:date ORDER
            BY date,start_time" );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief get overlapping requests or events.
    *
    * @param $venue
    * @param
    * @param $start_time
    * @param
    * @param $status
    *
    * @return
 */
function getEventsOnThisVenueBetweenTime( $venue, $date
    , $start_time, $end_time
   ,  $status = 'VALID' )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events
        WHERE venue=:venue AND status=:status AND date=:date AND status='VALID'
        AND ( (start_time < :start_time AND end_time > :start_time )
              OR ( start_time < :end_time AND end_time > :end_time )
              OR ( start_time >= :start_time AND end_time <= :end_time )
            )
        "
    );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':start_time', $start_time );
    $stmt->bindValue( ':end_time', $end_time );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getRequestsOnThisVenueOnThisday( $venue, $date, $status = 'PENDING' )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SELECT * FROM bookmyvenue_requests
        WHERE venue=:venue AND status=:status AND date=:date" );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getRequestsOnThisVenueBetweenTime( $venue, $date
    , $start_time, $end_time
    , $status = 'PENDING' )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM bookmyvenue_requests
        WHERE venue=:venue AND status=:status AND date=:date
        AND ( (start_time < :start_time AND end_time > :start_time )
              OR ( start_time < :end_time AND end_time > :end_time )
              OR ( start_time >= :start_time AND end_time <= :end_time )
            )
        " );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':start_time', $start_time );
    $stmt->bindValue( ':end_time', $end_time );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get number of entries of a given column.
    *
    * @param $tablename
    * @param $column
    *
    * @return
 */
function getNumberOfEntries( $tablename, $column = 'id' )
{
    global $hippoDB;
    $res = $hippoDB->query( "SELECT MAX($column) AS $column FROM $tablename" );
    return $res->fetch( PDO::FETCH_ASSOC );
}

function getUniqueFieldValue( $tablename, $column = 'id' )
{
    global $hippoDB;
    $res = $hippoDB->query( "SELECT MAX($column) AS $column FROM $tablename" );
    $res = $res->fetch( PDO::FETCH_ASSOC );
    return __get__( $res, $column , 0 );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get unique ID for a table.
    *
    * @Param $tablename
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getUniqueID( $tablename )
{
    $column = 'id';
    global $hippoDB;
    $res = $hippoDB->query( "SELECT MAX($column) AS $column FROM $tablename" );
    $res = $res->fetch( PDO::FETCH_ASSOC );
    return intval( __get__( $res, $column , 0  )) + 1;
}

/**
    * @brief Sunmit a request for review.
    *
    * @param $request
    *
    * @return  Group id of request.
 */
function submitRequest( $request )
{
    global $hippoDB;
    $collision = false;

    if( ! array_key_exists( 'user', $_SESSION ) )
    {
        echo printErrorSevere( "Error: I could not determine the name of user" );
        return false;
    }

    $request[ 'created_by' ] = $_SESSION[ 'user' ];
    $repeatPat = __get__( $request, 'repeat_pat', '' );

    if( strlen( $repeatPat ) > 0 )
        $days = repeatPatToDays( $repeatPat, $request[ 'date' ] );
    else
        $days = Array( $request['date'] );

    if( count( $days ) < 1 )
    {
        echo minionEmbarrassed( "I could not generate list of slots for you reuqest" );
        return false;
    }

    $rid = 0;
    $res = $hippoDB->query( 'SELECT MAX(gid) AS gid FROM bookmyvenue_requests' );
    $prevGid = $res->fetch( PDO::FETCH_ASSOC);
    $gid = intval( $prevGid['gid'] ) + 1;
    foreach( $days as $day )
    {
        $rid += 1;
        $request[ 'gid' ] = $gid;
        $request[ 'rid' ] = $rid;
        $request[ 'date' ] = $day;

        $collideWith = checkCollision( $request );
        $hide = 'rid,external_id,description,is_public_event,url,modified_by';
        if( $collideWith )
        {
            echo '<div style="font-size:x-small">';
            echo alertUser( 'Collision with following event/request' );
            foreach( $collideWith as $ev )
                echo arrayToTableHTML( $ev, 'events', $hide );
            echo '</div>';
            $collision = true;
            continue;
        }

        $request[ 'timestamp' ] = dbDateTime( 'now' );
        $res = insertIntoTable( 'bookmyvenue_requests'
            , 'gid,rid,external_id,created_by,venue,title,description' .
                ',date,start_time,end_time,timestamp,is_public_event,class'
            , $request
        );

        if( ! $res )
        {
            echo printWarning( "Could not submit request id $gid" );
            return 0;
        }

    }
    return $gid;
}


function increaseEventHostedByVenueByOne( $venueId )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( 'UPDATE venues SET total_events = total_events + 1 WHERE id=:id' );
    $stmt->bindValue( ':id', $venueId );
    $res = $stmt->execute( );
    return $res;
}

/**
    * @brief check for collision.
    *
    * @param $resques
    *
    * @return
 */
function checkCollision( $request )
{

    // Make sure this request is not clashing with another event or request.
    $events = getEventsOnThisVenueBetweenTime(
        $request[ 'venue' ] , $request[ 'date' ]
        , $request[ 'start_time' ], $request[ 'end_time' ]
        );
    $reqs = getRequestsOnThisVenueBetweenTime(
        $request[ 'venue' ] , $request[ 'date' ]
        , $request[ 'start_time' ], $request[ 'end_time' ]
        );

    $all = array();
    if( $events )
        $all = array_merge( $all, $events );

    if( $reqs )
        $all = array_merge( $all, $reqs );

    if( count( $all ) > 0 )
        return $all;

    return false;
}

/**
    * @brief Create a new event in dateabase. The group id and event id of event
    * is same as group id (gid) and rid of request which created it. If there is
    * alreay a event or request pending which collides with this request, REJECT
    * it.
    *
    * @param $gid
    * @param $rid
    *
    * @return
 */
function approveRequest( $gid, $rid )
{
    $request = getRequestById( $gid, $rid );
    global $hippoDB;

    $collideWith = checkCollision( $request );
    if( ! $collideWith )
    {
        echo alertUser( "Following request is colliding with another
            event or request. Rejecting it.." );
        echo arrayToTableHTML( $collideWith, 'request' );
        rejectRequest( $gid, $rid );
        return false;
    }

    $stmt = $hippoDB->prepare( 'INSERT INTO events (
        gid, eid, class, external_id, title, description, date, venue, start_time, end_time
        , created_by, last_modified_on
    ) VALUES (
        :gid, :eid, :class, :external_id, :title, :description, :date, :venue, :start_time, :end_time
        , :created_by, NOW()
    )');
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':eid', $rid );
    $stmt->bindValue( ':class', $request[ 'class' ] );
    $stmt->bindValue( ':external_id', $request[ 'external_id'] );
    $stmt->bindValue( ':title', $request['title'] );
    $stmt->bindValue( ':description', $request['description'] );
    $stmt->bindValue( ':date', $request['date'] );
    $stmt->bindValue( ':venue', $request['venue'] );
    $stmt->bindValue( ':start_time', $request['start_time'] );
    $stmt->bindValue( ':end_time', $request['end_time'] );
    $stmt->bindValue( ':created_by', $request['created_by'] );
    $res = $stmt->execute();
    if( $res )
    {
        changeRequestStatus( $gid, $rid, 'APPROVED' );
        // And update the count of number of events hosted by this venue.
        increaseEventHostedByVenueByOne( $request['venue'] );
        return true;
    }

    return false;
}

function rejectRequest( $gid, $rid )
{
    return changeRequestStatus( $gid, $rid, 'REJECTED' );
}


function actOnRequest( $gid, $rid, $status )
{
    if( $status == 'APPROVE' )
        approveRequest( $gid, $rid );
    elseif( $status == 'REJECT' )
        rejectRequest( $gid, $rid );
    else
        echo( printWarning( "unknown request " . $gid . '.' . $rid .
        " or status " . $status ) );
}

function changeIfEventIsPublic( $gid, $eid, $status )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "UPDATE events SET is_public_event=:status
        WHERE gid=:gid AND eid=:eid" );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':eid', $eid );
    return $stmt->execute();
}

// Fetch all events at given venue and given day-time.
function eventsAtThisVenue( $venue, $date, $time )
{
    $venue = trim( $venue );
    $date = trim( $date );
    $time = trim( $time );

    global $hippoDB;
    // Database reads in ISO format.
    $hDate = dbDate( $date );
    $clockT = date('H:i', $time );

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $hippoDB->prepare( 'SELECT * FROM events WHERE
        status=:status AND date=:date AND
        venue=:venue AND start_time <= :time AND end_time > :time' );
    $stmt->bindValue( ':date', $hDate );
    $stmt->bindValue( ':time', $clockT );
    $stmt->bindValue( ':venue', $venue );
    $stmt->bindValue( ':status', 'VALID' );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

// Fetch all requests for given venue and given day-time.
function requestsForThisVenue( $venue, $date, $time )
{
    $venue = trim( $venue );
    $date = trim( $date );
    $time = trim( $time );

    global $hippoDB;
    // Database reads in ISO format.
    $hDate = dbDate( $date );
    $clockT = date('H:i', $time );
    //echo "Looking for request at $venue on $hDate at $clockT ";

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $hippoDB->prepare( 'SELECT * FROM bookmyvenue_requests WHERE
        status=:status
        AND date=:date AND venue=:venue
        AND start_time <= :time AND end_time > :time'
    );
    $stmt->bindValue( ':status', 'PENDING' );
    $stmt->bindValue( ':date', $hDate );
    $stmt->bindValue( ':time', $clockT );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get all public events at this time.
    *
    * @param $date
    * @param $time
    *
    * @return
 */
function publicEvents( $date, $time )
{
    $date = trim( $date );
    $time = trim( $time );

    global $hippoDB;
    // Database reads in ISO format.
    $hDate = dbDate( $date );
    $clockT = date('H:i', $time );

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $hippoDB->prepare( 'SELECT * FROM events WHERE
        date=:date AND start_time <= :time AND end_time > :time' );
    $stmt->bindValue( ':date', $hDate );
    $stmt->bindValue( ':time', $clockT );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Update a group of requests. It can only modify fields which are set
    * editable in function.
    *
    * @param $gid
    * @param $options Any array as long as it contains fields with name in
    * editables.
    *
    * @return  On success True, else False.
 */
function updateRequestGroup( $gid, $options )
{
    global $hippoDB;
    $editable = Array( "title", "description", "is_public_event" );
    $fields = Array( );
    $placeholder = Array( );
    foreach( array_keys($options) as $key )
    {
        if( in_array( $key, $editable ) )
        {
            array_push( $fields, $key );
            array_push( $placeholder, "$key=:$key" );
        }
    }

    $placeholder = implode( ",", $placeholder );
    $query = "UPDATE bookmyvenue_requests SET $placeholder WHERE gid=:gid";

    $stmt = $hippoDB->prepare( $query );

    foreach( $fields as $f )
        $stmt->bindValue( ":$f", $options[ $f ] );

    $stmt->bindValue( ':gid', $gid );
    return $stmt->execute( );
}

function updateEventGroup( $gid, $options )
{
    $events = getEventsByGroupId( $gid );
    $results = Array( );
    foreach( $events as $event )
    {
        $res = updateEvent( $gid, $event['eid'], $options );
        $eid = $event[ 'eid' ];
        if( ! $res )
            echo printWarning( "I could not update sub-event $eid" );
        array_push( $results, $res );
    }
    return (! in_array( FALSE, $results ));

}

function updateEvent( $gid, $eid, $options )
{
    global $hippoDB;
    $editable = Array( "title", "description", "is_public_event"
        , "status", "class" );
    $fields = Array( );
    $placeholder = Array( );
    foreach( array_keys($options) as $key )
    {
        if( in_array( $key, $editable ) )
        {
            array_push( $fields, $key );
            array_push( $placeholder, "$key=:$key" );
        }
    }

    $placeholder = implode( ",", $placeholder );
    $query = "UPDATE events SET $placeholder WHERE gid=:gid AND eid=:eid";

    $stmt = $hippoDB->prepare( $query );

    foreach( $fields as $f )
        $stmt->bindValue( ":$f", $options[ $f ] );

    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':eid', $eid );
    return $stmt->execute( );
}

// Create user if does not exists and fill information form LDAP server.
function createUserOrUpdateLogin( $userid, $ldapInfo = Array() )
{
    global $hippoDB;

    if( ! $ldapInfo )
        $ldapInfo = @getUserInfoFromLdap( $userid );

    if( $ldapInfo[ 'last_name' ] == 'NA' )
        $ldapInfo[ 'last_name' ] = '';

    $stmt = $hippoDB->prepare(
       "INSERT IGNORE INTO logins
        (id, login, first_name, last_name, email, created_on, institute, laboffice)
            VALUES
            (:id, :login, :fname, :lname, :email,  NOW(), :institute, :laboffice)
        "
        );

    $institute = NULL;
    if( count( $ldapInfo ) > 0 )
        $institute = 'NCBS Bangalore';

    $stmt->bindValue( ':login', $userid );
    $stmt->bindValue( ':id', __get__( $ldapInfo, "uid", NULL ));
    $stmt->bindValue( ':fname', __get__( $ldapInfo, "first_name", NULL ));
    $stmt->bindValue( ':lname', __get__( $ldapInfo, "last_name", NULL ));
    $stmt->bindValue( ':email', __get__( $ldapInfo, 'email', NULL ));
    $stmt->bindValue( ':laboffice', __get__( $ldapInfo, 'laboffice', NULL ));
    $stmt->bindValue( ':institute', $institute );
    $stmt->execute( );

    $stmt = $hippoDB->prepare( "UPDATE logins SET last_login=NOW() WHERE login=:login" );
    $stmt->bindValue( ':login', $userid );
    return $stmt->execute( );
}

/**
    * @brief Get all logins.
    *
    * @return
 */
function getLogins( $status = ''  )
{
    global $hippoDB;
    $where = '';
    if( $status )
        $where = " WHERE status='$status' ";
    $query = "SELECT * FROM logins $where ORDER BY joined_on DESC";
    $stmt = $hippoDB->query( $query );
    $stmt->execute( );
    return  fetchEntries( $stmt );
}

function getLoginIds( )
{
    global $hippoDB;
    $stmt = $hippoDB->query( 'SELECT login FROM logins' );
    $stmt->execute( );
    $results =  fetchEntries( $stmt );
    $logins = Array();
    foreach( array_values($results) as $val )
        $logins[] = $val['login'];

    return $logins;
}

/**
    * @brief Get user info from database.
    *
    * @param $user Login id of user.
    *
    * @return Array.
 */
function getUserInfo( $loginOrEmail, $query_ldap = false )
{
    $user =  explode( '@', $loginOrEmail )[0];
    $res = getTableEntry( 'logins', 'login', array( 'login' => $user ) );

    $title = '';
    if( is_array($res) )
        $title = __get__( $res, 'title', '' );

    if( ! $res )
        $res = array( );

    // Fetch ldap as well.
    $ldap = null;
    if( $query_ldap )
        $ldap = getUserInfoFromLdap( $user );

    if( is_array($ldap) && is_array( $res ) && $ldap  )
    {
        foreach( $ldap as $key => $val )
            $res[ $key ] = $val;
    }

    // If title was found in database, overwrite ldap info.
    if( $title )
        $res[ 'title' ] = $title;

    if( !$res )
        $res = findAnyoneWithEmail( $loginOrEmail );

    return $res;
}

function getLoginInfo( $loginOrEmail, $query_ldap = false )
{
    return getUserInfo( $loginOrEmail, $query_ldap );
}

function getLoginByEmail( $email )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SELECT login FROM logins WHERE email=:email" );
    $stmt->bindValue( ":email", $email );
    $stmt->execute( );
    $res = $stmt->fetch( PDO::FETCH_ASSOC );
    if( $res )
        return $res['login'];
    return '';
}


function getLoginEmail( $login ) : string
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SELECT email FROM logins WHERE login=:login" );
    $stmt->bindValue( ":login", $login );
    $stmt->execute( );
    $res = $stmt->fetch( PDO::FETCH_ASSOC );

    if( ! $res )
        return '';

    if( strlen( trim($res[ 'email' ]) < 1 ) )
    {
        $info = @getUserInfoFromLdap( $login );
        if( $info && array_key_exists( 'email', $info) && $info['email'] )
        {
            // Update user in database.
            createUserOrUpdateLogin( $login, $info );
            $alternativeEmail = __get__( $info, 'alternative_email', '' );
            $res['email'] = __get__( $info, 'email', $alternativeEmail );
        }
    }
    return $res['email'];
}

function getRoles( $user )
{
    global $hippoDB;

    if( ! trim( $user ) )
        return array( );


    $stmt = $hippoDB->prepare( 'SELECT roles FROM logins WHERE login=:login' );
    $stmt->bindValue( ':login', $user );
    $stmt->execute( );
    $res = $stmt->fetch( PDO::FETCH_ASSOC );
    return explode( ",", $res['roles'] );
}

function getMyAws( $user )
{
    global $hippoDB;

    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC ";
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':speaker', $user );
    $stmt->execute( );
    return fetchEntries( $stmt );
}


function getMyAwsOn( $user, $date )
{
    global $hippoDB;

    $query = "SELECT * FROM annual_work_seminars
        WHERE speaker=:speaker AND date=:date ORDER BY date DESC ";
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':speaker', $user );
    $stmt->bindValue( ':date', $date );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getAwsById( $awsID )
{
    global $hippoDB;
    $query = "SELECT * FROM annual_work_seminars WHERE id=:id";
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':id', $awsID );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Return only recent most AWS given by this speaker.
    *
    * @param $speaker
    *
    * @return
 */
function getLastAwsOfSpeaker( $speaker )
{
    global $hippoDB;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC LIMIT 1";
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':speaker', $speaker );
    $stmt->execute( );
    # Only return the last one.
    return $stmt->fetch( PDO::FETCH_ASSOC );

}

/**
    * @brief Return all AWS given by this speaker.
    *
    * @param $speaker
    *
    * @return
 */
function getAwsOfSpeaker( $speaker )
{
    global $hippoDB;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC" ;
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':speaker', $speaker );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getSupervisors( )
{
    global $hippoDB;
    // First get all faculty members
    $faculty = getFaculty( 'ACTIVE' );

    // And then all supervisors.
    $stmt = $hippoDB->query( 'SELECT * FROM supervisors ORDER BY first_name' );
    $stmt->execute( );
    $supervisors = fetchEntries( $stmt );
    foreach( $supervisors as $super )
        array_push( $faculty, $super );
    return $faculty;
}


/**
    * @brief Find entry in database with given entry.
    *
    * @param $email
    *
    * @return
 */
function findAnyoneWithEmail( $email )
{
    $tables = array( "faculty", "speakers", "supervisors", "logins" );

    $res = array( );
    foreach( $tables as $table )
    {
        $res = getTableEntry( $table, 'email', array( 'email' => $email ) );
        if( $res )
            break;
    }
    return $res;
}


/**
    * @brief Generate a where expression.
    *
    * @param $keys
    * @param $data
    *
    * @return
 */
function whereExpr( $keys, $data )
{
    $whereExpr = array( );
    $keys = explode( ',', $keys );

    foreach( $keys as $k )
        $whereExpr[] = "$k='" . $data[ $k] . "'";

    return implode( ' AND ', $whereExpr );

}

/**
    * @brief
    *
    * @param $tablename
    * @param $orderby
    * @param $where
    *
    * @return
 */
function getTableEntries( $tablename, $orderby = '', $where = '' ) : array
{
    global $hippoDB;
    $query = "SELECT * FROM $tablename";

    if( is_string( $where) && strlen( $where ) > 0 )
        $query .= " WHERE $where ";

    if( strlen($orderby) > 0 )
        $query .= " ORDER BY $orderby ";

    $res = $hippoDB->query( $query );
    $entries = fetchEntries( $res );
    if( ! $entries )
        return array();

    return $entries;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get a single entry from table.
    *
    * @Param $tablename
    * @Param $whereKeys
    * @Param $data
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getTableEntry( $tablename, $whereKeys, $data ) : array
{
    global $hippoDB;
    if( is_string( $whereKeys ) )
        $whereKeys = explode( ",", $whereKeys );

    $where = array( );
    foreach( $whereKeys as $key )
        array_push( $where,  "$key=:$key" );

    $where = implode( " AND ", $where );

    $query = "SELECT * FROM $tablename WHERE $where";

    $stmt = $hippoDB->prepare( $query );

    foreach( $whereKeys as $key )
        $stmt->bindValue( ":$key", $data[ $key ] );

    try {
        $stmt->execute( );
        $res = $stmt->fetch( PDO::FETCH_ASSOC );
        if( $res )
            return $res;

    } catch (Exception $e) 
    {
        echo printWarning( "Failed to fetch. Error was " . $e->getMessage( ) );
        return array();
    }

    return array();
}


/**
    * @brief Insert a new entry in table.
    *
    * @param $tablename
    * @param $keys, Keys to update/insert in table.
    * @param $data
    *
    * @return  The id of newly inserted entry on success. Null otherwise.
 */
function insertIntoTable( $tablename, $keys, $data )
{
    global $hippoDB;

    if( is_string( $keys ) )
        $keys = explode( ',', $keys );

    $values = Array( );
    $cols = Array( );
    foreach( $keys as $k )
    {
        if( ! is_string( $k ) )
            continue;

        // If values for this key in $data is null then don't use it here.
        if( __get__( $data, $k, '' ) )
        {
            array_push( $cols, "$k" );
            array_push( $values, ":$k" );
        }
    }

    $keysT = implode( ",", $cols );
    $values = implode( ",", $values );

    $query = "INSERT INTO $tablename ( $keysT ) VALUES ( $values )";
    $stmt = $hippoDB->prepare( $query );

    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( is_array( $value ) )
            $value = implode( ',', $value );

        $stmt->bindValue( ":$k", $value );
    }

    try
    {
        $res = $stmt->execute( );
    }
    catch (Exception $e )
    {
        echo minionEmbarrassed(
            "I failed to update my database. Error was " . $e->getMessage( ) );
        return null;
    }


    if( $res )
    {
        // When created return the id of table else return null;
        $stmt = $hippoDB->query( "SELECT LAST_INSERT_ID() FROM $tablename" );
        $stmt->execute( );
        return $stmt->fetch( PDO::FETCH_ASSOC );
    }
    return null;
}

/**
    * @brief Insert an entry into table. On collision, update the table.
    *
    * @param $tablename
    * @param $keys
    * @param $updatekeys
    * @param $data
    *
    * @return The value of last updated row.
 */
function insertOrUpdateTable( $tablename, $keys, $updatekeys, $data )
{
    global $hippoDB;

    if( is_string( $keys ) )
        $keys = explode( ',', $keys );

    if( is_string( $updatekeys ) )
        $updatekeys = explode( ',', $updatekeys );

    $values = Array( );
    $cols = Array( );
    foreach( $keys as $k )
    {
        // If values for this key in $data is null then don't use it here.
        if( __get__($data, $k, '' ) )
        {
            array_push( $cols, "$k" );
            array_push( $values, ":$k" );
        }
    }

    $keysT = implode( ",", $cols );
    $values = implode( ",", $values );

    $updateExpr = '';
    if( count( $updatekeys ) > 0 )
    {
        $updateExpr .= ' ON DUPLICATE KEY UPDATE ';
        foreach( $updatekeys as $k )
            // Update only if the new value is not empty.
            if( strlen( $data[ $k ] ) > 0 )
            {
                $updateExpr .= "$k=:$k,";
                array_push( $cols, $k );
            }

        // Remove last ','
        $updateExpr = rtrim( $updateExpr, "," );
    }

    $query = "INSERT INTO $tablename ( $keysT ) VALUES ( $values ) $updateExpr";
    $stmt = $hippoDB->prepare( $query );
    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( is_array( $value ) )
            $value = implode( ',', $value );

        $stmt->bindValue( ":$k", $value );
    }

    $res = null;
    try
    {
        $res = $stmt->execute( );
    }
    catch ( PDOException $e )
    {
        echo minionEmbarrassed( "Failed to execute <pre> " . $query . "</pre>"
            , $e->getMessage( )
        );
    }

    // This is MYSQL specific. Only try this if table has an AUTO_INCREMENT
    // id field.
    if( array_key_exists( 'id', $data) && $res )
    {
        // When created return the id of table else return null;
        $stmt = $hippoDB->query( "SELECT LAST_INSERT_ID() FROM $tablename" );
        $stmt->execute( );
        $res = $stmt->fetch( PDO::FETCH_ASSOC );
        $lastInsertId = intval( __get__($res, 'LAST_INSERT_ID()', 0 ) );

        // Store the LAST_INSERT_ID if insertion happened else the id of update
        // execution.
        if( $lastInsertId > 0 )
            $res['id'] = $lastInsertId;
        else
            $res['id' ] = $data[ 'id' ];
        return $res;
    }
    return $res;
}

function getTableUniqueIndices( $tableName )
{
    $res = executeQuery( "SELECT DISTINCT CONSTRAINT_NAME
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE table_name = '$tableName' AND constraint_type = 'UNIQUE'"
    );
    return $res;
}

/**
    * @brief Delete an entry from table.
    *
    * @param $tableName
    * @param $keys
    * @param $data
    *
    * @return Status of execute statement.
 */
function deleteFromTable( $tablename, $keys, $data )
{
    global $hippoDB;

    if( gettype( $keys ) == "string" )
        $keys = explode( ',', $keys );

    $values = Array( );
    $cols = Array( );
    foreach( $keys as $k )
        if( $data[$k] )
        {
            array_push( $cols, "$k" );
            array_push( $values, ":$k" );
        }

    $values = implode( ",", $values );
    $query = "DELETE FROM $tablename WHERE ";

    $whereClause = array( );
    foreach( $cols as $k )
        array_push( $whereClause, "$k=:$k" );

    $query .= implode( " AND ", $whereClause );

    $stmt = $hippoDB->prepare( $query );
    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( gettype( $value ) == 'array' )
            $value = implode( ',', $value );
        $stmt->bindValue( ":$k", $value );
    }
    $res = $stmt->execute( );
    return $res;
}



/**
    * @brief A generic function to update a table.
    *
    * @param $tablename Name of table.
    * @param $wherekeys WHERE $wherekey=wherekeyval,... etc.
    * @param $keys Keys to be updated.
    * @param $data An array having all data.
    *
    * @return
 */
function updateTable( $tablename, $wherekeys, $keys, $data )
{
    global $hippoDB;
    $query = "UPDATE $tablename SET ";

    if( is_string( $wherekeys ) )
        $wherekeys = explode( ",", $wherekeys );
    if( is_string( $keys ) )
        $keys = explode(",",  $keys );

    $whereclause = array( );
    foreach( $wherekeys as $wkey )
        array_push( $whereclause, "$wkey=:$wkey" );

    $whereclause = implode( " AND ", $whereclause );

    $values = Array( );
    $cols = Array();
    foreach( $keys as $k )
    {
        // If values for this key in $data is null then don't use it here.
        if( ! __get__( $data, $k, false ) )
            $data[ $k ] = null;

        array_push( $cols, $k );
        array_push( $values, "$k=:$k" );
    }
    $values = implode( ",", $values );
    $query .= " $values WHERE $whereclause";

    $stmt = $hippoDB->prepare( $query );
    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( gettype( $value ) == 'array' )
            $value = implode( ',', $value );

        $stmt->bindValue( ":$k", $value );
    }

    foreach( $wherekeys as $wherekey )
        $stmt->bindValue( ":$wherekey", $data[$wherekey] );

    $res = $stmt->execute( );
    if( ! $res )
        echo printWarning( "<pre>Failed to execute $query </pre>" );
    return $res;
}


/**
    * @brief Get the AWS scheduled in future for this speaker.
    *
    * @param $speaker The speaker.
    *
    * @return  Array.
 */
function  scheduledAWSInFuture( $speaker )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM upcoming_aws WHERE
        speaker=:speaker AND date > NOW()
        " );
    $stmt->bindValue( ":speaker", $speaker );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Check if there is a temporary AWS schedule.
    *
    * @param $speaker
    *
    * @return
 */
function temporaryAwsSchedule( $speaker )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM aws_temp_schedule WHERE
        speaker=:speaker AND date > NOW()
        " );
    $stmt->bindValue( ":speaker", $speaker );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Fetch faculty from database. Order by last-name
    *
    * @param $status
    *
    * @return
 */
function getFaculty( $status = '', $order_by = 'first_name' )
{
    global $hippoDB;
    $query = 'SELECT * FROM faculty';
    $whereExpr = " WHERE affiliation != 'OTHER' ";
    if( $status )
        $query .= " $whereExpr AND status=:status ";
    else
        $query .= " $whereExpr AND status != 'INACTIVE' ";

    if( $order_by )
        $query .= " ORDER BY  '$order_by' ";

    $stmt = $hippoDB->prepare( $query );

    if( $status )
        $stmt->bindValue( ':status', $status );

    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get all pending requests for this user.
    *
    * @param $user Name of the user.
    * @param $status status of the request.
    *
    * @return
 */
function getAwsRequestsByUser( $user, $status = 'PENDING' )
{
    global $hippoDB;
    $query = "SELECT * FROM aws_requests WHERE status=:status AND speaker=:speaker";
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':speaker', $user );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getAwsRequestById( $id )
{
    global $hippoDB;
    $query = "SELECT * FROM aws_requests WHERE id=:id";
    $stmt = $hippoDB->prepare( $query );
    $stmt->bindValue( ':id', $id );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getPendingAWSRequests( )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM aws_requests WHERE status='PENDING'" );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getAllAWS( )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM annual_work_seminars ORDER BY date DESC"  );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Return AWS from last n years.
    *
    * @param $years
    *
    * @return  Array of events.
 */
function getAWSFromPast( $from  )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM annual_work_seminars
        WHERE date >= '$from' ORDER BY date DESC, speaker
    " );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function isEligibleForAWS( $speaker )
{
    $res = executeQuery( "SELECT login FROM logins WHERE login='$speaker' AND eligible_for_aws='YES' AND status='ACTIVE'" );
    if( ! $res )
        return false;

    if( count( $res ) == 0 )
        return false;

    return true;
}


/**
    * @brief Get AWS users.
    *
    * @return Array containing AWS speakers.
 */
function getAWSSpeakers( $sortby = '', $where_extra = '' )
{
    global $hippoDB;

    $sortExpr = '';
    if( $sortby )
        $sortExpr = " ORDER BY '$sortby'";

    $whereExpr = "status='ACTIVE' AND eligible_for_aws='YES'";
    if( $where_extra )
        $whereExpr .= " AND $where_extra";

    $stmt = $hippoDB->query( "SELECT * FROM logins WHERE $whereExpr $sortExpr " );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Return AWS entries schedules by my minion..
    *
    * @return
 */
function getTentativeAWSSchedule( $monday = null )
{
    global $hippoDB;

    $whereExpr = '';
    if( $monday )
    {
        $date = dbDate( $monday );
        $whereExpr = " WHERE date='$date' ";
    }
    $stmt = $hippoDB->query( "SELECT * FROM aws_temp_schedule $whereExpr ORDER BY date" );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get all upcoming AWSes. Closest to today first (Ascending date).
    *
    * @return Array of upcming AWS.
 */
function getUpcomingAWS( $monday = null )
{
    if( ! $monday )
    {
        $date = dbDate( 'this monday' );
        $whereExpr = "date >= '$date'";
    }
    else
    {
        $date = dbDate( $monday );
        $whereExpr = "date >= '$date'";
    }
    $res = executeQuery( "SELECT * FROM upcoming_aws WHERE $whereExpr ORDER BY date" );
    return $res;
}

function getUpcomingAWSOnThisMonday( $monday )
{
    $date = dbDate( $monday );
    $res = executeQuery( "SELECT * FROM upcoming_aws WHERE date='$date'" );
    return $res;
}

function getUpcomingAWSById( $id )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM upcoming_aws WHERE id = $id " );
    $stmt->execute( );
    return  $stmt->fetch( PDO::FETCH_ASSOC );
}

function getUpcomingAWSOfSpeaker( $speaker )
{
    return getTableEntry( 'upcoming_aws', 'speaker,status'
        , array( 'speaker'=> $speaker , 'status' => 'VALID' ) 
    );
}

/**
    * @brief Accept a auto generated schedule. We put the entry into table
    * upcoming_aws and delete this entry from aws_temp_schedule tables. In case
    * of any failure, leave everything untouched.
    *
    * @param $speaker
    * @param $date
    *
    * @return
 */
function acceptScheduleOfAWS( $speaker, $date )
{
    global $hippoDB;

    // If date is invalid, return.
    if( strtotime( $date ) < 0  or strtotime( $date ) < strtotime( '-7 day' ) )
        return 0;

    // If there is already a schedule for this person.
    $res = getTableEntry( 'upcoming_aws', 'speaker,date'
        , array( 'speaker' => $speaker, 'date' => dbDate( $date ) )
    );

    if( $res )
    {
        echo printInfo( "Already assigned for $speaker on $date" );
        return $res[ 'id' ];
    }

    // Else add to table.

    $hippoDB->beginTransaction( );

    $stmt = $hippoDB->prepare(
        'INSERT INTO upcoming_aws ( speaker, date ) VALUES ( :speaker, :date )'
    );

    $stmt->bindValue( ':speaker', $speaker );
    $stmt->bindValue( ':date', $date );

    $awsID = -1;
    try {

        $res = $stmt->execute( );
        // delete this row from temp table.
        $stmt = $hippoDB->prepare( 'DELETE FROM aws_temp_schedule WHERE
            speaker=:speaker AND date=:date
            ' );
        $stmt->bindValue( ':speaker', $speaker );
        $stmt->bindValue( ':date', $date );
        $res = $stmt->execute( );

        // If this happens, I must not commit the previous results into table.
        if( ! $res )
        {
            $hippoDB->rollBack( );
            return False;
        }

        // If successful add a query in queries to create a clickable query.
        $aws = getTableEntry( 'upcoming_aws', 'speaker,date'
            , array( 'speaker' => $speaker, 'date' => $date )
            );
        $awsID = $aws[ 'id' ];
        $clickableQ = "UPDATE upcoming_aws SET acknowledged='YES' WHERE id='$awsID'";
        insertClickableQuery( $speaker, "upcoming_aws.$awsID", $clickableQ );
    }
    catch (Exception $e)
    {
        $hippoDB->rollBack( );
        echo minionEmbarrassed(
            "Failed to insert $speaker, $date into database: " . $e->getMessage()
        );
        return False;
    }

    $hippoDB->commit( );
    return $awsID;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Insert a query which user can execute by clicking on URL.
    *
    * @Param $who_can_execute
    * @Param $external_id
    * @Param $query
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function insertClickableQuery( $who_can_execute, $external_id, $query )
{
    $data =  array(
        'query' => $query
        , 'external_id' => $external_id
        , 'who_can_execute' => $who_can_execute
        , 'last_modified_on' => dbDateTime( 'now' )
        , 'status' => 'PENDING'
        );

    $res = getTableEntry('queries', 'who_can_execute,query,external_id,status', $data );
    if( $res )
    {
        echo printInfo( "Clickable URL still unused." );
        return $res['id'];
    }

    $data['id'] = getUniqueID( 'queries' );
    $res = insertIntoTable( 'queries'
        , 'id,who_can_execute,external_id,query,last_modified_on,status'
        , $data
        );

    // Now fetch the query and return its ID. It may not be the ID which we have
    // generated above. The UNIQUE contraints may not allow creating a new
    // entry.
    return $data[ 'id' ];
}


/**
    * @brief Query AWS database of given query.
    *
    * @param $query
    *
    * @return  List of AWS with matching query.
 */
function queryAWS( $query )
{
    if( strlen( $query ) == 0 )
        return array( );

    if( strlen( $query ) < 3 )
    {
        echo printWarning( "Query is too small" );
        return array( );
    }

    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM annual_work_seminars
        WHERE LOWER(abstract) LIKE LOWER('%$query%')"
    );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Clear a given AWS from upcoming AWS list.
    *
    * @param $speaker
    * @param $date
    *
    * @return
 */
function clearUpcomingAWS( $speaker, $date )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        "DELETE FROM upcoming_aws WHERE speaker=:speaker AND date=:date"
    );

    $stmt->bindValue( ':speaker', $speaker );
    $stmt->bindValue( ':date', $date );
    return $stmt->execute( );
}

/**
    * @brief Delete an entry from annual_work_seminars table.
    *
    * @param $speaker
    * @param $date
    *
    * @return True, on success. False otherwise.
 */
function deleteAWSEntry( $speaker, $date )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare(
        "DELETE FROM annual_work_seminars WHERE speaker=:speaker AND date=:date"
    );
    $stmt->bindValue( ':speaker', $speaker );
    $stmt->bindValue( ':date', $date );
    return $stmt->execute( );
}

function getHolidays( $from = NULL )
{
    global $hippoDB;
    if( ! $from )
        $from = date( 'Y-m-d', strtotime( 'today' ) );
    $stmt = $hippoDB->query( "SELECT * FROM holidays WHERE date >= '$from' ORDER BY date" );
    return fetchEntries( $stmt );
}

/**
    * @brief Fetch all existing email templates.
    *
    * @return
 */
function getEmailTemplates( )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM email_templates" );
    return fetchEntries( $stmt );
}

function getEmailTemplateById( $id )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM email_templates where id='$id'" );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getEmailsByStatus( $status = 'PENDING' )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM emails where status = '$status'
        ORDER BY when_to_send DESC
        " );
    return fetchEntries( $stmt );
}

function getEmailById( $id )
{
    global $hippoDB;
    $stmt = $hippoDB->query( "SELECT * FROM emails where id = '$id'" );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getEmailByName( $name )
{
    $name = preg_replace( '#(Drs*|Mrs*|NA).?\s*#i', '', $name );
    if( ! $name )
        return '';

    $nameArr = explode( ' ', $name );
    $fname = $nameArr[0];
    $lname = $nameArr[ count($nameArr) - 1 ];
    $data = array( 'first_name' => $fname, 'last_name' => $lname );
    $res = getTableEntry( 'logins', 'first_name,last_name', $data );
    if( ! $res )
        $res = getTableEntry( 'faculty', 'first_name,last_name', $data );
    if( ! $res )
        $res = getTableEntry( 'logins', 'first_name', $data );
    if( ! $res )
        $res = getTableEntry( 'faculty', 'first_name', $data );

    if( ! $res )
        return '';

    return $res['email'];
}


function getUpcomingEmails( $from = null )
{
    global $hippoDB;
    if( ! $from )
        $from = dbDateTime( strtotime( 'today' ) );

    $stmt = $hippoDB->query( "SELECT *k FROM emails where when_to_send>='$from'" );
    return fetchEntries( $stmt );
}

function getSpeakers( )
{
    global $hippoDB;
    $res = $hippoDB->query( 'SELECT * FROM speakers' );
    return fetchEntries( $res );
}

/**
    * @brief Add a new talk.
    *
    * @param $data
    *
    * @return
 */
function addNewTalk( $data )
{
    global $hippoDB;
    // Get the max id
    $res = $hippoDB->query( 'SELECT MAX(id) AS id FROM talks' );
    $maxid = $res->fetch( PDO::FETCH_ASSOC);
    $id = intval( $maxid['id'] ) + 1;

    $data[ 'id' ] = $id;
    $res = insertIntoTable( 'talks'
        , 'id,host,class,coordinator,title,speaker,speaker_id,description,created_by,created_on'
        , $data );

    // Return the id of talk.
    if( $res )
        return array( "id" => $id );
    else
        return null;
}

/**
    * @brief Add or update the speaker and returns the id.
    *
    * @param $data
    *
    * @return
 */
function addOrUpdateSpeaker( $data )
{
    if( __get__( $data, 'id', 0 ) > 0 )
    {
        $speaker = getTableEntry( 'speakers', 'id', $data );
        if( $speaker )
        {
            $res = updateTable(
                'speakers', 'id'
                , 'honorific,email,first_name,middle_name,last_name'
                    . ',designation,department,institute,homepage'
                , $data
            );
            return getTableEntry( 'speakers', 'id', $speaker) ;
        }
    }

    // If we are here, then speaker is not found. Construct a new id.
    $id = getUniqueFieldValue( 'speakers', 'id' );
    $uid = intval( $id ) + 1;
    $data[ 'id' ] = $uid;
    $res = insertIntoTable( 'speakers'
        , 'id,email,honorific,first_name,middle_name,last_name,'
            . 'designation,department,institute,homepage'
        , $data
        );

    return getTableEntry( 'speakers', 'id', $data );
}


function getCourseName( $cexpr )
{
    $cid = explode( '-', $cexpr )[0];
    $c =  getTableEntry( 'courses_metadata', 'id', array( 'id' => $cid ) );
    return $c['name'];
}

function getSemesterCourses( $year, $sem )
{
    $sDate = dbDate( strtotime( "$year-01-01" ) );
    $eDate = dbDate( strtotime( "$year-07-31" ) );

    if( $sem == 'AUTUMN' )
    {
        $sDate = dbDate( strtotime( "$year-07-01" ) );
        $eDate = dbDate( strtotime( "$year-12-31" )  );
    }

    global $hippoDB;
    $res = $hippoDB->query( "SELECT * FROM courses WHERE
                    start_date >= '$sDate' AND end_date <= '$eDate' " );

    return fetchEntries( $res );
}

/**
    * @brief Get all the courses running this semester.
    *
    * @return
 */
function getRunningCourses( )
{
    $year = getCurrentYear( );
    $sem = getCurrentSemester( );
    return getSemesterCourses( $year, $sem );
}

function deleteBookings( $course )
{
    $bookedby = $course;

    // Make them invalid.
    $res = updateTable( 'events', 'created_by', 'status'
        , array( 'created_by' => $course, 'status' => 'INVALID' )
    );
    return $res;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis  Create events for given course.
 * The course id COURSE-SEM-YEAR is used a user for booking. When
 * deleting for course, we delete all events created by COURSE-SEM-YEAR
 *
 * @Param $runningCourseId Course Id of course.
 *
 * @Returns True if successful.
 */
/* ----------------------------------------------------------------------------*/
function addCourseBookings( $runningCourseId )
{
    // Fetch the course name.
    $course = getTableEntry( 'courses', 'id', array( 'id' => $runningCourseId ) );
    $cname = getCourseName( $course[ 'course_id' ] );

    $bookedby = $runningCourseId;

    $venue = $course[ 'venue' ];
    $title = "Course $cname";

    $tiles = getSlotTiles( $course[ 'slot' ] );
    $ignoreTiles = $course[ 'ignore_tiles' ];

    if( $ignoreTiles )
        $tiles = array_diff( $tiles, explode(',', $ignore_tiles) );

    $startDate = $course[ 'start_date' ];
    $endDate = $course[ 'end_date' ];

    // Select unique gid.
    $gid = intval( getUniqueFieldValue( 'bookmyvenue_requests', 'gid' ) ) + 1;

    $temp = $startDate;
    $rid = 0;
    while( strtotime($temp) <= strtotime( $endDate ) )
    {
        foreach( $tiles as $tile )
        {
            $rid += 1;
            $day = $tile[ 'day' ];
            $date = dbDate( strtotime( "this $day", strtotime( $temp ) ) );
            $startTime = $tile[ 'start_time' ];
            $endTime = $tile[ 'end_time' ];
            $msg = "$title at $venue on $date, $startTime, $endTime";

            $data = array(
                'gid' => $gid, 'rid' => $rid
                , 'date' => dbDate( $date )
                , 'start_time' => $startTime
                , 'end_time' => $endTime
                , 'venue' => $venue
                , 'title' => $title
                , 'class' => 'CLASS'
                , 'description' => 'AUTO BOOKED BY Hippo'
                , 'created_by' => $bookedby
                , 'last_modified_on' => dbDateTime( 'now' )
            );

            // Check if there is already an event here. If yes, notify the user.
            $events = getEventsOnThisVenueBetweenTime( $venue, $date, $startTime, $endTime );
            $reqs = getRequestsOnThisVenueBetweenTime( $venue, $date, $startTime, $endTime );

            foreach( $events as $ev )
            {
                echo arrayToTableHTML( $ev, 'event' );
                // Remove all of them.
                cancelEventAndNotifyBookingParty( $ev );
            }

            foreach( $reqs as $req )
            {
                echo arrayToTableHTML( $req, 'event' );
                cancelRequesttAndNotifyBookingParty( $req );
            }

            // Create request and approve it. Direct entry in event is
            // prohibited because then gid and eid are not synched.
            $res = insertIntoTable( 'bookmyvenue_requests', array_keys( $data ), $data );
            $res = approveRequest( $gid, $rid );
            if( ! $res )
                echo printWarning( "Could not book: $msg" );
        }

        // get the next week now.
        $temp = dbDate(  strtotime( '+1 week', strtotime($temp) ));
    }
    return true;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Update the booking for this course.
    *
    * @Param $course
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function updateBookings( $course )
{
    deleteBookings( $course );
    $res = addCourseBookings( $course );
    return $res;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get all the registrations beloning to course.
    *
    * @Param $cid Course ID.
    *
    * @Returns  Array containing registrations.
 */
/* ----------------------------------------------------------------------------*/
function getCourseRegistrations( string $cid, int $year, string $semester ) : array
{
    return getTableEntries( 'course_registration'
        , 'student_id'
        , "status='VALID' AND type != 'DROPPED' AND course_id='$cid' AND year='$year' AND semester='$semester'"
    );
}

function getMyCourses( $sem, $year, $user  ) : array
{
    $whereExpr = "status='VALID' AND semester='$sem' AND year='$year' AND student_id='$user'";
    $courses = getTableEntries( 'course_registration', 'course_id', $whereExpr );
    return array_filter( $courses, function( $x ) { return strlen( $x['course_id'] ) > 0; } );
}

/**
    * @brief Get all active recurrent events from today.
    *
    * @param $day
    *
    * @return
 */
function getActiveRecurrentEvents( $day )
{
    global $hippoDB;

    $from = dbDate( $day );

    // We get gid of events which are still valid.
    $res = $hippoDB->query( "SELECT gid FROM events WHERE
                date >= '$from' AND status='VALID' ORDER BY date"
            );
    $gids = fetchEntries( $res );

    $upcomingRecurrentEvents = array( );
    foreach( $gids as $gid )
    {
        $gid = $gid[ 'gid' ];

        // Must order by date.
        $gEvents = getTableEntries( 'events', 'date', "gid='$gid'" );

        // Definately there has to be more than 1 event in group to be qualified
        // as group event.
        if( count( $gEvents ) > 1 )
            $upcomingRecurrentEvents[ $gid ] = $gEvents;
    }

    return $upcomingRecurrentEvents;
}

/**
    * @brief Get login from logins table when name is given.
    *
    * @param $name
    *
    * @return
 */
function getLoginByName( $name )
{
    global $hippoDB;
    $name = explode( ' ', $name );
    $fname = $name[ 0 ];
    $lname = end( $name );
    $res = $hippoDB->query( "SELECT * FROM logins WHERE
        first_name='$fname' AND last_name='$lname'" );
    return $res->fetch( PDO::FETCH_ASSOC );
}

function getSpeakerByName( $name )
{
    global $hippoDB;

    $name = splitName( $name );
    $fname = $name[ 'first_name' ];
    $mname = $name[ 'middle_name' ];
    $lname = $name[ 'last_name' ];

    // WHERE condition.
    $where = array( "first_name='$fname'" );
    if( $lname )
        $where[] =  "last_name='$lname'";

    if( $mname )
        $where[] = "middle_name='$mname'";
    $whereExpr = implode( ' AND ', $where );

    $res = $hippoDB->query( "SELECT * FROM speakers WHERE $whereExpr " );
    return $res->fetch( PDO::FETCH_ASSOC );
}

function getSpeakerByID( $id )
{
    return getTableEntry( 'speakers', 'id', array( 'id' => $id ) );
}

function getWeeklyEventByClass( $classes )
{
    global $hippoDB;

    $classes = explode( ',', $classes );
    $where = array( );
    foreach( $classes as $cls )
        $whereExp[ ] = "class='$cls'";

    $whereExp = implode( ' OR ', $whereExp );

    $today = dbDate( 'today' );
    $query = "SELECT * FROM events WHERE
                ( $whereExp ) AND status='VALID' AND date > '$today' GROUP BY gid";
    $res = $hippoDB->query( $query );
    $entries = fetchEntries( $res );

    // Add which day these events happening.
    $result = array( );
    foreach( $entries as $entry )
    {
        $entry[ 'day' ] = date( 'D', strtotime( $entry[ 'date' ] ) );
        $result[] = $entry;
    }
    return $result;
}


function getLabmeetAndJC( )
{
    return getWeeklyEventByClass( 'JOURNAL CLUB MEETING,LAB MEETING' );
}

/**
    * @brief Is there a labmeet or JC on given slot/venue.
    *
    * @param $date
    * @param $starttime
    * @param $endtime
    * @param $entries
    *
    * @return
 */
function isThereAClashOnThisVenueSlot( $day, $starttime, $endtime, $venue, $entries )
{
    $clashes = clashesOnThisVenueSlot( $day, $starttime, $endtime, $venue, $entries );
    if( count( $clashes ) > 0 )
        return true;
    return false;
}

function clashesOnThisVenueSlot( $day, $starttime, $endtime, $venue, $entries )
{
    $days = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );

    if( ! in_array( $day, $days ) )
        $day = date( 'D', strtotime( $day ) );

    $clashes = array( );
    foreach( $entries as $entry )
    {
        if( $entry['day'] == $day )
        {
            if( strlen($venue) == 0 || $entry[ 'venue' ] == $venue )
            {
                $s1 = $entry[ 'start_time' ];
                $e1 = $entry[ 'end_time' ];
                if( isOverlappingTimeInterval( $starttime, $endtime, $s1, $e1 ) )
                    $clashes[ ] = $entry;
            }
        }
    }
    return $clashes;
}



function labmeetOrJCOnThisVenueSlot( $day, $starttime, $endtime, $venue, $entries = null )
{
    if( ! $entries )
        $entries = getLabmeetAndJC( );
    return clashesOnThisVenueSlot( $day, $starttime, $endtime, $venue, $entries );
}

function getOccupiedSlots( $year = null, $sem = null )
{
    if( ! $year )
        $year = getCurrentYear( );
    if( ! $sem )
        $sem = getCurrentSemester( );

    $res = $hippoDB->query(
        "SELECT slot FROM courses WHERE year='$year' AND semester='$sem'"
    );

    $slots = array_map(
        function($x) { return $x['slot']; }
        , fetchEntries( $res, PDO::FETCH_ASSOC )
        );

    return $slots;
}

function getRunningCoursesOnThisVenue( $venue, $date )
{
    global $hippoDB;

    $year = getYear( $date );
    $sem = getSemester( $date );
    $courses = getTableEntries( 'courses', 'id'
        , " ( end_date >= '$date' AND start_date <= '$date' )"
        . " AND venue='$venue' "
    );

    return $courses;
}

function getRunningCoursesOnTheseSlotTiles( $date, $tile )
{
    global $hippoDB;

    $year = getCurrentYear( );
    $sem = getCurrentSemester(  );
    $date = dbDate( $date );

    // Slot is integer value.
    $slot = getSlotIdOfTile( $tile );

    $courses = getTableEntries( 'courses', 'id'
        , " ( end_date >= '$date' AND start_date <= '$date' )"
        . " AND slot='$slot' "
    );

    return $courses;
}

/**
    * @brief This function returns running courses on this day, venue, and slot.
    *
    * @param $venue
    * @param $date
    * @param $startTime
    * @param $endTime
    *
    * @return
 */
function runningCoursesOnThisVenueSlot( $venue, $date, $startTime, $endTime )
{

    $courses = getRunningCoursesOnThisVenue( $venue, $date );

    $day = date( 'D', strtotime($date) );

    if( ! $courses )
        return null;

    // Check if any of these courses slot is clasing with booking.
    $clashes = array( );
    foreach( $courses as $course )
    {
        $slotId = $course[ 'slot' ];
        $slots = getTableEntries( 'slots', 'groupid', "groupid='$slotId'" );
        foreach( $slots as $sl )
        {
            // If this slot is on on the same day as of booking request, ignore
            // the course.
            if( strcasecmp( $sl[ 'day' ], $day ) !== 0 )
                continue;

            $st = $sl[ 'start_time' ];
            $et = $sl[ 'end_time' ];

            if( isOverlappingTimeInterval( $startTime, $endTime, $st, $et ))
                $clashes[ $course[ 'id' ] ] = $course;
        }

    }

    if( count( $clashes ) > 0 )
        return $clashes;
    return null;
}

function getSlotInfo( $id, $ignore = '' )
{
    global $hippoDB;

    $ignore = str_replace( ' ', ',', $ignore );
    $ignoreTiles = explode( ',', $ignore );

    $slots = getTableEntries( 'slots', 'id', "groupid='$id'" );
    $res = array( );
    foreach( $slots as $sl )
    {
        // This slot is in ignore tile list i.e. a course is not using its slot
        // fully.
        if( in_array( $sl['id'], $ignoreTiles ) )
            continue;

        $res[ ] = $sl[ 'day' ] . ' ' . dbTime( $sl[ 'start_time' ] ) . '-'
            . dbTime( $sl[ 'end_time' ] );
    }
    return  implode( ', ', $res );
}


/**
    * @brief Get the slot of given slot.
    *
    * @param $cid
    *
    * @return
 */
function getCourseSlot( $cid )
{
    global $hippoDB;
    $slot = getTableEntry( 'courses', 'slot', "course_id='$cid'" );
    return $slots[ 'slot' ];
}


function getCourseById( $cid )
{
    $c =  getTableEntry( 'courses_metadata', 'id', array( 'id' => $cid ) );
    return $c;
}


/**
    * @brief Check if registration for courses is open.
    *
    * @return
 */
function isRegistrationOpen( )
{
    $res = getTableEntry( 'conditional_tasks', 'id', array( 'id' => 'COURSE_REGISTRATION' ) );
    if( strtotime( $res[ 'end_date' ] ) >= strtotime( 'today' ) )
        return true;

    return false;

}

function getSlotTiles( $id )
{
   $tiles = getTableEntries( 'slots', 'groupid', "groupid='$id'" );
   $result = array( );

   foreach( $tiles as $tile )
       $result[ $tile[ 'id' ] ] = $tile;

   return $result;
}

/**
    * @brief Is a course running on given tile e.g. 7A, 7B etc.
    *
    * @param $course
    * @param $tile
    *
    * @return
 */
function isCourseRunningOnThisTile( $course, $tile )
{
    if( strpos( $course['ignore_tiles'], $tile ) !== 0 )
        return true;
    return false;
}

function getCourseSlotTiles( $course )
{
   $sid = $course[ 'slot' ];
   $tiles = getSlotTiles( $sid );
   $result = array( );
   foreach( $tiles as $id => $tile )
       if( isCourseRunningOnThisTile( $course, $id ) )
           $result[ ] = $id;

   return implode( ",", $result );
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  So far how many CLASS events have happened.
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function totalClassEvents( )
{
    $courses = getTableEntries( 'courses' );
    $numEvents = 0;
    foreach( $courses as $c )
    {
        $startDate = strtotime( $c[ 'start_date' ] );
        $endDate = min( strtotime( 'now' ), strtotime( $c['end_date' ] ) );
        $slots = $c[ 'slot' ];
        $nTiles = count( getSlotTiles( $slots ) );
        $nWeeks = intval( max(0,$endDate - $startDate) / (24*3600*7.0) );

        // For each week, add this many events.
        $numEvents += $nWeeks * $nTiles;
    }

    return $numEvents;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get the Type of column from mysql tables.
    *
    * @Param $tablename
    * @Param $columnname
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getTableColumnTypes( $tableName, $columnName )
{
    global $hippoDB;
    $stmt = $hippoDB->prepare( "SHOW COLUMNS FROM $tableName LIKE '$columnName'" );
    $stmt->execute( );
    $column = $stmt->fetch( PDO::FETCH_ASSOC );
    $type = $column[ "Type" ];

    $res = array( );
    if( preg_match( "/^(enum|set)\((.*)\)$/" , $type, $match ) )
    {
        foreach( explode(",", $match[2] ) as $v )
        {
            $v = str_replace( "'", "", $v );
            $res[] = $v;
        }
    }
    else
        $res[] = $type;

    return $res;

}

function getPIOrHost( $login )
{
    // A. Search in table logins.
    global $hippoDB;
    $row = getTableEntry( 'logins', "login", array( 'login' => $login ) );
    if( __get__($row, 'pi_or_host', '' ) )
        return $row[ 'pi_or_host' ];

    // B. Search in previous AWS databases.
    $awses = getMyAws( $login );
    if( count( $awses ) > 0 )
    {
        $mostRecentAWS = $awses[0];
        $piOrHost = $mostRecentAWS[ 'supervisor_1'];
        if( $piOrHost )
        {
            // Update PI or HOST table.
            updateTable( 'logins', 'login', 'pi_or_host'
                , array( 'login' => $login, 'pi_or_host' => $piOrHost )
            );
        }
        return $mostRecentAWS[ 'supervisor_1'];
    }

    return '';
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find all courses running on given venue/slot and between given
    * dates.
    *
    * @Param $venue
    * @Param $slot
    * @Param $start
    * @Param $end
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getCoursesAtThisVenueSlotBetweenDates( $venue, $slot, $start, $end )
{
    $whereExpr = "( end_date > '$start' AND start_date < '$end' )
                    AND slot='$slot' AND venue='$venue'";
    $courses = getTableEntries( 'courses', 'start_date' , $whereExpr );
    return $courses;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get the specialization available for student.
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getAllSpecialization( )
{
    global $hippoDB;
    $res = $hippoDB->query( 'SELECT DISTINCT(specialization) FROM faculty' );
    return fetchEntries( $res );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get specialization of given login.
    *
    * @Param $speaker (usually student, could be faculty as well).
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getLoginSpecialization( $login )
{
    global $hippoDB;
    $res = $hippoDB->query( "SELECT specialization FROM logins WHERE login='$login'");
    $res = $res->fetch( PDO::FETCH_ASSOC );
    return trim( $res[ 'specialization' ] );
}

function getFacultySpecialization( $email )
{
    global $hippoDB;
    $res = $hippoDB->query( "SELECT specialization FROM faculty WHERE email='$email'");
    $res = $res->fetch( PDO::FETCH_ASSOC );
    return trim( $res[ 'specialization' ] );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get login specialization, if not found, fetch the PIEmail
    * specialization from faculty database.
    *
    * @Param $login
    * @Param $PIEmail
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getSpecialization( $login, $PIEmail = '' )
{
    $specialization = getLoginSpecialization( $login );
    if( ! $specialization )
        if( $PIEmail )
            $specialization = getFacultySpecialization( $PIEmail );

    if( ! trim( $specialization ) )
        $specialization = 'UNSPECIFIED';

    return $specialization;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Generate slot map.
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getSlotMap( $slots = array( ) )
{
    if( ! $slots )
        $slots = getTableEntries( 'slots', 'groupid' );

    $slotMap = array();
    foreach( $slots as $s )
    {
        if( intval($s[ 'groupid' ]) == 0 )
            continue;

        $slotGroupId = $s[ 'groupid' ];
        if( ! array_key_exists( $slotGroupId, $slotMap ) )
            $slotMap[ $slotGroupId ] = $slotGroupId .  ' (' . $s['day'] . ':'
            . humanReadableTime( $s[ 'start_time' ] )
            . '-' . humanReadableTime( $s['end_time'] )
            . ')';
        else
            $slotMap[ $slotGroupId ] .= ' (' . $s['day'] . ':'
            . humanReadableTime( $s[ 'start_time' ] )
            . '-' . humanReadableTime( $s['end_time'] )
            . ')';
    }
    return $slotMap;
}

/////////////////////////////////////////////////////////////////////////////////
// JOURNAL CLUBS
//
///////////////////////////////////////////////////////////////////////////////

function getJCAdmins( $jc_id )
{
    return getTableEntries(
        'jc_subscriptions', 'login'
        , "jc_id='$jc_id' AND subscription_type='ADMIN'"
    );
}

function getJournalClubs( $status = 'ACTIVE' )
{
    return getTableEntries( 'journal_clubs', 'id', "status='$status'" );
}

function isSubscribedToJC( $login, $jc_id )
{
    $res = getTableEntry( 'jc_subscriptions'
            , 'login,jc_id,status'
            , array( 'login' => $login, 'jc_id' => $jc_id, 'status' => 'VALID' )
    );

    if( $res )
        return true;

   return false;
}

function getJCInfo( $jc )
{
    if( is_array( $jc ) )
        $jc_id = __get__( $jc, 'jc_id', $jc['id'] );
    else if( is_string( $jc ) )
        $jc_id = $jc;

    return getTableEntry( 'journal_clubs', 'id', array( 'id' => $jc_id ));
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Return the list of JC user is subscribed to.
    *
    * @Param $login
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getUserJCs( $login )
{
    if( ! $login )
        return array( );

    return getTableEntries( 'jc_subscriptions', 'login'
        , "login='$login' AND status='VALID' " );
}

function getMyJCs( )
{
    return getUserJCs( $_SESSION[ 'user' ] );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get JC presentations for given Journal Club for given day.
    *
    * @Param $jcID
    * @Param $date
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getUpcomingJCPresentations( $jcID = '', $date = 'today' )
{
    $date = dbDate( $date );

    $whereExpr = "date >= '$date'";
    if( $jcID )
        $whereExpr .= " AND jc_id='$jcID' ";

    $whereExpr .= " AND status='VALID' AND CHAR_LENGTH(presenter) > 1";
    $jcs = getTableEntries( 'jc_presentations' , 'date', $whereExpr );
    return $jcs;
}

function getUpcomingJCPresentationsOfUser( $presenter, $jcID, $date = 'today' )
{
    $date = dbDate( $date );
    return getTableEntries( 'jc_presentations'
        , 'date'
        , "date >= '$date' AND presenter='$presenter'
            AND jc_id='$jcID' AND status='VALID' "
    );
}

function getUpcomingPresentationsOfUser( $presenter, $date = 'today' )
{
    $date = dbDate( $date );
    return getTableEntries( 'jc_presentations'
        , 'date'
        , "date >= '$date' AND presenter='$presenter' AND status='VALID' "
    );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get JC presentations.
    *
    * @Param $jc
    * @Param $user
    * @Param $date
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getJCPresentation( $jc, $presenter = '', $date = 'today' )
{
    $date = dbDate( $date );
    $keys = 'jc_id,date';

    if( $presenter )
        $keys .= ',presenter';

    return getTableEntry( 'jc_presentations', $keys
        , array( 'jc_id' => $jc, 'presenter' => $presenter, 'date' => $date )
    );
}

function getJCPresentations( $jc, $date = '', $presenter = '' )
{
    $whereExpr = "status='VALID' AND jc_id='$jc' ";
    if( $date )
    {
        $date = dbDate( $date );
        $whereExpr .= " AND date='$date' ";
    }

    if( $presenter )
        $whereExpr .= " AND presenter='$presenter' ";

    return getTableEntries( 'jc_presentations', 'date', $whereExpr );

}


function isJCAdmin( $user )
{
    $res = getTableEntry( 'jc_subscriptions', 'login,subscription_type'
        , array( 'login' => $user, 'subscription_type' => 'ADMIN' )
    );
    if( $res )
        return true;
    return false;
}

function getJCForWhichUserIsAdmin( $user )
{
    return getTableEntries( 'jc_subscriptions', 'jc_id'
        , "login='$user' AND subscription_type='ADMIN' AND status='VALID'"
    );
}

function getJCSubscriptions( $jc_id )
{
    return getTableEntries( 'jc_subscriptions', 'login'
        , "jc_id='$jc_id' AND status='VALID'" );

}

function getAllPresentationsBefore( $date, $presenter = '' )
{
    $date = dbDate( $date );
    $whereExpr = " status='VALID' AND date <= '$date' ";
    if( $presenter )
        $whereExpr .= " AND presenter='$presenter' ";

    return getTableEntries( 'jc_presentations', 'date', $whereExpr );
}

function getAllAdminsOfJC( $jc_id )
{
    $admins = getTableEntries( 'jc_subscriptions', 'login'
        , "status='VALID' AND subscription_type='ADMIN' AND jc_id='$jc_id'"
    );

    $res = array( );
    foreach( $admins as $admin )
        $res[ $admin['login'] ] = loginToHTML( $admin['login'] );

    return $res;
}

function getUserVote( $voteId, $voter )
{
    $res = getTableEntry( 'votes', 'id,voter,status'
        , array( 'id' => $voteId, 'voter' => $voter, 'status' => 'VALID' )
    );
    return $res;
}

function getMyVote( $voteId )
{
    return getUserVote( $voteId, whoAmI( ) );
}

function getVotes( $voteId )
{
    return getTableEntries('votes', '', "id='$voteId' AND status='VALID'" );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get the config parameters from database.
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getConfigFromDB( )
{
    $config = array( );
    foreach( getTableEntries( 'config' ) as $row )
        $config[ $row['id'] ] = $row[ 'value' ];
    return $config;
}

function getConfigValue( $key, $config = null )
{
    if( ! $config )
        $config = getConfigFromDB( );
    $val = __get__( $config, $key, '' );
    return $val;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get a clickbale url for a query.
    *
    * @Param $idOrExternalId
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getQueryWithIdOrExtId( $idOrExternalId )
{
    $res = executeQuery(
        "SELECT  * FROM queries WHERE
        (id='$idOrExternalId' OR external_id='$idOrExternalId')
            AND status='PENDING'"
        );

    if( ! $res )
        return -1;

    return intval( $res[0]['id'] );
}

function getActiveJCs( )
{
    return getTableEntries( 'journal_clubs', 'id', "status='ACTIVE'" );
}

function pickPresenter( $jcID, $picker = 'random', $gap_between_presentations_in_months = 6 )
{
    $logins = getJCSubscriptions( $jcID );

    $suitable = array( );
    foreach( $logins as $login )
    {
        $presenter = $login[ 'login' ];

        if( ! $presenter )
            continue;

        $onOrBefore = strtotime( 'now' ) + $gap_between_presentations_in_months * 30 * 24 * 3600;

        // Get presentations of this USER in lats
        // gap_between_presentations_in_months months. It does not matter in
        // which JC she has given presentations.
        $presentations = getAllPresentationsBefore( $onOrBefore, $presenter );
        if( count( $presentations )  > 0 )
            continue;

        $upcoming = getUpcomingJCPresentationsOfUser(  $jcID, $presenter );
        if( $upcoming )
        {
            echo printInfo( "user $presenter has upcoming JC" );
            continue;
        }

        $suitable[] = $presenter;
        if( $picker == 'round_robin' )
            return $presenter;
    }

    // Else return a random sample.
    return $suitable[ mt_rand(0, count($suitable) - 1) ];
}


?>
