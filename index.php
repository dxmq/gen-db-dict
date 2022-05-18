<?php

require_once dirname(__FILE__)."/vendor/autoload.php";

use App\GenDict;

$dict = new GenDict();
$dict->generate_md();