<?php

class Class_Stats {
  public $Name = '';
  public $Dir = '';
  public $Type = '';
  public $TotalLines = 0;
  public $BlankLines = 0;
  public $WhitespaceChars = 0;
  public $NonWhitespaceChars = 0;
  
  public $AllowedExtensions = array();
  public $IgnoreDirectories = array();
  
  public $FileStats = array();
  public $FolderStats = array();
  
  public function __construct($Name, $Type, $Dir) {
    $this->Name = $Name;
    $this->Type = $Type;
    $this->Dir = $Dir;
  }
  
  public function AddExtension($Ext) {
    $this->AllowedExtensions[] = strtolower($Ext);
  }
  
  public function AddArrayOfExtensions($A) {
    foreach ($A as $Ext) $this->AddExtension($Ext);
  }
  
  public function IgnoreFolder($Folder) {
    $this->IgnoreDirectories[] = $Folder;
  }
  
  public function Go($StartingDirectory) {
    set_time_limit(60);
    
    $this->ProcessDir($StartingDirectory, '');
    ?><h1>All Stats</h1><?php
    ?><p>Total Folders: <?php echo count($this->FolderStats) ?></p><?php
    $this->Display();
    ?><hr /><h2>File Type Stats</h2><?php
    foreach ($this->AllowedExtensions as $ext) {
      $stat = new self('STATS FOR ' . $ext, $ext, '');
      $stat->AddInstance($this->GetFileExtensionStats($ext));
      $stat->Display();
    }
    ?><hr /><h2>Folder Stats</h2><?php
    foreach ($this->FolderStats as $Stat) $Stat->Display();
    ?><hr /><h2>File Stats</h2><?php
    foreach ($this->FileStats as $Stat) $Stat->Display();
  }
  
  private function GetFileExtensionStats($Ext) {
    $TempStat = new self('', $Ext, '');
    foreach ($this->FileStats as $Stat) {
      /* @var $Stat Class_Stats */
      if ($Stat->Type === $Ext) {
        $TempStat->AddInstance($Stat);
        $TempStat->FileStats[] = $Stat;
      }
    }

    return $TempStat;
  }
  
  public function Display() {
    $UsefulLines = $this->TotalLines - $this->BlankLines;
    $TotalChars = $this->WhitespaceChars + $this->NonWhitespaceChars;
    $CharDensity = 0;
    if ($TotalChars > 0) $CharDensity = $this->NonWhitespaceChars / $TotalChars;
    $AverageLinesPerFile = 0;
    if (count($this->FileStats) > 0) $AverageLinesPerFile = $this->TotalLines / count($this->FileStats);
    ?>
    <p>
      Name: <?php echo $this->Name ?>
      <br>Dir: <?php echo $this->Dir ?>
      <br>Ext: <?php echo $this->Type ?>
      <br>Total Lines: <?php echo $this->TotalLines ?>
      <br>Blank Lines: <?php echo $this->BlankLines ?>
      <br><strong>Useful Lines: <?php echo $UsefulLines ?></strong>
      <br><em>Avg Lines Per File: <?php echo $AverageLinesPerFile ?></em>
      <br>Whitespace: <?php echo $this->WhitespaceChars ?> chars
      <br>Non-Whitespace: <?php echo $this->NonWhitespaceChars ?> chars
      <br><em>Useful Char Density: <?php echo $CharDensity ?></em>
    </p>
    <p>
      Total Files: <?php echo count($this->FileStats) ?>
    </p>
    <?php
  }
  
  private function IsWantedExtension($Ext) {
    $result = false;
    $Ext = strtolower($Ext);
    foreach ($this->AllowedExtensions as $l) {
      if ($l === $Ext) $result = true;
    }
    if (count($this->AllowedExtensions) === 0) $result = true;
    return $result;
  }
  
  private function IsIgnoredFolder($Folder) {
    $result = false;
    foreach ($this->IgnoreDirectories as $d) {
      if ($d === $Folder) $result = true;
    }
    return $result;
  }
  
