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
        <ol class="breadcrumb">
         <li><a href="index.php">Home</a></li>
        </ol>
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
    var problems = <?php echo json_encode($problems); ?>;

    // Parse the data into a format that C3 can read
    function parseData(data, problems){
      var names = [];

      // for each problem
      var grades = {};
      var attempts = {};
      var efficiency = {};
      var averages = {};

      for (var i = 0; i < problems.length; i++){
        grades[problems[i]] = [];
        attempts[problems[i]] = [];
        efficiency[problems[i]] = [];
        averages[problems[i]] = [];
      }

      for (var i = 0; i < data.length; i++){
        names.push(data[i].display_name);
        // efficiency.push(data[i].run_count / data[i].top_grade);
        // populate grades
        for (var j = 0; j < problems.length; j++){
          grades[problems[j]].push(data[i][problems[j] + '_grade']);
          attempts[problems[j]].push(data[i][problems[j] + '_attempts']);
        }
      }

      // helper function for averages
      function avg(arr) {
        var sum = 0;
        console.log(arr);
        for( var i = 0; i < arr.length; i++ ){
            sum += parseFloat( arr[i], 10 );
        }
        return sum / arr.length;
      }

      // computing avgs
      for (var i = 0; i < problems.length; i++){
        averages[problems[i]]['grades'] = avg(grades[problems[i]]);
        averages[problems[i]]['attempts'] = avg(attempts[problems[i]]);
      }

      return {
        'names': names,
        'grades': grades,
        'attempts': attempts,
        'efficiency': efficiency,
        'averages': averages
      };

    }

    var parsed_data = parseData(data, problems);
    console.log(parsed_data);
    var ctx = document.getElementById("gradeChart").getContext("2d");

    // create all datasets
    datasets = [];
    for (var i = 0; i < problems.length; i++){
      datasets.push({
        label: problems[i] + " Grade",
        data: parsed_data.grades[problems[i]]
      });
      datasets.push({
        label: problems[i] + " Attempts",
        data: parsed_data.attempts[problems[i]]
      })
    }

    var chart_data = {
        labels: parsed_data.names,
        datasets: datasets
    };

    // Create the chart.js element
    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: chart_data
    });

    // Write out the data so you can figure out how to format it
    // LIKE ON PAPER!

    // var myBarChart = new Chart(ctx, {
    //     type: 'bar',
    //     data: {
    //       labels: problems,
    //       datasets: [
    //         {
    //           label: 'Average grades',
    //           data: parsed_data.averages.grades
    //         },
    //         {
    //           label: 'Average attempts',
    //           data: parsed_data.averages.attempts
    //         }
    //       ]
    //     }
    // });

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
