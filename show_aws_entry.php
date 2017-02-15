<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'html2text.php';

echo "<h2>Browse AWSs of a particular day</h2>";

echo printInfo( "Please select a day (MONDAY) to see the details of annual 
    work seminars" );

$today = dbDate( 'next monday' );

if( array_key_exists( 'date', $_GET ) )
    $default[ 'date' ] = $_GET[  'date' ];
else
    $default = array( 'date' => $today );

echo '
    <form method="get" action="">
    <table border="0">
        <tr>
            <td>Select date</td>
            <td><input class="datepicker" type="text" name="date" value="' . 
                    $default[ 'date' ] . '" ></td>
            <td><button type="submit" name="response" value="show">' . 
                $symbScan . '</button></td>
        </tr>
    </table>
    </form>
    ';

$whichDay = $default[ 'date' ];

$awses = getTableEntries( 'annual_work_seminars', 'date' , "date='$whichDay'" );
$upcoming = getTableEntries( 'upcoming_aws', 'date', "date='$whichDay'" );
$awses = array_merge( $awses, $upcoming );

if( count( $awses ) < 1 )
{
    echo printInfo( "I could not find any AWS in my database on this day" );
}
else 
{
    foreach( $awses as $aws )
    {
        $user = $aws[ 'speaker' ];
        $awstext = awsToTable( $aws );
        $imgHtml = "<div style=\"margin-left:50%\">" . getUserPicture( $user ) . "</div>";
        echo "<div float=\"right\"> $imgHtml </div>";
        echo $awstext;
    }
}

echo closePage( );

?>
