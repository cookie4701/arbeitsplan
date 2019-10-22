<?php



class CGuiGenerator {
	private $prefixId;
	private $daydate;
	
	public function __construct() {
		$this->prefixId = "default";
		$this->daydate = 1;
	}
	
	public function setPrefix($str) {
		$this->prefixId = $str;
	}
	
	public function setDayDate($str) {
		$this->daydate = $str;
	}
	
	public function generateDayDate() {
		$ret = "<span class=\"daydate\" id=\"daydate" . $this->daydate . "\">  </span>";
		return $ret;
	}
	
	public function generateAll() {
		$ret = "<div class=\"sep\"> </div>";
		$ret .= "<div class=\"day\" id=\"" . $this->prefixId . "\" >";
		$ret .= $this->generateColOne();
		$ret .= $this->generateWorkareas();
		$ret .= "<div class=\"col3\" id=\"" . $this->prefixId . "col3\">";
		$ret .= $this->generateCommentBox();
		$ret .= "</div>";
		$ret .= "<div class=\"col4\">";
		$ret .= $this->generateDropDownBox();		
		$ret .= "</div>";
		$ret .= "<div class=\"col5\">";
		$ret .= $this->generateCarActionBox();
		$ret.= "</div>";
		$ret .= "</div>";
		return $ret;
	}
	
	public function generateColOne() {
		$ret = "<div class=\"col1\" id=\"" . $this->prefixId . "col1\" />";
		$ret .= $this->generateDayDate();
		$ret .= $this->generateTblWorkhours();
		$ret .= $this->generateDisplayWorkhours();
		$ret .= "</div>";
		return $ret;
	}
	
	public function generateDropDownBox() {
		$ret = "<select id=\"" . $this->prefixId . "hollidaybox\" onChange=\"javascript:GUIchanges();\" >";
		$ret .= "<option value=\"1\">Normaler Arbeitstag</option>";
		$ret .= "<option value=\"2\">Urlaub</option>";
		$ret .= "<option value=\"3\">Feiertag</option>";
		$ret .= "<option value=\"4\">Krankheit</option>";
		$ret .= "<option value=\"5\">Sonstiges</option>";
		$ret .= "</select>";
		$ret .= "<br><input type=\"text\" id=\"" . $this->prefixId . "hollidaydescription\">";
		return $ret;
	}
	
	public function generateTblWorkhours() {
		$ret = "<!-- workhours -->";
		$ret .= $this->generateDayDate();
		$ret .= "<table class=\"workhours\" id=\"" . $this->prefixId . "workhours\">";
		$ret .= "<tr class=\"workentrydescription\">";
		$ret .= "<td class=\"workfrom\">Von</td>";
		$ret .= "<td class=\"workto\">Bis</td>";
		$ret .= "</tr>";
		
		for ( $i = 1; $i <= 4; $i++ ) {
			$ret .= "<tr class=\"workentry$i\">";
			$ret .= "<td class=\"workfrom\"> <input type=\"text\" size=\"9\" name=\"workfrom$i\" id=\"" . $this->prefixId . "workfrom$i\" onChange=\"javascript:calcChanges('" . $this->prefixId . "');GUIchanges();\"> </td>";
			$ret .= "<td class=\"workto\"> <input type=\"text\" size=\"9\" name=\"workto$i\" id=\"" . $this->prefixId . "workto$i\" onChange=\"javascript:calcChanges('" . $this->prefixId . "');GUIchanges();\"> </td>";
			$ret .= "</tr>";
		}
		
		$ret .= "</table>";
		$ret .= "<!-- end table workhours -->";
		//$ret .= "</div>";
		return $ret;
	}
	
	public function generateDisplayWorkhours() {
		$ret = "<span class=\"descriptionWorkhours\"> Geleistete Stunden: </span> <input type=\"text\" id=\"" . $this->prefixId . "displayworkhours\" name=\"" . $this->prefixId . "displayworkhours\" size=\"8\">";
		return $ret;
	}
	
	// col2
	public function generateWorkareas() {
		$ret = "<!-- workareas -->";
		$ret .= "<div class=\"col2\">";
		$ret .= "<table class=\"workareas\">";
		
		
		for ( $lines = 0; $lines < 6; $lines++ ) {
			$ret .= "<tr>";
			
			for ( $columns = 0; $columns < 4; $columns++ ) {
				$runNbr = ($lines * 4) + ($columns + 1);
				$ret .= "<td class=\"workarealabel\" id=\"" . $this->prefixId . "walbl$runNbr\"> </td>";
				$ret .= "<td class=\"workareainput\" ><input type=\"text\" class=\"inputtime\" name=\"" . $this->prefixId . "wa$runNbr\" id=\"" . $this->prefixId . "wa$runNbr\" onChange=\"javascript:calcChanges('" . $this->prefixId . "');GUIchanges();\" ></td>";
			}
			
			$ret .= "</tr>";
		}
		
		$ret .= "</table>";
		$ret .= "</div>";
		$ret .= "<!-- end div col2monday -->";
		return $ret;
	}
	
	// col3
	public function generateCommentBox() {
		$ret = "<!-- comment on day -->";
		$ret .= "<textarea id=\"" . $this->prefixId . "comment\" class=\"comment\"> </textarea>";
		return $ret;
	}
	
	public function generateCarActionBox() {
		$ret = "<!-- kilometers (to be filled by javascript) -->";
		$ret .= "<div class=\"kilometeres\" id=\"" . $this->prefixId . "kilometer\">";
		$ret .= "<table id=\"" . $this->prefixId . "cartable\" class=\"cartable\" >";
		$ret .= "<thead><tr><th>Von</th><th>Bis</th><th>KM</th></thead>"; 
		$ret .= "</table>"; 
		$ret .= "<div id=\"" . $this->prefixId . "travel\">";
		$ret .= "<input type=\"button\" value=\"Fahrt hinzuf&uuml;gen\" id=\"" . $this->prefixId . "plus\" onClick=\"javascript:doPlusKm('" . $this->prefixId . "');\" >";
		$ret .= "<input type=\"button\" value=\"Fahrt entfernen\" id=\"" . $this->prefixId . "minus\" onClick=\"javascript:doMinusKm('" . $this->prefixId . "');\" >";
		$ret .= "</div>";
		$ret .= "</div>";
		$ret .= "<!-- end kilometers -->";
		return $ret;
	}
	
	
}

?>
