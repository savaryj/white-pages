<?php
/*
 * Display photo
 */ 


require_once("../conf/config.inc.php");

$result = "";
$dn = "";
$entry = "";
$photo = "";

if (isset($_GET["dn"]) and $_GET["dn"]) { $dn = $_GET["dn"]; }
 else { $result = "dnrequired"; }

if ($result === "") {

    require_once("../conf/config.inc.php");

    # Connect to LDAP
    $ldap = ldap_connect($ldap_url);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if ( $ldap_starttls && !ldap_start_tls($ldap) ) {
        $result = "ldaperror";
        error_log("LDAP - Unable to use StartTLS");
    } else {

        # Bind
        if ( isset($ldap_binddn) && isset($ldap_bindpw) ) {
            $bind = ldap_bind($ldap, $ldap_binddn, $ldap_bindpw);
        } else {
            $bind = ldap_bind($ldap);
        }

        $errno = ldap_errno($ldap);

        if ( $errno ) {
            $result = "ldaperror";
            error_log("LDAP - Bind error $errno  (".ldap_error($ldap).")");
        } else {

            # Search entry
            $search = ldap_read($ldap, $dn, $ldap_user_filter, array('jpegPhoto'));

            $errno = ldap_errno($ldap);

            if ( $errno ) {
                $result = "ldaperror";
                error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            } else {
                $entry = ldap_get_entries($ldap, $search);
                if ( !isset($entry[0]["jpegphoto"]) ) {
                    $result = "photonotfound";
                } else {
                    $jpegphoto = $entry[0]["jpegphoto"][0];
                    $photo = imagecreatefromstring($jpegphoto);
                }
            }
        }
    }
}

# Display default photo if any error
if ( !$photo ) {
    $photo = imagecreatefromjpeg($default_photo);
}

header('Content-Type: image/jpeg');
imagejpeg($photo);
imagedestroy($photo);

?>
