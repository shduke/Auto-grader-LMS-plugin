<?php
    require_once "config.php";
    use \Tsugi\Core\LTIX;

    $LAUNCH = LTIX::session_start();
    // Set session to show that we came from submit
    $_SESSION['previous'] = 'Submitting for Grading';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>APT Grading CompSci 101</title>
    <link rel="stylesheet" href="topstyle.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">

<style type="text/css">

td > a{
  margin-left: 8px;
}

body {
  counter-reset: globalcounter;
}

TABLE {
   /*border: 1px solid black;*/
   border-collapse: collapse;
   counter-reset: aptcounter;
   counter-increment: tablecounter;
}

TD.probdesc{
   background-color: #bdc3c7;
}

TD.numbered{
  border-right: none !important;
}

TD.hint{
  border-left: none !important;
}

</style>


  </head>

  <body>

    <nav class="navbar nav navbar-default navbar-static-top">
      <div class="container">
        <ol class="breadcrumb">
          <li><a href="index.php">Home</a></li>
          <li>Submit Files</li>
        </ol>
        <p class="navbar-text navbar-right"><a href="gradebook.php" class="navbar-link">Gradebook</a></p>
        <?php if ($USER->instructor){ ?>
          <p class="navbar-text navbar-right"><a href="analytics.php" class="navbar-link analytics">Analytics</a> | </p>
        <?php } ?>
      </div>
    </nav>

    <div class="container">

      <div class="center header">
        <h1>APT Grading: CompSci 101, Spring 2016</h1>
      </div>

<?php
    if ( isset($USER->displayname) ) {
        echo("<p>Hello ".$USER->displayname."</p>\n");
    }
?>
<P>
This is the webpage for <em>grading and submitting</em> your APTs.
You should have already tested using the
<a href="apt_test.php">regular APT system</a> that doesn't require
a secure/netid authenticated login.
</p>
<hr>

<?php
$course = "compsci101";    # this was "newapt"
$apturl = "http://www.cs.duke.edu/csed/pythonapt/";
$apturl = "http://localhost:8888/apt_files";
$name= "apt.txt";
$upload="https://cgi.cs.duke.edu/~ola/aptsec/";
$upload="";

#below here doesn't need to change?

#### $name= $apturl."apt.txt";

$formHeader = '<form method="POST" enctype="multipart/form-data" action="'.$upload.'pythonupload.php"> <input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="language" value="python">';

$all = file_get_contents($name);
$lines = explode("\n",$all);
$listing="";
$groupCount = 0;
$probCount = 0;
$lastGroup = -1;
for($k=0; $k < count($lines); $k++){
  $temp = $lines[$k];
  if (substr($temp,0,1) == "#") {
    $header[$groupCount] = substr($temp,1);
    $choices[$groupCount] = "";
    $listing[$groupCount] = "";
    $probStart[$groupCount] = 0;
  }
  else {
    $data = explode(":", $temp);
    if (count($data) >= 3){
        $lastGroup = $groupCount;
        $probCount++;
        if ($probStart[$groupCount] == 0) {
            $probStart[$groupCount] = $probCount;

        }
       $button = '<input type="radio" name="problem" value="'.$data[0].'">';

       $listing[$groupCount].='<td class="numbered">'.$button.
       '<a href="apt/'.strtolower($data[1]).'/'.strtolower($data[2]).'" target="_top">'.$data[1].'</em></A><br>';



       if (count($data) > 3) {
          $listing[$groupCount] .= '<td class="hint">'.$data[3]."<br><tr>";
       }
       else{
          $listing[$groupCount] .= '<td class="hint"> <br><tr>';
       }
   }
   else  {
      # finished a group, blank-line or at least not three ':' characters
      if ($lastGroup == $groupCount){
         $groupCount++;
      }
   }
  }
}

$chooseBoiler = 'Test file: <input type="file" name="upfile" size="80"><br>';
$chooseBoiler .= '<center><input class = "btn" type="submit" value="test/run"></center></form>';

for ($k=0; $k < $groupCount; $k++){
   print '<table class = "table table-bordered">';
   print '<th> Problem Set '.($groupCount-$k);  #list backwards
#   print '<th width="250px"> Problem Set '.($k+1);   # list in order
   print '<th> Details';
   print '<tr>';
   print $formHeader;
   print '<td colspan="2" class="probdesc">';
   print $header[$k];
   print '<tr>';
   print $listing[$k];
   print '<td colspan="2" class="submit">';
   print $chooseBoiler;
   print '</td>';
   print  '</table><P>';
#   print '<hr>';
}

?>

  </div> <!-- Container end -->

  </body>
</html>
