<?php

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

// Directory to store the mdsum of sent emails.
$maildir = getDataDir( ) . '/_mails';
if( ! file_exists( $maildir ) )
    mkdir( $maildir, 0777, true );

function generateAWSEmail( $monday )
{

    $res = array( );

    $upcomingAws = getUpcomingAWS( $monday );
    if( ! $upcomingAws )
        $upcomingAws = getTableEntries( 'annual_work_seminars', "date" , "date='$monday'" );

    $html = '';
    if( count( $upcomingAws ) < 1 )
    {
        $html .= "<p>Greetings</p>";
        $html .= "<p>I could not find any annual work seminar 
                scheduled on " . humanReadableDate( $monday ) . ".</p>";

        $holiday = getTableEntry( 'holidays', 'date'
                        , array( 'date' => dbDate( $monday ) ) );

        if( $holiday )
        {
            $html .= "<p>It is most likely due to following event/holiday: " . 
                        strtoupper( $holiday['description'] ) . ".</p>";

        }

        $html .= "<br>";
        $html .= "<p>That's all I know! </p>";

        $html .= "<br>";
        $html .= "<p>-- NCBS Hippo</p>";

        return array( "email" => $html, "speakers" => null );

    }

    $speakers = array( );
    $logins = array( );
    $outfile = getDataDir( ) . "AWS_" . $monday . "_";

    foreach( $upcomingAws as $aws )
    {
        $html .= awsToHTML( $aws );
        array_push( $logins, $aws[ 'speaker' ] );
        array_push( $speakers, __ucwords__( loginToText( $aws['speaker'], false ) ) );
    }

    $outfile .= implode( "_", $logins );  // Finished generating the pdf file.
    $pdffile = $outfile . ".pdf";
    $res[ 'speakers' ] = $speakers;

    $data = array( 'EMAIL_BODY' => $html
        , 'DATE' => humanReadableDate( $monday ) 
        , 'TIME' => '4:00 PM'
    );

    $mail = emailFromTemplate( 'aws_template', $data );

    echo "Generating pdf";
    $script = __DIR__ . '/generate_pdf_aws.php';
    $cmd = "php -q -f $script date=$monday";
    echo "Executing <pre> $cmd </pre>";
    ob_flush( );

    $ret = `$cmd`;

    if( ! file_exists( $pdffile ) )
    {
        echo printWarning( "Could not generate PDF $pdffile." );
        $pdffile = '';
    }

    $res[ 'pdffile' ] = $pdffile;
    $res[ 'email' ] = $mail;
    return $res;
}


function mailFooter( )
{
    return "
        <p>==========================================================</p>
        This email is automatically generated by NCBS Hippo (https://ncbs.res.in/hippo). 
        If you are not an intended recipient of this email, please write to 
        hippo@lists.ncbs.res.in or acadoffice@ncbs.res.in .
        <p>==========================================================</p>
        ";
}


function sendPlainTextEmail($msg, $sub, $to, $cclist='', $attachment = null) 
{
    global $maildir;
    $conf = getConf( );

    if( ! is_string( $msg ) )
    {
        error_log( "Email msg is not in string format" );
        echo printInfo( 'Email msg not in string format' );
        return;
    }

    printInfo( "Trying to send email to $to, $cclist with subject $sub" );
    if( strlen( trim( $msg ) ) < 1 )
        return;

    if( ! array_key_exists( 'send_emails', $conf['global' ] ) )
    {
        echo printInfo( "Email service has not been configured." );
        error_log( "Mail service is not configured" );
        return;
    }


    if( $conf['global']['send_emails' ] == false )
    {
        echo alertUser( "<br>Sending emails has been disabled in this installation" );
        return;
    }


    // Check if this email has already been sent.
    $archivefile = $maildir . '/' . md5($sub . $msg) . '.email';
    if( file_exists( $archivefile ) )
    {
        echo printInfo( "This email has already been sent. Doing nothing" );
        return;
    }

    printInfo( "... preparing email" );

    $timestamp = date( 'r', strtotime( 'now' ) );

    $msg .= mailFooter( );

    $textMail = html2Markdown( $msg, $strip_inline_image = true );

    $msgfile = tempnam( '/tmp', 'hippo_msg' );
    file_put_contents( $msgfile, $textMail );

    $to =  implode( ' -t ', explode( ',', trim( $to ) ) );

    // Use \" whenever possible. ' don't escape especial characters in bash.
    $cmd= __DIR__ . "/sendmail.py -t $to -s \"$sub\" -i \"$msgfile\" ";

    if( $cclist )
    {
        $cclist =  implode( ' -c ', explode( ',', trim( $cclist ) ) );
        $cmd .= "-c $cclist";
    }

    if( $attachment )
    {
        foreach( explode( ',', $attachment ) as $f )
            $cmd .= " -a \"$f\" ";
    }

    $out = `$cmd`;

    error_log( "<pre> $cmd </pre>" );
    error_log( '... $out' );
    error_log( "Saving the mail in archive" . $archivefile );

    // generate md5 of email. And store it in archive.
    file_put_contents( $archivefile, "SENT" );
    unlink( $msgfile );
    return true;
}


// $res = sendEmail( "testing"
//     , "Your request has been created"
//     , "dilawars@ncbs.res.in" 
//     );

?>
