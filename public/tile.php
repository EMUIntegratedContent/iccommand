<?php
/** DO NOT DELETE THIS FILE!
 *
 * For displaying EMU Campus illustrated map on map item edit/create pages
 *
 **/
if (isset($_GET['zoom']) && isset($_GET['x']) && isset($_GET['y'])) {
    $name = dirname(__FILE__) . '/images/maptiles/' . $_GET['zoom'] . '/' . $_GET['zoom'] . '_' . $_GET['x'] . '_' . $_GET['y'] . '.jpg';

    if (file_exists($name)) {
        $fp = fopen($name, 'rb');

        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($name));

        fpassthru($fp);
        exit;
    }
    else {
        $name = dirname(__FILE__) . '/images/maptiles/' . $_GET['zoom'] . '/empty.jpg';
        $fp   = fopen($name, 'rb');

        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($name));

        fpassthru($fp);
    }
}
