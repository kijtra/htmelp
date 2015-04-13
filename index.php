<?php
include(__DIR__.'/htmlpart.php');

// example
$_SERVER['REQUEST_URI'] = '/path/to/dir?param1=value1';

/* MEta Tag */

htmlpart::meta('description', 'description text');
htmlpart::meta('description', 'overwrite description text');

htmlpart::meta('keywords', 'aa');
htmlpart::meta('keywords', array('bb', 'cc'));
htmlpart::meta('keywords', 'dd,ee,aa');

htmlpart::meta('og:title', 'og:title text');
htmlpart::meta('og:description', 'og:description text');
htmlpart::meta('og:image', 'http://xxxxxx/xxx.jpg');

htmlpart::meta('og:image', array('url' => 'http://xxxxxx/xxx.jpg', 'width' => 500, 'height' => 500));


htmlpart::meta('twitter:image', array('url' => 'http://xxxxxx/xxx.jpg', 'width' => 500, 'height' => 500));
htmlpart::meta('twitter:image', array('url' => 'http://xxxxxx/yyy.jpg', 'width' => 500, 'height' => 500));


htmlpart::meta('canonical', 'http://xxxxxx/aaa/');

htmlpart::css('/aaa.css');
htmlpart::css('/bbb.css');
htmlpart::css('/aaa.css');


htmlpart::js('/aaa.js');
htmlpart::js('/bbb.json');

// Output
echo htmlpart::out('meta');
/*
<meta name="description" content="overwrite description text">
<meta name="keywords" content="aa,bb,cc,dd,ee">
<meta property="og:description" content="og:description text">
<meta property="og:image" content="http://xxxxxx/xxx.jpg">
<meta property="og:image" content="http://xxxxxx/xxx.jpg">
<meta property="og:image" content="500">
<meta property="og:image" content="500">
<meta property="og:title" content="og:title text">
<meta property="twitter:image" content="http://xxxxxx/yyy.jpg">
<meta property="twitter:image" content="500">
<meta property="twitter:image" content="500">
<link rel="canonical" href="http://xxxxxx/aaa/">
<link rel="next" href="http://localhost:8080site/path/is/depth?a=b&page=2">
<link rel="stylesheet" href="/aaa.css">
<link rel="stylesheet" href="/bbb.css">
<script src="/aaa.js">
*/


/* Pagination */

htmlpart::pagination(1026, 20)->option(array(
    'query_key' => 'page',
));

// Output
echo htmlpart::out('pagination');
/*
<div class="pagination-wrap">
<ul class="pagination">
<li class="disabled"><a href="#">&lt; PREV</a></li>
<li class="active"><a href="http://localhost:8080/path/to/dir?param1=value1">1</a></li>
<li><a href="http://localhost:8080/path/to/dir?param1=value1&page=2">2</a></li>
<li><a href="http://localhost:8080/path/to/dir?param1=value1&page=3">3</a></li>
<li><a href="http://localhost:8080/path/to/dir?param1=value1&page=4">4</a></li>
<li><a href="http://localhost:8080/path/to/dir?param1=value1&page=5">5</a></li>
<li><a href="http://localhost:8080/path/to/dir?param1=value1&page=2">NEXT &gt;</a></li>
</ul>
</div>
*/


/* Breadcrumb */
htmlpart::bread('Home', '/');
htmlpart::bread('Second', '../?param2=value2');

// Output
echo htmlpart::out('bread');
/*
<ol class="breadcrumb" xmlns:v="http://rdf.data-vocabulary.org/#">
<li class="first" typeof="v:Breadcrumb"><a href="http://localhost:8080/" rel="v:url">home</a></li>
<li class="first active last" typeof="v:Breadcrumb"><a href="http://localhost:8080/path/to/?param2=value2" rel="v:url">self</a></li>
</ol>
*/
