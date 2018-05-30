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
    $sub = [];
    foreach ($links as $link) {
        if ($_POST['title'] == 'true' || $_POST['title'] == 'checked') {
            $sub['title'] = $link['title'];
        }
        if ($_POST['link'] == true || $_POST['link'] == 'checked') {
            $sub['link'] = $link['link'];
        }
        if ($_POST['description'] == true || $_POST['description'] == 'checked') {
            $sub['description'] = $link['description'];
        }
    }

    $fp = fopen('scrap.csv', 'w'); // need to add title       
    fputcsv($fp, array('Title', 'Link', 'Description'));
    foreach ($links as $link) {
        fputcsv($fp, $link);
    }

    fclose($fp);
}

function screen_shot($siteURL) {
    if (filter_var($siteURL, FILTER_VALIDATE_URL)) {
        //call Google PageSpeed Insights API
        $googlePagespeedData = file_get_contents("https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$siteURL&screenshot=true");

        //decode json data
        $googlePagespeedData = json_decode($googlePagespeedData, true);

        //screenshot data
        $screenshot = $googlePagespeedData['screenshot']['data'];
        $screenshot = str_replace(array('_', '-'), array('/', '+'), $screenshot);

        //display screenshot image
        return "<img src=\"data:image/jpeg;base64," . $screenshot . "\" />";
    } else {
        return "Please enter a valid URL.";
    }
}

$result = array();

if (isset($_POST['footprint'])) {

    $footprint = $_POST['footprint'];
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
            $link = extract_url_from_redirect_link($url);
            $result[] = array(
                'title' => strip_tags($a->innertext),
                'link' => $link,
                'description' => strip_tags_content($s->innertext)
            );
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
        <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
        <link rel="stylesheet" href="css/buttons.dataTables.min.css">
 
    </head>
    <body>
        <div id="app"  class="container">
            <h1>Google scraper</h1>
            <form method="post" action="scraper.php">
                <div class="row">
                    <input type="hidden" name='id' id='id'  value="true"/>
                    <input type="hidden" name='title' id='title' value="true"/>
                    <input type="hidden" name="link" id='link' value="true"/>
                    <input type="hidden" name="description" id='description' value="true"/>                    


                    <input type="text"  class="form-control" placeholder="Search" name="footprint"  style="width: 30%;    display: inline;"  value="<?php echo $footprint; ?>" />
                    <input type="submit" class="btn btn-success" value="Scrap!"/>

                    <!--//                    if (!empty($result)) {
                    //                        echo '<a href="scrap.csv"  class="btn btn-success" >Download CSV</a>';
                    //                    }-->

                </div>
            </form>

            <br/>
            <div class="row">

<!--                <div>
                    <p>Toggle column: </p>      
                    <div class="form-group">                                           
                        <label style="margin-right: 5px"><input type="checkbox" class="toggle-vis" data-column="0" name='id' checked >ID</label>
                        <label style="margin-right: 5px"><input type="checkbox" class="toggle-vis" data-column="1" name='title' checked >Title</label>
                        <label style="margin-right: 5px"><input type="checkbox" class="toggle-vis" data-column="2" name='link' checked >Link</label>
                        <label style="margin-right: 5px"><input type="checkbox" class="toggle-vis" data-column="3" name='description' checked >Description</label>
                    </div>
                </div>-->

                <?php
                $i = 1;
                $index = 1;
                $body = '  <table id="tblId" width="100%" class="table table-striped  table-bordered">
                    <thead>
                        <tr>
                            <th >ID</th>
                            <th >Title</th>
                            <th >Link</th>
                            <th >Description</th>  
                            <th >Print</th>
                        </tr>
                    </thead>
                    <tbody>';
                foreach ($result as $line) {

                    $index = $i++;
                    $body .= '<tr>' .
                            '<td >' . $index . '</td>' .
                            '<td >' . $line['title'] . '</td>' .
                            '<td ><a href="' . $line['link'] . '"  target="_blank" >' . $line['link'] . ' </a></td>' .
                            '<td >' . $line['description'] . '</td>' .
                            '<td >
                             <a href="print.php?url=' . $line['link'] . '"    class="btn btn-success"  target="_blank" >CAPTURE</a>                 
                           </td>' .
                            '</tr>';
                }

                $body .= '</tbody></table>';

//                echo '<b>Total: ' . count($result) . '</b><br>';

                echo $body;
                ?>

            </div>
        </div>
        <script src="js/jquery-3.2.1.min.js"></script> 
        <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.dataTables.min.js"></script>
        <script src="js/dataTables.bootstrap.min.js"></script>

        <script src="js/dataTables.buttons.min.js"></script>
        <script src="js/buttons.flash.min.js"></script>
        <script src="js/jszip.min.js"></script>
        <script src="js/pdfmake.min.js"></script>
        <script src="js/vfs_fonts.js"></script>
        <script src="js/buttons.html5.min.js"></script>
        <script src="js/buttons.print.min.js"></script>
        <script src="js/buttons.colVis.min.js"></script>
        <script>

            $(document).ready(function () {
                var table = $('#tblId').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'print',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'csv',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excel',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },

                        'colvis'
                    ],
                    columnDefs: [{
                            targets: -1,
                            visible: false
                        }]
//                    dom: 'Bfrtip',
//                    buttons: [
//                        'copy', 'csv', 'excel', 'pdf', 'print'
//                    ]
                });

                $('input.toggle-vis').on('click', function (e) {
                    // e.preventDefault();

                    // Get the column API object
                    var column = table.column($(this).attr('data-column'));
                    var colName = $(this).attr('name');
                    console.log(colName);

                    // Toggle the visibility
                    column.visible(!column.visible());


                    console.log($('#description').val());
                    $(this).prop('checked', column.visible());

                    $('#' + colName).val(column.visible());
                });

            });
        </script>


    </body>
</html>