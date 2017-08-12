<!-- <script src="http://code.highcharts.com/highcharts.js"></script> -->
<script src="./node_modules/highcharts/highcharts.js"></script>
<?php

include_once 'header.php';
include_once 'database.php';


$upto = dbDate( 'tomorrow' );
$requests = getTableEntries( 'bookmyvenue_requests', 'date'
                , "date >= '2017-02-28' AND date <= '$upto'" );
$nApproved = 0;
$nRejected = 0;
$nCancelled = 0;
$nPending = 0;
$nOther = 0;
$timeForAction = array( );

$firstDate = $requests[0]['date'];
$lastDate = end( $requests )['date'];
$timeInterval = strtotime( $lastDate ) - strtotime( $firstDate );

foreach( $requests as $r )
{
    if( $r[ 'status' ] == 'PENDING' )
        $nPending += 1;

    else if( $r[ 'status' ] == 'APPROVED' )
        $nApproved += 1;

    else if( $r[ 'status' ] == 'REJECTED' )
        $nRejected += 1;

    else if( $r[ 'status' ] == 'CANCELLED' )
        $nCancelled += 1;
    else 
        $nOther += 1;

    // Time take to approve a request, in hours
    if( $r[ 'last_modified_on' ] )
    {
        $time = strtotime( $r['date'] . ' ' . $r[ 'start_time' ] ) 
                    - strtotime( $r['last_modified_on'] );
        $time = $time / (24 * 3600.0);
        array_push( $timeForAction, array($time, 1) ); 
    }
}

// rate per day.
$rateOfRequests = 24 * 3600.0 * count( $requests ) / (1.0 * $timeInterval);

/*
 * Venue usage timne.
 */
$events = getTableEntries( 'events', 'date'
                , "status='VALID' AND date >= '2017-02-28' AND date < '$upto'" );

$venueUsageTime = array( );
// How many events, as per class.
$eventsByClass = array( );

foreach( $events as $e )
{
    $time = (strtotime( $e[ 'end_time' ] ) - strtotime( $e[ 'start_time' ] ) ) / 3600.0;
    $venue = $e[ 'venue' ];

    $venueUsageTime[ $venue ] = __get__( $venueUsageTime, $venue, 0.0 ) + $time;
    $eventsByClass[ $e[ 'class' ] ] = __get__( $eventsByClass, $e['class'], 0 )
                                            + 1;
}

$venues = array_keys( $venueUsageTime );
$venueUsage = array_values( $venueUsageTime );

$bookingTable = "<table border='1'>
    <tr> <td>Total booking requests</td> <td>" . count( $requests ) . "</td> </tr>
    <tr> <td>Rate of booking (# per day)</td> <td>" 
            .   number_format( $rateOfRequests, 2 ) . "</td> </tr>
    <tr> <td>Approved requests</td> <td> $nApproved </td> </tr>
    <tr> <td>Rejected requests</td> <td> $nRejected </td> </tr>
    <tr> <td>Pending requests</td> <td> $nPending </td> </tr>
    <tr> <td>Cancelled by user</td> <td> $nCancelled </td> </tr>
    </table>";

$thesisSeminars = getTableEntries( 'talks', 'class', "class='THESIS SEMINAR'" );
$thesisSemPerYear = array( );
$thesisSemPerMonth = array( );

for( $i = 1; $i <= 12; $i ++ )
    $thesisSemPerMonth[ date( 'F', strtotime( "2000/$i/01" ) )] = 0;

foreach( $thesisSeminars as $ts )
{
    // Get event of this seminar.
    $event = getEventsOfTalkId( $ts[ 'id' ] );

    $year = intval( date( 'Y', strtotime( $event['date'] )  ));
    $month = date( 'F', strtotime( $event['date'] ) );


    if( $year > 2000 )
        $thesisSemPerYear[ $year ] = __get__( $thesisSemPerYear, $year, 0 ) + 1;

    $thesisSemPerMonth[ $month ] += 1;

}
?>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var venueUsage = <?php echo json_encode( $venueUsage ); ?>;
    var venues = <?php echo json_encode( $venues ); ?>;

    Highcharts.chart('venues_plot', {

        chart : { type : 'column' },
        title: { text: 'Venue usage in hours' },
        yAxis: { title: { text: 'Time in hours' } },
        xAxis : { categories : venues }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Venue usage', data: venueUsage, }], 
    });

});
</script>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var eventsByClass = <?php echo json_encode( array_values( $eventsByClass) ); ?>;
    var cls = <?php echo json_encode( array_keys( $eventsByClass) ); ?>;

    Highcharts.chart('events_class', {

        chart : { type : 'column' },
        title: { text: 'Event distribution by categories' },
        yAxis: { title: { text: 'Number of events' } },
        xAxis : { categories : cls }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Total events by class', data: eventsByClass, }], 
    });

});

