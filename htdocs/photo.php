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

if(isset($_GET["local"]) and $_GET["local"]){
    $photo = imagecreatefromjpeg($_GET["local"]);
    $result = "localimage"; 
}

if ($result === "") {

    require_once("../conf/config.inc.php");
    require_once("../lib/ldap.inc.php");

    # Defauft value for LDAP photo attribute
    if (!isset($photo_ldap_attribute)) { $photo_ldap_attribute = "jpegPhoto"; }
    $photo_attributes[] = $photo_ldap_attribute;
    if (isset($photo_local_ldap_attribute)) { $photo_attributes[] = $photo_local_ldap_attribute; }

    # Connect to LDAP
    $ldap_connection = wp_ldap_connect($ldap_url, $ldap_starttls, $ldap_binddn, $ldap_bindpw);

    $ldap = $ldap_connection[0];
    $result = $ldap_connection[1];

    if ($ldap) {

        $ldap_filter = "(&(objectCategory=Person))";

        # Search entry
        $search = ldap_read($ldap, $dn, $ldap_filter, $photo_attributes);

        $errno = ldap_errno($ldap);

        if ( $errno ) {
            $result = "ldaperror";
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
        } else {
            $entry = ldap_get_entries($ldap, $search);
            if ( !isset($entry[0][strtolower($photo_ldap_attribute)]) ) {
                if ( $photo_local_ldap_attribute and isset($entry[0][strtolower($photo_local_ldap_attribute)]) ) {
                    $filephoto = $photo_local_directory . $entry[0][strtolower($photo_local_ldap_attribute)][0] . $photo_local_extension;
                    if ( file_exists($filephoto) ) {
                        $photo = imagecreatefromjpeg($filephoto);
                    }
                }
            } else {
                $ldapphoto = $entry[0][strtolower($photo_ldap_attribute)][0];
                $photo = imagecreatefromstring($ldapphoto);
            }
        }
    }
}

# Display default photo if any error
if ( !$photo ) {
    $photo = imagecreatefromjpeg($default_photo);
}

# Resize photo if needed
if ($photo_fixed_width or $photo_fixed_height) {
    $ratio = imagesx($photo)/imagesy($photo);
    $width = $photo_fixed_width ? $photo_fixed_width : $photo_fixed_height * $ratio;
    $height = $photo_fixed_height ? $photo_fixed_height : $photo_fixed_width / $ratio;
    $src = $photo;
    $photo = imagecreatetruecolor($width,$height);
    imagecopyresampled($photo,$src,0,0,0,0,$width,$height,imagesx($src),imagesy($src));
    imagedestroy($src);
}

header('Content-Type: image/jpeg');
imagejpeg($photo);
imagedestroy($photo);

?>
