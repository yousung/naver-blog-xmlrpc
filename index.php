<?php

require 'vendor/autoload.php';

use Lovizu\NaverXmlRpc\NaverBlogXml;

$post = new NaverBlogXml('nug22', '86d94d0786130ababdeb3aef8fca4864');
var_dump($post->delBlog('221298547224'));
