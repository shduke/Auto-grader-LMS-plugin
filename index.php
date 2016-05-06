<?php
    require_once "config.php";
    use \Tsugi\Core\LTIX;

    $LAUNCH = LTIX::session_start();
    $title = "APTs";
?>

<html>
  <head>
    <link rel="stylesheet" type="text/css" href="topstyle.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  </head>
  <body>

  <nav class="navbar nav navbar-default navbar-static-top">
    <div class="container">
      <ol class="breadcrumb">
        <li>Home</li>
      </ol>
      <p class="navbar-text navbar-right"><a href="gradebook.php" class="navbar-link">Gradebook</a></p>
      <?php if ($USER->instructor){ ?>
        <p class="navbar-text navbar-right"><a href="analytics.php" class="navbar-link analytics">Analytics</a> | </p>
      <?php } ?>
    </div>
  </nav>

  <div class="container">

  <div class="center header">
    <h1>APTs</h1>
  </div>

  <?php
      if ( isset($USER->displayname) ) {
          echo("<p>Hello ".$USER->displayname."</p>\n");
      }
  ?>

  <p> For each problem  in an APT set, complete these steps by the due date
  <ul>
  <li> first test it and get it working correctly using the <strong>Test
  </strong> link
  <li> then submit the code for grading  using the <strong>Submit
  </strong>  link
  <li> <strong>check your grade </strong> on the grade code page
  by clicking on <strong>check submissions</strong>
  <li> then fill out the README form
  </ul>

  <p> In solving APTs, your program should work for all cases, not just the
  test cases we provide. We may test your program on additional data.

  <p>
  <table class = "table table-bordered">
  <tr> <th> APT </th> <th>Test code  </th> <th> Grade code </th> <th> Finish it
  </th>
  <th> Due Date </th>
  </tr>
  <tr>
  <td>
  <em>APT 1</em>
  </td>
  <td> <a
  href="apt_test.php"> Test </a>
  </td>
  <td> <a
  href="apt_submit.php"> Submit
  </a>
  </td>
  <td>
  <a href="http://goo.gl/forms/3IWpxnYfSN"> README </a>
  </td>
  <td> Jan 28 </td>
  </tr>

  </table>

  <p>
  We may do some APTs partially in class or lab, but you still have to do
  them and submit them.
  There will usually be extra apts listed. You can do more than required to
  challenge yourself. We do notice
  if you do more APTs than those required.
  If you do extra APTs, they still have to be turned in on the due date.

  </div> <!-- Container end -->

  </body>
</head>
