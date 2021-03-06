<?php

require_once "config.php";
use \Tsugi\Core\LTIX;
use Tsugi\Core\Context;

// Launch a tsugi session
$LAUNCH = LTIX::session_start();

// Get info about problem
$language = $_POST['language'];
$problem  = $_POST['problem'];
$course   = $_POST['course'];
$p = $CFG->dbprefix;

ob_start();
$user="none";
$runs = 0;
$runs_cookie = $problem . '_runs';

// Handle cookies...the $runs_cookie corresponds to how
// many times a certain problem has been run...to make this
// information persistent accross sessions we query the database
// at each session start

//TODO: Completely remove coookies from this

if (isset($_COOKIE[$runs_cookie])){
  $runs=$_COOKIE[$runs_cookie];
  if (isset($_COOKIE[$problem])){
    $user=$_COOKIE[$problem];
  }else{
    $user = "anonymous";
  }
  $runs++;
  setcookie($runs_cookie, $runs);
}
else {
    // get the number of attempts from db
    $attempts = $PDOX->rowDie("SELECT {$problem}_attempts FROM {$p}apt_grader
      WHERE user_id = :UID", array(':UID' => $USER->id));
    // if the row exists
    $runs = 1;
    if ($attempts !== FALSE){
      $res = $attempts[$problem."_attempts"];
      $runs = $res + 1;
    }
    $user="apt".rand();
    setcookie($problem, $user, time()+60*60*5);
    setcookie($runs_cookie, $runs, time()+60*60*5);
}

$displayname = !isset($USER->displayname) ? "anonymous" : $USER->displayname;

$base = "./apt/";
$scratch_directory = $base."incoming/";
$gradedir = $base."gradesave";
$log = $base."log";
$gradelog = $base."gradelog";
$imports = "import re random math string operator";
$imports_array = explode(" ",$imports);

// inputdir
$inputdir = "";
$input_directory = $base.$inputdir;
$meta_directory = $base."meta";

// get whether we are testing or submitting
$previous = 'Testing';
if (isset($_SESSION['previous'])){
  $previous = $_SESSION['previous'];
}
$testing = $previous == 'Testing' ? True : False;

#

function get_tempdir_name(){
    $tempfile = tempnam('','');
#    if (file_exists($tempfile)) {
#        unlink($tempfile);
#    }
    return basename($tempfile);
}

// name of executable
$execname = "runpython";
$tester = "Tester.py";

###$user =     $_POST['user'];

$filename = basename($_FILES['upfile']['name']);
$ipaddress = $_SERVER['REMOTE_ADDR'];

echo "<html><head><title>APT: $problem</title>\n";
?>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="topstyle.css">
  <script src="https://code.jquery.com/jquery-1.12.3.min.js" integrity="sha256-aaODHAgvwQW1bFOGXMeX+pC4PZIPsvn2h1sArYOhgXQ=" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>
<body bgcolor="#ffffff" text="#000000">
<?php

// get correct link based on where you came from
$prev_link = $previous == "Testing" ? "apt_test.php" : "apt_submit.php";
$_SESSION['prev_link'] = $prev_link;
$prev_link_title = $previous == "Testing" ? "Test Files" : "Submit Files";

?>

<nav class="navbar nav navbar-default navbar-static-top">
  <div class="container">
    <ol class="breadcrumb">
      <li><a href="index.php">Home</a></li>
      <li> <?php echo "<a href = " . $prev_link . ">" . $prev_link_title . "</a>"; ?>
      </li>
      <li class="active"><?php echo $previous; ?></li>
    </ol>
    <p class="navbar-text navbar-right"><a href="gradebook.php" class="navbar-link">Gradebook</a></p>
    <?php if ($USER->instructor){ ?>
      <p class="navbar-text navbar-right"><a href="analytics.php" class="navbar-link analytics">Analytics</a> | </p>
    <?php } ?>
  </div>
</nav>

<?php

echo "<div class = 'container'>";
echo("<div class = \"center\"><h1>" .$previous." ".$problem."</h1>");
///echo  "<div class = \"center\"><h1>Submitting for Grading ".$problem."</h1>";
if ( isset($USER->displayname) ) {
    echo("<p>Hello ".$USER->displayname."</p>\n");
}

// Labels that appear below the title informing the user
// of the problem they are currently on

echo  "<span class=\"label label-default\">Problem: ". $problem. "</span>";
echo  "<span class=\"label label-primary\">Language: " .$language. "</span>";
echo  "<span class=\"label label-success\">Files: " .$filename. "</span></div>";

echo "<hr>";

// Toggle console (inside a panel)

?>

<div class="panel panel-default">
  <div class="panel-heading" role="tab" id="headingTwo">
    <div class="panel-title">
      <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
        Toggle Console
      </a>
    </div>
  </div>
  <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
    <div class="panel-body" style="padding:0px; font-family:Menlo; padding: 15px;">

<?php

// What follows is the logging code in which the problem
// is actually tested. This has been largely unchanged

echo  "<br>Number of " . $problem . " runs this session is: ".$runs."<P>";

$user = "anonymous user";
if ( isset($LAUNCH->user->email) ) {
    $user = $LAUNCH->user->email;
} else if (isset($_SERVER['REMOTE_USER'])) {
    $user = $_SERVER['REMOTE_USER'];
}
echo "user is: ".$user."<P>";


$tempdir = $scratch_directory.get_tempdir_name();
mkdir($tempdir);
if (! is_dir($tempdir)){
    echo "creating tempdir failed, bailing...for ".$tempdir;
    exit;
}

if (move_uploaded_file($_FILES['upfile']['tmp_name'],$tempdir.'/'.$filename)){
    echo "<P>upload ok, files moved: "; # move to ".$tempdir;
    passthru("ls $tempdir");
}
else {
    echo "upload issue on move ".$tempdir.'/'.$filename."<P>";
    exit;
}


function checkfile($fname){
   global $imports_array;

   $file = fopen(__DIR__.'/'.$fname,"r");
   if (! $file ) die("Unable to open $fname");
   while (!feof($file)){
      $line = fgets($file);
      $chunks = preg_split("/[\s,]+/",$line);
      if (count($chunks) > 0){
          if ($chunks[0] == 'import'){
              foreach ($chunks as $imp) {
                  $imp = trim($imp);
                  if (strlen($imp) > 1 && ! (in_array($imp, $imports_array))){
                      return $imp;
                  }
              }
          }
      }
   }
   return "Yes";
}

$probdir = $input_directory."/".$problem;
if (!file_exists($probdir."/Tester.py")) {
    echo "<b>Cannot find information for problem: ".$problem." in ".$probdir;
    echo "please contact your instructor.</b><P>";
}
else {
    $prob2copy = array('Tester.py', 'input');
    foreach ($prob2copy as $file) {
        if (!copy($probdir."/".$file, $tempdir."/".$file)){
           echo "failed to copy ".$file."<P>";
        }
    }
    $meta2copy = array('Parse.py', 'StoppableThread.py');
    foreach ($meta2copy as $file) {
        if (!copy($meta_directory."/".$file, $tempdir."/".$file)){
           echo "failed to copy ".$file."<P>";
        }
    }
}
$contents = file_get_contents($probdir."/prob.spec");

#
# change directory and compile
#
#
$currentdir = getcwd();
if (! chdir($tempdir)){
    echo "couldn't change directory to compile and run<p>";
    exit;
}

list($name,$ext) = explode(".",$filename);

#did user submit right file?

$perc = "ok";
#$contents = file_get_contents($probdir."/prob.spec");
$all = explode("\n",$contents);
$anames = explode(":",$all[0]);
$expected = trim($anames[1]);

if ($expected != $name){
    echo "Wrong file name, expected ".$expected." got ".$name."<P>";
    $perc = "wrongclass\n";
}

$result = checkfile($tempdir."/".$filename);
echo "<p>".$result."</p>";
if ($result != "Yes"){
  $perc = "bad import: ".$result;
#  print_r($imports_array);
#  if (in_array($result,$imports_array)){
#     echo "<P>".$result." found<p>";
#  }
  echo "bad import statement, '".$result."' legal imports are ".$imports."<P>";
  echo "check with your professor<P>";
}

echo "<p>".$perc."</p>";
if ($perc == "ok") {
  echo "<P>Compiling...<P>";
  usleep(100);
  ob_flush();
  flush();

#passthru("/usr/local/bin/javac ".$filename." 2>&1");
#if (!file_exists($tempdir."/".$name.".class")){
  if (1 == 2) {
    echo "<P><b> could not compile ".$filename." to $name.class<P></b>";
    $perc = "nocompile\n";
  }
  else {
    echo "compile succeeded";
    echo "<P><b>Program running:</b> standard output below";
    echo "<P>(if you don't see output immediately, wait ... your<br>";
    echo "code may have time-limit exceeded problems)<br>\n";
    usleep(100);
    ob_flush();
    flush();
    #$line = system("/usr/local/bin/python Tester.py > $problem.out");
    $line = system("python Tester.py > $problem.out");
    passthru("cat $problem.out");
    echo "<P>\n";
    echo "<b>Test Results Follow (scroll to see all)</b><p>";

    echo "</div></div></div>";

    // Below is the navigation bar for filtering the results
    // of the above test.

    ?>

    <nav class="navbar navbar-default">
    <div class="container-fluid">
      <!-- Brand and toggle get grouped for better mobile display -->
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="#">Results</a>
      </div>

      <!-- Collect the nav links, forms, and other content for toggling -->
      <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
        <ul class="nav navbar-nav">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Filter Results <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="#" onclick="showPassed()">Show Passed</a></li>
              <li><a href="#" onclick="showFailed()">Show Failed</a></li>
              <li role="separator" class="divider"></li>
              <li><a href="#" onclick="reset()">Reset</a></li>
            </ul>
          </li>
        </ul>

        <ul class="nav navbar-nav navbar-right">
          <form class="navbar-form navbar-left" role="search">
            <div class="form-group">
              <input id = "search" type="text" class="form-control" placeholder="Search">
            </div>
          </form>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
    </nav>

    <?php

    echo "<table class='table table-bordered'\n";
    passthru("cat results");
    echo ("\n</table>\n");

    $perc = file_get_contents("perc");
    if ($perc === FALSE){
        $perc = "nocompile\n";
    }
  }
}
## log entries

$netid = "";
$probdir = "";

if ( isset($LAUNCH->result) && !$USER->instructor) {
  // grade to pass to server/sakai
  $gradetosend = $perc+0.0;
  // column entry in table
  $grade_entry = $problem . "_grade";
  $attempt_entry = $problem . "_attempts";

  // Insert / update the database with the new information
  // (new number of attempts + grade if higher than previous)

  $PDOX->queryDie("INSERT INTO {$p}apt_grader
      (display_name, link_id, user_id, {$attempt_entry}, {$grade_entry})
      VALUES ( :DNAME, :LI, :UI, :COUNT, :GRADE)
      ON DUPLICATE KEY UPDATE display_name=:DNAME, {$attempt_entry}=:COUNT,
      {$grade_entry} = CASE WHEN {$grade_entry} < :GRADE THEN :GRADE ELSE {$grade_entry} END
      ",
      array(
          ':DNAME' => $displayname,
          ':LI' => $LINK->id,
          ':UI' => $USER->id,
          ':COUNT' => $runs,
          ':GRADE' => $gradetosend
      )
  );

  // If we are not testing (came here from submit) and also this
  // is the problem that you are submitting, the grade is sent
  // back to the LMS gradebook
  if (!$testing){

    # dynamically generating query fields
    $grade_cols = array();
    foreach ($problems as $problem){
      array_push($grade_cols, $problem . "_grade");
    }
    $str_query = implode('+', $grade_cols);
    $str_query = '(' . $str_query . ')';
    $str_query .= "/".count($problems);

    // get average of all grades
    $avg = $PDOX->rowDie("SELECT {$str_query} from {$p}apt_grader
      WHERE user_id = :UID", array(':UID' => $USER->id));

    // Send average grade back over LTI
    $retval = $LAUNCH->result->gradeSend($avg[$str_query]);
    $msg = 'Grade '.$avg[$str_query].' sent by '.$USER->id;
    if ($debug){
      echo $msg;
      echo("<p>Result of grade send: ");var_dump($retval);echo("</p>\n");
    }
  }

}

#
# This section removes files created and copied
# for testing
#
chdir($currentdir);
#echo "clean up<P>";
$rmfiles = array('Tester.py', 'input','Parse.py','StoppableThread.py');
if ($handle = opendir($tempdir)) {
    foreach ($rmfiles as $file) {
        if (!unlink($tempdir.'/'.$file)){
           echo "failed to remove ".$file."<P>";
        }
    }
    while (($torm = readdir($handle))) {
        if (!is_dir($torm)){
           ##echo "<P>should remove ".$torm;
           $sub = substr($torm,-3);
           if ($sub == ".py" || $torm == "perc") {
                $cpme = $tempdir."/".$torm;
	        #echo "copying ".$cpme." to ".$probdir."<P>";
                passthru("cp ".$cpme." ".$probdir);
                #don't remove
           }
           else {
	     if (!unlink($tempdir.'/'.$torm)) {
               echo "removal of $torm failed<P>";
              }
              else {
                #echo "<P>removed ".$torm." ".$sub;
              }
	   }
        }
    }
}

# Link to frontend script
echo "<script src = \"app.js\"></script>";

echo "</div></body></html>\n";


?>
