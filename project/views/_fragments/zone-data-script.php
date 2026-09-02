<?php
/** @var array $sbZoneDataMap */
echo '<script type="application/json" class="sb-wf-zone-data">' . json_encode($sbZoneDataMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