  private function AddInstance(self $Instance) {
    $this->TotalLines += $Instance->TotalLines;
    $this->BlankLines += $Instance->BlankLines;
    $this->WhitespaceChars += $Instance->WhitespaceChars;
    $this->NonWhitespaceChars += $Instance->NonWhitespaceChars;
    foreach ($Instance->FileStats as $Item) $this->FileStats[] = $Item;
    foreach ($Instance->FolderStats as $Item) $this->FolderStats[] = $Item;
  }
  
  private function GetExtension($Filename) {
    $ext = '';
    $len = strlen($Filename);
    $i = $len - 1;
    while ($i > -1) {
      $subs = substr($Filename, $i, 1);
      if ($subs !== '.') $ext = $subs . $ext;
      else $i = -1;
      $i--;
    }
    return strtolower($ext);
  }
  
  public function ProcessFile($Filename, $Dir) {
    $FullName = $Dir . DIRECTORY_SEPARATOR . $Filename;
    if ($Dir === DIRECTORY_SEPARATOR) $FullName = $Filename;

  /*var_dump($Filename);
  var_dump($Dir);
  var_dump($FullName);*/
    
    $ext = $this->GetExtension($Filename);

    if ($this->IsWantedExtension($ext)) {
      $stats = new self($FullName, $ext, $Dir);
      if (false !== ($f = fopen($FullName, 'rb'))) {
        $LineHasChars = false;
        while (!feof($f)) {
          $in = fgetc($f);
          if (($in === ' ') || ($in === "\t")) $stats->WhitespaceChars++;
          if (($in !== ' ') && ($in !== "\t") && ($in !== "\r") && ($in !== "\n") && (!feof($f))) {
            $stats->NonWhitespaceChars++;
            $LineHasChars = true;
          }
          if (($in === "\n") || (feof($f))) {
            $stats->TotalLines++;
            if (!$LineHasChars) $stats->BlankLines++;
            $LineHasChars = false;
          }
        }
        fclose($f);
        $this->AddInstance($stats);
        $this->FileStats[] = $stats;
      }
    }
  }
  
  public function ProcessDir($Dir, $ParentDir) {
  //  echo '<p><b>Folder: ' . $Dir . '</b></p>';
  //echo '<p>dir = ' . $Dir;
  //echo '<br>parentdir = ' . $ParentDir;

    if (false !== $d = opendir($Dir)) {
      $DirStats = new self($Dir, 'DIRECTORY', $ParentDir);
      while (false !== ($entry = readdir($d))) {
        $testdir = $Dir . DIRECTORY_SEPARATOR . $entry;
        if (($entry !== '.') && ($entry !== '..')) {
  //echo '<p>entry = ' . $entry;
          $sub = $Dir . DIRECTORY_SEPARATOR . $entry;
          if ($Dir == '') $sub = $Dir;
  //echo '<p>sub = ' . $sub;
          if (is_dir($testdir)) {
            if (!$this->IsIgnoredFolder($testdir)) {
              $temp = new self($sub, '', $ParentDir);
              foreach ($this->AllowedExtensions as $allowed) $temp->AddExtension($allowed);
              foreach ($this->IgnoreDirectories as $ignored) $temp->IgnoreFolder($ignored);
              $temp->ProcessDir($sub, $Dir);
              $DirStats->FolderStats[] = $temp;
              $DirStats->AddInstance($temp);
            }
          } else {
            $temp = new self($entry, $this->GetExtension($entry), $Dir);
            foreach ($this->AllowedExtensions as $allowed) $temp->AddExtension($allowed);
            foreach ($this->IgnoreDirectories as $ignored) $temp->IgnoreFolder($ignored);
            $temp->ProcessFile($entry, $Dir);
            $DirStats->AddInstance($temp);
          }
        }
      }
      closedir($d);
      $this->AddInstance($DirStats);
    }
  }
}