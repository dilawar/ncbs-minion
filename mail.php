<?php

include_once 'database.php';

// Directory to store the mdsum of sent emails.
$maildir = getDataDir( ) . '/_mails';
if( ! file_exists( $maildir ) )
    mkdir( $maildir, 0777, true );

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

    printInfo( "<pre> $cmd </pre>" );
    $out = `$cmd`;
    printInfo( '... $out' );

    echo printInfo( "Saving the mail in archive" . $archivefile );
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
