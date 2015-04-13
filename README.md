
## Examples

### MetaTag example

```php
<?php
// Include class file
include('htmlpart.php');

// Add meta keywords
htmlpart::meta('keywords', 'word1');
htmlpart::meta('keywords', 'word2');
htmlpart::meta('keywords', array('word3', 'word4'));

// Add og:image
htmlpart::meta('og:image', 'http://example.com/ogp.jpg');

// Add multiple
htmlpart::meta('description, og:description', 'description text');

// Add canonical
htmlpart::meta('canonical', 'http://example.com/aaa/');

// Add CSS file
htmlpart::css('/css/app.css');

// Add JavaScript file
htmlpart::js('http://example.com/js/app.js');
?>
<html>
<head>
<?php
// Output MetaTags
echo htmlpart::out('meta');

/* Results
<meta name="description" content="description text">
<meta name="keywords" content="word1,word2,word3,word4">
<meta property="og:description" content="description text">
<meta property="og:image" content="http://example.com/ogp.jpg">
<link rel="canonical" href="http://example.com/aaa/">
<link rel="stylesheet" href="/css/app.css">
<script src="http://example.com/js/app.js">
*/
?>
</head>
<body>
    ...
</body>
</html>
```


### Breadcrumb example

```php
<?php
// Include class file
include('htmlpart.php');

// Add Bread
htmlpart::bread('Home', '/');

// Add Bread
htmlpart::bread('Second', '/second/');

// Add Bread
htmlpart::bread('Third', '/second/third/');
?>
<html>
<head>
    ...
</head>
<body>
<?php
// Output Breadcrumb
echo htmlpart::out('bread');

/* Results
<ol class="breadcrumb" xmlns:v="http://rdf.data-vocabulary.org/#">
<li class="first" typeof="v:Breadcrumb"><a href="http://example.com/" rel="v:url">Home</a></li>
<li typeof="v:Breadcrumb"><a href="http://example.com/second/" rel="v:url">Second</a></li>
<li class="active last" typeof="v:Breadcrumb"><a href="http://example.com/second/third/" rel="v:url">Third</a></li>
</ol>
*/
?>
</body>
</html>
```



### Pagination example

```php
<?php
// Include class file
include('htmlpart.php');

$total_items = 214;
$per_page = 20;
htmlpart::pagination($total_items, $per_page);
?>
<html>
<head>
    ...
</head>
<body>
<?php
// Output Pagination
echo htmlpart::out('pagination');

/* Results
<div class="pagination-wrap">
<ul class="pagination">
<li class="disabled"><a href="#">&lt; PREV</a></li>
<li class="active"><a href="http://example.com/">1</a></li>
<li><a href="http://example.com/?page=2">2</a></li>
<li><a href="http://example.com/?page=3">3</a></li>
<li><a href="http://example.com/?page=4">4</a></li>
<li><a href="http://example.com/?page=5">5</a></li>
<li><a href="http://example.com/?page=2">NEXT &gt;</a></li>
</ul>
</div>
*/
?>
</body>
</html>
```
