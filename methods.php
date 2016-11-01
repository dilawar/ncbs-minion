<?php 

include_once('database.php');
include_once('error.php');
include_once( 'logger.php' );

date_default_timezone_set('Asia/Kolkata');

/**
    * @brief generate a select list of available strains.
    *
    * @return  A html SELECT list.
 */
function strainsToHtml( $selected_strain = NULL )
{
    if( ! $selected_strain )
        $html = "<select name=\"animal_strain\" required > 
        <option value=\"unknown\">Yet to determine</option>"
        ;
    else
        $html = "<select name=\"animal_strain\" required>"; 

    $listOfStrain = $_SESSION['conf']['animal']['strain'];
    foreach( $listOfStrain as $strain )
    {
        $html .= '<option value="' . $strain . "\" ";
        $html .= ($strain == $selected_strain) ? "\"selected\"" : " ";
        $html .=  '>' . $strain . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function cagesToHtml( $cages, $default = NULL )
{
    if( ! $default )
        $html = "<select name=\"cage_id\"> 
            <option disabled selected value> -- select a cage -- </option>"
            ;
    else
        $html = "<select name=\"cage_id\">";

    foreach( $cages as $cage )
    {
        if( $default == $cage['id'] )
            $selected = 'selected';
        else 
            $selected = '';

        $html .= '<option value="'.$cage['id']. '" ' . $selected . ' >' 
            . $cage['id'] .  " (" . $cage['type'] . ")" . '</option>';
    }
    $html .= "</select>";
    return $html;
}

function venuesToHTMLSelect( $venues, $ismultiple = false )
{
    $multiple = '';
    $default = '-- select a venue --';
    $name = 'velue';
    if( $ismultiple )
    {
        $multiple = 'multiple size="3"';
        $default = '-- select multiple venues --';
        $name = 'venue[]';
    }

    $html = "<select $multiple name=\"$name\"> 
        <option disabled selected value>  $default </option>"
        ;
    foreach( $venues as $v )
    {
        $text = $v['id'] . ' (' . $v['strength'] . ') ';
        if( $v['suitable_for_conference'] == 'Yes' )
            $text .= '<font color=\"blue\"> +C </font>';
        if( $v['has_projector'] == 'Yes' )
            $text .= '<font color=\"blue\"> +P </font>';

        $html .= '<option value="' . $v['id'] . '">' . $text . '</option>';
    }

    $html .= "</select>";
    return $html;
}

function animalsToDataList( $animals )
{
    $html = "<datalist id=\"animal_list\">";
    foreach( $animals as $anim )
    {
        $text = $anim['id'] . ' ' . $anim['name'];
        $html .= '<option value="' . $anim['id'] . '">' . $text . '</option>';
    }

    $html .= "</datalist>";
    return $html;
}

function generateRandomString($length = 10) 
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/* Go to a page */
function goToPage($page="index.php", $delay = 3)
{
  echo printWarning("... Going to page $page in $delay seconds ...");
  $conf = $_SESSION['conf'];
  $url = $conf['global']['base_url']."/".$page;
  header("Refresh: $delay, url=$url");
}

function goBackToPageLink( $url, $title = "Go back" )
{
    $html = "<br />";
    $html .= "<a style=\"float: right\" href=\"$url\">
            <font color=\"blue\" size=\"5\">$title</font>
        </a>";
    return $html;
}

function __get__( $arr, $what, $default = NULL )
{
    if( array_key_exists( $what, $arr ) )
        return $arr[$what];
    else
        return $default;
}

function repeatPatToDays( $pat )
{
    assert( strlen( $pat ) > 0 );
    $weekdays = array( "sun", "mon", "tue", "wed", "thu", "fri", "sat" );
    $exploded = explode( ",", $pat);
    $days = $exploded[0];
    // These are absolute indices of days.
    if( $days == "*" )
        $days = "0/1/2/3/4/5/6";
    $weeks = __get__( $exploded, 1, "*" );
    $months = __get__( $exploded, 2, "*" );
    if( $weeks == "*" )
        $weeks = "0/1/2/3";
    if( $months == "*" );
        $months = "0/1/2/3/4/5/6/7/8/9/10/11";


    $months = explode( "/", $months );
    $weeks = explode( "/", $weeks );
    $days = explode( "/", $days );


    $result = Array();

    // Now fill the dates for given pattern.
    foreach( $months as $m )
        foreach( $weeks as $w )
            foreach( $days as $d )
            {
                $day = 28 * intval($m) + 7 * intval($w) + 1 + intval($d);
                array_push( $result, $day );
            }

    // Get the base day which is first in the pattern and compute dates from 
    // this day.
    $baseDay = strtotime( "next " . $weekdays[$days[0]] );
    return daysToDate($result, $baseDay);
}

function daysToDate( $ndays, $baseDay = NULL )
{
    $bd = date("l", $baseDay);
    $result = Array( );
    $baseDay = date("Y-m-d", $baseDay);
    foreach( $ndays as $nd )
    {
        $date = date('Y-m-d', strtotime( $baseDay . ' + ' . $nd  . ' days'));
        array_push( $result, $date );
    }
    return $result;
}

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function humanReadableDate( $date )
{
    if( is_int( $date ) )
        return date( 'Y-M-d', $date );

    return date( 'Y-M-d', strtotime($date) );
}


// Return a format date for mysql database.
function dbDate( $date )
{
    if( is_int( $date ) )
        return date( 'Y-m-d', $date );

    return date( 'Y-m-d', strtotime( $date ) );
}

// Return the name of the day for given date.
function nameOfTheDay( $date )
{
    return date( 'l', strtotime( $date ) );
}

?>
