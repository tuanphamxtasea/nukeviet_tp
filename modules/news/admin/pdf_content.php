<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Options;

$id = $nv_Request->get_int('id', 'post', 0);
$checkss = $nv_Request->get_string('checkss', 'post', '');
$listid = $nv_Request->get_string('listid', 'post', '');
$contents = 'NO_' . $id;

if ($listid != '' and NV_CHECK_SESSION == $checkss) {
    $pdf_array = array_map('intval', explode(',', $listid));
} elseif (md5($id . NV_CHECK_SESSION) == $checkss) {
    $pdf_array = [
        $id
    ];
}

if (!empty($pdf_array)) {
    global $admin_info;
    $contents = '';

    $query = $db_slave->query('SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE id = ' . $id);
    $news_contents = $query->fetch();
    if (empty($news_contents)) {
        $contents = 'NO_' . $id;
        include NV_ROOTDIR . '/includes/header.php';
        echo $contents;
        include NV_ROOTDIR . '/includes/footer.php';


    }
    $body_contents = $db_slave->query('SELECT titlesite, description, bodyhtml, voicedata, keywords, sourcetext, files, layout_func, imgposition, copyright, allowed_send, allowed_print, allowed_save FROM ' . NV_PREFIXLANG . '_' . $module_data . '_detail where id=' . $news_contents['id'])->fetch();
    $news_contents = array_merge($news_contents, $body_contents);
    unset($body_contents);

    $page_title = empty($news_contents['titlesite']) ? $news_contents['title'] : $news_contents['titlesite'];
    $description = empty($news_contents['description']) ? $news_contents['hometext'] : $news_contents['description'];
    $body_html = $news_contents['bodyhtml'];

    $basePath = NV_MY_DOMAIN . NV_BASE_SITEURL;
    $html = '<html lang="vi" xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns#">
        <head>                
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />      
            <link rel="styleSheet" href="'. $basePath.'themes/'. $admin_info['admin_theme'] .'/css/news_pdf.css">            
         </head>
        <div class="panel-body">
        <h1 class="title margin-bottom-lg" itemprop="headline" >'.$page_title.'</h1>              
        <div class="clearfix">
            <div class="hometext m-bottom" itemprop="description">'.$description.' </div>
        </div>
        <div id="news-bodyhtml" class="bodytext margin-bottom-lg">'.$body_html.' </div>                
        </html>
        ';

    //Dompdf can't read if using XTemplate. Try this below code to see
    //$html = nv_pdf_news_detail($basePath, $page_title, $description, $body_html);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->setBasePath($basePath);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfContent = $dompdf->output();
    $content  = 'OK&'.base64_encode($pdfContent);
}

include NV_ROOTDIR . '/includes/header.php';
echo $content;
include NV_ROOTDIR . '/includes/footer.php';

