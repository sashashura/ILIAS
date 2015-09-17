<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Utilities for generali users.
*
* @author	Richard Klees <richard.klees@concepts-and-training.de>
* @version	$Id$
*/
require_once("Services/GEV/Utils/classes/class.gevBuildingBlockUtils.php");

class gevCourseBuildingBlockUtils {
	static protected $instances = array();
	const TABLE_NAME = "dct_crs_building_block";
	const TABLE_NAME_JOIN1 = "dct_building_block";
	const DURATION_PER_POINT = 45;
	const MAX_DURATION_MINUTES = 720;

	protected $course_building_block_id = "";
	protected $crs_id = null;
	protected $building_block = "";
	protected $start_time = "";
	protected $end_time = "";
	protected $crs_request_id = null;
	protected $credit_points = 0;

	protected function __construct($a_course_building_block_id) {
		global $ilDB, $ilUser;
				
		$this->course_building_block_id = $a_course_building_block_id;
		$this->db = $ilDB;
		$this->ilUser = $ilUser;
	}

	public function getInstance($a_course_building_block_id) {
		if (array_key_exists($a_block_unit_id, self::$instances)) {
			return self::$instances[$a_course_building_block_id];
		}
		
		self::$instances[$a_course_building_block_id] = new gevCourseBuildingBlockUtils($a_course_building_block_id);
		return self::$instances[$a_course_building_block_id];
	}

	public function getId() {
		return $this->course_building_block_id;
	}

	public function getCrsId() {
		return $this->crs_id;
	}

	public function setCrsId($a_crs_id) {
		$this->crs_id = $a_crs_id;
	}

	public function getStartTime() {
		return $this->start_time;
	}

	public function setStartTime($a_start_time) {
		$this->start_time = $a_start_time;
	}

	public function getEndTime() {
		return $this->end_time;
	}

	public function setEndTime($a_end_time) {
		$this->end_time = $a_end_time;
	}

	public function getBuildingBlock() {
		return $this->building_block;
	}

	public function setBuildingBlock($a_building_block_id) {
		$bb_utils = gevBuildingBlockUtils::getInstance($a_building_block_id);
		$bb_utils->loadData();
		$this->building_block = $bb_utils;
	}

	public function getCourseRequestId() {
		return $this->crs_request_id;
	}

	public function setCourseRequestId($a_crs_request_id) {
		$this->crs_request_id = $a_crs_request_id;
	}

	public function setCreditPoints($credit_points) {
		$this->credit_points = $credit_points;
	}

	public function getCreditPoints() {
		return $this->credit_points;
	}

	public function getTime() {
		$start_time = $this->getStartTime();
		$end_time = $this->getEndTime();

		$ret = array("start"=>array("time"=>$arr_start_time[1],"date"=>$arr_start_time[0])
					,"end"=>array("time"=>$arr_end_time[1],"date"=>$arr_end_time[0]));
		
		return $ret;
	}

	public function loadData() {
		$sql = "SELECT crs_id, bb_id, start_time, end_time, credit_points\n"
			  ."  FROM ".self::TABLE_NAME." WHERE id = ".$this->db->quote($this->getId(), "integer");

		$res = $this->db->query($sql);
		
		if($this->db->numRows($res) > 0) {
			$row = $this->db->fetchAssoc($res);
			$this->setCrsId($row["crs_id"]);
			$this->setBuildingBlock($row["bb_id"]);
			$this->setStartTime($row["start_time"]);
			$this->setEndTime($row["end_time"]);
			$this->setCreditPoints($row["credit_points"]);
		}
	}

