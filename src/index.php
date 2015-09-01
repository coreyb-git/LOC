<?php
set_time_limit(60);
?>
<html>
  <head>
    <title>LOC</title>
  </head>
  <body>
    <?php
    require_once 'Class_Stats.php';
    $Stats = new Class_Stats('ALL', '', __DIR__);
    $Stats->AddExtension('php');
    $Stats->AddExtension('htm');
    $Stats->AddExtension('html');
    $Stats->AddExtension('css');
    $Stats->IgnoreFolder('D:\wamp\www\LOC\nbproject');
    $Stats->Go(__DIR__);
    ?>
  </body>
</html>
<?php