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
    <script src="https://code.jquery.com/jquery-1.12.3.min.js" integrity="sha256-aaODHAgvwQW1bFOGXMeX+pC4PZIPsvn2h1sArYOhgXQ=" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
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

  <div class="container">
    <div class="center">
      <h1>Grades</h1>
    </div>

    <p>
      Welcome to APT Analytics. Here you can visualize data on the student's APT performance.
    </p>

    <!-- Toolbar -->

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
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Datasets<span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="#" onclick="showAverages()">Show Averages</a></li>
              <li><a href="#" onclick="showAll()">Show All</a></li>
              <li role="separator" class="divider"></li>
              <li><a href="#" onclick="viewJSON()">View Raw data</a></li>
            </ul>
          </li>
        </ul>

        <!-- <ul class="nav navbar-nav navbar-right">
          <form class="navbar-form navbar-left" role="search">
            <div class="form-group">
              <button class = "btn" type="button" name="button">Options</button>
            </div>
          </form>
        </ul> -->

      </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
    </nav>


    <div class="center">
      <canvas id="gradeChart" width="600" height="300"></canvas>
    </div>
    <pre class = "dump" style = "display: none;">

    </pre>
  </div>

  <script>

    // dropdown
    $('.dropdown-toggle').dropdown();

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
        for( var i = 0; i < arr.length; i++ ){
            sum += parseFloat( arr[i], 10 );
        }
        return sum / arr.length;
      }

      // computing avgs
      for (var i = 0; i < problems.length; i++){
        averages[problems[i]]['grade'] = avg(grades[problems[i]]);
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

    // get grade averages
    var avg_array = [];
    var attempts_array = [];
    // iterate over each problem and retrieve average/attempts
    for (var i = 0; i < problems.length; i++){
      avg_array.push(parsed_data.averages[problems[i]].grade);
      attempts_array.push(parsed_data.averages[problems[i]].attempts);
    }

    console.log(avg_array);
    console.log(attempts_array);

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

    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: chart_data
    });

    // Create the chart.js element
    function showAll(){
      myBarChart.destroy();
      $('#gradeChart').show();
      $('.dump').hide();
      myBarChart = new Chart(ctx, {
          type: 'bar',
          data: chart_data
      });
    }

    // show averages
    function showAverages(){

      //destroy old chart
      myBarChart.destroy();
      $('#gradeChart').show();
      $('.dump').hide();
      myBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: problems,
          datasets: [
            {
              label: 'Average grades',
              data: avg_array
            },
            {
              label: 'Average attempts',
              data: attempts_array
            }
          ]
        }
      })

    }

    function viewJSON(){
      // destroy the chart
      myBarChart.destroy();
      $('#gradeChart').hide();
      $('.dump').show().text(JSON.stringify(data, null, 4));
    }

  </script>

  <?php
} else{
  echo "you need instructor access to view this page";
}
  ?>

  </body>
</html>