</script>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var thesisSemPerMonth = <?php echo json_encode( array_values( $thesisSemPerMonth) ); ?>;
    var cls = <?php echo json_encode( array_keys( $thesisSemPerMonth) ); ?>;

    Highcharts.chart('thesis_seminar_per_month', {

        chart : { type : 'column' },
        title: { text: 'Thesis Seminar distributuion (monthly)' },
        yAxis: { title: { text: 'Number of events' } },
        xAxis : { categories : cls }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Total events by class', data: thesisSemPerMonth, }], 
    });

});
</script>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var thesisSemPerYear = <?php echo json_encode( array_values( $thesisSemPerYear) ); ?>;
    var cls = <?php echo json_encode( array_keys( $thesisSemPerYear) ); ?>;

    Highcharts.chart('thesis_seminar_per_year', {

        chart : { type : 'column' },
        title: { text: 'Thesis Seminar distributuion (yearly)' },
        yAxis: { title: { text: 'Number of events' } },
        xAxis : { categories : cls }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Total events by class', data: thesisSemPerYear, }], 
    });

});

</script>

<?php 

$awses = getAllAWS( );
$speakers = getAWSSpeakers( );

$awsPerSpeaker = array( );

$awsYearData = array_map(
    function( $x ) { return array(date('Y', strtotime($x['date'])), 0); } , $awses
    );

// Here each valid AWS speaker initialize her count to 0.
foreach( $speakers as $speaker )
    $awsPerSpeaker[ $speaker['login'] ] = array();

// If there is already an AWS for a speaker, add to her count.
foreach( $awses as $aws )
{
    $speaker = $aws[ 'speaker' ];
    if( ! array_key_exists( $speaker, $awsPerSpeaker ) )
        $awsPerSpeaker[ $speaker ] = array();

    array_push( $awsPerSpeaker[ $speaker ], $aws );
}

$awsCounts = array( );
$awsDates = array( );
foreach( $awsPerSpeaker as $speaker => $awses )
{
    $awsCounts[ $speaker ] = count( $awses );
    $awsDates[ $speaker ] = array_map( 
        function($x) { return $x['date']; }, $awses 
    );
}

$numAWSPerSpeaker = array( );
$gapBetweenAWS = array( );
foreach( $awsCounts as $key => $val )
{
    array_push( $numAWSPerSpeaker,  array($val, 0) );

    for( $i = 1; $i < count( $awsDates[ $key ] ); $i++ )
    {
        $gap = (strtotime( $awsDates[ $key ][$i-1] ) - 
            strtotime( $awsDates[ $key ][$i]) )/ (30.5 * 86400);

        // We need a tuple. Second entry is dummy.
        array_push( $gapBetweenAWS, array( $gap, 0 ) );
    }
}


?>


<script type="text/javascript" charset="utf-8">
$(function () {
    
    var data = <?php echo json_encode( $awsYearData ); ?>;

    /**
     * Get histogram data out of xy data
     * @param   {Array} data  Array of tuples [x, y]
     * @param   {Number} step Resolution for the histogram
     * @returns {Array}       Histogram data
     */
    function histogram(data, step) {
        var histo = {},
            x,
            i,
            arr = [];

        // Group down
        for (i = 0; i < data.length; i++) {
            x = Math.floor(data[i][0] / step) * step;
            if (!histo[x]) {
                histo[x] = 0;
            }
            histo[x]++;
        }

        // Make the histo group into an array
        for (x in histo) {
            if (histo.hasOwnProperty((x))) {
                arr.push([parseFloat(x), histo[x]]);
            }
        }

        // Finally, sort the array
        arr.sort(function (a, b) {
            return a[0] - b[0];
        });

        return arr;
    }

    Highcharts.chart('container0', {
        chart: { type: 'column' },
        title: { text: 'Number of Annual Work Seminars per year' },
        xAxis: { min : 2010 },
        yAxis: [{ title: { text: 'AWS Count' } }, ],
        series: [{
            name: 'AWS this year',
            type: 'column',
            data: histogram(data, 1),
            pointPadding: 0,
            groupPadding: 0,
            pointPlacement: 'between'
        }, 
    ] });

});

