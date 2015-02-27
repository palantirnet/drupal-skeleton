<?php
$enable = array(
  'site_frontpage' => 'home',
  'site_slogan' => 'Economic thinking for environmental policy.'
);
foreach ($enable as $var => $setting) {
  if (!is_numeric($var)) {
    // set the site variables.
    variable_set($var, $setting);
  }
}
