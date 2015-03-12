<?php
$enable = array(
  'site_frontpage' => 'node',
  'site_slogan' => "Palantir.net's starter website kit."
);
foreach ($enable as $var => $setting) {
  if (!is_numeric($var)) {
    // set the site variables.
    variable_set($var, $setting);
  }
}
