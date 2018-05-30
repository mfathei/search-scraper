<?php

$websiteURL =$_GET['url'];

//echo $websiteURL;

        //"https://www.facebook.com/public/Rasha-Atta";
$api_response = file_get_contents("https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$websiteURL&screenshot=true");
//decode json data
$result = json_decode($api_response, true);
//screenshot data
$screenshot = $result['screenshot']['data'];
$screenshot = str_replace(array('_', '-'), array('/', '+'), $screenshot);
//display screenshot image
echo "<img src=\"data:image/jpeg;base64," . $screenshot . "\" />";
?>



