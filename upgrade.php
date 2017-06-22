<html>
<head>
  <title>Upgrading...</title>
</head>
<body>
<?php
require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');

$updates =
  array("alter table teachingtip add status enum ('draft', 'active', 'deleted') not null default 'active'",
	"update teachingtip set status = 'draft' where draft = 1 and archived = 0",
	"update teachingtip set status = 'deleted' where archived = 1".
	"alter table teachingtip drop draft",
	"alter table teachingtip drop archived");
foreach ($updates as $query)
  dataConnection::runQuery($query);
echo "Done";
?>
</body>
</html>