	public function update() {
		$sql = "UPDATE ".self::TABLE_NAME."\n"
			  ."   SET bb_id = ".$this->db->quote($this->getBuildingBlock()->getId(), "integer")."\n"
			  ."     , start_time = ".$this->db->quote($this->getStartTime(), "time")."\n"
			  ."     , end_time = ".$this->db->quote($this->getEndTime(), "time")."\n"
			  ."     , credit_points = ".$this->db->quote($this->getCreditPoints(), "integer")."\n"
			  ."     , last_change_user = ".$this->db->quote($this->ilUser->getId(), "integer")."\n"
			  ."     , last_change_date = NOW()"
			  ." WHERE id = ".$this->db->quote($this->getId(), "integer");

		$this->db->manipulate($sql);

		if($this->getCrsId() !== null) {
			self::courseUpdates($this->getCrsId(),$this->db);
		}
	}

	public function save() {
		/*$method_serial = preg_replace('/\"/','\\\"',serialize($this->getMethods()));
		$media_serial = preg_replace('/\"/','\\\"',serialize($this->getMedia()));*/

		$sql = "INSERT INTO ".self::TABLE_NAME.""
			  ." (id, crs_id, bb_id, start_time, end_time, last_change_user, last_change_date, crs_request_id, credit_points)\n"
			  ." VALUES ( ".$this->db->quote($this->getId(), "integer")."\n"
			  ."        , ".$this->db->quote($this->getCrsId(), "integer")."\n"
			  ."        , ".$this->db->quote($this->getBuildingBlock()->getId(), "integer")."\n"
			  ."        , ".$this->db->quote($this->getStartTime(), "time")."\n"
			  ."        , ".$this->db->quote($this->getEndTime(), "time")."\n"
			  ."        , ".$this->db->quote($this->ilUser->getId(), "integer")."\n"
			  ."        , NOW()\n"
			  ."        , ".$this->db->quote($this->getCourseRequestId(), "integer")."\n"
			  ."        , ".$this->db->quote($this->getCreditPoints(), "integer")."\n"
			  ."        )";

		$this->db->manipulate($sql);

		if($this->getCrsId() !== null) {
			self::courseUpdates($this->getCrsId(),$this->db);
		}
	}

	public function delete() {
		$query = "DELETE FROM ".self::TABLE_NAME." WHERE id = ".$this->db->quote($this->getId(),"integer");
		$this->db->manipulate($query);

		if($this->getCrsId() !== null) {
			self::courseUpdates($this->getCrsId(),$this->db);
		}
	}

	static public function getAllCourseBuildingBlocksRaw($a_crs_ref_id,$a_request_id = null) {
		global $ilDB;

		$sql = "SELECT\n"
			  ."    base.id, base.crs_id, base.bb_id, base.start_time, base.end_time, base.credit_points,\n"
			  ."    join1.title, join1.learning_dest, join1.content, base.crs_request_id, base.bb_id, join1.dbv_topic\n"
			  ." FROM ".self::TABLE_NAME." as base\n"
			  ." JOIN ".self::TABLE_NAME_JOIN1." as join1\n"
			  ."   ON  base.bb_id = join1.obj_id\n";
		
		if($a_crs_ref_id !== null) {
			$sql .= " WHERE base.crs_id = ".$ilDB->quote($a_crs_ref_id, "integer")."\n";
		} else {
			if($a_request_id !== null) {
				$sql .= " WHERE base.crs_request_id = ".$ilDB->db->quote($a_request_id, "integer")."\n";
			}
		}
	
		$sql .= " ORDER BY base.start_time";

		$ret = array();
		$res = $ilDB->query($sql);
		while($row = $ilDB->fetchAssoc($res)) {
			$ret[] = $row;
		}

		return $ret;
	}
	
	static public function getAllCourseBuildingBlocks($a_crs_ref_id, $a_request_id = null) {
		return array_map(function($row) {
			$obj = new gevCourseBuildingBlockUtils($row["id"]);
			$obj->setCrsId($row["crs_id"]);
			$obj->setStartTime($row["start_time"]);
			$obj->setEndTime($row["end_time"]);
			$obj->setCourseRequestId($row["crs_request_id"]);
			$obj->setBuildingBlock($row["bb_id"]);
			$obj->setCreditPoints($row["credit_points"]);
			return $obj;
		}, self::getAllCourseBuildingBlocksRaw($a_crs_ref_id, $a_request_id));
	}











