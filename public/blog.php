<?php
require dirname(__DIR__) . '/app/bootstrap.php';
page_start('Catatan pengembangan','guide');
echo '<div class="blog-layout panel">';
readfile(dirname(__DIR__) . '/docs/blogspot.html');
echo '</div>';
page_end();
