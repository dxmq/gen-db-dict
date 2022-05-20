<?php

require_once dirname(__FILE__)."/vendor/autoload.php";

use App\GenDict;

$dict = new GenDict();
// $dict->write_table_infos_to_file();
$dict->generate_md();