	static public function updateCrsBuildungBlocksCrsIdByCrsRequestId($a_crs_id, $a_crs_request_id) {
		global $ilDB;

		$sql = "UPDATE ".self::TABLE_NAME."\n"
			  ."   SET crs_id = ".$ilDB->quote($a_crs_id, "integer")."\n"
			  ."     , crs_request_id = NULL\n"
			  ." WHERE crs_request_id = ".$ilDB->quote($a_crs_request_id, "integer");
		$ilDB->manipulate($sql);
	}

	static public function courseUpdates($a_crs_ref_id, $a_db = null) {
		if($a_crs_ref_id === null) {
			return;
		}

		if($a_db === null) {
			global $ilDB;
			$a_db = $ilDB;
		}

		self::updateWP($a_crs_ref_id, $a_db);
	}

	static private function updateWP($a_crs_ref_id, $a_db) {
		$sql = "SELECT base.id, base.start_time, base.end_time "
		      ." FROM ".self::TABLE_NAME." base"
		      ." JOIN ".self::TABLE_NAME_JOIN1." join1"
		      ." ON base.bb_id = join1.obj_id WHERE join1.is_wp_relevant = 1"
		      ." ORDER BY base.start_time";
		
		$res = $a_db->query($sql);
		$totalMinutes = 0;
		while($row = $a_db->fetchAssoc($res)) {
			$start_time = split(" ",$row["start_time"]);
			$end_time = split(" ",$row["end_time"]);
			
			$start = split(":",$start_time[1]);
			$end = split(":",$end_time[1]);

			$minutes = 0;
			$hours = 0;
			if($end[1] < $start[1]) {
				$minutes = 60 - $start[1] + $end[1];
				$hours = -1;
			} else {
				$minutes = $end[1] - $start[1];
			}
			$hours = $hours + $end[0] - $start[0];
			$totalMinutes += $hours * 60 + $minutes;
		}
		
		$wp = null;
		$wp = round($totalMinutes / self::DURATION_PER_POINT);
		
		if($wp < 0) {
			$wp = 0;
		}

		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		gevCourseUtils::updateWP($wp, $a_crs_ref_id);
	}

	static public function getMaxDurationReached($a_crs_ref_id, $a_crs_request_id, array $a_time) {
		global $ilDB;

		if($a_crs_ref_id == null && $a_crs_request_id === null) {
			throw new Exception("gevCourseBuildingBlockUtils::getMaxDurationReached: Either set course_ref_id or course_request_id.");
		}

		$sql = "SELECT SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/60) as minutes_diff FROM ".self::TABLE_NAME;
		
		if($a_crs_ref_id !== null) {
			$sql .= " WHERE crs_id = ".$a_crs_ref_id. " ORDER BY start_time";
		} else {
			$sql .= " WHERE crs_request_id = ".$a_crs_request_id. " ORDER BY start_time";
		}
		
		$res = $ilDB->query($sql);

		$old_time_diff = 0;
		if($ilDB->numRows($res) > 0) {
			$row = $ilDB->fetchAssoc($res);
			$old_time_diff = $row["minutes_diff"];
			$old_time_diff = (int) $old_time_diff;
		}

		if($old_time_diff > self::MAX_DURATION_MINUTES) {
			return true;
		}

		$start_time = $a_time["start"]["time"];
		$end_time  = $a_time["end"]["time"];

		$start = split(":",$start_time);
		$end = split(":",$end_time);

		$minutes = 0;
		$hours = 0;
		if($end[1] < $start[1]) {
			$minutes = 60 - $start[1] + $end[1];
			$hours = -1;
		} else {
			$minutes = $end[1] - $start[1];
		}
		$hours = $hours + $end[0] - $start[0];
		$totalMinutes = $hours * 60 + $minutes;

		if(($old_time_diff + $totalMinutes) > self::MAX_DURATION_MINUTES) {
			return true;
		}

		return false;
	}

