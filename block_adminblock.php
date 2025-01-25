<?php
defined('MOODLE_INTERNAL') || die();

class block_adminblock extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_adminblock');
    }

    public function get_content() {
		global $CFG,$DB;
        if ($this->content !== null) {
            return $this->content;
        }

        // Restrict access to admin only
        global $USER, $OUTPUT;
        if (!is_siteadmin($USER)) {
            return null;
        }
		
		?>
		


<?php

        $this->content = new stdClass();
		$objCourses = $DB->get_records_sql("select * from {course} where id > 1");
		$vOutput = '<table id="example" class="table table-bordered generaltable flexible boxaligncenter completionreport">';
		$vOutput .= '<tr>';
		$vOutput .= '<th>Course Name</th>';
		$vOutput .= '<th>Enrolled</th>';
		$vOutput .= '<th>Not Started</th>';
		$vOutput .= '<th>In-Progress</th>';
		$vOutput .= '<th>Completed</th>';
		$vOutput .= '<th>Actions</th>';
		$vOutput .= '<tr>';
		foreach($objCourses as $course){
			
			//Get enrolled users
			$query = "select b.userid from {$CFG->prefix}context a,{$CFG->prefix}role_assignments b where a.contextlevel = 50 and a.instanceid  = $course->id and a.id = b.contextid and b.roleid = 5";
		
			$objEnrolled = $DB->get_records_sql($query);
			
			$vCount = 0;
			$arrEnrolled = array();
			foreach($objEnrolled as $enrolled){
				$arrEnrolled[$vCount] = $enrolled->userid;
				$vCount++;
			}
			
			
			//Get started users		
		
			$objStarted = $DB->get_records('user_lastaccess',array("courseid"=>$course->id));
			
			$vCount = 0;
			$arrStarted = array();
			foreach($objStarted as $started){
				$arrStarted[$vCount] = $started->userid;
				$vCount++;
			}
			
			
		
		
			//Get completed users
			$objCompleted = $DB->get_records("course_completions",array("course"=>$course->id));
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
			
			
			$vOutput .= '<tr>';
			$vOutput .= '<td>'.$course->fullname.'</td>';
			if(count($arrEnrolled) > 0){
			$vLink = '<a href="'.$CFG->wwwroot.'/blocks/adminblock/detail_report.php?status=enrolled&courseid='.$course->id.'">'.count($arrEnrolled).'</a>';
			}else{
				$vLink = count($arrEnrolled);
			}
			$vOutput .= '<td align="center">'.$vLink.'</td>';
			
		if(count($arrNotStarted) > 0){
			$vLink = '<a href="'.$CFG->wwwroot.'/blocks/adminblock/detail_report.php?status=notstarted&courseid='.$course->id.'">'.count($arrNotStarted).'</a>';
			}else{
				$vLink = count($arrNotStarted);
			}
			
			$vOutput .= '<td align="center">'.$vLink.'</td>';
			
					if(count($arrInprogress) > 0){
			$vLink = '<a href="'.$CFG->wwwroot.'/blocks/adminblock/detail_report.php?status=inprogress&courseid='.$course->id.'">'.count($arrInprogress).'</a>';
			}else{
				$vLink = count($arrInprogress);
			}
			
			
			$vOutput .= '<td align="center">'.$vLink.'</td>';
			
			if(count($arrCompleted) > 0){
			$vLink = '<a href="'.$CFG->wwwroot.'/blocks/adminblock/detail_report.php?status=completed&courseid='.$course->id.'">'.count($arrCompleted).'</a>';
			}else{
				$vLink = count($arrCompleted);
			}
			
			
			
			$vOutput .= '<td align="center">'.$vLink.'</td>';
			
			//Calculate completion progress
			$vCompletionProgress = 0;
			$vCompletionProgress = (count($arrCompleted) / count($arrEnrolled)) * 100;
			$vOutput .= '<td><a href="">
			<label for="file">Completion progress: '.$vCompletionProgress.'%</label>
<progress id="file" value="'.$vCompletionProgress.'" max="100"> '.$vCompletionProgress.'% </progress>
<br>
			Send reminder to incomplete users</a></td>
			';
			$vOutput .= '</tr>';
		}
		$vOutput .= '</table><br>';
	
		
        $this->content->text = $vOutput;
        /*$this->content->footer = $OUTPUT->single_button(
            new moodle_url('/admin/settings.php'),
            get_string('adminsettings', 'block_adminblock')
        );*/

        return $this->content;
    }
}
