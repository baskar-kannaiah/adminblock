<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * My Moodle -- a user's personal dashboard
 *
 * - each user can currently have their own page (cloned from system and then customised)
 * - only the user can see their own dashboard
 * - users can add any blocks they want
 * - the administrators can define a default site dashboard for users who have
 *   not created their own dashboard
 *
 * This script implements the user's view of the dashboard, and allows editing
 * of the dashboard.
 *
 * @package    moodlecore
 * @subpackage my
 * @copyright  2010 Remote-Learner.net
 * @author     Hubert Chathi <hubert@remote-learner.net>
 * @author     Olav Jordan <olav.jordan@remote-learner.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/my/lib.php');

redirect_if_major_upgrade_required();

// TODO Add sesskey check to edit
$edit   = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off
$reset  = optional_param('reset', null, PARAM_BOOL);

require_login();

// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);

if(isset($_GET["status"])){
	$vStatus = $_GET["status"];
} 
if($vStatus == 'enrolled'){
	$vHeading = 'Enrolled Users';
}else if($vStatus == 'notstarted'){
	$vHeading = 'Not Started Users';
}else if($vStatus == 'inprogress'){
	$vHeading = 'Inprogress Users';
}else if($vStatus == 'completed'){
	$vHeading = 'Completed Users';
}
if(isset($_GET["courseid"])){
	$vCourseId = $_GET["courseid"];
}

$objRecord = $DB->get_record('course',array("id"=>$vCourseId));
$vHeading = $objRecord->fullname.' ['.$vHeading.']';
$PAGE->set_heading($vHeading);


echo $OUTPUT->header();




?>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap.js"></script>

<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap.css">

<script src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.print.min.js"></script>

<link href='https://cdn.datatables.net/buttons/1.6.2/css/buttons.dataTables.min.css' rel='stylesheet' type='text/css'>

<table id="example" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Firstname</th>
                <th>Lastname</th>                
				<th style="text-align:center">Actions</th>                
            </tr>
        </thead>
        <tbody>
		<?php
		
		
			//Get enrolled users
			$query = "select b.userid from {$CFG->prefix}context a,{$CFG->prefix}role_assignments b where a.contextlevel = 50 and a.instanceid  = $vCourseId and a.id = b.contextid and b.roleid = 5";
		
			$objEnrolled = $DB->get_records_sql($query);
			
			$vCount = 0;
			$arrEnrolled = array();
			foreach($objEnrolled as $enrolled){
				$arrEnrolled[$vCount] = $enrolled->userid;
				$vCount++;
			}
		
			
			//Get started users		
		
			$objStarted = $DB->get_records('user_lastaccess',array("courseid"=>$vCourseId));
			
			$vCount = 0;
			$arrStarted = array();
			foreach($objStarted as $started){
				$arrStarted[$vCount] = $started->userid;
				$vCount++;
			}
			
			
		
		
			//Get completed users
			$objCompleted = $DB->get_records("course_completions",array("course"=>$vCourseId));
			$vCount = 0;
			$arrCompleted = array();
			foreach($objCompleted as $completed){
				$arrCompleted[$vCount] = $completed->userid;
				$vCount++;
			}
	
				//Get not started users
				$arrNotStarted = array();
				$vCount = 0;
				$arrInprogress = array();
				$vCount = 0;
		
			foreach($arrEnrolled as $userid){
				if(!in_array($userid,$arrStarted) && !in_array($userid,$arrCompleted)){
				$arrNotStarted[$vCount] = $userid;
				$vCount++;
				}else if(in_array($userid,$arrStarted) && !in_array($userid,$arrCompleted)){
				$arrInprogress[$vCount] = $userid;
				$vCount++;
				}
			}
			
			
		if($vStatus == 'enrolled'){
		
		
			 $vUsersId = implode(',',$arrEnrolled);
			
			 $query = "select * from {$CFG->prefix}user where id in ($vUsersId)";
		$objRecords = $DB->get_records_sql($query);
			
	
		}else if($vStatus == 'notstarted'){
		$vUsersId = implode(',',$arrNotStarted);
			
			$objRecords = $DB->get_records_sql("select * from {$CFG->prefix}user where id in ($vUsersId)");
			
		}else if($vStatus == 'inprogress'){
		$vUsersId = implode(',',$arrInprogress);
			
			$objRecords = $DB->get_records_sql("select * from {$CFG->prefix}user where id in ($vUsersId)");
			
		}else if($vStatus == 'completed'){
		$vUsersId = implode(',',$arrCompleted);
			
			$objRecords = $DB->get_records_sql("select * from {$CFG->prefix}user where id in ($vUsersId)");
			
		}
		
		foreach($objRecords as $record){
		?>
            <tr>
                <td><a href="<?php echo $CFG->wwwroot;?>/user/editadvanced.php?id=<?php echo $record->id;?>&course=1">
				<?php echo $record->username;?></td>
				<td><?php echo $record->email;?></td>
				<td><?php echo $record->firstname;?></td>
				<td><?php echo $record->lastname;?></td>
				<td align="center">
				<a href="<?php echo $CFG->wwwroot;?>/user/editadvanced.php?id=<?php echo $record->id;?>&course=1;?>">
				<i class="icon fa fa-cog fa-fw " title="Edit" role="img" aria-label="Edit"></i></a>
				
				</td>
                
            </tr>
          <?php
		}
		  ?>
           <tbody>
			</table>
			
			<script>
					
$(document).ready(function () {
    $('#example').DataTable({
		'pageLength': 10,
		 "bLengthChange" : false,
        'dom': 'lBfrtip',
      'buttons': [
            'excelHtml5',
            'pdfHtml5'
        ]
	});

});
 </script>	
			

<?php

echo $OUTPUT->footer();

?>

<style>
.form-inline{
	display:block !important;
}
.dt-search{
	float:right;
	padding:10px;
}
.dt-paging{
	float:right;
	padding:10px;
}
</style>