	static public function getMaxDurationReachedOnUpdate($a_crs_ref_id, $a_crs_request_id, array $a_time,$a_updated_crs_building_block_id) {
		global $ilDB;

		if($a_crs_ref_id == null && $a_crs_request_id === null) {
			throw new Exception("gevCourseBuildingBlockUtils::getMaxDurationReached: Either set course_ref_id or course_request_id.");
		}

		$sql = "SELECT id, end_time, start_time FROM ".self::TABLE_NAME;
		
		if($a_crs_ref_id !== null) {
			$sql .= " WHERE crs_id = ".$a_crs_ref_id. " ORDER BY start_time";
		} else {
			$sql .= " WHERE crs_request_id = ".$a_crs_request_id. " ORDER BY start_time";
		}
		$res = $ilDB->query($sql);

		$old_time_diff = 0;
		$dates = array();
		while($row = $ilDB->fetchAssoc($res)) {
			if($row["id"] != $a_updated_crs_building_block_id) {
				$start_time = split(" ",$row["start_time"]);
				$end_time = split(" ",$row["end_time"]);
				$dates[$row["id"]] = array("start_time"=>$start_time[1],"end_time"=>$end_time[1]);
			} else {
				$dates[$row["id"]] = array("start_time"=>$a_time["start"]["time"],"end_time"=>$a_time["end"]["time"]);
			}
		}

		$start_time = "00:00:00";
		$end_time  = "00:00:00";

		foreach ($dates as $key => $value) {
			if($start_time == "00:00:00") {
				$start_time = $value["start_time"];
			}

			if($end_time == "00:00:00") {
				$end_time = $value["end_time"];
			}

			if($start_time > $value["start_time"]) {
				$start_time = $vlaue["start_time"];
			}

			if($end_time < $value["end_time"]) {
				$end_time = $value["end_time"];
			}
		}

		$start = split(":",$start_time);
		$end = split(":",$end_time);

		$minutes = 0;
		$hours = 0;
		if($end[1] < $start[1]) {
			$minutes = 60 - $start[1] + $end[1];
			$hours = -1;
		} else {
			$minutes = $end[1] - $start[1];
		}
		$hours = $hours + $end[0] - $start[0];
		$totalMinutes = $hours * 60 + $minutes;

		if($totalMinutes > self::MAX_DURATION_MINUTES) {
			return true;
		}

		return false;
	}

	static public function getRemainingTime($a_crs_ref_id,$a_crs_request_id) {
		global $ilDB;

		if($a_crs_ref_id === null && $a_crs_request_id === null) {
			throw new Exception("gevCourseBuildingBlockUtils::getRemainingTime: Either set course_ref_id or course_request_id.");
		}

		$sql = "SELECT SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/60) as minutes_diff\n"
			  ."  FROM ".self::TABLE_NAME;
		
		if($a_crs_ref_id !== null) {
			$sql .= " WHERE crs_id = ".$ilDB->quote($a_crs_ref_id, "integer"). " ORDER BY start_time";
		} else {
			$sql .= " WHERE crs_request_id = ".$ilDB->quote($a_crs_request_id, "integer"). " ORDER BY start_time";
		}

		$res = $ilDB->query($sql);

		$old_time_diff = 0;
		if($ilDB->numRows($res) > 0) {
			$row = $ilDB->fetchAssoc($res);
			$old_time_diff = $row["minutes_diff"];
		}

		return self::MAX_DURATION_MINUTES - $old_time_diff;
	}

