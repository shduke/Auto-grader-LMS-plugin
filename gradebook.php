<?php

require_once('config.php');
use \Tsugi\Core\LTIX;

// Launch a tsugi session
$LAUNCH = LTIX::session_start();

?>

<html>
  <head>
    <meta charset="utf-8">
    <title>Gradebook</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="topstyle.css">
  </head>
  <body>

    <nav class="navbar nav navbar-default navbar-static-top">
      <div class="container">
        <ol class="breadcrumb">
          <li><a href="index.php">Home</a></li>
        </ol>
        <!-- <p class="navbar-text navbar-right"><a href="gradebook.php" class="navbar-link">Gradebook</a></p> -->
        <?php if ($USER->instructor){ ?>
          <p class="navbar-text navbar-right"><a href="analytics.php" class="navbar-link">Analytics</a></p>
        <?php } ?>
      </div>
    </nav>

    <div class="center">
      <h1>Gradebook</h1>
    </div>

    <div class="container">

  <?php

    # dynamically generating query fields
    $query = array();
    array_push($query, 'display_name');
    foreach ($problems as $problem){
      array_push($query, $problem . "_grade", $problem . "_attempts");
    }
    $str_query = implode(', ', $query);

    // get database prefix
    $p = $CFG->dbprefix;

    if ($USER->instructor){
      // initialize result to empty array
      $query = array();
      // Get all data from table
      if (isset($LAUNCH)){
        $all_grades = $PDOX->allRowsDie("SELECT {$str_query} FROM {$p}apt_grader",
            array(':UI' => $USER->id)
        );

        // creating the table

        echo "<table class = 'table table-bordered'>";
        echo "<tr>";
        echo "<th style = 'border: none;'></th>";
        foreach ($problems as $problem){
          echo "<th>" . $problem . " grade" . "</th>";
          echo "<th>" . $problem . " attempts" . "</th>";
        }
        echo "</tr>";

        // iterate over each entry in each row
        foreach ($all_grades as $row){
          echo "<tr>";
          foreach ($row as $entry){
            echo "<td>" . $entry . "</td>";
          }
          echo "</tr>";
        }

        echo "</table>";

      }
    }else{
      $student_grades = $PDOX->rowDie("SELECT {$str_query} FROM {$p}apt_grader
        WHERE user_id = :UID", array(':UID' => $USER->id));

      echo "<table class = 'table table-bordered'>";
      echo "<tr><td></td><td>Grade</td><td>Attempts</td>";
      // iterate over each entry
      foreach ($problems as $problem){
        echo "<tr>";
        echo "<td>" . $problem . "</td>";
        echo "<td>" . $student_grades[$problem . "_grade"] . "</td>";
        echo "<td>" . $student_grades[$problem . "_attempts"] . "</td>";
        echo "</tr>";
      }
      echo "</table>";

    }
  ?>

    </div>

  </body>
</html>
