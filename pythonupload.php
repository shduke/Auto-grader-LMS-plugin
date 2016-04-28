<?php
    session_start();
    require_once "config.php";
    use \Tsugi\Core\LTIX;

    $LAUNCH = LTIX::session_start();

ob_start();
$user="none";
if (isset($_COOKIE['apt'])){
    $user=$_COOKIE['apt'];
    $runs=$_COOKIE['aptaccess'];
    $runs++;
    setcookie('aptaccess',$runs);
}
else {
    $runs = 1;
    $user="apt".rand();
    setcookie('apt', $user, time()+60*60*5);
    setcookie('aptaccess', $runs, time()+60*60*5);
}
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
$language = $_POST['language'];
$problem  = $_POST['problem'];
$course   = $_POST['course'];

$filename = basename($_FILES['upfile']['name']);
$ipaddress = $_SERVER['REMOTE_ADDR'];

echo "<html><head><title>APT: $problem</title>\n";
echo  "<link rel=\"stylesheet\" type=\"text/css\" href=\"topstyle.css\">\n";
echo  "</head>\n";
echo  "<body bgcolor=\"#ffffff\" text=\"#000000\">\n";
echo  "<h1>Submitting for Grading ".$problem."</h1>";
if ( isset($USER->displayname) ) {
    echo("<p>Hello ".$USER->displayname."</p>\n");
}
echo  "<b>Problem</b>: ". $problem. "<br>\n";
echo  "<b>Language</b>: ". $language. "<br>\n";
echo  "<b>Files</b>: ";
echo  $filename;
echo  "<br>Number of APT runs this session is: ".$runs."<P>";

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
    echo "code may have time-limit exceeded problems)<br><hr><pre>\n";
    usleep(100);
    ob_flush();
    flush();
    #$line = system("/usr/local/bin/python Tester.py > $problem.out");
    $line = system("python Tester.py > $problem.out");
    passthru("cat $problem.out");
    echo "</pre><hr><P>\n";
    echo "<b>Test Results Follow (scroll to see all)</b><p>";
    echo "<table class=border\n";
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

if ( isset($LAUNCH->result) ) {
    $gradetosend = $perc+0.0;
    $retval = $LAUNCH->result->gradeSend($gradetosend);
    echo("<p>Result of grade send: ");var_dump($retval);echo("</p>\n");
}

if ($user != "anonymous user"){
   $netid = substr($user,0,strpos($user,"@"));
   echo "<h2>Logging Results for ".$problem."</h2>";
   echo "<ul>";
   echo "<li> netid is ".$netid."</li>";
}
if (!$gradehandle = fopen(__DIR__.'/'.$gradelog,'a')){
    echo "could not open grade log file $gradelog<P>";
}

if (!$handle = fopen(__DIR__.'/'.$log,'a')){
    echo "could not open log file $log<P>";
}
else {
   $tt = time();
   $logentry = $user.":".$tt.":".$ipaddress.":".$problem.":".$course.":".$perc;
   if (!fwrite($handle,$logentry)){
       echo "could not write to log file<P>";
   }   
   else {
       echo "<li>logged entry score = ".$perc."</li>";
   }
   if (strlen($netid) >= 1){
       #echo "logging submission for ".$user."<P>";
       $logentry = $netid.":".$tt.":".$ipaddress.":".$problem.":".$course.":".$perc;
       if (!fwrite($gradehandle,$logentry)){
	      echo "could not write to gradelog file<P>";
       }	  
       $netdir = $gradedir."/".$netid;
       if (!is_dir($netdir)){
           mkdir($netdir);
	   #echo "creating save directory for ".$netid."<P>";
       }
       $probdir = $netdir."/".$problem;
       if (!is_dir($probdir)){
           mkdir($probdir);
	   echo "<li>creating save directory for ".$netid." on ".$problem."</li>";
       }      
       echo "</ul>";
       #copy files
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

#if (!rmdir($tempdir)) {
#   echo "removing tempdir failed<P>";
#}
#else {
#   echo "<P>all finished<P>";
#}
echo "</body></html>\n";


?>