	static public function checkTimeIssues($start,$end,$crs_id = null,$request_id = null) {
		global $ilDB;
		$where = "";
		
		$sql = "SELECT COUNT(*) AS issues\n"
			." FROM ".self::TABLE_NAME."\n"
			." WHERE ("
				." ((".$ilDB->quote($start,"time")." > start_time AND ".$ilDB->quote($start,"time")." < end_time) AND ".$ilDB->quote($end,"time")." >= end_time)\n"
				." OR (".$ilDB->quote($start,"time")." <= start_time AND (".$ilDB->quote($end,"time")." > start_time AND ".$ilDB->quote($end,"time")." < end_time))\n"
				." OR (".$ilDB->quote($start,"time")." <= start_time AND ".$ilDB->quote($end,"time")." >= end_time)\n"
				." OR ((".$ilDB->quote($start,"time")." > start_time AND ".$ilDB->quote($start,"time")." < end_time) AND (".$ilDB->quote($end,"time")." > start_time AND ".$ilDB->quote($end,"time")." < end_time))\n"
				.")\n";
			

		if($request_id !== null) {
			$where .= " AND crs_request_id = ".$ilDB->quote($request_id,"integer")."\n";
		}

		if($crs_id !== null) {
			$where .= " AND crs_id = ".$ilDB->quote($crs_id,"integer")."\n";
		}

		$res = $ilDB->query($sql.$where);
		$row = $ilDB->fetchAssoc($res);

		return ($row["issues"] == 0) ? true : false;
	}

	public static function getNextCrsBBlockId() {
		global $ilDB;

		return $ilDB->nextId(self::TABLE_NAME);
	}

	static public function updateTimesAndCreditPoints($id, $start, $end) {
		$block = gevCourseBuildingBlockUtils::getInstance($id);
		$block->loadData();
		$block->setStartTime($start);
		$block->setEndTime($end);

		$block->update();
	}

	static public function timeIssuesCrs($crs_ref_id, $crs_request_id) {
		global $ilDB;

		require_once("Services/GEV/DecentralTrainings/classes/class.gevDecentralTrainingCreationRequestDB.php");
		$request_db = new gevDecentralTrainingCreationRequestDB();
		$request = $request_db->request($crs_request_id);

		$start = $request->settings()->start();
		$end = $request->settings()->end();

		$sql = "SELECT MIN(start_time) AS start_time, MAX(end_time) AS end_time FROM ".self::TABLE_NAME." WHERE crs_request_id = ".$ilDB->quote($crs_request_id,"integer");
		$res = $ilDB->query($sql);

		if($ilDB->numRows($res) > 0 ) {
			$row = $ilDB->fetchAssoc($res);
			$date = $start->get(IL_CAL_DATE);

			$block_start = new IlDateTime($date." ".$row["start_time"],IL_CAL_DATETIME);
			$block_end = new IlDateTime($date." ".$row["end_time"],IL_CAL_DATETIME);
			
			if($start->get(IL_CAL_UNIX) > $block_start->get(IL_CAL_UNIX) || $end->get(IL_CAL_UNIX) < $block_end->get(IL_CAL_UNIX)) {
				return true;
			}
		}

		return false;
	}

	static public function timeIssuesBlocks($crs_ref_id, $crs_request_id) {
		global $ilDB;

		$ret = array();
		$where = "";
		$sql = "SELECT A.start_time AS start_time_before, A.end_time AS end_time_before, B.start_time AS start_time_end, B.end_time AS end_time_end"
				." FROM dct_crs_building_block A"
				." JOIN dct_crs_building_block B ON A.end_time > B.start_time AND A.start_time < B.start_time";

		if($crs_ref_id !== null) {
			$where = " WHERE A.crs_id = ".$ilDB->quote($crs_ref_id,"integer")." AND B.crs_id = ".$ilDB->quote($crs_ref_id,"integer")."";
		}

		if($crs_ref_id === null && $crs_request_id !== null) {
			$where = " WHERE A.crs_request_id = ".$ilDB->quote($crs_request_id,"integer")." AND B.crs_request_id = ".$ilDB->quote($crs_request_id,"integer")."";
		}

		$sql .= $where;

		$res = $ilDB->query($sql);
		while($row = $ilDB->fetchAssoc($res)) {
			$ret[] = $row;
		}

		return $ret;
	}
}
?>