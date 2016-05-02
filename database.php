<?php

require_once "config.php";

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}apt_grader"
);
// The SQL to create the tables if they don't exist

$command = "create table {$CFG->dbprefix}apt_grader (
    display_name   varchar(64) NOT NULL,
    link_id        INTEGER NOT NULL,
    user_id        INTEGER NOT NULL,\n";

// store # of attempts and top grade for each problem
foreach ($problems as $problem){
  $command .= "    " . $problem . "_grade FLOAT NOT NULL,\n";
  $command .= "    " . $problem . "_attempts INTEGER NOT NULL,\n";
}

$command .= "
CONSTRAINT `{$CFG->dbprefix}apt_grader_ibfk_1`
    FOREIGN KEY (`link_id`)
    REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `{$CFG->dbprefix}apt_grader_ibfk_2`
    FOREIGN KEY (`user_id`)
    REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
UNIQUE(link_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8";

$DATABASE_INSTALL = array(
  array( "{$CFG->dbprefix}apt_grader", $command)
);
