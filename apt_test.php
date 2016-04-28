<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>APT: CompSci 101  Web Testing</title>
 
<style type="text/css">

body {
  counter-reset: globalcounter;
}

TABLE {
   border: 1px solid black;
   border-collapse: collapse;
   counter-reset: aptcounter;
   counter-increment: tablecounter;
}
TD{
   border: 1px solid black;
   padding: 3px;
}
TH{
   border: 1px solid black;
   padding: 5px;
   background-color: #B0B0B0;
}
TD.probdesc{
   border-top: 1px solid black;
   border-bottom: 1px solid black;
   background-color: #B0B0B0;
}
TD.probdesc:before{
#  content: "(" counter(tablecounter) ") ";
  background-color: #B0B0B0;
}

TD.submit{
  border-top: 1px solid black;
  background-color: #B0B0B0;
}

TD.numbered {
  border: 0px solid black;
  background-color: #DCDCDC;
}

#TD.numbered:before{
#  content: counter(globalcounter)" "counter(aptcounter)". ";
#  counter-increment: aptcounter,globalcounter;
#}

TD.hint {
  border: 0px solid white;
  background-color: #DCDCDC;
}


body {
  background-color: #E8E8E8;
}
</style>


  </head>

  <body>

    <h1>APT: CompSci 101, Spring 2016, APT </h1>
<P>
This is the testing page. Once your program works here, you need to run
your APT on the submit page (back on the previous page).  
</p>

<?php

$course = "compsci101";    # this was "newapt"
$apturl = "http://www.cs.duke.edu/csed/pythonapt/";
$apturl = "http://localhost:8888/apt_files";
$name= "apt.txt";
$upload="https://cgi.cs.duke.edu/~ola/";
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
       '<a href="'.$apturl.$data[2]. '" target="_top">'.$data[1].'</em></A><br>';



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
$chooseBoiler .= '<center><input type="submit" value="test/run"></center></form>';

for ($k=0; $k < $groupCount; $k++){
   print '<table>';
   print '<th width="250px"> Problem Set '.($groupCount-$k);  #list backwards
#   print '<th width="250px"> Problem Set '.($k+1);   # list in order
   print '<th width="450px"> Details';
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

  </body>
</html>
