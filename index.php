<?php

  /* ERROR REPORTING */
  set_time_limit( 0 );
  ini_set( 'display_errors', 1 );
  ini_set( 'display_startup_errors', 1 );
  error_reporting( E_ALL );

  /* SETUP BASE VARIABLES */
  $key = 'aaa';
  $user_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2";
  $accessmonitor_eval = 'https://accessmonitor.acessibilidade.gov.pt/api/amp/eval/';

  /* SETUP SITES FOR ASSESSMENT */
  $https = 'https%3A%2F%2F';
  $site1 = $https . 'www.google.com';
  $site2 = $https . 'www.microsoft.com';
  $site3 = $https . 'www.apple.com';
  $sitesArr = array( $site1, $site2, $site3 );
  $lengthArr = count( $sitesArr );
  $arrFinal = [];

  echo '
  <!doctype html>
  <html>
  <head>
  <meta charset="utf-8">
  <title>Direct access to web accessibility best practices using PHP cURL</title>
  <meta name="viewport" content="minimal-ui, width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <meta name="robots" content="none" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <style>
  body {width: 100%;height: 100%;margin: 0 auto;padding: 0;}
  .output {width: 100%;height: 50%;background-color: #8ab4c4;color: #000;margin: 0 auto;padding: 10px;}
  </style>
  </head>

  <body>';

  /* ALGORYTHM BEGIN */
  for ( $j = 0; $j < $lengthArr; $j++ ) {

    try {
      /* STORE EACH SITE FROM THE ARRAY IN A VARIABLE */
      $v = $sitesArr[ $j ];
      $url_final = $accessmonitor_eval . $v;
      $siteName = substr( $v, 14 );

      /* CLEAN ADDRESS FROM SCRIPT INJECTIONS */
      $new_url = filter_var( $url_final, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH );

      /* OPEN CONNECTION */
      $z = curl_init();

      /* INSERT URL */
      curl_setopt( $z, CURLOPT_URL, $new_url );

      /* INSERT HEADERS - THE MOST IMPORTANT IN THIS CASE, AS WE NEED TO ACCESS THE SERVER */
      curl_setopt( $z, CURLOPT_HTTPHEADER, array(
        'If-None-Match: ' . $key,
        'Referer: https://accessmonitor.acessibilidade.gov.pt/' ) );

      /* OPTIONAL, TELL SERVER WHICH USER_AGENT IS ACCESSING  */
      curl_setopt( $z, CURLOPT_USERAGENT, $user_agent );

      /* OPTIONAL, IF YOU WANT TO MONITOR PROGRESS */
      curl_setopt( $z, CURLOPT_NOPROGRESS, false );

      /* OPTIONAL, GET DATA FROM PROGRESS */
      curl_setopt( $z, CURLOPT_PROGRESSFUNCTION, (

        function ( $resourcez, $downloadSizez, $ddownloadedz, $uploadSizez, $uploadedz ) {
          /* FIRST CHECK THE STATE OF DATA OUTPUT */
          /* ZERO OR NAN, NOTHING HAPPENS */
          if ( ( $downloadSizez === 0 ) || ( $downloadSizez === "NaN" ) ) {
            $percentOutputClean = 0;
          } else {
            /* IF A NUMBER REACHES THE GATE, START TO OUTPUT DOWNLOADED PERCENTAGE */
            $percentOutputRaw = ( $ddownloadedz / $downloadSizez * 100 );
            $percentOutputClean = number_format( $percentOutputRaw, 2 );
            /* WHOEVER WANTS TO USE A LOADING BAR, YOU CAN JUST INJECT THE PERCENTAGE DIRECTLY INTO A JS FUNCTION AND MAKE THE BAR LOAD WITH PERCENTAGE :) */
            // echo '<script>progresspublic(' . $percentOutputClean . ');</script>';
            /* REACH ME WITH A MESSAGE AT WORK[@]BRUNOMATOS.PT IF YOU WISH TO GET THE JS FOR THIS PROGRESS */
          } /* end if */
        } /* end function */
      ) );

      /* SETTING TIMEOUT TO ZERO PROVIDES US: INFINITE NUMBER OF SECONDS TO ALLOW cURL TO EXECUTE */
      curl_setopt( $z, CURLOPT_TIMEOUT, 0 );

      /* SETTING TIMEOUT TO ZERO PROVIDES US: INFINITE NUMBER OF SECONDS TO WAIT WHILE TRYING TO CONNECT - NOTE: FOR SECURITY REASONS, PHP SCRIPT SHOULD RUN AS FAST AS POSSIBLE TILL ITS END */
      curl_setopt( $z, CURLOPT_CONNECTTIMEOUT, 0 );

      /* AND, OF COURSE, WE WANT THE RETURN OF DATA FOR ANALYSIS */
      curl_setopt( $z, CURLOPT_RETURNTRANSFER, true );

      /* STORE DATA */
      $response = curl_exec( $z );

      /* HANDLE LACK OF DATA */
      if ( !curl_errno( $z ) ) {
        $a = json_encode( $response, true );
      } else {
        return false;
        $a = 0;
      } /* end if */

      /* DATA IS ALREADY ON OUR SIDE -> CLOSE CONNECTION */
      curl_close( $z );

      /* HANDLE DATA BY SELECTING ONLY WHAT WE WANT => THE SCORE /// CLEAN IT AND STORE IT FOR OUTPUT */
      if ( preg_match_all( '/score\\\"\:\\\"\d{1,2}+\.\d{1,2}+\\\"\}\}\}\"$/', $a, $match ) ) {
        
        /* REMOVE ALL THE JUNK AND PRETTIFY OUTPUT */
        foreach ( $match as & $val ) {
          $trim1 = str_replace( "score", "", $val );
          $trim2 = str_replace( "\\", "", $trim1 );
          $trim3 = str_replace( ":", "", $trim2 );
          $trim4 = str_replace( "\"", "", $trim3 );
          $trim5 = str_replace( "10.0", "10", $trim4 );
          $trim6 = str_replace( "}}}", "", $trim5 );
          array_push( $arrFinal, $trim6[ 0 ] );
        } /* end foreach */

      }

    echo '
    <div style="margin-top: 100px"></div>
    <div class="output">
    <h1>' . $siteName . '</h1>
    <h2>score: ' . $arrFinal[ 0 ] . '</h2>
    </div>
    <div style="margin-bottom: 100px"></div>';

    } catch ( Exception $e ) {
      echo 'Caught exception: ', $e->getMessage(), "\n";
    } /* end try/catch */

  /* CLEAN ARRAY */
  $arrFinal = array_diff( $arrFinal, $arrFinal );

} /* end for loop*/

  echo '
  </body>
  </html>
  ';

/* OUTPUTS THE TOP MOST OUTPUT BUFFER AND CLEARS IT  */
ob_flush();
flush();
exit;

?>