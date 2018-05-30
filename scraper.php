<?php
ini_set('display_errors', 1);
include('simple_html_dom.php');

//$file_name = "scrap".rand().".csv";

function strip_tags_content($text, $tags = '', $invert = FALSE) {

    $text = str_ireplace("<br>", "", $text);

    preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
    $tags = array_unique($tags[1]);

    if (is_array($tags) AND count($tags) > 0) {
        if ($invert == FALSE) {
            return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
        } else {
            return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
        }
    } elseif ($invert == FALSE) {
        return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
    }

    return $text;
}

function extract_url_from_redirect_link($url) {
    if ($url != '') {
        $q = parse_url($url)['query'];
        parse_str($q, $url_params)['q']['q'];

        if (isset($url_params['q']) AND ( strpos($url_params['q'], 'https://') !== false OR strpos($url_params['q'], 'http://') !== false))
            return $url_params['q'];
        else
            return false;
    }
}

function get_content($url) {


    $data = file_get_html($url);

#
#	Possible également avec CURL
#

    return $data;
}

function scrap_to_csv($links) {
    $fp = fopen('scrap.csv', 'w'); // need to add title       
    fputcsv($fp, array('Title', 'Link', 'Description'));
    foreach ($links as $link) {
        fputcsv($fp, $link);
    }

    fclose($fp);
}

$result = array();

if (isset($_POST['footprint'])) {
    $footprint = $_POST['footprint'];
//echo $footprint;
    $q = urlencode(str_replace(' ', '+', $footprint));
    $data = get_content('http://www.google.com/search?hl=en&q=' . $q . '&num=200&filter=0');
    $html = str_get_html($data);

    foreach ($html->find('.g') as $g) {
        $url = '';
        $h3 = $g->find('h3.r', 0);
        $s = $g->find('span.st', 0);
        if (isset($h3)) {
            $a = $h3->find('a', 0);
            $url = $a->getAttribute('href');
        }

        $link = extract_url_from_redirect_link($url);
        if (extract_url_from_redirect_link($url)) {
            $result[] = array(
                'title' => strip_tags($a->innertext),
                'link' => extract_url_from_redirect_link($url),
                'description' => strip_tags_content($s->innertext));
        }
    }
    scrap_to_csv($result);
} else {
    $footprint = '';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Google Scraper</title>
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link href="http://codegena.com/assets/css/image-preview-for-link.css" rel="stylesheet">
    </head>
    <body>
        <div id="app"  class="container">
            <h1>Google scraper</h1>
            <form method="post" action="scraper.php">
                <div class="row">
                    <input type="text"  class="form-control" placeholder="Search" name="footprint"  style="width: 30%;    display: inline;"  value="<?php echo $footprint; ?>" />
                    <input type="submit" class="btn btn-success" value="Scrap!"/>
                    <?php
                    if (!empty($result)) {
                        echo '<a href="scrap.csv"  class="btn btn-success" >Download CSV</a>';
                    }
                    ?>
                </div>
            </form>

            <br/>
            <div class="row">

                <?php
                $i = 1;
                $body = '  <table id="tbl" width="60%" class="table table-striped  table-bordered">
                    <thead>
                    <th width="5%">ID</th>
                    <th width="10%">Title</th>
                    <th style="width:\'20px\'">Link</th>
                    <th width="30%">Description</th>
                </thead>
                <tbody>';
                foreach ($result as $line) {

                    $body .= '<tr>' .
                            '<td width="5%">' . $i++ . '</td>' .
                            '<td width="10%">' . $line['title'] . '</td>' .
    //                            '<td style="width:\'20px\'">' . $line['link'] . '</td>' .
                            '<td style="width:\'20px\'"><a href="' . $line['link'] . '"  target="_blank" >' . $line['link'] . ' </a></td>' .
                            '<td width="30%">' . $line['description'] . '</td>'.
                            '</tr>';
                }

                $body .= '</tbody></table>';
                echo '<img id="img" /> <button id="getImage">Get Image</button>';
                echo '<p id="p1"><a href="http://cnet.com?output=embed";">Cnet</a></p>
                <p id="p2"><a href="http://codegena.com">Codegena</a></p>
                <p id="p3"><a href="http://apple.com">Apple</a></p>';
                echo '<b>Total: ' . count($result).'</b><br>' ;
                
                echo $body;
                ?>
            </div>
        </div>
        <script src="js/jquery-3.2.1.min.js"></script>
        <script src="http://codegena.com/assets/js/image-preview-for-link.js"></script>
        <script>
            $(document).ready(function(){

                function getImage(){
                    var url = 'https://www.facebook.com/';
                    $.ajax({
                        url: 'https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url=' + url + '&screenshot=true',
                        context: this,
                        type: 'GET',
                        dataType: 'json',
                        timeout: 60000,
                        success: function(result) {
                            var imgData = result.screenshot.data.replace(/_/g, '/').replace(/-/g, '+');
                            $("img").attr('src', 'data:image/jpeg;base64,' + imgData);
                        },
                        error:function(e) {
                            $("#msg").html("Error to fetch image preview. Please enter full url (eg: http://www.iamrohit.in)");
                        }
                    });
                }

                $('#getImage').click(getImage);

                $(function() {
                    $('#p1 a').miniPreview({ prefetch: 'pageload' });
                    $('#tbl td a').miniPreview({ prefetch: 'parenthover' });
                    // $('#p3 a').miniPreview({ prefetch: 'none' });
                });
            });
        </script>
    </body>
</html>