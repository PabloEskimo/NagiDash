<?php

include('../lib/common.php');

DB::exec('DELETE FROM NagiosHost');
DB::exec('DELETE FROM NagiosService');
DB::exec('DELETE FROM NagiosField');
DB::exec('DELETE FROM NagiosValue');

DB::exec('UPDATE NagiosServer SET chrHash = ""');

NagiosStatus::poll();
exit;
