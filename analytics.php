<?php
  // Tsugi setup
  require_once "config.php";
  use \Tsugi\Core\LTIX;

  $LAUNCH = LTIX::session_start();

?>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>APT Analytics</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.0.1/Chart.bundle.min.js"></script>
    <!-- Styles -->
    <link rel="stylesheet" type="text/css" href="topstyle.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  </head>
  <body>

    <!-- Nabar -->
    <nav class="navbar nav navbar-default navbar-static-top">
      <div class="container">
        <!-- <ol class="breadcrumb">
         <li> <?php echo "<a href = " . $prev_link . "> <- Back</a>"; ?>
        </ol>
        <?php if ($USER->instructor){ ?>
          <p class="navbar-text navbar-right"><a href="analytics.php" class="navbar-link">Analytics</a></p>
        <?php } ?> -->
      </div>
    </nav>


  <?php
    if ($USER->instructor){

      // initialize result to empty array
      $query = array();
      // Get all data from table
      $p = $CFG->dbprefix;
      if (isset($LAUNCH)){
        $query = $PDOX->allRowsDie("SELECT * FROM {$p}apt_grader",
            array(':UI' => $USER->id)
        );
      }
  ?>

  <div class="container center">
    <h1>Grades</h1>

    <h3>Statistics</h3>

    <?php
      // compute statistics for grades
      echo "Average grade: ";

    ?>

    <div class="center">
      <canvas id="gradeChart" width="600" height="300"></canvas>
    </div>
  </div>

  <script>

    // Inject sql query into javscript for processing
    var admin = <?php
      $admin = $USER->instructor ? "true" : "false";
      echo $admin;
    ?>;
    var data = <?php echo json_encode($query); ?>;
    console.log(data);

    // Parse the data into a format that C3 can read
    function parseData(data){
      var names = [];
      var grades = [];

      for (var i = 0; i < data.length; i++){
        names.push(data[i].display_name);
        grades.push(data[i].top_grade);
      }

      return {
        'names': names,
        'grades': grades
      };

    }

    var parsed_data = parseData(data);
    var ctx = document.getElementById("gradeChart").getContext("2d");

    var chart_data = {
        labels: parsed_data.names,
        datasets: [
            {
                label: "Grades",
                backgroundColor: "rgba(255,99,132,0.2)",
                borderColor: "rgba(255,99,132,1)",
                borderWidth: 1,
                hoverBackgroundColor: "rgba(255,99,132,0.4)",
                hoverBorderColor: "rgba(255,99,132,1)",
                data: parsed_data.grades,
            }
        ]
    };

    // Create the chart.js element
    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: chart_data
    });

  </script>

  <?php
} else{
  echo "you need instructor access to view this page";
}
  ?>

  </body>
</html>

<?php


?>