</script>


<script type="text/javascript" charset="utf-8">
$(function () {
    
    var data = <?php echo json_encode( $numAWSPerSpeaker ); ?>;

    /**
     * Get histogram data out of xy data
     * @param   {Array} data  Array of tuples [x, y]
     * @param   {Number} step Resolution for the histogram
     * @returns {Array}       Histogram data
     */
    function histogram(data, step) {
        var histo = {},
            x,
            i,
            arr = [];

        // Group down
        for (i = 0; i < data.length; i++) {
            x = Math.floor(data[i][0] / step) * step;
            if (!histo[x]) {
                histo[x] = 0;
            }
            histo[x]++;
        }

        // Make the histo group into an array
        for (x in histo) {
            if (histo.hasOwnProperty((x))) {
                arr.push([parseFloat(x), histo[x]]);
            }
        }

        // Finally, sort the array
        arr.sort(function (a, b) {
            return a[0] - b[0];
        });

        return arr;
    }

    Highcharts.chart('container1', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Total speakers with #AWSs'
        },
        xAxis: { min : 0 },
        yAxis: [{
            title: {
                text: 'Speaker Count'
            }
        }, ],
        series: [{
            name: 'Total speakers with #AWS',
            type: 'column',
            data: histogram(data, 1),
            pointPadding: 0,
            groupPadding: 0,
            pointPlacement: 'between'
        }, 
    ] });

});

</script>


<script>

$(function () {
    
    var data = <?php echo json_encode( $gapBetweenAWS ); ?>;

    /**
     * Get histogram data out of xy data
     * @param   {Array} data  Array of tuples [x, y]
     * @param   {Number} step Resolution for the histogram
     * @returns {Array}       Histogram data
     */
    function histogram(data, step) {
        var histo = {},
            x,
            i,
            arr = [];

        // Group down
        for (i = 0; i < data.length; i++) {
            x = Math.floor(data[i][0] / step) * step;
            if (!histo[x]) {
                histo[x] = 0;
            }
            histo[x]++;
        }

        // Make the histo group into an array
        for (x in histo) {
            if (histo.hasOwnProperty((x))) {
                arr.push([parseFloat(x), histo[x]]);
            }
        }

        // Finally, sort the array
        arr.sort(function (a, b) {
            return a[0] - b[0];
        });

        return arr;
    }

    Highcharts.chart('container2', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Gap in months between consecutive AWSs'
        },
        xAxis: { max : 36 },
        yAxis: [{
            title: {
                text: 'AWS Count'
            }
        }, ],
        series: [{
            name: '#AWS with this gap',
            type: 'column',
            data: histogram(data, 1),
            pointPadding: 0,
            groupPadding: 0,
            pointPlacement: 'between'
        }, 
    ] });

});
</script>

<h1>Booking requests between <?php
    echo humanReadableDate( 'march 01, 2017') ?> 
    and <?php echo humanReadableDate( $upto ); ?></h1>

<?php 
echo $bookingTable;
?>

<h1>Venue usage between <?php
    echo humanReadableDate( 'march 01, 2017') ?> 
    and <?php echo humanReadableDate( $upto ); ?></h1>

<h3></h3>
<div id="venues_plot" style="width:100%; height:400px;"></div>

<h3></h3>
<div id="events_class" style="width:100%; height:400px;"></div>

<h1>Academic statistics since March 01, 2017</h1>
<!--
<p class="warn">
The goodness of following two histograms depends on the correctness of the joining date
in my database. The years to graduation is computed by substracting joining 
date from thesis seminar date. </p>


<h3>Years to Graduate</h3>
<div id="timeToGraduate" style="width:100%; height:400px;"></div>
-->

<h3></h3>
<div id="container0" style="width:100%; height:400px;"></div>

<h3></h3>
<div id="container1" style="width:100%; height:400px;"></div>

<h3> Gap between consecutive AWSs </h3>
Ideally, this value should be 12 months for all AWSs.
<div id="container2" style="width:100%; height:400px;"></div>

<h3>Thesis seminar distribution (monthly)</h3>
<div id="thesis_seminar_per_month" style="width:100%; height:400px;"></div>

<h3>Thesis seminar distribution (yearly)</h3>
<div id="thesis_seminar_per_year" style="width:100%; height:400px;"></div>

<a href="javascript:window.close();">Close Window</a>

